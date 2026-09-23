<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class WalletSimulationController extends Controller
{
    public function index(): View
    {
        return view('wallet.index');
    }
}