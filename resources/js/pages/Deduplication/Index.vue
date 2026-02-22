<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { index } from '@/routes/deduplication';
import { type BreadcrumbItem } from '@/types';

interface BeneficiaryRecord {
    id: number;
    first_name: string;
    last_name: string;
    middle_name: string | null;
    birth_date: string | null;
    barangay: string;
    purok: string;
    sex: string;
    marked_as_duplicate: boolean;
    created_at: string;
}

interface DuplicateGroup {
    key: string;
    records: BeneficiaryRecord[];
}

interface Location {
    name: string;
    municipalities: string[];
}

const props = defineProps<{
    groups: DuplicateGroup[];
    locations: Location[];
    filters: { municipality: string };
    pagination: {
        current_page: number;
        last_page: number;
        total: number;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Deduplication',
        href: index().url,
    },
];

// ── Municipality filter ─────────────────────────────────────────────────────
const municipality = ref(props.filters.municipality);

const allMunicipalities = computed(() => {
    const munis: string[] = [];
    for (const loc of props.locations) {
        for (const m of loc.municipalities) {
            munis.push(m);
        }
    }
    return munis.sort();
});

watch(municipality, (val) => {
    router.get(index().url, val ? { municipality: val } : {}, {
        preserveState: true,
        replace: true,
    });
});

// ── Mark / Unmark ───────────────────────────────────────────────────────────
const processing = ref<Record<number, boolean>>({});

function markDuplicate(record: BeneficiaryRecord) {
    processing.value[record.id] = true;
    router.patch(
        `/deduplication/${record.id}/mark`,
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                processing.value[record.id] = false;
            },
        },
    );
}

function unmarkDuplicate(record: BeneficiaryRecord) {
    processing.value[record.id] = true;
    router.patch(
        `/deduplication/${record.id}/unmark`,
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                processing.value[record.id] = false;
            },
        },
    );
}

// ── Export ───────────────────────────────────────────────────────────────────
function exportCleanList() {
    const params = municipality.value
        ? `?municipality=${encodeURIComponent(municipality.value)}`
        : '';
    window.location.href = `/deduplication/export-clean-list${params}`;
}

// ── Pagination ──────────────────────────────────────────────────────────────
function goToPage(page: number) {
    const params: Record<string, string | number> = { page };
    if (municipality.value) {
        params.municipality = municipality.value;
    }
    router.get(index().url, params, { preserveState: true, replace: true });
}

// ── Diff highlighting ───────────────────────────────────────────────────────
function highlightDiff(name: string, group: DuplicateGroup): string {
    // Find all unique first_name chars across the group
    const others = group.records
        .map((r) => r.first_name)
        .filter((n) => n !== name);

    if (others.length === 0) return escapeHtml(name);

    // Compare with the first different name to find differing chars
    const reference = others[0];
    let result = '';

    for (let i = 0; i < name.length; i++) {
        const char = name[i];
        const refChar = i < reference.length ? reference[i] : null;

        if (refChar === null || char.toLowerCase() !== refChar.toLowerCase()) {
            result += `<span class="bg-yellow-200 dark:bg-yellow-800 font-bold rounded px-0.5">${escapeHtml(char)}</span>`;
        } else {
            result += escapeHtml(char);
        }
    }

    return result;
}

