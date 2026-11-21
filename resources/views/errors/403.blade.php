<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>403 — Akses Ditolak</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@500;600&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="{{ asset('css/simperpus.css') }}?v={{ time() }}" rel="stylesheet">
</head>

<body class="d-flex align-items-center justify-content-center min-vh-100 p-3">
    <div class="card border-0 shadow-sm rounded-4 w-100 text-center" style="max-width: 520px;">
        <div class="card-body p-5">
            <div class="display-1 fw-bold text-danger mb-2">403</div>
            <h1 class="h3 fw-bold text-slate-900">Akses Ditolak</h1>
            <p class="text-secondary mb-4">
                Anda tidak memiliki izin untuk membuka halaman ini.
            </p>

            @auth
                <a href="{{ route('dashboard') }}" class="btn btn-primary">
                    <i class="bi bi-arrow-left me-1"></i> Kembali ke Dashboard
                </a>
            @else
                <a href="{{ route('login') }}" class="btn btn-primary">
                    <i class="bi bi-box-arrow-in-right me-1"></i> Masuk ke Akun
                </a>
            @endauth
        </div>
    </div>
</body>

</html>
