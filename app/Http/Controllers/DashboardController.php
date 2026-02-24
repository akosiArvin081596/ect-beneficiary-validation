<?php

namespace App\Http\Controllers;

use App\Models\Beneficiary;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * DROMIC-based targets per barangay for Lanuza municipality.
     */
    private const LANUZA_BASELINE = [
        'Agsam' => 712,
        'Bocawe' => 242,
        'Bunga' => 420,
        'Gamuton' => 420,
        'Habag' => 259,
        'Mampi' => 223,
        'Nurcia' => 352,
        'Pakwan' => 295,
        'Sibahay' => 393,
        'Zone I (Pob.)' => 170,
        'Zone II (Pob.)' => 193,
        'Zone III (Pob.)' => 213,
        'Zone IV (Pob.)' => 196,
    ];

    public function __invoke(Request $request): Response
    {
        $lanuzaActual = Beneficiary::where('municipality', 'Lanuza')
            ->select('barangay')
            ->selectRaw('count(*) as count')
            ->groupBy('barangay')
            ->pluck('count', 'barangay');

        $baselineComparison = collect(self::LANUZA_BASELINE)->map(function ($target, $barangay) use ($lanuzaActual) {
            $actual = $lanuzaActual->get($barangay, 0);

            return [
                'barangay' => $barangay,
                'baseline' => $target,
                'actual' => $actual,
                'remaining' => $target - $actual,
                'progress' => $target > 0 ? round(($actual / $target) * 100, 1) : 0,
            ];
        })->values();

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
            'by_barangay' => Beneficiary::select('municipality', 'barangay')
                ->selectRaw('count(*) as count')
                ->selectRaw("sum(classify_extent_of_damaged_house = 'Totally Damaged (Severely)') as totally_damaged")
                ->selectRaw("sum(classify_extent_of_damaged_house = 'Partially Damaged (Slightly)') as partially_damaged")
                ->groupBy('municipality', 'barangay')
                ->orderBy('municipality')
                ->orderBy('barangay')
                ->get(),
            'recent_beneficiaries' => Beneficiary::select(['id', 'first_name', 'last_name', 'middle_name', 'municipality', 'barangay', 'created_at'])
                ->latest()
                ->limit(10)
                ->get(),
            'baseline_comparison' => $baselineComparison,
        ]);
    }

    public function beneficiariesByBarangay(Request $request): JsonResponse
    {
        $request->validate([
            'municipality' => ['required', 'string'],
            'barangay' => ['required', 'string'],
            'damage_type' => ['required', 'in:totally,partially,all'],
        ]);

        $query = Beneficiary::select(['id', 'first_name', 'last_name', 'middle_name', 'extension_name', 'sex', 'birth_date', 'classify_extent_of_damaged_house', 'purok'])
            ->where('municipality', $request->municipality)
            ->where('barangay', $request->barangay);

        if ($request->damage_type === 'totally') {
            $query->where('classify_extent_of_damaged_house', 'Totally Damaged (Severely)');
        } elseif ($request->damage_type === 'partially') {
            $query->where('classify_extent_of_damaged_house', 'Partially Damaged (Slightly)');
        }

        return response()->json($query->orderBy('last_name')->orderBy('first_name')->get());
    }
}
