<?php

namespace App\Http\Controllers;

use App\Models\Prize;
use App\Models\SpinHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SpinWheelController extends Controller
{
    public function index()
    {
        return view('spin-wheel.index', ['prizes' => Prize::where('is_active', true)->orderBy('id')->get()]);
    }

    public function spin()
    {
        $prizes = Prize::where('is_active', true)->orderBy('id')->get();
        if ($prizes->isEmpty()) {
            return response()->json(['message' => 'Belum ada hadiah tersedia.'], 422);
        }

        $random = random_int(1, max($prizes->sum('weight'), 1));
        $winner = $prizes->first();
        $cumulative = 0;
        foreach ($prizes as $prize) {
            $cumulative += $prize->weight;
            if ($random <= $cumulative) {
                $winner = $prize;
                break;
            }
        }

        $history = SpinHistory::create(['user_id' => Auth::id(), 'prize_id' => $winner->id]);
        return response()->json([
            'winner_index' => $prizes->search(fn ($prize) => $prize->id === $winner->id),
            'prize' => ['id' => $winner->id, 'name' => $winner->name],
            'history_id' => $history->id,
        ]);
    }

    public function claim(SpinHistory $history)
    {
        if ($history->user_id && $history->user_id !== Auth::id()) {
            abort(403, 'Hadiah ini bukan milik Anda.');
        }
        if ($history->is_claimed) {
            return response()->json(['message' => 'Hadiah ini sudah pernah diklaim.'], 422);
        }

        $history->update(['user_id' => $history->user_id ?? Auth::id(), 'is_claimed' => true, 'claimed_at' => now()]);
        return response()->json(['message' => 'Hadiah berhasil diklaim!']);
    }
}