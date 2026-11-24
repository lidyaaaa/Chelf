<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;

class ChatController extends Controller
{
    public const COOLDOWN_SECONDS = 3;

    public const MAX_MESSAGES_PER_MINUTE = 5;

    /**
     * Halaman percakapan / chat.
     */
    public function index(Request $request)
    {
        $currentUser = Auth::user();
        $activeUser = null;
        $activeTransaction = null;

        if ($request->filled('transaction_id')) {
            $activeTransaction = Transaction::with(['user', 'items.book'])->find($request->transaction_id);
            if ($activeTransaction) {
                if ($currentUser->role === 'admin') {
                    $activeUser = $activeTransaction->user;
                } else {
                    $activeUser = User::where('role', 'admin')->first();
                }
            }
        }

        if (! $activeUser && $request->filled('user_id')) {
            $candidate = User::find($request->user_id);
            if ($currentUser->role === 'admin') {
                $activeUser = $candidate;
            } elseif ($candidate && $candidate->role === 'admin') {
                $activeUser = $candidate;
            }
        }

        if ($currentUser->role === 'admin') {
            // Daftar kontak untuk admin: semua user pelanggan yang pernah chat atau bertransaksi
            $contacts = User::where('id', '!=', $currentUser->id)
                ->where(function ($query) use ($currentUser): void {
                    $query->whereHas('sentMessages', function ($messageQuery) use ($currentUser): void {
                        $messageQuery->where('receiver_id', $currentUser->id);
                    })->orWhereHas('receivedMessages', function ($messageQuery) use ($currentUser): void {
                        $messageQuery->where('sender_id', $currentUser->id);
                    })->orWhereHas('transactions');
                })
                ->withCount([
                    'sentMessages as unread_count' => function ($q) use ($currentUser): void {
                        $q->where('receiver_id', $currentUser->id)->where('is_read', false);
                    },
                ])
                ->latest('id')
                ->paginate(12, ['*'], 'contacts_page')
                ->withQueryString();

            if (! $activeUser && $contacts->isNotEmpty()) {
                $activeUser = $contacts->first();
            }
        } else {
            // Customer: lawan bicaranya adalah admin toko
            $admin = User::where('role', 'admin')->first();
            $contacts = $admin ? collect([$admin]) : collect();
            if (! $activeUser) {
                $activeUser = $admin;
            }
        }

        $messages = collect();
        if ($activeUser) {
            // Tandai pesan dari activeUser ke currentUser sebagai sudah dibaca
            Message::where('sender_id', $activeUser->id)
                ->where('receiver_id', $currentUser->id)
                ->where('is_read', false)
                ->update(['is_read' => true]);

            $messages = Message::where(function ($q) use ($currentUser, $activeUser): void {
                $q->where(function ($sentQuery) use ($currentUser, $activeUser): void {
                    $sentQuery->where('sender_id', $currentUser->id)->where('receiver_id', $activeUser->id);
                })->orWhere(function ($receivedQuery) use ($currentUser, $activeUser): void {
                    $receivedQuery->where('sender_id', $activeUser->id)->where('receiver_id', $currentUser->id);
                });
            })
                ->with(['sender', 'transaction'])
                ->latest('id')
                ->paginate(30, ['*'], 'messages_page')
                ->withQueryString();
            $messages->setCollection($messages->getCollection()->reverse()->values());
        }

        // Ambil riwayat transaksi antara activeUser dan admin jika ada
        $userTransactions = collect();
        if ($currentUser->role === 'admin' && $activeUser) {
            $userTransactions = Transaction::where('user_id', $activeUser->id)
                ->with('items.book')
                ->latest()
                ->take(5)
                ->get();
        } elseif ($currentUser->role !== 'admin') {
            $userTransactions = Transaction::where('user_id', $currentUser->id)
                ->with('items.book')
                ->latest()
                ->take(5)
                ->get();
        }

        $cooldownKey = $this->cooldownKey($currentUser->id);
        $burstKey = $this->burstKey($currentUser->id);

        $chatConfig = [
            'cooldown_seconds' => self::COOLDOWN_SECONDS,
            'max_per_minute' => self::MAX_MESSAGES_PER_MINUTE,
            'remaining_per_minute' => RateLimiter::remaining($burstKey, self::MAX_MESSAGES_PER_MINUTE),
            'cooldown_remaining' => RateLimiter::tooManyAttempts($cooldownKey, 1)
                ? max(1, RateLimiter::availableIn($cooldownKey))
                : 0,
        ];

        return view('chat.index', compact('contacts', 'activeUser', 'messages', 'activeTransaction', 'userTransactions', 'chatConfig'));
    }

