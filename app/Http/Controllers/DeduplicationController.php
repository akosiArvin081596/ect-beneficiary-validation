<?php

namespace App\Http\Controllers;

use App\Models\Beneficiary;
use App\Models\Province;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DeduplicationController extends Controller
{
    public function index(Request $request): Response
    {
        $municipality = $request->query('municipality');
        $page = max(1, (int) $request->query('page', 1));
        $perPage = 10;

        $groups = [];
        $total = 0;

        if ($municipality) {
            $beneficiaries = Beneficiary::query()
                ->where('municipality', $municipality)
                ->get();

            // Group by lowercase(last_name) + lowercase(middle_name)
            $candidates = $beneficiaries->groupBy(
                fn (Beneficiary $b) => mb_strtolower($b->last_name).'|'.mb_strtolower($b->middle_name ?? '')
            );

            $allGroups = [];

            foreach ($candidates as $key => $group) {
                if ($group->count() < 2) {
                    continue;
                }

                // Within each group, find pairs with similar first_name via levenshtein
                $members = $group->values();
                $matched = [];
                $used = [];

                for ($i = 0; $i < $members->count(); $i++) {
                    for ($j = $i + 1; $j < $members->count(); $j++) {
                        $a = $members[$i];
                        $b = $members[$j];

                        $distance = levenshtein(
                            mb_strtolower($a->first_name),
                            mb_strtolower($b->first_name)
                        );

                        $birthMatch = $a->birth_date && $b->birth_date
                            && $a->birth_date->format('Y-m-d') === $b->birth_date->format('Y-m-d');

                        $threshold = $birthMatch ? 3 : 2;

                        if ($distance > 0 && $distance <= $threshold) {
                            $matched[$a->id] = $a;
                            $matched[$b->id] = $b;
                            $used[$i] = true;
                            $used[$j] = true;
                        }
                    }
                }

                if (! empty($matched)) {
                    [$lastName, $middleName] = explode('|', $key);

                    $allGroups[] = [
                        'key' => ucfirst($lastName).($middleName ? ', '.ucfirst($middleName) : '').' — '.$municipality,
                        'records' => collect($matched)->values()->map(fn (Beneficiary $b) => [
                            'id' => $b->id,
                            'first_name' => $b->first_name,
                            'last_name' => $b->last_name,
                            'middle_name' => $b->middle_name,
                            'birth_date' => $b->birth_date?->format('Y-m-d'),
                            'barangay' => $b->barangay,
                            'purok' => $b->purok,
                            'sex' => $b->sex,
                            'marked_as_duplicate' => $b->marked_as_duplicate,
                            'created_at' => $b->created_at->toISOString(),
                        ])->all(),
                    ];
                }
            }

            $total = count($allGroups);

            // Manual pagination
            $groups = array_slice($allGroups, ($page - 1) * $perPage, $perPage);
        }

        $locations = Province::with('municipalities')
            ->orderBy('name')
            ->get()
            ->map(fn (Province $p) => [
                'name' => $p->name,
                'municipalities' => $p->municipalities->sortBy('name')->pluck('name')->values()->all(),
            ])
            ->values()
            ->all();

        return Inertia::render('Deduplication/Index', [
            'groups' => $groups,
            'locations' => $locations,
            'filters' => [
                'municipality' => $municipality ?? '',
            ],
            'pagination' => [
                'current_page' => $page,
                'last_page' => $total > 0 ? (int) ceil($total / $perPage) : 1,
                'total' => $total,
            ],
        ]);
    }

    public function markDuplicate(Beneficiary $beneficiary): JsonResponse
    {
        $beneficiary->update(['marked_as_duplicate' => true]);

        return response()->json(['success' => true]);
    }

    public function unmarkDuplicate(Beneficiary $beneficiary): JsonResponse
    {
        $beneficiary->update(['marked_as_duplicate' => false]);

        return response()->json(['success' => true]);
    }

    public function exportCleanList(Request $request): StreamedResponse
    {
        $municipality = $request->query('municipality');

        $beneficiaries = Beneficiary::query()
            ->with(['siblings', 'children', 'relatives'])
            ->where('marked_as_duplicate', false)
            ->when($municipality, fn ($query, $municipality) => $query->where('municipality', $municipality))
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
        }, 'clean-list-'.now()->format('Y-m-d').'.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }
}