function escapeHtml(text: string): string {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function formatDate(dateStr: string): string {
    return new Date(dateStr).toLocaleDateString('en-PH', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
}
</script>

<template>
    <Head title="Deduplication" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-4 p-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <h1 class="text-xl font-semibold">Deduplication</h1>
                    <Badge v-if="pagination.total > 0" variant="secondary">
                        {{ pagination.total }} possible duplicate group{{
                            pagination.total === 1 ? '' : 's'
                        }}
                    </Badge>
                </div>
                <Button
                    v-if="municipality"
                    variant="outline"
                    size="sm"
                    @click="exportCleanList"
                >
                    Export Clean List
                </Button>
            </div>

            <!-- Municipality filter -->
            <select
                v-model="municipality"
                class="h-9 w-64 rounded-md border border-input bg-background px-3 py-1 text-sm text-foreground shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
            >
                <option value="">Select municipality to scan...</option>
                <option
                    v-for="muni in allMunicipalities"
                    :key="muni"
                    :value="muni"
                >
                    {{ muni }}
                </option>
            </select>

            <!-- Empty state -->
            <div
                v-if="!municipality"
                class="rounded-xl border px-4 py-12 text-center text-muted-foreground"
            >
                Select a municipality to scan for possible duplicates.
            </div>

            <div
                v-else-if="groups.length === 0"
                class="rounded-xl border px-4 py-12 text-center text-muted-foreground"
            >
                No possible duplicates found in {{ municipality }}.
            </div>

            <!-- Duplicate groups -->
            <Card v-for="group in groups" :key="group.key">
                <CardHeader
                    class="flex flex-row items-center justify-between pb-3"
                >
                    <CardTitle class="text-base font-semibold">
                        {{ group.key }}
                        <Badge variant="outline" class="ml-2">
                            {{ group.records.length }} records
                        </Badge>
                    </CardTitle>
                </CardHeader>
                <CardContent class="p-0">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-t bg-muted/50">
                                    <th class="px-4 py-2 text-left font-medium">
                                        First Name
                                    </th>
                                    <th class="px-4 py-2 text-left font-medium">
                                        Last Name
                                    </th>
                                    <th class="px-4 py-2 text-left font-medium">
                                        Middle Name
                                    </th>
                                    <th class="px-4 py-2 text-left font-medium">
                                        Birth Date
                                    </th>
                                    <th class="px-4 py-2 text-left font-medium">
                                        Barangay
                                    </th>
                                    <th class="px-4 py-2 text-left font-medium">
                                        Purok
                                    </th>
                                    <th class="px-4 py-2 text-left font-medium">
                                        Sex
                                    </th>
                                    <th class="px-4 py-2 text-left font-medium">
                                        Status
                                    </th>
                                    <th class="px-4 py-2 text-left font-medium">
                                        Date Added
                                    </th>
                                    <th
                                        class="px-4 py-2 text-right font-medium"
                                    ></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="record in group.records"
                                    :key="record.id"
                                    class="border-t transition-colors"
                                    :class="
                                        record.marked_as_duplicate
                                            ? 'bg-red-50 opacity-60 dark:bg-red-950/30'
                                            : 'hover:bg-muted/30'
                                    "
                                >
                                    <td class="px-4 py-2">
                                        <span
                                            v-html="
                                                highlightDiff(
                                                    record.first_name,
                                                    group,
                                                )
                                            "
                                        />
                                    </td>
                                    <td class="px-4 py-2">
                                        {{ record.last_name }}
                                    </td>
                                    <td class="px-4 py-2">
                                        {{ record.middle_name || '—' }}
                                    </td>
                                    <td class="px-4 py-2">
                                        {{ record.birth_date || '—' }}
                                    </td>
                                    <td class="px-4 py-2">
                                        {{ record.barangay }}
                                    </td>
                                    <td class="px-4 py-2">
                                        {{ record.purok }}
                                    </td>
                                    <td class="px-4 py-2">{{ record.sex }}</td>
                                    <td class="px-4 py-2">
                                        <Badge
                                            v-if="record.marked_as_duplicate"
                                            variant="destructive"
                                        >
                                            Duplicate
                                        </Badge>
                                    </td>
                                    <td class="px-4 py-2">
                                        {{ formatDate(record.created_at) }}
                                    </td>
                                    <td class="px-4 py-2 text-right">
                                        <Button
                                            v-if="record.marked_as_duplicate"
                                            size="sm"
                                            variant="outline"
                                            :disabled="processing[record.id]"
                                            @click="unmarkDuplicate(record)"
                                        >
                                            Unmark
                                        </Button>
                                        <Button
                                            v-else
                                            size="sm"
                                            variant="destructive"
                                            :disabled="processing[record.id]"
                                            @click="markDuplicate(record)"
                                        >
                                            Mark Duplicate
                                        </Button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </CardContent>
            </Card>

            <!-- Pagination -->
            <div v-if="pagination.last_page > 1" class="flex flex-wrap gap-1">
                <button
                    v-for="page in pagination.last_page"
                    :key="page"
                    class="rounded border px-3 py-1 text-sm transition-colors"
                    :class="
                        pagination.current_page === page
                            ? 'border-primary bg-primary text-primary-foreground'
                            : 'hover:bg-muted'
                    "
                    @click="goToPage(page)"
                >
                    {{ page }}
                </button>
            </div>
        </div>
    </AppLayout>
</template>