    /**
     * Ambil pesan baru secara AJAX (polling real-time).
     */
    public function messages(Request $request): JsonResponse
    {
        $currentUser = Auth::user();
        $activeUser = null;

        if ($request->filled('user_id')) {
            $candidate = User::find($request->integer('user_id'));
            if ($currentUser->role === 'admin') {
                $activeUser = $candidate;
            } elseif ($candidate && $candidate->role === 'admin') {
                $activeUser = $candidate;
            }
        } elseif ($currentUser->role !== 'admin') {
            $activeUser = User::where('role', 'admin')->first();
        }

        if (! $activeUser) {
            return response()->json([
                'messages' => [],
                'unread_total' => 0,
            ]);
        }

        // Tandai pesan masuk dari activeUser ke currentUser sebagai sudah dibaca
        Message::where('sender_id', $activeUser->id)
            ->where('receiver_id', $currentUser->id)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        $afterId = max(0, $request->integer('after_id'));

        $newMessages = Message::where(function ($q) use ($currentUser, $activeUser): void {
            $q->where(function ($sentQuery) use ($currentUser, $activeUser): void {
                $sentQuery->where('sender_id', $currentUser->id)->where('receiver_id', $activeUser->id);
            })->orWhere(function ($receivedQuery) use ($currentUser, $activeUser): void {
                $receivedQuery->where('sender_id', $activeUser->id)->where('receiver_id', $currentUser->id);
            });
        })
            ->where('id', '>', $afterId)
            ->with(['sender', 'transaction'])
            ->orderBy('id')
            ->limit(50)
            ->get()
            ->map(fn (Message $msg): array => $this->formatMessage($msg, $currentUser->id))
            ->values();

        $unreadTotal = Message::where('receiver_id', $currentUser->id)
            ->where('is_read', false)
            ->count();

        return response()->json([
            'messages' => $newMessages,
            'unread_total' => $unreadTotal,
        ]);
    }

    /**
     * Kirim pesan chat (mendukung AJAX JSON dan fallback form standar).
     */
    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $currentUser = Auth::user();
        $cooldownKey = $this->cooldownKey($currentUser->id);
        $burstKey = $this->burstKey($currentUser->id);

        if (RateLimiter::tooManyAttempts($burstKey, self::MAX_MESSAGES_PER_MINUTE)) {
            $retryAfter = max(1, RateLimiter::availableIn($burstKey));
            $errorMessage = sprintf(
                'Batas kirim pesan tercapai (maksimal %d pesan per menit). Silakan tunggu %d detik lagi.',
                self::MAX_MESSAGES_PER_MINUTE,
                $retryAfter
            );

            return $this->rateLimitedResponse($request, $errorMessage, $retryAfter, 'rate_limit');
        }

        if (RateLimiter::tooManyAttempts($cooldownKey, 1)) {
            $retryAfter = max(1, RateLimiter::availableIn($cooldownKey));
            $errorMessage = sprintf(
                'Mohon tunggu %d detik sebelum mengirim pesan berikutnya.',
                $retryAfter
            );

            return $this->rateLimitedResponse($request, $errorMessage, $retryAfter, 'cooldown');
        }

        $validated = $request->validate([
            'receiver_id' => 'required|exists:users,id',
            'transaction_id' => 'nullable|exists:transactions,id',
            'message' => [
                'required',
                'string',
                'max:500',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (trim((string) $value) === '') {
                        $fail('Pesan tidak boleh kosong atau hanya berisi spasi.');
                    }
                },
            ],
        ], [
            'message.required' => 'Pesan wajib diisi.',
            'message.max' => 'Pesan maksimal 500 karakter.',
        ]);

        // Customer hanya boleh chat dengan admin, admin boleh chat dengan siapapun
        $receiver = User::findOrFail($validated['receiver_id']);
        if ($currentUser->role !== 'admin' && $receiver->role !== 'admin') {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Anda hanya dapat mengirim pesan ke admin toko.',
                ], 403);
            }

            abort(403, 'Anda hanya dapat mengirim pesan ke admin toko.');
        }

        $message = Message::create([
            'sender_id' => $currentUser->id,
            'receiver_id' => $receiver->id,
            'transaction_id' => $validated['transaction_id'] ?? null,
            'message' => trim($validated['message']),
            'is_read' => false,
        ]);

        RateLimiter::hit($cooldownKey, self::COOLDOWN_SECONDS);
        RateLimiter::hit($burstKey, 60);

        if ($request->expectsJson()) {
            $message->load(['sender', 'transaction']);

            return response()->json([
                'message' => $this->formatMessage($message, $currentUser->id),
                'cooldown_seconds' => self::COOLDOWN_SECONDS,
                'remaining_per_minute' => RateLimiter::remaining($burstKey, self::MAX_MESSAGES_PER_MINUTE),
                'max_per_minute' => self::MAX_MESSAGES_PER_MINUTE,
            ], 201);
        }

        return redirect()->route('chat.index', [
            'user_id' => $receiver->id,
            'transaction_id' => $validated['transaction_id'] ?? null,
        ])->with('success', 'Pesan berhasil dikirim.');
    }

    /**
     * Format satu pesan untuk payload JSON AJAX.
     *
     * @return array<string, mixed>
     */
    private function formatMessage(Message $message, int $currentUserId): array
    {
        return [
            'id' => $message->id,
            'sender_id' => $message->sender_id,
            'sender_name' => $message->sender_id === $currentUserId ? 'Anda' : ($message->sender?->name ?? 'Pengguna'),
            'is_me' => $message->sender_id === $currentUserId,
            'message' => $message->message,
            'created_at_time' => $message->created_at?->format('H:i') ?? now()->format('H:i'),
            'transaction_id' => $message->transaction_id,
            'transaction_code' => $message->transaction?->kode_transaksi,
        ];
    }

    private function rateLimitedResponse(Request $request, string $message, int $retryAfter, string $reason): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'retry_after' => $retryAfter,
                'reason' => $reason,
            ], 429, [
                'Retry-After' => (string) $retryAfter,
            ]);
        }

        return back()
            ->withErrors(['message' => $message])
            ->withInput();
    }

    private function cooldownKey(int $userId): string
    {
        return 'chat-cooldown:'.$userId;
    }

    private function burstKey(int $userId): string
    {
        return 'chat-burst:'.$userId;
    }
}
