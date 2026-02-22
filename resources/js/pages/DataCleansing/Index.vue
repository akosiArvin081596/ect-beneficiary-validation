<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';
import { ref, watch } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/AppLayout.vue';
import { index } from '@/routes/data-cleansing';
import { type BreadcrumbItem } from '@/types';

interface Sibling {
    id: number;
    name: string;
    birth_date: string;
}

interface Child {
    id: number;
    name: string;
    birth_date: string;
}

interface Relative {
    id: number;
    name: string;
    birth_date: string;
    relationship: string;
}

interface BeneficiaryRecord {
    id: number;
    first_name: string;
    last_name: string;
    middle_name: string | null;
    municipality: string;
    barangay: string;
    civil_status: string;
    classify_extent_of_damaged_house: string | null;
    created_at: string;
    siblings: Sibling[];
    children: Child[];
    relatives: Relative[];
}

interface DuplicateGroup {
    key: string;
    records: BeneficiaryRecord[];
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

const props = defineProps<{
    groups: DuplicateGroup[];
    pagination: {
        data: unknown[];
        links: PaginationLink[];
        total: number;
    };
    filters: { search: string };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Data Cleansing',
        href: index().url,
    },
];

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

// ── Selected records for merge ──────────────────────────────────────────────
const selectedKeep = ref<Record<string, number>>({});

function selectKeep(groupKey: string, recordId: number) {
    selectedKeep.value[groupKey] = recordId;
}

// ── Delete ──────────────────────────────────────────────────────────────────
const deleteDialogOpen = ref(false);
const deleteTarget = ref<BeneficiaryRecord | null>(null);

function confirmDelete(record: BeneficiaryRecord) {
    deleteTarget.value = record;
    deleteDialogOpen.value = true;
}

function executeDelete() {
    if (!deleteTarget.value) return;
    router.delete(`/data-cleansing/${deleteTarget.value.id}`, {
        preserveScroll: true,
        onFinish: () => {
            deleteDialogOpen.value = false;
            deleteTarget.value = null;
        },
    });
}

// ── Merge ───────────────────────────────────────────────────────────────────
const mergeDialogOpen = ref(false);
const mergeGroup = ref<DuplicateGroup | null>(null);

function confirmMerge(group: DuplicateGroup) {
    mergeGroup.value = group;
    mergeDialogOpen.value = true;
}

function executeMerge() {
    if (!mergeGroup.value) return;
    const groupKey = mergeGroup.value.key;
    const keepId = selectedKeep.value[groupKey];
    if (!keepId) return;

    const removeIds = mergeGroup.value.records
        .filter((r) => r.id !== keepId)
        .map((r) => r.id);

    router.post(
        '/data-cleansing/merge',
        { keep_id: keepId, remove_ids: removeIds },
        {
            preserveScroll: true,
            onFinish: () => {
                mergeDialogOpen.value = false;
                mergeGroup.value = null;
            },
        },
    );
}

// ── Merge All ──────────────────────────────────────────────────────────────
const mergeAllDialogOpen = ref(false);
const isMergingAll = ref(false);

function executeMergeAll() {
    isMergingAll.value = true;
    router.post(
        '/data-cleansing/merge-all',
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                mergeAllDialogOpen.value = false;
                isMergingAll.value = false;
            },
        },
    );
}

