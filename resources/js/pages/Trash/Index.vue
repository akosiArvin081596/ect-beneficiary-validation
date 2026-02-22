<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';
import { RotateCcw, Trash2 } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/AppLayout.vue';
import { index, restore, destroy } from '@/routes/trash';
import { type BreadcrumbItem } from '@/types';

interface Beneficiary {
    id: number;
    first_name: string;
    last_name: string;
    middle_name: string | null;
    extension_name: string | null;
    municipality: string;
    barangay: string;
    deleted_at: string;
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

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Trash', href: index().url }];

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

// ── Permanent delete confirmation ───────────────────────────────────────────
const showDeleteDialog = ref(false);
const deleteTarget = ref<Beneficiary | null>(null);
const processing = ref(false);

function openDeleteDialog(b: Beneficiary) {
    deleteTarget.value = b;
    showDeleteDialog.value = true;
}

function confirmDelete() {
    if (!deleteTarget.value) return;
    processing.value = true;
    router.delete(destroy(deleteTarget.value.id).url, {
        onFinish: () => {
            processing.value = false;
            showDeleteDialog.value = false;
            deleteTarget.value = null;
        },
    });
}

function restoreBeneficiary(b: Beneficiary) {
    router.post(restore(b.id).url);
}
</script>

<template>
    <Head title="Trash" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-4 p-4">
            <div class="flex items-center gap-3">
                <h1 class="text-xl font-semibold">Trash</h1>
                <Badge variant="secondary">
                    {{ beneficiaries.total }} records
                </Badge>
            </div>

            <!-- Search -->
            <Input
                v-model="search"
                type="text"
                placeholder="Search by last name, first name, or middle name..."
                class="max-w-sm"
            />

            <div class="overflow-x-auto rounded-xl border">
                <table class="w-full text-sm whitespace-nowrap">
                    <thead>
                        <tr class="border-b bg-muted/50">
                            <th class="px-4 py-3 text-left font-medium">
                                Full Name
                            </th>
                            <th class="px-4 py-3 text-left font-medium">
                                Municipality
                            </th>
                            <th class="px-4 py-3 text-left font-medium">
                                Barangay
                            </th>
                            <th class="px-4 py-3 text-left font-medium">
                                Date Deleted
                            </th>
                            <th class="px-4 py-3 text-right font-medium">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="beneficiaries.data.length === 0">
                            <td
                                colspan="5"
                                class="px-4 py-8 text-center text-muted-foreground"
                            >
                                <template v-if="search">
                                    No deleted beneficiaries found matching "{{
                                        search
                                    }}".
                                </template>
                                <template v-else> Trash is empty. </template>
                            </td>
                        </tr>
                        <tr
                            v-for="b in beneficiaries.data"
                            :key="b.id"
                            class="border-b last:border-0"
                        >
                            <td class="px-4 py-3 font-medium">
                                {{ fullName(b) }}
                            </td>
                            <td class="px-4 py-3">{{ b.municipality }}</td>
                            <td class="px-4 py-3">{{ b.barangay }}</td>
                            <td class="px-4 py-3">
                                {{ formatDate(b.deleted_at) }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div
                                    class="flex items-center justify-end gap-2"
                                >
                                    <Button
                                        size="sm"
                                        variant="outline"
                                        @click="restoreBeneficiary(b)"
                                    >
                                        <RotateCcw class="mr-1 h-3.5 w-3.5" />
                                        Restore
                                    </Button>
                                    <Button
                                        size="sm"
                                        variant="destructive"
                                        @click="openDeleteDialog(b)"
                                    >
                                        <Trash2 class="mr-1 h-3.5 w-3.5" />
                                        Delete Permanently
                                    </Button>
                                </div>
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

        <Dialog v-model:open="showDeleteDialog">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Permanently Delete</DialogTitle>
                    <DialogDescription>
                        Are you sure you want to permanently delete
                        <template v-if="deleteTarget"
                            >{{ deleteTarget.last_name }},
                            {{ deleteTarget.first_name }}</template
                        >? This action cannot be undone.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button variant="outline" @click="showDeleteDialog = false"
                        >Cancel</Button
                    >
                    <Button
                        variant="destructive"
                        :disabled="processing"
                        @click="confirmDelete"
                    >
                        <template v-if="processing">Deleting...</template>
                        <template v-else>Delete Permanently</template>
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
