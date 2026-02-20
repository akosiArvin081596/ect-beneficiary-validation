<?php

namespace App\Http\Controllers;

use App\Models\Beneficiary;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DataCleansingController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->query('search');

        $duplicateKeysQuery = Beneficiary::query()
            ->select('first_name', 'last_name', 'birth_date')
            ->when($search, function ($query, $search) {
                $escaped = str_replace(['%', '_'], ['\\%', '\\_'], $search);
                $query->where(function ($q) use ($escaped) {
                    $q->where('last_name', 'like', "%{$escaped}%")
                        ->orWhere('first_name', 'like', "%{$escaped}%");
                });
            })
            ->groupBy('first_name', 'last_name', 'birth_date')
            ->havingRaw('COUNT(*) > 1')
            ->orderBy('last_name')
            ->orderBy('first_name');

        $duplicateKeys = $duplicateKeysQuery->paginate(10)->withQueryString();

        $groups = [];

        if ($duplicateKeys->isNotEmpty()) {
            $conditions = $duplicateKeys->getCollection()->map(fn ($row) => [
                'first_name' => $row->first_name,
                'last_name' => $row->last_name,
                'birth_date' => $row->birth_date,
            ])->all();

            $beneficiaries = Beneficiary::query()
                ->with(['siblings', 'children', 'relatives'])
                ->where(function ($query) use ($conditions) {
                    foreach ($conditions as $condition) {
                        $query->orWhere(function ($q) use ($condition) {
                            $q->where('first_name', $condition['first_name'])
                                ->where('last_name', $condition['last_name'])
                                ->where('birth_date', $condition['birth_date']);
                        });
                    }
                })
                ->orderBy('created_at')
                ->get();

            $grouped = $beneficiaries->groupBy(fn ($b) => $b->first_name.'|'.$b->last_name.'|'.$b->birth_date);

            foreach ($duplicateKeys->getCollection() as $key) {
                $groupKey = $key->first_name.'|'.$key->last_name.'|'.$key->birth_date;
                if (isset($grouped[$groupKey])) {
                    $groups[] = [
                        'key' => $key->last_name.', '.$key->first_name.' — '.$key->birth_date,
                        'records' => $grouped[$groupKey]->values(),
                    ];
                }
            }
        }

        return Inertia::render('DataCleansing/Index', [
            'groups' => $groups,
            'pagination' => $duplicateKeys,
            'filters' => ['search' => $search ?? ''],
        ]);
    }

    public function destroy(Beneficiary $beneficiary): RedirectResponse
    {
        $beneficiary->delete();

        return back();
    }

    public function merge(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'keep_id' => ['required', 'exists:beneficiaries,id'],
            'remove_ids' => ['required', 'array', 'min:1'],
            'remove_ids.*' => ['exists:beneficiaries,id'],
        ]);

        if (in_array($validated['keep_id'], $validated['remove_ids'])) {
            return back()->withErrors(['keep_id' => 'The kept record cannot also be in the removed list.']);
        }

        $this->mergeRecords($validated['keep_id'], $validated['remove_ids']);

        return back();
    }

    public function mergeAll(): RedirectResponse
    {
        $duplicateKeys = Beneficiary::query()
            ->select('first_name', 'last_name', 'birth_date')
            ->groupBy('first_name', 'last_name', 'birth_date')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicateKeys as $key) {
            $records = Beneficiary::where('first_name', $key->first_name)
                ->where('last_name', $key->last_name)
                ->where('birth_date', $key->birth_date)
                ->orderBy('created_at')
                ->pluck('id');

            if ($records->count() < 2) {
                continue;
            }

            $keepId = $records->first();
            $removeIds = $records->slice(1)->values()->all();

            $this->mergeRecords($keepId, $removeIds);
        }

        return back();
    }

    private function mergeRecords(int $keepId, array $removeIds): void
    {
        DB::transaction(function () use ($keepId, $removeIds) {
            DB::table('beneficiary_siblings')
                ->whereIn('beneficiary_id', $removeIds)
                ->update(['beneficiary_id' => $keepId]);

            DB::table('beneficiary_children')
                ->whereIn('beneficiary_id', $removeIds)
                ->update(['beneficiary_id' => $keepId]);

            DB::table('beneficiary_relatives')
                ->whereIn('beneficiary_id', $removeIds)
                ->update(['beneficiary_id' => $keepId]);

            Beneficiary::whereIn('id', $removeIds)->delete();
        });
    }
}