function relationsCount(r: BeneficiaryRecord): number {
    return r.siblings.length + r.children.length + r.relatives.length;
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
    <Head title="Data Cleansing" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-4 p-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <h1 class="text-xl font-semibold">Data Cleansing</h1>
                    <Badge v-if="pagination.total > 0" variant="secondary">
                        {{ pagination.total }} duplicate group{{
                            pagination.total === 1 ? '' : 's'
                        }}
                    </Badge>
                </div>
                <Button
                    v-if="groups.length > 0"
                    variant="destructive"
                    size="sm"
                    @click="mergeAllDialogOpen = true"
                >
                    Merge All
                </Button>
            </div>

            <!-- Search -->
            <Input
                v-model="search"
                type="text"
                placeholder="Search by name..."
                class="max-w-sm"
            />

            <!-- Empty state -->
            <div
                v-if="groups.length === 0"
                class="rounded-xl border px-4 py-12 text-center text-muted-foreground"
            >
                <template v-if="search">
                    No duplicate groups found matching "{{ search }}".
                </template>
                <template v-else>
                    No duplicate records found. All data is clean.
                </template>
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
                    <Button
                        size="sm"
                        :disabled="!selectedKeep[group.key]"
                        @click="confirmMerge(group)"
                    >
                        Merge
                    </Button>
                </CardHeader>
                <CardContent class="p-0">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-t bg-muted/50">
                                    <th class="px-4 py-2 text-left font-medium">
                                        Keep
                                    </th>
                                    <th class="px-4 py-2 text-left font-medium">
                                        Municipality
                                    </th>
                                    <th class="px-4 py-2 text-left font-medium">
                                        Barangay
                                    </th>
                                    <th class="px-4 py-2 text-left font-medium">
                                        Civil Status
                                    </th>
                                    <th class="px-4 py-2 text-left font-medium">
                                        Damage
                                    </th>
                                    <th class="px-4 py-2 text-left font-medium">
                                        Relations
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
                                        selectedKeep[group.key] === record.id
                                            ? 'bg-green-50 dark:bg-green-950/30'
                                            : 'hover:bg-muted/30'
                                    "
                                >
                                    <td class="px-4 py-2">
                                        <input
                                            type="radio"
                                            :name="`keep-${group.key}`"
                                            :value="record.id"
                                            :checked="
                                                selectedKeep[group.key] ===
                                                record.id
                                            "
                                            class="accent-green-600"
                                            @change="
                                                selectKeep(group.key, record.id)
                                            "
                                        />
                                    </td>
                                    <td class="px-4 py-2">
                                        {{ record.municipality }}
                                    </td>
                                    <td class="px-4 py-2">
                                        {{ record.barangay }}
                                    </td>
                                    <td class="px-4 py-2">
                                        {{ record.civil_status }}
                                    </td>
                                    <td class="px-4 py-2">
                                        {{
                                            record.classify_extent_of_damaged_house ||
                                            '—'
                                        }}
                                    </td>
                                    <td class="px-4 py-2">
                                        {{ relationsCount(record) }}
                                    </td>
                                    <td class="px-4 py-2">
                                        {{ formatDate(record.created_at) }}
                                    </td>
                                    <td class="px-4 py-2 text-right">
                                        <Button
                                            size="sm"
                                            variant="ghost"
                                            class="text-destructive hover:text-destructive"
                                            @click="confirmDelete(record)"
                                        >
                                            Delete
                                        </Button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </CardContent>
            </Card>

            <!-- Pagination -->
            <div
                v-if="pagination.links.length > 3"
                class="flex flex-wrap gap-1"
            >
                <template v-for="link in pagination.links" :key="link.label">
                    <Link
                        v-if="link.url"
                        :href="link.url"
                        class="rounded border px-3 py-1 text-sm transition-colors"
                        :class="
                            link.active
                                ? 'border-primary bg-primary text-primary-foreground'
                                : 'hover:bg-muted'
                        "
                        ><span v-html="link.label"
                    /></Link>
                    <span
                        v-else
                        class="rounded border px-3 py-1 text-sm text-muted-foreground opacity-50"
                        v-html="link.label"
                    />
                </template>
            </div>
        </div>

        <!-- Delete confirmation dialog -->
        <Dialog v-model:open="deleteDialogOpen">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Delete Record</DialogTitle>
                    <DialogDescription>
                        Are you sure you want to delete this beneficiary record?
                        This action cannot be undone.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <DialogClose as-child>
                        <Button variant="outline">Cancel</Button>
                    </DialogClose>
                    <Button variant="destructive" @click="executeDelete">
                        Delete
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Merge confirmation dialog -->
        <Dialog v-model:open="mergeDialogOpen">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Merge Records</DialogTitle>
                    <DialogDescription>
                        This will keep the selected record and transfer all
                        family relations from the other record(s) to it. The
                        duplicate record(s) will be deleted. This cannot be
                        undone.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <DialogClose as-child>
                        <Button variant="outline">Cancel</Button>
                    </DialogClose>
                    <Button @click="executeMerge">Merge</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Merge All confirmation dialog -->
        <Dialog v-model:open="mergeAllDialogOpen">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Merge All Duplicates</DialogTitle>
                    <DialogDescription>
                        This will merge all {{ pagination.total }} duplicate
                        group{{ pagination.total === 1 ? '' : 's' }}. For each
                        group, the oldest record will be kept and all family
                        relations will be transferred to it. All other
                        duplicates will be deleted. This cannot be undone.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <DialogClose as-child>
                        <Button variant="outline" :disabled="isMergingAll"
                            >Cancel</Button
                        >
                    </DialogClose>
                    <Button
                        variant="destructive"
                        :disabled="isMergingAll"
                        @click="executeMergeAll"
                    >
                        {{ isMergingAll ? 'Merging...' : 'Merge All' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
