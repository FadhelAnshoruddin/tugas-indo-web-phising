@extends('layouts.app')

@section('title', 'Lucky Loop — Putar & Menangkan')

@section('content')
<div data-prizes="{{ $prizes->count() }}" class="min-h-screen overflow-x-hidden bg-[#102a43] text-white">
    <script>window.isAuthenticated = @json(auth()->check());</script>
    <header class="mx-auto flex w-full max-w-6xl items-center justify-between px-6 py-6 lg:px-10">
        <a href="{{ route('spin-wheel.index') }}" class="flex items-center gap-3 text-sm font-semibold tracking-wide"><span class="grid h-9 w-9 place-items-center rounded-xl bg-[#f4d35e] text-xl text-[#102a43]">✦</span> LUCKY LOOP</a>
        <nav class="flex items-center gap-4 text-sm text-slate-300">
            @auth
                <a href="{{ route('dashboard') }}" class="hidden hover:text-white sm:block">Halo, {{ auth()->user()->name }}</a>
                <form action="{{ route('logout') }}" method="POST">@csrf <button class="rounded-full border border-white/15 px-4 py-2 hover:border-white/40">Keluar</button></form>
            @else
                <a href="{{ route('login') }}" class="hover:text-white">Masuk</a>
                <a href="{{ route('register') }}" class="rounded-full bg-[#f4d35e] px-4 py-2 font-semibold text-[#102a43] hover:bg-[#ffe887]">Daftar</a>
            @endauth
        </nav>
    </header>

    <main class="mx-auto grid w-full max-w-6xl items-center gap-12 px-6 pb-16 pt-8 lg:grid-cols-[.85fr_1.15fr] lg:px-10 lg:pt-14">
        <section class="order-2 lg:order-1">
            <p class="mb-5 text-xs font-semibold uppercase tracking-[.28em] text-[#f4d35e]">Hadiah pilihanmu menunggu</p>
            <h1 class="max-w-xl font-[var(--font-display)] text-5xl leading-[.95] text-white sm:text-7xl">Putar sekali.<br><span class="text-[#f4d35e]">Bawa pulang</span> hoki.</h1>
            <p class="mt-7 max-w-md text-base leading-7 text-slate-300">Coba keberuntunganmu hari ini. Gratis untuk semua, dan hadiah yang kamu menangkan akan tersimpan sampai siap diklaim.</p>
            <div class="mt-9 grid max-w-md grid-cols-2 gap-3">
                <div class="rounded-2xl border border-white/10 bg-white/5 p-4"><p class="text-2xl font-semibold text-[#f4d35e]">{{ $prizes->count() }}</p><p class="mt-1 text-xs text-slate-400">hadiah menarik</p></div>
                <div class="rounded-2xl border border-white/10 bg-white/5 p-4"><p class="text-2xl font-semibold text-[#72bda3]">100%</p><p class="mt-1 text-xs text-slate-400">gratis diputar</p></div>
            </div>
        </section>

        <section class="order-1 flex flex-col items-center lg:order-2">
            <div class="relative h-[min(82vw,430px)] w-[min(82vw,430px)]">
                <div class="absolute -top-3 left-1/2 z-20 -translate-x-1/2 drop-shadow-lg"><div class="h-0 w-0 border-x-[18px] border-t-[30px] border-x-transparent border-t-[#f4d35e]"></div></div>
                <div id="wheel" class="wheel-shadow relative h-full w-full rounded-full border-[10px] border-[#f4d35e] transition-transform duration-[5200ms] ease-[cubic-bezier(.17,.67,.12,.99)]" style="background: conic-gradient(@foreach ($prizes as $i => $prize) {{ $prize->color }} {{ $i * (360 / max($prizes->count(), 1)) }}deg {{ ($i + 1) * (360 / max($prizes->count(), 1)) }}deg{{ !$loop->last ? ',' : '' }} @endforeach);">
                    @foreach ($prizes as $i => $prize)
                        <span class="absolute left-1/2 top-1/2 w-[28%] origin-left -translate-y-1/2 pl-3 text-center text-[10px] font-bold uppercase leading-tight text-white drop-shadow-md sm:text-xs" style="transform: rotate({{ ($i + .5) * (360 / max($prizes->count(), 1)) }}deg) translateX(18%);">{{ $prize->name }}</span>
                    @endforeach
                    <div class="absolute left-1/2 top-1/2 z-10 grid h-24 w-24 -translate-x-1/2 -translate-y-1/2 place-items-center rounded-full border-8 border-[#102a43] bg-[#f4d35e] shadow-xl sm:h-28 sm:w-28"><button id="spinBtn" class="h-full w-full rounded-full text-sm font-black tracking-wider text-[#102a43] transition hover:bg-[#ffe887] active:scale-95 disabled:cursor-wait disabled:opacity-70">PUTAR</button></div>
                </div>
            </div>
            <p class="mt-8 text-center text-sm text-slate-400">Tekan tombol untuk menemukan hadiahmu <span class="text-[#f4d35e]">✦</span></p>
        </section>
    </main>

    <div id="prizeModal" class="modal-backdrop fixed inset-0 z-50 hidden items-center justify-center p-5"><div class="pop w-full max-w-sm rounded-[2rem] bg-[#f7f4ed] p-8 text-center text-[#102a43] shadow-2xl"><p class="text-5xl">✦</p><p class="mt-5 text-xs font-bold uppercase tracking-[.25em] text-[#657786]">Kamu menang!</p><h2 id="prizeName" class="mt-2 text-3xl font-bold"></h2><p class="mt-3 text-sm leading-6 text-[#657786]">Hadiahmu sudah diamankan. Klaim sekarang agar tercatat di akunmu.</p><div class="mt-7 flex gap-3"><button id="claimBtn" class="flex-1 rounded-full bg-[#102a43] px-4 py-3 font-semibold text-white hover:bg-[#1d4668]">Klaim hadiah</button><button id="closeModalBtn" class="rounded-full border border-[#d5d9dc] px-5 py-3 font-semibold hover:bg-[#e9e6de]">Tutup</button></div></div></div>
    <div id="loginRequiredModal" class="modal-backdrop fixed inset-0 z-50 hidden items-center justify-center p-5"><div class="pop w-full max-w-sm rounded-[2rem] bg-[#f7f4ed] p-8 text-center text-[#102a43] shadow-2xl"><p class="text-5xl">◌</p><h2 class="mt-5 text-2xl font-bold">Satu langkah lagi</h2><p class="mt-3 text-sm leading-6 text-[#657786]">Hadiahmu sudah tersimpan. Masuk atau buat akun untuk mengklaimnya tanpa perlu memutar ulang.</p><div class="mt-7 flex gap-3"><button id="goToLoginBtn" class="flex-1 rounded-full bg-[#102a43] px-4 py-3 font-semibold text-white hover:bg-[#1d4668]">Masuk</button><button id="cancelLoginBtn" class="rounded-full border border-[#d5d9dc] px-5 py-3 font-semibold hover:bg-[#e9e6de]">Batal</button></div></div></div>
    <div id="claimToast" class="fixed bottom-6 left-1/2 z-50 hidden -translate-x-1/2 rounded-full bg-[#72bda3] px-6 py-3 text-sm font-semibold text-[#102a43] shadow-xl">Hadiah berhasil diklaim!</div>
</div>
@endsection