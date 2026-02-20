<?php

namespace App\Http\Controllers;

use App\Models\Beneficiary;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        return Inertia::render('Dashboard', [
            'total_beneficiaries' => Beneficiary::count(),
            'totally_damaged' => Beneficiary::where('classify_extent_of_damaged_house', 'Totally Damaged (Severely)')->count(),
            'partially_damaged' => Beneficiary::where('classify_extent_of_damaged_house', 'Partially Damaged (Slightly)')->count(),
            'nhts_poor' => Beneficiary::where('nhts_pr_classification', 'Poor')->count(),
            'nhts_near_poor' => Beneficiary::where('nhts_pr_classification', 'Near Poor')->count(),
            'nhts_not_poor' => Beneficiary::where('nhts_pr_classification', 'Not Poor')->count(),
            'by_municipality' => Beneficiary::select('municipality')
                ->selectRaw('count(*) as count')
                ->groupBy('municipality')
                ->orderByDesc('count')
                ->get(),
            'recent_beneficiaries' => Beneficiary::select(['id', 'first_name', 'last_name', 'middle_name', 'municipality', 'barangay', 'created_at'])
                ->latest()
                ->limit(10)
                ->get(),
        ]);
    }
}
