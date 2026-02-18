<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBeneficiaryRequest;
use App\Models\Beneficiary;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;

class BeneficiaryController extends Controller
{
    public function index(): Response
    {
        $beneficiaries = Beneficiary::query()
            ->select(['id', 'first_name', 'last_name', 'middle_name', 'municipality', 'barangay', 'civil_status', 'created_at'])
            ->latest()
            ->paginate(20);

        return Inertia::render('Beneficiaries/Index', ['beneficiaries' => $beneficiaries]);
    }

    public function create(): Response
    {
        return Inertia::render('Beneficiaries/Create');
    }

    public function store(StoreBeneficiaryRequest $request): RedirectResponse
    {
        Beneficiary::create($request->validated());

        return to_route('beneficiaries.index');
    }

    public function offlineSync(StoreBeneficiaryRequest $request): JsonResponse
    {
        Beneficiary::create($request->validated());

        return response()->json(['synced' => true], 201);
    }
}
