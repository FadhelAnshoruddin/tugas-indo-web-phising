@extends('layouts.app')

@section('title', 'E-Wallet Security Simulation')

@section('content')
<main class="wallet-page min-h-screen px-4 py-8 text-white sm:px-6 lg:px-8">
    <div class="mx-auto w-full max-w-2xl">
        <header class="mb-8 flex items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="grid h-12 w-12 place-items-center rounded-full border border-red-500/30 bg-red-500/10 text-2xl">!</div>
                <div>
                    <p class="text-xs font-bold uppercase tracking-[.2em] text-slate-500">Security Simulation</p>
                    <h1 class="mt-1 text-xl font-black sm:text-2xl">E-Wallet</h1>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <span class="hidden text-sm text-slate-400 sm:inline">{{ auth()->user()->name }}</span>
                <form action="{{ route('logout') }}" method="POST">@csrf<button class="rounded-full border border-white/10 px-4 py-2 text-xs font-bold text-slate-300 transition hover:border-white/30 hover:text-white">Keluar</button></form>
            </div>
        </header>

        <div class="mb-8 text-center">
            <div class="mx-auto mb-5 grid h-20 w-20 place-items-center rounded-full border border-red-500/30 bg-red-500/10 text-4xl text-red-400">!</div>
            <h2 class="text-3xl font-black sm:text-4xl">PERINGATAN KEAMANAN</h2>
            <p class="mt-3 text-slate-400">Simulasi transaksi tidak dikenal, {{ auth()->user()->name }}</p>
        </div>

        <section id="walletCard" class="wallet-card overflow-hidden">
            <div class="border-b border-white/10 p-6 sm:p-8">
                <div class="flex items-start justify-between gap-4">
                    <div><p class="text-sm text-slate-500">Saldo tersedia</p><div id="balance" class="balance-number mt-2 text-4xl font-black sm:text-6xl">Rp 80.000.000</div></div>
                    <span id="securityBadge" class="rounded-full border border-red-500/30 bg-red-500/10 px-3 py-2 text-xs font-bold text-red-400">! Simulasi aktif</span>
                </div>
                <div class="mt-6 h-3 overflow-hidden rounded-full bg-slate-800"><div id="balanceBar" class="balance-bar h-full rounded-full bg-red-500" style="width: 100%"></div></div>
                <div class="mt-3 flex justify-between gap-4 text-xs text-slate-500"><span>Saldo awal: Rp 80.000.000</span><span id="percentage">100%</span></div>
            </div>

            <div class="p-5 sm:p-7"><div class="security-alert rounded-2xl p-4 sm:p-5"><div class="flex gap-3"><div class="text-xl">!</div><div class="min-w-0"><p class="font-black text-red-400">PERINGATAN KEAMANAN</p><p id="warning" class="mt-1 text-sm leading-6 text-slate-300" aria-live="polite">Simulasi sedang memulai pemantauan aktivitas.</p></div></div></div></div>

            <div class="px-5 pb-7 sm:px-7"><div class="mb-3 flex items-center justify-between"><h2 class="font-bold">Aktivitas Terbaru</h2><span id="activityCount" class="text-xs text-slate-500">0 aktivitas</span></div><div id="transactions" class="max-h-80 space-y-2 overflow-y-auto" aria-live="polite"></div></div>

            <div id="finalAlert" class="mx-5 mb-6 hidden rounded-2xl border border-red-500/40 bg-red-500/10 p-5 text-center sm:mx-7">
                <div class="mb-3 text-4xl text-red-400">!</div>
                <h2 class="text-xl font-bold text-red-300">SIMULASI SELESAI</h2>
                <p class="mt-2 text-sm text-slate-400">Saldo simulasi telah mencapai Rp0.</p>
            </div>

            <div class="mx-5 mb-7 rounded-2xl border border-sky-500/20 bg-sky-500/5 p-5 text-sm text-slate-300 sm:mx-7">
                <p class="mb-2 font-semibold text-sky-300">Apa yang baru saja terjadi?</p>
                <ul class="list-inside list-disc space-y-1 text-slate-400">
                    <li>Iming-iming hadiah mendorong user login dengan cepat tanpa berpikir panjang.</li>
                    <li>Saldo berkurang digunakan sebagai simulasi manipulasi rasa panik atau <em>urgency</em>.</li>
                    <li>Dalam serangan nyata, pelaku dapat melanjutkan dengan meminta OTP atau kode verifikasi.</li>
                </ul>
            </div>
        </section>

        <p class="mt-6 text-center text-xs leading-5 text-slate-500">SIMULASI EDUKASI — Tidak ada saldo, akun e-wallet, atau transaksi nyata pihak ketiga yang terlibat.</p>
    </div>
</main>
@endsection