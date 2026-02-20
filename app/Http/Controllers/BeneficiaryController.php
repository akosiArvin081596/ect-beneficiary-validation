<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBeneficiaryRequest;
use App\Models\Beneficiary;
use App\Models\Province;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class BeneficiaryController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->query('search');

        $beneficiaries = Beneficiary::query()
            ->select(['id', 'first_name', 'last_name', 'middle_name', 'municipality', 'barangay', 'civil_status', 'created_at'])
            ->when($search, function ($query, $search) {
                $escaped = str_replace(['%', '_'], ['\\%', '\\_'], $search);
                $query->where(function ($q) use ($escaped) {
                    $q->where('last_name', 'like', "%{$escaped}%")
                        ->orWhere('first_name', 'like', "%{$escaped}%")
                        ->orWhere('middle_name', 'like', "%{$escaped}%");
                });
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Beneficiaries/Index', [
            'beneficiaries' => $beneficiaries,
            'filters' => ['search' => $search ?? ''],
        ]);
    }

    public function create(): Response
    {
        $locations = Province::with('municipalities.barangays')
            ->orderBy('name')
            ->get()
            ->map(fn (Province $province) => [
                'name' => $province->name,
                'municipalities' => $province->municipalities
                    ->sortBy('name')
                    ->values()
                    ->map(fn ($municipality) => [
                        'name' => $municipality->name,
                        'barangays' => $municipality->barangays
                            ->sortBy('name')
                            ->pluck('name')
                            ->values()
                            ->all(),
                    ])
                    ->all(),
            ])
            ->values()
            ->all();

        return Inertia::render('Beneficiaries/Create', [
            'locations' => $locations,
        ]);
    }

    public function store(StoreBeneficiaryRequest $request): RedirectResponse
    {
        $data = $request->validated();

        if ($this->beneficiaryExists($data)) {
            return back()->withErrors([
                'first_name' => 'A beneficiary with this name and birth date already exists.',
            ])->withInput();
        }

        $this->createBeneficiaryWithRelations($data);

        return to_route('beneficiaries.index');
    }

    public function offlineSync(StoreBeneficiaryRequest $request): JsonResponse
    {
        $offlineId = $request->header('X-Offline-ID');

        if ($offlineId && Beneficiary::where('offline_id', $offlineId)->exists()) {
            return response()->json(['synced' => true], 201);
        }

        $data = $request->validated();

        if ($this->beneficiaryExists($data)) {
            return response()->json(['synced' => true, 'duplicate' => true], 201);
        }

        if ($offlineId) {
            $data['offline_id'] = $offlineId;
        }

        $this->createBeneficiaryWithRelations($data);

        return response()->json(['synced' => true], 201);
    }

    private function beneficiaryExists(array $data): bool
    {
        return Beneficiary::where('first_name', $data['first_name'])
            ->where('last_name', $data['last_name'])
            ->where('birth_date', $data['birth_date'])
            ->exists();
    }

    private function createBeneficiaryWithRelations(array $data): Beneficiary
    {
        $siblings = $data['siblings'] ?? [];
        $children = $data['children'] ?? [];
        $relatives = $data['relatives'] ?? [];

        unset($data['siblings'], $data['children'], $data['relatives']);
        unset($data['siblings_count'], $data['children_count'], $data['relatives_count']);

        return DB::transaction(function () use ($data, $siblings, $children, $relatives) {
            $beneficiary = Beneficiary::create($data);

            if (! empty($siblings)) {
                $beneficiary->siblings()->createMany($siblings);
            }

            if (! empty($children)) {
                $beneficiary->children()->createMany($children);
            }

            if (! empty($relatives)) {
                $beneficiary->relatives()->createMany($relatives);
            }

            return $beneficiary;
        });
    }
}
