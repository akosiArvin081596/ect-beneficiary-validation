<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateBeneficiaryRequest;
use App\Models\Beneficiary;
use App\Models\Province;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MasterlistController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->query('search');

        $beneficiaries = Beneficiary::query()
            ->withCount(['siblings', 'children', 'relatives'])
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

        return Inertia::render('Masterlist/Index', [
            'beneficiaries' => $beneficiaries,
            'filters' => ['search' => $search ?? ''],
        ]);
    }

    public function show(Beneficiary $beneficiary): Response
    {
        $beneficiary->load(['siblings', 'children', 'relatives']);

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

        return Inertia::render('Masterlist/Show', [
            'beneficiary' => $beneficiary,
            'locations' => $locations,
        ]);
    }

    public function update(UpdateBeneficiaryRequest $request, Beneficiary $beneficiary): RedirectResponse
    {
        $data = $request->validated();

        $siblings = $data['siblings'] ?? [];
        $children = $data['children'] ?? [];
        $relatives = $data['relatives'] ?? [];

        unset($data['siblings'], $data['children'], $data['relatives']);
        unset($data['siblings_count'], $data['children_count'], $data['relatives_count']);

        DB::transaction(function () use ($beneficiary, $data, $siblings, $children, $relatives) {
            $beneficiary->update($data);

            $beneficiary->siblings()->delete();
            if (! empty($siblings)) {
                $beneficiary->siblings()->createMany($siblings);
            }

            $beneficiary->children()->delete();
            if (! empty($children)) {
                $beneficiary->children()->createMany($children);
            }

            $beneficiary->relatives()->delete();
            if (! empty($relatives)) {
                $beneficiary->relatives()->createMany($relatives);
            }
        });

        return to_route('masterlist.show', $beneficiary);
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $search = $request->query('search');

        $beneficiaries = Beneficiary::query()
            ->with(['siblings', 'children', 'relatives'])
            ->when($search, function ($query, $search) {
                $escaped = str_replace(['%', '_'], ['\\%', '\\_'], $search);
                $query->where(function ($q) use ($escaped) {
                    $q->where('last_name', 'like', "%{$escaped}%")
                        ->orWhere('first_name', 'like', "%{$escaped}%")
                        ->orWhere('middle_name', 'like', "%{$escaped}%");
                });
            })
            ->latest()
            ->get();

        $headers = [
            'Last Name', 'First Name', 'Middle Name', 'Extension Name',
            'Sex', 'Birth Date', 'Civil Status',
            'Province', 'Municipality', 'Barangay', 'Purok',
            'Damage Classification', 'NHTS-PR Classification', 'Applicable Sectors',
            'Living w/ Father', 'Father Name', 'Father Birth Date',
            'Living w/ Mother', 'Mother Name', 'Mother Birth Date',
            'Living w/ Spouse', 'Spouse Name', 'Spouse Birth Date',
            'Siblings Count', 'Children Count', 'Relatives Count',
            'Date Added',
        ];

        return response()->streamDownload(function () use ($beneficiaries, $headers) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $headers);

            foreach ($beneficiaries as $b) {
                fputcsv($handle, [
                    $b->last_name,
                    $b->first_name,
                    $b->middle_name,
                    $b->extension_name,
                    $b->sex,
                    $b->birth_date?->format('Y-m-d'),
                    $b->civil_status,
                    $b->province,
                    $b->municipality,
                    $b->barangay,
                    $b->purok,
                    $b->classify_extent_of_damaged_house,
                    $b->nhts_pr_classification,
                    is_array($b->applicable_sector) ? implode(', ', $b->applicable_sector) : $b->applicable_sector,
                    $b->living_with_father ? 'Yes' : 'No',
                    $b->living_with_father ? trim("{$b->father_last_name}, {$b->father_first_name} {$b->father_middle_name}") : '',
                    $b->living_with_father ? $b->father_birth_date?->format('Y-m-d') : '',
                    $b->living_with_mother ? 'Yes' : 'No',
                    $b->living_with_mother ? trim("{$b->mother_last_name}, {$b->mother_first_name} {$b->mother_middle_name}") : '',
                    $b->living_with_mother ? $b->mother_birth_date?->format('Y-m-d') : '',
                    $b->living_with_spouse ? 'Yes' : 'No',
                    $b->living_with_spouse ? trim("{$b->spouse_last_name}, {$b->spouse_first_name} {$b->spouse_middle_name}") : '',
                    $b->living_with_spouse ? $b->spouse_birth_date?->format('Y-m-d') : '',
                    $b->siblings->count(),
                    $b->children->count(),
                    $b->relatives->count(),
                    $b->created_at?->format('Y-m-d'),
                ]);
            }

            fclose($handle);
        }, 'masterlist-'.now()->format('Y-m-d').'.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }
}
