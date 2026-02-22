<?php

namespace App\Http\Controllers;

use App\Models\Beneficiary;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TrashController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->query('search');

        $beneficiaries = Beneficiary::onlyTrashed()
            ->when($search, function ($query, $search) {
                $escaped = str_replace(['%', '_'], ['\\%', '\\_'], $search);
                $query->where(function ($q) use ($escaped) {
                    $q->where('last_name', 'like', "%{$escaped}%")
                        ->orWhere('first_name', 'like', "%{$escaped}%")
                        ->orWhere('middle_name', 'like', "%{$escaped}%");
                });
            })
            ->latest('deleted_at')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Trash/Index', [
            'beneficiaries' => $beneficiaries,
            'filters' => ['search' => $search ?? ''],
        ]);
    }

    public function restore(int $id): RedirectResponse
    {
        $beneficiary = Beneficiary::onlyTrashed()->findOrFail($id);
        $beneficiary->restore();

        return back();
    }

    public function destroy(int $id): RedirectResponse
    {
        $beneficiary = Beneficiary::onlyTrashed()->findOrFail($id);
        $beneficiary->forceDelete();

        return back();
    }
}
