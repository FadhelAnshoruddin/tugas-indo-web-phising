# 🎡 Spin Wheel Berhadiah + Login/Register — Laravel 13 + Tailwind CSS 4 + MySQL

Panduan gabungan: fitur **Spin Wheel (Roda Putar Berhadiah)** yang digabung dengan sistem **autentikasi (Login/Register)**, dengan aturan bisnis:

> ✅ **Siapa saja boleh memutar roda** (tanpa perlu login) untuk mencoba peruntungan.
> 🔒 **Tapi untuk meng-klaim hadiah (tombol "Claim"), user WAJIB login terlebih dahulu.**

Jika user belum login lalu menekan **Claim**, sistem akan menampilkan notifikasi bahwa ia harus login dulu, mengarahkannya ke halaman **Login**, dan setelah berhasil login/daftar, hadiah yang tadi dimenangkan **otomatis diklaim** dan user dikembalikan ke halaman spin wheel — tanpa perlu memutar ulang.

**Stack:** Laravel 13, PHP 8.3+, Blade, Tailwind CSS 4, MySQL, Session Authentication, Vanilla JavaScript.

---

## 📁 Struktur Project (Gabungan)

```
app/
 ├─ Http/Controllers/
 │   ├─ AuthController.php
 │   └─ SpinWheelController.php
 └─ Models/
     ├─ User.php
     ├─ Prize.php
     └─ SpinHistory.php
database/
 ├─ migrations/
 │   ├─ 0001_01_01_000000_create_users_table.php   (bawaan Laravel)
 │   ├─ 2026_09_22_000000_create_prizes_table.php
 │   └─ 2026_09_22_000001_create_spin_histories_table.php
 └─ seeders/
     └─ PrizeSeeder.php
resources/
 ├─ css/app.css
 ├─ js/app.js
 └─ views/
     ├─ auth/
     │   ├─ login.blade.php
     │   └─ register.blade.php
     ├─ dashboard.blade.php
     └─ spin-wheel/
         └─ index.blade.php
routes/
 └─ web.php
```

---

## 1. Membuat Project & Database MySQL

```bash
laravel new spin-wheel-app
cd spin-wheel-app
```

Buat database:

```bash
mysql -u root -p
```

```sql
CREATE DATABASE spin_wheel_app;
exit;
```

Atur `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=spin_wheel_app
DB_USERNAME=root
DB_PASSWORD=
```

---

## 2. Migration

### a. Tabel `users` (bawaan Laravel, tidak perlu diubah)

Laravel 13 sudah menyediakan migration `users` secara default berisi kolom `id`, `name`, `email`, `password`, dll. Pastikan `app/Models/User.php` memiliki:

```php
protected $fillable = [
    'name',
    'email',
    'password',
];
```

### b. Tabel `prizes`

```php
<?php
// database/migrations/2026_09_22_000000_create_prizes_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prizes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('color')->default('#6366f1'); // warna segmen roda
            $table->unsignedInteger('weight')->default(1); // bobot probabilitas
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prizes');
    }
};
```

### c. Tabel `spin_histories`

Kolom `user_id` bersifat **nullable**, karena spin boleh dilakukan oleh tamu (guest). Kolom ini baru diisi ketika user login lalu meng-klaim hadiahnya.

```php
<?php
// database/migrations/2026_09_22_000001_create_spin_histories_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('spin_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('prize_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_claimed')->default(false);
            $table->timestamp('claimed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('spin_histories');
    }
};
```

Jalankan migrasi:

```bash
php artisan migrate
```

---

## 3. Models

### `app/Models/User.php` (bawaan, pastikan `$fillable` sudah benar seperti di atas)

