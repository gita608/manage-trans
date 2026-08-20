<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use App\Models\PartnerRequest;
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
        $partnerId = $partnerUser->partner_id;

        // Get request counts by status
        $pendingCount = PartnerRequest::where('partner_id', $partnerId)
            ->where('status', PartnerRequest::STATUS_PENDING)
            ->count();

        $approvedCount = PartnerRequest::where('partner_id', $partnerId)
            ->where('status', PartnerRequest::STATUS_APPROVED)
            ->count();

        $declinedCount = PartnerRequest::where('partner_id', $partnerId)
            ->where('status', PartnerRequest::STATUS_DECLINED)
            ->count();

        $withdrawnCount = PartnerRequest::where('partner_id', $partnerId)
            ->where('status', PartnerRequest::STATUS_WITHDRAWN)
            ->count();

        // Get recent requests (latest 5)
        $recentRequests = PartnerRequest::where('partner_id', $partnerId)
            ->with(['partnerUser', 'items'])
            ->latest()
            ->take(5)
            ->get();

        return view('partner.dashboard', compact(
            'partnerUser',
            'partner',
            'pendingCount',
            'approvedCount',
            'declinedCount',
            'withdrawnCount',
            'recentRequests'
        ));
    }
}

