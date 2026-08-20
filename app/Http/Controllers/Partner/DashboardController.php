<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    /**
     * Display the partner dashboard.
     */
    public function index()
    {
        $partnerUser = Auth::guard('partner')->user();
        $partner = $partnerUser->partner;

        return view('partner.dashboard', compact('partnerUser', 'partner'));
    }
}