### `app/Models/Prize.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Prize extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'color', 'weight', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
```

### `app/Models/SpinHistory.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SpinHistory extends Model
{
    protected $fillable = ['user_id', 'prize_id', 'is_claimed', 'claimed_at'];

    protected $casts = [
        'is_claimed' => 'boolean',
        'claimed_at' => 'datetime',
    ];

    public function prize()
    {
        return $this->belongsTo(Prize::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
```

---

## 4. Seeder — `PrizeSeeder.php`

```php
<?php
// database/seeders/PrizeSeeder.php

namespace Database\Seeders;

use App\Models\Prize;
use Illuminate\Database\Seeder;

class PrizeSeeder extends Seeder
{
    public function run(): void
    {
        $prizes = [
            ['name' => 'Voucher 50K',   'color' => '#f97316', 'weight' => 10],
            ['name' => 'Voucher 20K',   'color' => '#facc15', 'weight' => 20],
            ['name' => 'Diskon 10%',    'color' => '#4ade80', 'weight' => 25],
            ['name' => 'Coba Lagi',     'color' => '#94a3b8', 'weight' => 20],
            ['name' => 'Gratis Ongkir', 'color' => '#38bdf8', 'weight' => 15],
            ['name' => 'Hadiah Utama',  'color' => '#a855f7', 'weight' => 2],
            ['name' => 'Diskon 5%',     'color' => '#f472b6', 'weight' => 25],
            ['name' => 'Zonk',          'color' => '#64748b', 'weight' => 15],
        ];

        foreach ($prizes as $prize) {
            Prize::create($prize);
        }
    }
}
```

Daftarkan di `DatabaseSeeder.php`:

```php
public function run(): void
{
    $this->call(PrizeSeeder::class);
}
```

Jalankan:

```bash
php artisan db:seed --class=PrizeSeeder
```

---

## 5. Setup Tailwind CSS 4

```bash
npm install tailwindcss @tailwindcss/vite
```

**`vite.config.js`**

```js
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        tailwindcss(),
    ],
});
```

**`resources/css/app.css`**

```css
@import "tailwindcss";

@theme {
  --animate-pop: pop 0.35s ease-out;
}

@keyframes pop {
  0%   { transform: scale(0.7); opacity: 0; }
  70%  { transform: scale(1.05); opacity: 1; }
  100% { transform: scale(1); }
}
```

---

## 6. AuthController (dengan dukungan "redirect kembali setelah login")

Bagian **kunci integrasi**: `showLogin()` dan `showRegister()` menyimpan parameter `redirect_to` (misalnya `/spin-wheel`) ke session. Setelah login/register berhasil, user diarahkan kembali ke halaman tersebut — bukan selalu ke `/dashboard`. Inilah yang membuat alur "klaim hadiah → wajib login → balik lagi ke spin wheel" berjalan mulus.

```php
<?php
// app/Http/Controllers/AuthController.php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    public function showRegister(Request $request)
    {
        $this->rememberRedirect($request);

        return view('auth.register');
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        Auth::login($user);

        return redirect()->to($this->pullRedirect() ?? route('dashboard'));
    }

    public function showLogin(Request $request)
    {
        $this->rememberRedirect($request);

        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            return redirect()->to($this->pullRedirect() ?? route('dashboard'));
        }

        return back()
            ->withErrors(['email' => 'Email atau password tidak valid.'])
            ->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    /**
     * Simpan tujuan redirect (mis. /spin-wheel) ke session, hanya jika
     * berupa path relatif yang aman (mencegah open-redirect).
     */
    private function rememberRedirect(Request $request): void
    {
        $redirectTo = $request->query('redirect_to');

        if ($redirectTo && str_starts_with($redirectTo, '/') && !str_starts_with($redirectTo, '//')) {
            $request->session()->put('redirect_to', $redirectTo);
        }
    }

    private function pullRedirect(): ?string
    {
        return session()->pull('redirect_to');
    }
}
```

---

## 7. SpinWheelController (klaim wajib login)

- `index()` dan `spin()` **tetap publik** — tamu boleh mencoba peruntungan.
- `claim()` dilindungi middleware `auth` di routes (lihat bagian Routes), dan otomatis menautkan `user_id` histori ke user yang sedang login apabila sebelumnya `null` (kasus spin sebagai tamu).

```php
<?php
// app/Http/Controllers/SpinWheelController.php

namespace App\Http\Controllers;

