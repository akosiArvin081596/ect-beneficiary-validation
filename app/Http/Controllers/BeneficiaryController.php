<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBeneficiaryRequest;
use App\Models\Beneficiary;
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
                $query->where(function ($q) use ($search) {
                    $q->where('last_name', 'like', "%{$search}%")
                        ->orWhere('first_name', 'like', "%{$search}%")
                        ->orWhere('middle_name', 'like', "%{$search}%")
                        ->orWhere('municipality', 'like', "%{$search}%")
                        ->orWhere('barangay', 'like', "%{$search}%");
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
        return Inertia::render('Beneficiaries/Create');
    }

    public function store(StoreBeneficiaryRequest $request): RedirectResponse
    {
        $this->createBeneficiaryWithRelations($request->validated());

        return to_route('beneficiaries.index');
    }

    public function offlineSync(StoreBeneficiaryRequest $request): JsonResponse
    {
        $this->createBeneficiaryWithRelations($request->validated());

        return response()->json(['synced' => true], 201);
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
