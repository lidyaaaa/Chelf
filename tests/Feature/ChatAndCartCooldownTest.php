<?php

namespace Tests\Feature;

use App\Http\Controllers\CartController;
use App\Http\Controllers\ChatController;
use App\Models\Book;
use App\Models\Category;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class ChatAndCartCooldownTest extends TestCase
{
    use RefreshDatabase;

    public function test_chat_ajax_send_returns_json_and_enforces_cooldown(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'user']);

        // 1. Kirim pesan pertama via AJAX berhasil (201)
        $first = $this->actingAs($customer)->postJson(route('chat.store'), [
            'receiver_id' => $admin->id,
            'message' => 'Halo admin, buku ready?',
        ]);

        $first->assertCreated()
            ->assertJsonPath('message.message', 'Halo admin, buku ready?')
            ->assertJsonPath('message.is_me', true)
            ->assertJsonPath('cooldown_seconds', ChatController::COOLDOWN_SECONDS)
            ->assertJsonPath('remaining_per_minute', ChatController::MAX_MESSAGES_PER_MINUTE - 1);

        // 2. Langsung kirim pesan kedua dalam masa jeda -> ditolak 429 cooldown
        $second = $this->actingAs($customer)->postJson(route('chat.store'), [
            'receiver_id' => $admin->id,
            'message' => 'Spam cepat!',
        ]);

        $second->assertStatus(429)
            ->assertJsonPath('reason', 'cooldown')
            ->assertJsonStructure(['message', 'retry_after', 'reason']);

        $this->assertDatabaseCount('messages', 1);

        // 3. Setelah melewati masa jeda, pesan berikutnya berhasil dikirim
        $this->travel(ChatController::COOLDOWN_SECONDS + 1)->seconds();

        $third = $this->actingAs($customer)->postJson(route('chat.store'), [
            'receiver_id' => $admin->id,
            'message' => 'Terima kasih infonya admin.',
        ]);

        $third->assertCreated()
            ->assertJsonPath('message.message', 'Terima kasih infonya admin.');

        $this->assertDatabaseCount('messages', 2);
    }

    public function test_chat_enforces_per_minute_burst_limit(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'user']);

        $burstKey = 'chat-burst:'.$customer->id;
        for ($i = 0; $i < ChatController::MAX_MESSAGES_PER_MINUTE; $i++) {
            RateLimiter::hit($burstKey, 60);
        }

        $response = $this->actingAs($customer)->postJson(route('chat.store'), [
            'receiver_id' => $admin->id,
            'message' => 'Pesan ke-16 dalam semenit',
        ]);

        $response->assertStatus(429)
            ->assertJsonPath('reason', 'rate_limit');

        $this->assertDatabaseCount('messages', 0);
    }

    public function test_chat_rejects_whitespace_only_message(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'user']);

        $response = $this->actingAs($customer)->postJson(route('chat.store'), [
            'receiver_id' => $admin->id,
            'message' => '     ',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('message');

        $this->assertDatabaseCount('messages', 0);
    }

    public function test_chat_ajax_polling_returns_only_newer_messages_and_marks_as_read(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'user']);

        $oldMsg = Message::create([
            'sender_id' => $admin->id,
            'receiver_id' => $customer->id,
            'message' => 'Pesan lama',
            'is_read' => true,
        ]);

        $newMsg = Message::create([
            'sender_id' => $admin->id,
            'receiver_id' => $customer->id,
            'message' => 'Pesan balasan terbaru',
            'is_read' => false,
        ]);

        $response = $this->actingAs($customer)->getJson(route('chat.messages', [
            'user_id' => $admin->id,
            'after_id' => $oldMsg->id,
        ]));

        $response->assertOk()
            ->assertJsonCount(1, 'messages')
            ->assertJsonPath('messages.0.id', $newMsg->id)
            ->assertJsonPath('messages.0.message', 'Pesan balasan terbaru')
            ->assertJsonPath('messages.0.is_me', false);

        $this->assertTrue($newMsg->fresh()->is_read);
    }

    public function test_add_to_cart_ajax_enforces_per_book_cooldown_and_prevents_double_click(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $category = Category::create(['nama' => 'Fiksi']);

        $bookA = Book::create([
            'category_id' => $category->id,
            'judul' => 'Buku Pertama',
            'penulis' => 'Penulis A',
            'penerbit' => 'Penerbit A',
            'tahun_terbit' => 2025,
            'harga' => 75000,
            'stok' => 10,
            'total_koleksi' => 10,
        ]);

        $bookB = Book::create([
            'category_id' => $category->id,
            'judul' => 'Buku Kedua',
            'penulis' => 'Penulis B',
            'penerbit' => 'Penerbit B',
            'tahun_terbit' => 2025,
            'harga' => 85000,
            'stok' => 10,
            'total_koleksi' => 10,
        ]);

        // 1. Tambah buku A via AJAX berhasil
        $first = $this->actingAs($user)->postJson(route('cart.store', $bookA), ['jumlah' => 1]);
        $first->assertOk()
            ->assertJsonPath('cart_count', 1)
            ->assertJsonPath('cooldown_seconds', CartController::COOLDOWN_SECONDS);

        // 2. Double-click buku A langsung dalam masa cooldown -> ditolak 429
        $doubleClick = $this->actingAs($user)->postJson(route('cart.store', $bookA), ['jumlah' => 1]);
        $doubleClick->assertStatus(429)
            ->assertJsonStructure(['message', 'retry_after']);

        $this->assertDatabaseHas('carts', [
            'user_id' => $user->id,
            'book_id' => $bookA->id,
            'jumlah' => 1,
        ]);

        // 3. Tambah buku B (buku berbeda) tetap langsung berhasil
        $otherBook = $this->actingAs($user)->postJson(route('cart.store', $bookB), ['jumlah' => 1]);
        $otherBook->assertOk()
            ->assertJsonPath('cart_count', 2);

        // 4. Setelah melewati jeda cooldown, buku A bisa ditambahkan lagi
        $this->travel(CartController::COOLDOWN_SECONDS + 1)->seconds();

        $afterCooldown = $this->actingAs($user)->postJson(route('cart.store', $bookA), ['jumlah' => 1]);
        $afterCooldown->assertOk()
            ->assertJsonPath('cart_count', 3);

        $this->assertDatabaseHas('carts', [
            'user_id' => $user->id,
            'book_id' => $bookA->id,
            'jumlah' => 2,
        ]);
    }

    public function test_cart_shows_editable_number_input_and_updates_quantity_directly(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $category = Category::create(['nama' => 'Teknologi']);

        $book = Book::create([
            'category_id' => $category->id,
            'judul' => 'Clean Code',
            'penulis' => 'Robert C. Martin',
            'penerbit' => 'Prentice Hall',
            'tahun_terbit' => 2024,
            'harga' => 100000,
            'stok' => 12,
            'total_koleksi' => 12,
        ]);

        $cart = $user->carts()->create([
            'book_id' => $book->id,
            'jumlah' => 2,
        ]);

        $page = $this->actingAs($user)->get(route('cart.index'));
        $page->assertOk()
            ->assertSee('cart-qty-display', false)
            ->assertSee('data-max="12"', false);

        $update = $this->actingAs($user)->patchJson(route('cart.update', $cart), [
            'jumlah' => 7,
        ]);

        $update->assertOk()
            ->assertJsonPath('jumlah', 7)
            ->assertJsonPath('subtotal', 700000)
            ->assertJsonPath('max_stok', 12)
            ->assertJsonPath('cart_count', 7);

        $this->assertDatabaseHas('carts', [
            'id' => $cart->id,
            'jumlah' => 7,
        ]);
    }
}