use App\Models\Prize;
use App\Models\SpinHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SpinWheelController extends Controller
{
    public function index()
    {
        $prizes = Prize::where('is_active', true)->orderBy('id')->get();

        return view('spin-wheel.index', compact('prizes'));
    }

    public function spin(Request $request)
    {
        $prizes = Prize::where('is_active', true)->orderBy('id')->get();

        if ($prizes->isEmpty()) {
            return response()->json(['message' => 'Belum ada hadiah tersedia.'], 422);
        }

        // Weighted random selection di server side (tidak bisa dimanipulasi client)
        $totalWeight = $prizes->sum('weight');
        $random = random_int(1, max($totalWeight, 1));

        $winner = null;
        $cumulative = 0;

        foreach ($prizes as $prize) {
            $cumulative += $prize->weight;
            if ($random <= $cumulative) {
                $winner = $prize;
                break;
            }
        }

        $winner = $winner ?? $prizes->last();
        $winnerIndex = $prizes->search(fn ($p) => $p->id === $winner->id);

        // user_id diisi jika kebetulan sudah login saat spin, boleh null jika tamu.
        $history = SpinHistory::create([
            'user_id'  => Auth::id(),
            'prize_id' => $winner->id,
        ]);

        return response()->json([
            'winner_index' => $winnerIndex,
            'prize' => [
                'id'   => $winner->id,
                'name' => $winner->name,
            ],
            'history_id' => $history->id,
        ]);
    }

    /**
     * Rute ini dilindungi middleware 'auth' — hanya bisa diakses jika sudah login.
     */
    public function claim(Request $request, SpinHistory $history)
    {
        // Jika histori sudah pernah tertaut ke user lain, tolak.
        if ($history->user_id && $history->user_id !== Auth::id()) {
            abort(403, 'Hadiah ini bukan milik Anda.');
        }

        if ($history->is_claimed) {
            return response()->json(['message' => 'Hadiah ini sudah pernah diklaim.'], 422);
        }

        $history->update([
            'user_id'    => $history->user_id ?? Auth::id(), // tautkan ke user login
            'is_claimed' => true,
            'claimed_at' => now(),
        ]);

        return response()->json([
            'message' => 'Hadiah berhasil diklaim!',
        ]);
    }
}
```

---

## 8. Routes — `routes/web.php`

```php
<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\SpinWheelController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('spin-wheel.index');
});

// ----- Autentikasi (hanya untuk tamu) -----
Route::middleware('guest')->group(function () {
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.store');

    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});

// ----- Spin Wheel -----
// Melihat halaman & memutar roda: PUBLIK (tamu boleh mencoba)
Route::get('/spin-wheel', [SpinWheelController::class, 'index'])->name('spin-wheel.index');
Route::post('/spin-wheel/spin', [SpinWheelController::class, 'spin'])->name('spin-wheel.spin');

// Klaim hadiah: WAJIB LOGIN
Route::post('/spin-wheel/claim/{history}', [SpinWheelController::class, 'claim'])
    ->middleware('auth')
    ->name('spin-wheel.claim');
```

---

## 9. View — `resources/views/auth/login.blade.php`

Sama seperti implementasi auth standar, tidak ada perubahan wajib — session `redirect_to` sudah ditangani otomatis oleh controller di atas.

```html
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gray-100">
    <div class="flex min-h-screen items-center justify-center px-4">
        <div class="w-full max-w-md">
            <div class="rounded-2xl bg-white p-8 shadow-xl">
                <div class="mb-8 text-center">
                    <h1 class="text-3xl font-bold text-gray-900">Selamat Datang</h1>
                    <p class="mt-2 text-sm text-gray-500">Masuk ke akun Anda untuk klaim hadiah</p>
                </div>

                @if ($errors->any())
                    <div class="mb-5 rounded-lg bg-red-50 p-4 text-sm text-red-700">
                        {{ $errors->first() }}
                    </div>
                @endif

                <form action="{{ route('login.store') }}" method="POST" class="space-y-5">
                    @csrf

                    <div>
                        <label for="email" class="mb-2 block text-sm font-medium text-gray-700">Email</label>
                        <input type="email" id="email" name="email" value="{{ old('email') }}" required
                               autocomplete="email"
                               class="w-full rounded-xl border border-gray-300 px-4 py-3 outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200"
                               placeholder="nama@email.com">
                    </div>

                    <div>
                        <label for="password" class="mb-2 block text-sm font-medium text-gray-700">Password</label>
                        <input type="password" id="password" name="password" required
                               autocomplete="current-password"
                               class="w-full rounded-xl border border-gray-300 px-4 py-3 outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200"
                               placeholder="Masukkan password">
                    </div>

                    <div class="flex items-center gap-2">
                        <input type="checkbox" name="remember" id="remember" class="h-4 w-4 rounded border-gray-300">
                        <label for="remember" class="text-sm text-gray-600">Ingat saya</label>
                    </div>

                    <button type="submit"
                            class="w-full rounded-xl bg-indigo-600 px-4 py-3 font-semibold text-white transition hover:bg-indigo-700 focus:outline-none focus:ring-4 focus:ring-indigo-200">
                        Masuk
                    </button>
                </form>

                <div class="mt-6 text-center text-sm text-gray-600">
                    Belum mempunyai akun?
                    <a href="{{ route('register') }}" class="font-semibold text-indigo-600 hover:text-indigo-700">Daftar</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
