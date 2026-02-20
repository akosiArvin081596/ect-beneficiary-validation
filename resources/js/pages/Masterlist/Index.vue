<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';
import { Download } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/AppLayout.vue';
import { index, show, exportCsv } from '@/routes/masterlist';
import { type BreadcrumbItem } from '@/types';

interface Beneficiary {
    id: number;
    first_name: string;
    last_name: string;
    middle_name: string | null;
    extension_name: string | null;
    sex: string;
    birth_date: string;
    civil_status: string;
    province: string;
    municipality: string;
    barangay: string;
    purok: string | null;
    classify_extent_of_damaged_house: string;
    nhts_pr_classification: string | null;
    applicable_sector: string[] | null;
    siblings_count: number;
    children_count: number;
    relatives_count: number;
    created_at: string;
}

interface PaginatedBeneficiaries {
    data: Beneficiary[];
    links: { url: string | null; label: string; active: boolean }[];
    total: number;
}

const props = defineProps<{
    beneficiaries: PaginatedBeneficiaries;
    filters: { search: string };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Masterlist', href: index().url },
];

function fullName(b: Beneficiary): string {
    const parts = [b.last_name, b.first_name];
    if (b.middle_name) parts.push(b.middle_name.charAt(0) + '.');
    if (b.extension_name) parts.push(b.extension_name);
    return parts.join(', ');
}

function formatDate(dateStr: string): string {
    return new Date(dateStr).toLocaleDateString('en-PH', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
}

function exportUrl(): string {
    const url = exportCsv().url;
    if (search.value) {
        return `${url}?search=${encodeURIComponent(search.value)}`;
    }
    return url;
}

// ── Search ──────────────────────────────────────────────────────────────────
const search = ref(props.filters.search);

const performSearch = useDebounceFn(() => {
    router.get(index().url, search.value ? { search: search.value } : {}, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}, 300);

watch(search, () => {
    performSearch();
});
</script>

<template>
    <Head title="Masterlist" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-4 p-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <h1 class="text-xl font-semibold">Masterlist</h1>
                    <Badge variant="secondary">
                        {{ beneficiaries.total }} records
                    </Badge>
                </div>
                <Button as-child variant="outline">
                    <a :href="exportUrl()">
                        <Download class="mr-2 h-4 w-4" />
                        Export CSV
                    </a>
                </Button>
            </div>

            <!-- Search -->
            <Input
                v-model="search"
                type="text"
                placeholder="Search by name, municipality, or barangay..."
                class="max-w-sm"
            />

            <div class="overflow-x-auto rounded-xl border">
                <table class="w-full text-sm whitespace-nowrap">
                    <thead>
                        <tr class="border-b bg-muted/50">
                            <th class="px-4 py-3 text-left font-medium">Full Name</th>
                            <th class="px-4 py-3 text-left font-medium">Sex</th>
                            <th class="px-4 py-3 text-left font-medium">Birth Date</th>
                            <th class="px-4 py-3 text-left font-medium">Civil Status</th>
                            <th class="px-4 py-3 text-left font-medium">Province</th>
                            <th class="px-4 py-3 text-left font-medium">Municipality</th>
                            <th class="px-4 py-3 text-left font-medium">Barangay</th>
                            <th class="px-4 py-3 text-left font-medium">Purok</th>
                            <th class="px-4 py-3 text-left font-medium">Damage Class.</th>
                            <th class="px-4 py-3 text-left font-medium">NHTS-PR</th>
                            <th class="px-4 py-3 text-left font-medium">Sectors</th>
                            <th class="px-4 py-3 text-center font-medium">Siblings</th>
                            <th class="px-4 py-3 text-center font-medium">Children</th>
                            <th class="px-4 py-3 text-center font-medium">Relatives</th>
                            <th class="px-4 py-3 text-left font-medium">Date Added</th>
                            <th class="px-4 py-3 text-left font-medium"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="beneficiaries.data.length === 0">
                            <td
                                colspan="16"
                                class="px-4 py-8 text-center text-muted-foreground"
                            >
                                <template v-if="search">
                                    No beneficiaries found matching "{{ search }}".
                                </template>
                                <template v-else>
                                    No beneficiaries recorded yet.
                                </template>
                            </td>
                        </tr>
                        <tr
                            v-for="b in beneficiaries.data"
                            :key="b.id"
                            class="border-b last:border-0 hover:bg-muted/30"
                        >
                            <td class="px-4 py-3 font-medium">{{ fullName(b) }}</td>
                            <td class="px-4 py-3">{{ b.sex }}</td>
                            <td class="px-4 py-3">{{ formatDate(b.birth_date) }}</td>
                            <td class="px-4 py-3">{{ b.civil_status }}</td>
                            <td class="px-4 py-3">{{ b.province }}</td>
                            <td class="px-4 py-3">{{ b.municipality }}</td>
                            <td class="px-4 py-3">{{ b.barangay }}</td>
                            <td class="px-4 py-3">{{ b.purok || '—' }}</td>
                            <td class="px-4 py-3">{{ b.classify_extent_of_damaged_house }}</td>
                            <td class="px-4 py-3">{{ b.nhts_pr_classification || '—' }}</td>
                            <td class="px-4 py-3">
                                {{ b.applicable_sector?.join(', ') || '—' }}
                            </td>
                            <td class="px-4 py-3 text-center">{{ b.siblings_count }}</td>
                            <td class="px-4 py-3 text-center">{{ b.children_count }}</td>
                            <td class="px-4 py-3 text-center">{{ b.relatives_count }}</td>
                            <td class="px-4 py-3">{{ formatDate(b.created_at) }}</td>
                            <td class="px-4 py-3">
                                <Button as-child size="sm" variant="ghost">
                                    <Link :href="show(b.id).url">View</Link>
                                </Button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div
                v-if="beneficiaries.links.length > 3"
                class="flex flex-wrap gap-1"
            >
                <template v-for="link in beneficiaries.links" :key="link.label">
                    <Link
                        v-if="link.url"
                        :href="link.url"
                        class="rounded border px-3 py-1 text-sm transition-colors"
                        :class="
                            link.active
                                ? 'border-primary bg-primary text-primary-foreground'
                                : 'hover:bg-muted'
                        "
                    ><span v-html="link.label" /></Link>
                    <span
                        v-else
                        class="rounded border px-3 py-1 text-sm text-muted-foreground opacity-50"
                        v-html="link.label"
                    />
                </template>
            </div>
        </div>
    </AppLayout>
</template>
