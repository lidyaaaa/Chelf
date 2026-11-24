<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'BookStore — Toko Buku')</title>

    {{-- Google Fonts: Plus Jakarta Sans & JetBrains Mono --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@500;600&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="{{ asset('css/simperpus.css') }}?v={{ time() }}" rel="stylesheet">
</head>

<body>

@auth
    @if(auth()->user()->role === 'admin')
        {{-- LAYOUT KHUSUS ADMIN DENGAN SIDEBAR --}}
        <div class="d-flex min-vh-100">
            {{-- Mobile Backdrop --}}
            <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

            {{-- Sidebar Admin --}}
            @include('layouts.partials.sidebar-admin')

            {{-- Admin Main Content Area --}}
            <div class="flex-grow-1 d-flex flex-column min-vh-100 overflow-x-hidden" style="min-width: 0;">
                {{-- Topbar Mini Admin untuk Mobile / Quick Info --}}
                <header class="navbar navbar-expand bg-white border-bottom py-2 px-3 px-lg-4 no-print sticky-top shadow-xs">
                    <div class="container-fluid p-0 d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center gap-2">
                            <button class="btn btn-light btn-sm d-lg-none border shadow-none" type="button" id="sidebarToggle">
                                <i class="bi bi-list fs-5"></i>
                            </button>
                            <span class="fw-semibold text-slate-800 d-none d-sm-inline">
                                @yield('title', 'Admin Panel')
                            </span>
                        </div>

                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 py-2 px-3 d-none d-sm-inline-flex align-items-center">
                                <i class="bi bi-shield-check me-1"></i> Administrator
                            </span>
                        </div>
                    </div>
                </header>

                <main class="container-fluid px-3 px-lg-4 py-4 flex-grow-1">
                    @include('layouts.partials.flash-alerts')
                    @yield('content')
                </main>

                <footer class="bg-white border-top py-3 mt-auto no-print">
                    <div class="container-fluid px-3 px-lg-4 d-flex flex-column flex-sm-row justify-content-between align-items-center text-slate-600 small gap-2">
                        <div>
                            <strong class="text-slate-900">BookStore Admin</strong> &mdash; Panel Manajemen Toko Buku
                        </div>
                        <div>
                            Katalog &bull; Transaksi &bull; Laporan Penjualan
                        </div>
                    </div>
                </footer>
            </div>
        </div>
    @else
        {{-- LAYOUT KHUSUS USER (NAVBAR ATAS) --}}
        <nav class="navbar navbar-expand-xl navbar-glass py-2 no-print">
            <div class="container-fluid px-3 px-xl-5">
                <a class="navbar-brand fw-bold d-flex align-items-center gap-2 text-slate-900 me-2 me-xl-4 flex-shrink-0" href="{{ route('home') }}">
                    <span class="brand-logo-box">
                        <i class="bi bi-bag-check-fill fs-5"></i>
                    </span>
                    <div>
                        <div class="lh-1 fw-extrabold" style="font-size: 1.05rem;">BookStore</div>
                        <div class="text-slate-600 fw-medium" style="font-size: 0.68rem;">Toko Buku</div>
                    </div>
                </a>

                <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain">
                    <i class="bi bi-list fs-3 text-slate-900"></i>
                </button>

                <div class="collapse navbar-collapse" id="navbarMain">
                    @include('layouts.partials.navbar-user')
                </div>
            </div>
        </nav>

        <main class="container py-4 flex-grow-1">
            @include('layouts.partials.flash-alerts')
            @yield('content')
        </main>

        <footer class="bg-white border-top py-3 mt-5 no-print">
            <div class="container d-flex flex-column flex-md-row justify-content-between align-items-center text-slate-600 small">
                <div>
                    <strong class="text-slate-900">BookStore</strong> &mdash; Sistem Informasi Toko Buku &amp; Transaksi Online
                </div>
                <div>
                    Katalog Buku &bull; Keranjang Belanja &bull; Transaksi Terpadu
                </div>
            </div>
        </footer>
    @endif
@else
    {{-- LAYOUT GUEST (NAVBAR ATAS) --}}
    <nav class="navbar navbar-expand-lg navbar-glass py-2 no-print">
        <div class="container">
            <a class="navbar-brand fw-bold d-flex align-items-center gap-2 text-slate-900 me-4" href="{{ route('home') }}">
                <span class="brand-logo-box">
                    <i class="bi bi-bag-check-fill fs-5"></i>
                </span>
                <div>
                    <div class="lh-1 fw-extrabold" style="font-size: 1.05rem;">BookStore</div>
                    <div class="text-slate-600 fw-medium" style="font-size: 0.68rem;">Toko Buku</div>
                </div>
            </a>

            <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain">
                <i class="bi bi-list fs-3 text-slate-900"></i>
            </button>

            <div class="collapse navbar-collapse" id="navbarMain">
                @include('layouts.partials.navbar-guest')
            </div>
        </div>
    </nav>

    <main class="container py-4 flex-grow-1">
        @include('layouts.partials.flash-alerts')
        @yield('content')
    </main>

    <footer class="bg-white border-top py-3 mt-5 no-print">
        <div class="container d-flex flex-column flex-md-row justify-content-between align-items-center text-slate-600 small">
            <div>
                <strong class="text-slate-900">BookStore</strong> &mdash; Sistem Informasi Toko Buku &amp; Transaksi Online
            </div>
            <div>
                Katalog Buku &bull; Keranjang Belanja &bull; Transaksi Terpadu
            </div>
        </div>
    </footer>
@endauth

    @auth
        @if(auth()->user()->role === 'admin')
            <div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1090;">
                <div id="book-delete-blocked-toast" class="toast border-0" role="alert" aria-live="assertive" aria-atomic="true">
                    <div class="toast-header">
                        <i class="bi bi-exclamation-triangle-fill text-warning me-2"></i>
                        <strong class="me-auto">Buku belum dapat dihapus</strong>
                        <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Tutup"></button>
                    </div>
                    <div id="book-delete-blocked-toast-message" class="toast-body"></div>
                </div>
            </div>
        @include('layouts.partials.book-delete-confirmation-modal')
        @else
            <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1090; margin-top: 68px;">
                <div id="cart-action-toast" class="toast border-0 shadow" role="alert" aria-live="polite" aria-atomic="true">
                    <div class="toast-header">
                        <i id="cart-action-toast-icon" class="bi bi-cart-check-fill text-success me-2"></i>
                        <strong id="cart-action-toast-title" class="me-auto">Keranjang Belanja</strong>
                        <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Tutup"></button>
                    </div>
                    <div id="cart-action-toast-message" class="toast-body small"></div>
                </div>
            </div>
        @endif

        @include('layouts.partials.logout-confirmation-modal')
    @endauth

    {{-- Floating Web Chat Button (khusus non-admin atau opsional) --}}
    @auth
        @if(auth()->user()->role !== 'admin')
            <a href="{{ route('chat.index') }}"
               class="chat-floating-btn no-print"
               title="Live Chat Admin Toko"
               aria-label="Live Chat">
                <i class="bi bi-chat-dots-fill fs-5"></i>
                <span class="chat-text">Chat Admin</span>
                @php
                    $unreadFloatCount = \App\Models\Message::where('receiver_id', auth()->id())->where('is_read', false)->count();
                @endphp
                @if($unreadFloatCount > 0)
                    <span class="badge rounded-pill bg-danger ms-1">{{ $unreadFloatCount }}</span>
                @endif
            </a>
        @endif
    @endauth

    <style>
        .chat-floating-btn {
            position: fixed;
            bottom: 24px;
            right: 24px;
            z-index: 1050;
            display: inline-flex;
            align-items: center;
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            color: #ffffff !important;
            padding: 12px 18px;
            border-radius: 9999px;
            box-shadow: 0 10px 25px -5px rgba(37, 99, 235, 0.45), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
            text-decoration: none;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            overflow: hidden;
            font-weight: 600;
            font-size: 0.95rem;
        }
        .chat-floating-btn:hover {
            transform: translateY(-3px) scale(1.02);
            box-shadow: 0 16px 30px -4px rgba(37, 99, 235, 0.55), 0 10px 12px -5px rgba(0, 0, 0, 0.15);
            color: #ffffff !important;
        }
        .chat-floating-btn .chat-text {
            margin-left: 8px;
            white-space: nowrap;
            letter-spacing: 0.01em;
        }
        @media (max-width: 576px) {
            .chat-floating-btn {
                bottom: 18px;
                right: 18px;
                padding: 12px;
            }
            .chat-floating-btn .chat-text {
                display: none;
            }
        }
    </style>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Sidebar Toggle untuk Mobile
        const sidebarToggle = document.getElementById('sidebarToggle');
        const adminSidebar = document.getElementById('adminSidebar');
        const sidebarBackdrop = document.getElementById('sidebarBackdrop');

        if (sidebarToggle && adminSidebar && sidebarBackdrop) {
            sidebarToggle.addEventListener('click', function() {
                adminSidebar.classList.toggle('show');
                sidebarBackdrop.classList.toggle('show');
            });

            sidebarBackdrop.addEventListener('click', function() {
                adminSidebar.classList.remove('show');
                sidebarBackdrop.classList.remove('show');
            });
        }

        const blockedDeleteToast = document.getElementById('book-delete-blocked-toast');
        const blockedDeleteToastMessage = document.getElementById('book-delete-blocked-toast-message');

        document.addEventListener('click', function (event) {
            const blockedDeleteButton = event.target.closest('[data-delete-blocked-reason]');

            if (!blockedDeleteButton || !blockedDeleteToast || !blockedDeleteToastMessage) {
                return;
            }

            blockedDeleteToastMessage.textContent = blockedDeleteButton.dataset.deleteBlockedReason;
            bootstrap.Toast.getOrCreateInstance(blockedDeleteToast).show();
        });

        let logoutFormToSubmit = null;

        document.querySelectorAll('.logout-confirm-trigger').forEach((button) => {
            button.addEventListener('click', function () {
                logoutFormToSubmit = this.closest('form');
            });
        });

        const confirmLogoutButton = document.getElementById('confirmLogoutButton');
        if (confirmLogoutButton) {
            confirmLogoutButton.addEventListener('click', function () {
                if (logoutFormToSubmit) {
                    logoutFormToSubmit.submit();
                }
            });
        }

        let bookDeleteFormToSubmit = null;
        const bookDeleteConfirmTitle = document.getElementById('bookDeleteConfirmTitle');

        document.addEventListener('click', function (event) {
            const trigger = event.target.closest('.book-delete-confirm-trigger');
            if(!trigger) {
                return;
            }
            
            bookDeleteFormToSubmit = trigger.closest('form');
            if (bookDeleteConfirmTitle) {
                bookDeleteConfirmTitle.textContent = trigger.dataset.bookTitle || 'ini';
            }
        });

        const confirmBookDeleteButton = document.getElementById('confirmBookDeleteButton');
        if (confirmBookDeleteButton) {
            confirmBookDeleteButton.addEventListener('click', function () {
                if (bookDeleteFormToSubmit) {
                    bookDeleteFormToSubmit.submit();
                }
            });
        }
       

        window.addEventListener('pageshow', function (event) {
            if (event.persisted || (window.performance && window.performance.getEntriesByType('navigation')[0]?.type === 'back_forward')) {
                window.location.reload();
            }
        });

        // Indikator batas karakter real-time untuk semua input/textarea yang memiliki atribut maxlength
        document.addEventListener('input', function (event) {
            const el = event.target;
            if (!el.matches('input[maxlength], textarea[maxlength]')) {
                return;
            }
            if (el.type === 'password' || el.type === 'hidden') {
                return;
            }

            const max = parseInt(el.getAttribute('maxlength'), 10);
            if (!max || max <= 0) {
                return;
            }

            let hint = el.parentElement.querySelector(':scope > .char-limit-hint');
            if (!hint) {
                hint = document.createElement('div');
                hint.className = 'char-limit-hint small mt-1 w-100';
                if (el.parentElement.classList.contains('input-group')) {
                    el.parentElement.appendChild(hint);
                } else {
                    el.insertAdjacentElement('afterend', hint);
                }
            }

            const len = el.value.length;
            if (len === 0) {
                hint.classList.add('d-none');
                return;
            }

            hint.classList.remove('d-none');
            hint.textContent = `${len}/${max} karakter (maksimal ${max})`;
            const isOverLimit = len > max;
            hint.className = 'char-limit-hint small mt-1 w-100 ' + (isOverLimit ? 'text-danger fw-semibold' : 'text-muted');
        });

        // AJAX + Cooldown anti double-click untuk tombol Tambah ke Keranjang (.js-cart-add-form)
        (function () {
            const cartInFlight = new Set();
            const cartCooldownUntil = new Map();
            const cartCooldownTimers = new Map();
            const cartToastEl = document.getElementById('cart-action-toast');
            const cartToastIcon = document.getElementById('cart-action-toast-icon');
            const cartToastTitle = document.getElementById('cart-action-toast-title');
            const cartToastMessage = document.getElementById('cart-action-toast-message');

            const showCartToast = (message, variant = 'success') => {
                if (!cartToastEl || !cartToastMessage) {
                    return;
                }
                const styles = {
                    success: { icon: 'bi bi-cart-check-fill text-success me-2', title: 'Berhasil Ditambahkan' },
                    warning: { icon: 'bi bi-hourglass-split text-warning me-2', title: 'Tunggu Sebentar' },
                    danger: { icon: 'bi bi-exclamation-circle-fill text-danger me-2', title: 'Gagal Menambahkan' },
                };
                const style = styles[variant] || styles.success;
                if (cartToastIcon) {
                    cartToastIcon.className = style.icon;
                }
                if (cartToastTitle) {
                    cartToastTitle.textContent = style.title;
                }
                cartToastMessage.textContent = message;
                bootstrap.Toast.getOrCreateInstance(cartToastEl, { delay: 3000 }).show();
            };

            const syncCartCountUI = (count) => {
                if (typeof count !== 'number') {
                    return;
                }
                document.querySelectorAll('[data-cart-count-badge]').forEach((badge) => {
                    if (count > 0) {
                        badge.className = 'badge rounded-pill bg-danger ms-1';
                        badge.textContent = String(count);
                    } else {
                        badge.className = 'text-slate-500 ms-1';
                        badge.textContent = '(0)';
                    }
                });
                document.querySelectorAll('[data-cart-count-text]').forEach((el) => {
                    el.textContent = String(count);
                });
            };

            const getBookSubmitButtons = (bookId) => {
                return Array.from(document.querySelectorAll(`.js-cart-add-form[data-book-id="${bookId}"] button[type="submit"]`));
            };

            const restoreButtons = (buttons) => {
                buttons.forEach((btn) => {
                    btn.disabled = false;
                    if (btn.dataset.originalHtml) {
                        btn.innerHTML = btn.dataset.originalHtml;
                    }
                });
            };

            const startBookCooldown = (bookId, seconds) => {
                if (!seconds || seconds <= 0) {
                    return;
                }
                const until = Date.now() + seconds * 1000;
                cartCooldownUntil.set(bookId, Math.max(cartCooldownUntil.get(bookId) || 0, until));

                const tick = () => {
                    const remainingMs = (cartCooldownUntil.get(bookId) || 0) - Date.now();
                    const buttons = getBookSubmitButtons(bookId);

                    if (remainingMs <= 0) {
                        const existingTimer = cartCooldownTimers.get(bookId);
                        if (existingTimer) {
                            clearInterval(existingTimer);
                            cartCooldownTimers.delete(bookId);
                        }
                        cartCooldownUntil.delete(bookId);
                        if (!cartInFlight.has(bookId)) {
                            restoreButtons(buttons);
                        }
                        return;
                    }

                    const secs = Math.ceil(remainingMs / 1000);
                    buttons.forEach((btn) => {
                        if (!btn.dataset.originalHtml) {
                            btn.dataset.originalHtml = btn.innerHTML;
                        }
                        btn.disabled = true;
                        btn.innerHTML = `<i class="bi bi-hourglass-split"></i> Tunggu ${secs}d`;
                    });
                };

                tick();
                const oldTimer = cartCooldownTimers.get(bookId);
                if (oldTimer) {
                    clearInterval(oldTimer);
                }
                cartCooldownTimers.set(bookId, setInterval(tick, 250));
            };

            document.addEventListener('submit', async function (event) {
                const form = event.target.closest('.js-cart-add-form');
                if (!form) {
                    return;
                }

                event.preventDefault();

                const bookId = form.dataset.bookId || form.action;
                const submitter = event.submitter || form.querySelector('button[type="submit"]');

                if (cartInFlight.has(bookId)) {
                    return;
                }

                const existingUntil = cartCooldownUntil.get(bookId) || 0;
                if (existingUntil > Date.now()) {
                    const secs = Math.ceil((existingUntil - Date.now()) / 1000);
                    showCartToast(`Mohon tunggu ${secs} detik sebelum menambahkan buku ini lagi.`, 'warning');
                    return;
                }

                const formButtons = Array.from(form.querySelectorAll('button[type="submit"]'));
                formButtons.forEach((btn) => {
                    if (!btn.dataset.originalHtml) {
                        btn.dataset.originalHtml = btn.innerHTML;
                    }
                    btn.disabled = true;
                });

                if (submitter) {
                    submitter.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Memproses...';
                }

                cartInFlight.add(bookId);

                try {
                    const formData = new FormData(form);
                    if (submitter && submitter.name) {
                        formData.set(submitter.name, submitter.value);
                    }

                    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                        || form.querySelector('input[name="_token"]')?.value
                        || '';

                    const response = await fetch(form.action, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': csrfToken,
                        },
                        body: formData,
                    });

                    const data = await response.json().catch(() => ({}));

                    if (response.status === 429) {
                        const retryAfter = parseInt(data.retry_after || '2', 10);
                        showCartToast(data.message || `Tunggu ${retryAfter} detik sebelum menambahkan lagi.`, 'warning');
                        startBookCooldown(bookId, retryAfter);
                        return;
                    }

                    if (!response.ok) {
                        showCartToast(data.message || 'Gagal menambahkan buku ke keranjang.', 'danger');
                        restoreButtons(formButtons);
                        return;
                    }

                    if (typeof data.cart_count === 'number') {
                        syncCartCountUI(data.cart_count);
                    }

                    if (data.redirect_url) {
                        window.location.href = data.redirect_url;
                        return;
                    }

                    showCartToast(data.message || 'Buku berhasil ditambahkan ke keranjang.', 'success');
                    startBookCooldown(bookId, parseInt(data.cooldown_seconds || '2', 10));
                } catch (error) {
                    showCartToast('Koneksi terganggu. Silakan coba lagi.', 'danger');
                    restoreButtons(formButtons);
                } finally {
                    cartInFlight.delete(bookId);
                }
            });
        })();
    </script>
</body>

</html>