```

---

## 10. View — `resources/views/auth/register.blade.php`

```html
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Akun</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gray-100">
    <div class="flex min-h-screen items-center justify-center px-4 py-8">
        <div class="w-full max-w-md">
            <div class="rounded-2xl bg-white p-8 shadow-xl">
                <div class="mb-8 text-center">
                    <h1 class="text-3xl font-bold text-gray-900">Buat Akun</h1>
                    <p class="mt-2 text-sm text-gray-500">Daftar dulu supaya bisa klaim hadiah spin wheel</p>
                </div>

                @if ($errors->any())
                    <div class="mb-5 rounded-lg bg-red-50 p-4">
                        <ul class="list-inside list-disc text-sm text-red-700">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('register.store') }}" method="POST" class="space-y-5">
                    @csrf

                    <div>
                        <label for="name" class="mb-2 block text-sm font-medium text-gray-700">Nama Lengkap</label>
                        <input type="text" id="name" name="name" value="{{ old('name') }}" required
                               autocomplete="name"
                               class="w-full rounded-xl border border-gray-300 px-4 py-3 outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200"
                               placeholder="Nama lengkap">
                    </div>

                    <div>
                        <label for="email" class="mb-2 block text-sm font-medium text-gray-700">Email</label>
                        <input type="email" id="email" name="email" value="{{ old('email') }}" required
                               autocomplete="email"
                               class="w-full rounded-xl border border-gray-300 px-4 py-3 outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200"
                               placeholder="nama@email.com">
                    </div>

                    <div>
                        <label for="password" class="mb-2 block text-sm font-medium text-gray-700">Password</label>
                        <input type="password" id="password" name="password" required
                               autocomplete="new-password"
                               class="w-full rounded-xl border border-gray-300 px-4 py-3 outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200"
                               placeholder="Minimal 8 karakter">
                    </div>

                    <div>
                        <label for="password_confirmation" class="mb-2 block text-sm font-medium text-gray-700">Konfirmasi Password</label>
                        <input type="password" id="password_confirmation" name="password_confirmation" required
                               autocomplete="new-password"
                               class="w-full rounded-xl border border-gray-300 px-4 py-3 outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200"
                               placeholder="Ulangi password">
                    </div>

                    <button type="submit"
                            class="w-full rounded-xl bg-indigo-600 px-4 py-3 font-semibold text-white transition hover:bg-indigo-700 focus:outline-none focus:ring-4 focus:ring-indigo-200">
                        Buat Akun
                    </button>
                </form>

                <div class="mt-6 text-center text-sm text-gray-600">
                    Sudah mempunyai akun?
                    <a href="{{ route('login') }}" class="font-semibold text-indigo-600 hover:text-indigo-700">Masuk</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
```

---

## 11. View — `resources/views/dashboard.blade.php`

Ditambahkan tautan ke halaman **Spin Wheel** di navbar.

```html
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gray-100">
    <nav class="border-b bg-white">
        <div class="mx-auto flex max-w-6xl items-center justify-between px-6 py-4">
            <h1 class="text-xl font-bold text-gray-900">Dashboard</h1>

            <div class="flex items-center gap-3">
                <a href="{{ route('spin-wheel.index') }}"
                   class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                    🎡 Spin Wheel
                </a>

                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit"
                            class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700">
                        Logout
                    </button>
                </form>
            </div>
        </div>
    </nav>

    <main class="mx-auto max-w-6xl px-6 py-10">
        <div class="rounded-2xl bg-white p-8 shadow">
            <h2 class="text-2xl font-bold text-gray-900">
                Selamat datang, {{ auth()->user()->name }}
            </h2>
            <p class="mt-2 text-gray-600">Anda berhasil login menggunakan:</p>
            <p class="mt-1 font-medium text-indigo-600">{{ auth()->user()->email }}</p>
        </div>
    </main>
</body>
</html>
```

---

## 12. View — `resources/views/spin-wheel/index.blade.php`

Perubahan dibanding versi awal:

- Navbar kecil menampilkan status login (Login/Daftar untuk tamu, atau nama user + Dashboard/Logout jika sudah login).
- `window.isAuthenticated` diekspos ke JavaScript.
- Modal baru: **`loginRequiredModal`** — muncul saat tamu menekan **Claim**.

```blade
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Spin Wheel Berhadiah</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script>
        window.isAuthenticated = @json(auth()->check());
    </script>
</head>
<body class="min-h-screen bg-gradient-to-br from-slate-900 via-indigo-950 to-slate-900 flex flex-col items-center p-6">

    <!-- Navbar status login -->
    <div class="w-full max-w-md flex items-center justify-between mb-6 text-sm">
        @auth
            <span class="text-slate-300">Halo, <span class="font-semibold text-white">{{ auth()->user()->name }}</span></span>
            <div class="flex items-center gap-3">
                <a href="{{ route('dashboard') }}" class="text-slate-300 hover:text-white">Dashboard</a>
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="text-red-400 hover:text-red-300">Logout</button>
                </form>
            </div>
        @else
            <span class="text-slate-400">Belum login</span>
            <div class="flex items-center gap-3">
                <a href="{{ route('login') }}" class="text-slate-300 hover:text-white">Login</a>
                <a href="{{ route('register') }}" class="rounded-lg bg-indigo-600 px-3 py-1.5 font-semibold text-white hover:bg-indigo-500">Daftar</a>
            </div>
        @endauth
    </div>

    <div class="w-full max-w-md text-center">
        <h1 class="text-2xl md:text-3xl font-bold text-white mb-1">🎉 Spin & Menangkan Hadiah</h1>
        <p class="text-slate-400 mb-8 text-sm">Putar roda gratis — untuk klaim hadiah, kamu perlu login.</p>

        <div class="relative mx-auto" style="width: 320px; height: 320px;">
            <!-- Penunjuk / pointer -->
            <div class="absolute -top-2 left-1/2 -translate-x-1/2 z-20">
                <div class="w-0 h-0 border-l-[14px] border-l-transparent border-r-[14px] border-r-transparent border-t-[24px] border-t-yellow-400 drop-shadow-lg"></div>
            </div>

            <!-- Roda -->
            <div id="wheel"
                 class="relative w-full h-full rounded-full border-[6px] border-yellow-400 shadow-2xl shadow-indigo-900/60"
                 style="transition: transform 5s cubic-bezier(0.17, 0.67, 0.12, 0.99); transform: rotate(0deg);">

                @php $segmentAngle = 360 / max(count($prizes), 1); @endphp

                @foreach ($prizes as $i => $prize)
                    <div class="absolute inset-0 flex justify-center"
                         style="transform: rotate({{ $i * $segmentAngle }}deg);">
                        <span class="mt-4 text-[11px] font-semibold text-white tracking-tight"
                              style="transform: rotate({{ $segmentAngle / 2 }}deg); transform-origin: center 150px; width: 90px; text-align:center; text-shadow: 0 1px 2px rgba(0,0,0,.5);">
                            {{ $prize->name }}
                        </span>
                    </div>
                @endforeach

                <!-- background conic-gradient segmen warna -->
                <div class="absolute inset-0 rounded-full -z-10"
                     style="background: conic-gradient(
                        @foreach ($prizes as $i => $prize)
                            {{ $prize->color }} {{ $i * $segmentAngle }}deg {{ ($i + 1) * $segmentAngle }}deg{{ !$loop->last ? ',' : '' }}
                        @endforeach
                     );">
                </div>
            </div>

            <!-- Titik tengah -->
            <button id="spinBtn"
                    class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 z-30
                           w-16 h-16 rounded-full bg-yellow-400 hover:bg-yellow-300 active:scale-95
                           font-bold text-slate-900 shadow-lg transition disabled:opacity-50 disabled:cursor-not-allowed">
                PUTAR
            </button>
        </div>
    </div>

    <!-- Modal Notifikasi Hadiah -->
    <div id="prizeModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/60 backdrop-blur-sm p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-sm w-full p-6 text-center animate-[pop_0.35s_ease-out]">
            <div class="text-5xl mb-3">🎁</div>
            <h2 class="text-lg font-semibold text-slate-800">Selamat!</h2>
            <p class="text-slate-600 mt-1 mb-6">
                Kamu memenangkan
                <span id="prizeName" class="font-bold text-indigo-600 block text-xl mt-1"></span>
            </p>
            <div class="flex gap-3">
                <button id="claimBtn"
                        class="flex-1 bg-indigo-600 hover:bg-indigo-500 text-white font-semibold py-2.5 rounded-xl transition">
                    Claim
                </button>
                <button id="closeModalBtn"
                        class="flex-1 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold py-2.5 rounded-xl transition">
                    Tutup
                </button>
            </div>
        </div>
    </div>

    <!-- Modal: wajib login untuk klaim -->
    <div id="loginRequiredModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/60 backdrop-blur-sm p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-sm w-full p-6 text-center animate-[pop_0.35s_ease-out]">
            <div class="text-5xl mb-3">🔒</div>
            <h2 class="text-lg font-semibold text-slate-800">Login Dulu, Yuk!</h2>
            <p class="text-slate-600 mt-1 mb-6">
                Hadiahmu sudah tersimpan. Silakan login atau daftar akun dulu untuk mengklaimnya —
                setelah login kamu akan otomatis kembali ke sini.
            </p>
            <div class="flex gap-3">
                <button id="goToLoginBtn"
                        class="flex-1 bg-indigo-600 hover:bg-indigo-500 text-white font-semibold py-2.5 rounded-xl transition">
                    Login Sekarang
                </button>
                <button id="cancelLoginBtn"
                        class="flex-1 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold py-2.5 rounded-xl transition">
                    Batal
                </button>
            </div>
        </div>
    </div>

    <!-- Toast kecil setelah klaim berhasil -->
    <div id="claimToast"
         class="fixed bottom-6 left-1/2 -translate-x-1/2 hidden bg-emerald-500 text-white text-sm font-medium px-5 py-3 rounded-full shadow-lg z-50">
        ✅ Hadiah berhasil diklaim!
    </div>

</body>
</html>
```

---

## 13. JavaScript — `resources/js/app.js`

Logika tambahan dibanding versi awal:

- `window.isAuthenticated` dibaca untuk menentukan apakah user sudah login.
- Saat **Claim** ditekan oleh tamu → `history_id` disimpan ke `localStorage`, modal `loginRequiredModal` muncul, tombol "Login Sekarang" mengarah ke `/login?redirect_to=/spin-wheel`.
- Saat halaman `/spin-wheel` dimuat ulang **setelah login berhasil**, script otomatis mendeteksi `pending_claim_history_id` di `localStorage` dan langsung mengirim request klaim — tanpa user perlu spin ulang.

```js
// resources/js/app.js

document.addEventListener('DOMContentLoaded', () => {
    const wheel          = document.getElementById('wheel');
    const spinBtn         = document.getElementById('spinBtn');
    const modal            = document.getElementById('prizeModal');
    const prizeNameEl      = document.getElementById('prizeName');
    const claimBtn          = document.getElementById('claimBtn');
    const closeBtn           = document.getElementById('closeModalBtn');
    const toast               = document.getElementById('claimToast');
    const loginModal           = document.getElementById('loginRequiredModal');
    const goToLoginBtn          = document.getElementById('goToLoginBtn');
    const cancelLoginBtn         = document.getElementById('cancelLoginBtn');

    if (!wheel) return;

    const segmentCount = wheel.querySelectorAll(':scope > div').length - 1; // -1 karena elemen conic-gradient
    const segmentAngle = 360 / segmentCount;

    const PENDING_KEY = 'pending_claim_history_id';
    const isAuthenticated = window.isAuthenticated === true;

    let currentRotation = 0;
    let isSpinning = false;
    let currentHistoryId = null;

    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    // Jika user baru saja login & sebelumnya ada klaim tertunda, langsung klaim otomatis.
    (function autoClaimPending() {
        const pendingId = localStorage.getItem(PENDING_KEY);
        if (pendingId && isAuthenticated) {
            localStorage.removeItem(PENDING_KEY);
            claimPrize(pendingId, true);
        }
    })();

    spinBtn.addEventListener('click', async () => {
        if (isSpinning) return;
        isSpinning = true;
        spinBtn.disabled = true;

        try {
            const res = await fetch('/spin-wheel/spin', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
            });

            if (!res.ok) throw new Error('Gagal memutar roda');

            const data = await res.json();
            currentHistoryId = data.history_id;

            const extraSpins = 6; // jumlah putaran penuh sebelum berhenti
            const targetSegmentCenter = data.winner_index * segmentAngle + (segmentAngle / 2);
            const finalAngle = (extraSpins * 360) + (360 - targetSegmentCenter);

            currentRotation += finalAngle;
            wheel.style.transform = `rotate(${currentRotation}deg)`;

            setTimeout(() => {
                isSpinning = false;
                spinBtn.disabled = false;
                showPrizeModal(data.prize.name);
            }, 5200);

        } catch (err) {
            console.error(err);
            isSpinning = false;
            spinBtn.disabled = false;
            alert('Terjadi kesalahan saat memutar roda. Coba lagi.');
        }
    });

    function showPrizeModal(name) {
        prizeNameEl.textContent = name;
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function hideModal() {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    function hideLoginModal() {
        loginModal.classList.add('hidden');
        loginModal.classList.remove('flex');
    }

    closeBtn.addEventListener('click', hideModal);

    // Klik tombol "Claim" di modal hadiah
    claimBtn.addEventListener('click', () => {
        if (!currentHistoryId) return;

        if (!isAuthenticated) {
            // Simpan history id agar otomatis diklaim setelah user login
            localStorage.setItem(PENDING_KEY, currentHistoryId);
            hideModal();
            loginModal.classList.remove('hidden');
            loginModal.classList.add('flex');
            return;
        }

        claimPrize(currentHistoryId);
    });

    // Klik "Login Sekarang" di modal login
    goToLoginBtn.addEventListener('click', () => {
        window.location.href = '/login?redirect_to=' + encodeURIComponent('/spin-wheel');
    });

    // Klik "Batal" di modal login
    cancelLoginBtn.addEventListener('click', () => {
        hideLoginModal();
        localStorage.removeItem(PENDING_KEY);
    });

    async function claimPrize(historyId, silent = false) {
        try {
            const res = await fetch(`/spin-wheel/claim/${historyId}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
            });

            if (!res.ok) throw new Error('Gagal klaim hadiah');

            hideModal();
            hideLoginModal();
            toast.classList.remove('hidden');
            setTimeout(() => toast.classList.add('hidden'), 3000);

        } catch (err) {
            console.error(err);
            if (!silent) alert('Gagal mengklaim hadiah. Coba lagi.');
        }
    }
});
```

---

## 14. Cara Menjalankan

```bash
# 1. Install dependency PHP & JS
composer install
npm install

# 2. Copy env & generate key
cp .env.example .env
php artisan key:generate

# 3. Migrasi & seed data hadiah
php artisan migrate
php artisan db:seed --class=PrizeSeeder

# 4. Build asset frontend (dev)
npm run dev

# 5. Jalankan server (di terminal terpisah)
php artisan serve
```

Buka: `http://127.0.0.1:8000/spin-wheel`

---

## 15. Alur Kerja Lengkap (Flow)

### Skenario A — User belum login

```text
/spin-wheel (tamu)
     |
     v
Tekan "PUTAR" ---> POST /spin-wheel/spin (publik, tidak perlu login)
     |
     v
Server pilih pemenang (weighted random) & simpan SpinHistory (user_id = null)
     |
     v
Roda berhenti -> Modal "Selamat! Kamu memenangkan ..." muncul
     |
     v
Tekan "Claim"
     |
     v
JS cek window.isAuthenticated === false
     |
     v
Simpan history_id ke localStorage -> tampilkan modal "Login Dulu, Yuk!"
     |
     v
Tekan "Login Sekarang" -> redirect ke /login?redirect_to=/spin-wheel
     |
     v
User login / daftar akun baru
     |
     v
AuthController redirect balik ke /spin-wheel (dari session 'redirect_to')
     |
     v
JS mendeteksi pending_claim_history_id di localStorage + isAuthenticated true
     |
     v
Otomatis POST /spin-wheel/claim/{history}  (middleware 'auth' meloloskan karena sudah login)
     |
     v
Server tautkan user_id ke user yang login, set is_claimed = true
     |
     v
Toast "✅ Hadiah berhasil diklaim!" muncul
```

### Skenario B — User sudah login

```text
/spin-wheel (sudah login)
     |
     v
Tekan "PUTAR" ---> POST /spin-wheel/spin
     |
     v
Roda berhenti -> Modal hadiah muncul
     |
     v
Tekan "Claim" -> isAuthenticated true -> langsung POST /spin-wheel/claim/{history}
     |
     v
Server set is_claimed = true, claimed_at = now()
     |
     v
Toast "✅ Hadiah berhasil diklaim!" muncul
```

---

## 16. Keamanan yang Digunakan

| Mekanisme | Penjelasan |
|---|---|
| **CSRF Protection** | Semua form/POST memakai `@csrf` / header `X-CSRF-TOKEN`. |
| **Password Hashing** | `Hash::make()`, password tidak pernah disimpan plaintext. |
| **Session Regeneration** | `$request->session()->regenerate()` setelah login mencegah session fixation. |
| **Middleware `auth`** | Rute `/spin-wheel/claim/{history}`, `/dashboard`, dan `/logout` hanya bisa diakses user yang login. |
| **Middleware `guest`** | Rute `/login` dan `/register` hanya untuk yang belum login. |
| **Kepemilikan histori** | `SpinWheelController@claim` menolak (403) jika `user_id` histori sudah tertaut ke user lain. |
| **Anti open-redirect** | `redirect_to` hanya diterima jika berupa path relatif (`/...`), bukan URL absolut ke domain lain. |
| **Server-side randomness** | Pemenang ditentukan `random_int()` di server, bukan di JS, agar tidak bisa dimanipulasi. |

---

## 17. Pengujian Manual

1. **Coba spin tanpa login:**
   Buka `http://127.0.0.1:8000/spin-wheel` (dalam mode incognito), tekan **PUTAR**, tunggu roda berhenti.
2. **Coba klaim tanpa login:**
   Tekan **Claim** → modal "Login Dulu, Yuk!" harus muncul.
3. **Login/Daftar dari modal:**
   Tekan **Login Sekarang** → isi form daftar/login → setelah sukses, halaman harus kembali otomatis ke `/spin-wheel` dan toast **"✅ Hadiah berhasil diklaim!"** muncul tanpa perlu spin ulang.
4. **Cek database:**

```sql
USE spin_wheel_app;

SELECT sh.id, sh.user_id, u.name, p.name AS prize, sh.is_claimed, sh.claimed_at
FROM spin_histories sh
LEFT JOIN users u ON u.id = sh.user_id
JOIN prizes p ON p.id = sh.prize_id
ORDER BY sh.id DESC;
```

Pastikan baris yang baru diklaim memiliki `user_id` terisi dan `is_claimed = 1`.

---

## 18. Ide Pengembangan Lanjutan

- Batasi 1x spin per user per hari (khusus user yang sudah login), cek berdasarkan `user_id` & `created_at`.
- Tambahkan verifikasi email sebelum hadiah bisa diklaim (`email_verified_at`).
- Tambahkan suara "tick" saat roda berputar dan efek confetti saat menang.
- Buat halaman admin (CRUD) untuk mengatur `prizes`, `weight`, dan melihat daftar klaim.
- Tambahkan kolom `stock` pada `prizes` agar hadiah fisik otomatis nonaktif saat stok habis.
- Kirim notifikasi email otomatis ke user setelah berhasil klaim hadiah (`Mail::to($user)->send(...)`).
