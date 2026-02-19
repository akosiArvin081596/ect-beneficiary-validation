<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';
import { ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useOfflineQueue } from '@/composables/useOfflineQueue';
import AppLayout from '@/layouts/AppLayout.vue';
import { index, create } from '@/routes/beneficiaries';
import { type BreadcrumbItem } from '@/types';

interface Beneficiary {
    id: number;
    first_name: string;
    last_name: string;
    middle_name: string | null;
    municipality: string;
    barangay: string;
    civil_status: string;
    created_at: string;
}

interface PaginatedBeneficiaries {
    data: Beneficiary[];
    links: { url: string | null; label: string; active: boolean }[];
    meta?: {
        current_page: number;
        last_page: number;
        total: number;
    };
}

const props = defineProps<{
    beneficiaries: PaginatedBeneficiaries;
    filters: { search: string };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Beneficiaries',
        href: index().url,
    },
];

function fullName(b: Beneficiary): string {
    const parts = [b.last_name, b.first_name];
    if (b.middle_name) parts.push(b.middle_name.charAt(0) + '.');
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

// ── Offline queue ────────────────────────────────────────────────────────────
const {
    queue,
    pendingCount,
    failedCount,
    isSyncing,
    lastSyncMessage,
    syncAll,
    retryFailed,
    removeEntry,
    retrySingle,
} = useOfflineQueue();

const queueExpanded = ref(false);

// Reload list after sync completes
watch(isSyncing, (syncing, was) => {
    if (was && !syncing) router.reload({ only: ['beneficiaries'] });
});

// ── Toast ────────────────────────────────────────────────────────────────────
const toastVisible = ref(false);
const toastMessage = ref('');
const toastType = ref<'success' | 'error' | 'info'>('info');
watch(lastSyncMessage, (msg) => {
    if (!msg) return;
    toastMessage.value = msg.text;
    toastType.value = msg.type;
    toastVisible.value = true;
    setTimeout(() => {
        toastVisible.value = false;
    }, 5000);
});
</script>

<template>
    <Head title="Beneficiaries" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-4 p-4">
            <div class="flex items-center justify-between">
                <h1 class="text-xl font-semibold">Beneficiaries</h1>
                <Button as-child>
                    <Link :href="create().url">Add Beneficiary</Link>
                </Button>
            </div>

            <!-- Search -->
            <Input
                v-model="search"
                type="text"
                placeholder="Search by name, municipality, or barangay..."
                class="max-w-sm"
            />

            <!-- Offline queue status -->
            <div
                v-if="pendingCount > 0 || failedCount > 0"
                class="rounded-lg border text-sm"
                :class="
                    failedCount > 0
                        ? 'border-red-200 bg-red-50 text-red-800 dark:border-red-800 dark:bg-red-950 dark:text-red-200'
                        : 'border-blue-200 bg-blue-50 text-blue-800 dark:border-blue-800 dark:bg-blue-950 dark:text-blue-200'
                "
            >
                <!-- Summary row -->
                <div class="flex items-center justify-between px-4 py-3">
                    <button
                        type="button"
                        class="flex items-center gap-2 font-medium"
                        @click="queueExpanded = !queueExpanded"
                    >
                        <svg
                            class="h-4 w-4 transition-transform"
                            :class="{ 'rotate-90': queueExpanded }"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                        </svg>
                        <span>
                            <span v-if="pendingCount > 0">{{ pendingCount }} record(s) pending sync.</span>
                            <span v-if="failedCount > 0" class="ml-2 text-red-700 dark:text-red-300">{{ failedCount }} failed.</span>
                        </span>
                    </button>
                    <div class="flex gap-2">
                        <Button
                            v-if="failedCount > 0"
                            size="sm"
                            variant="outline"
                            @click="retryFailed"
                            :disabled="isSyncing"
                        >Retry Failed</Button>
                        <Button
                            size="sm"
                            variant="outline"
                            @click="syncAll"
                            :disabled="isSyncing || pendingCount === 0"
                        >
                            {{ isSyncing ? 'Syncing...' : 'Sync Now' }}
                        </Button>
                    </div>
                </div>

                <!-- Expanded queue list -->
                <div v-if="queueExpanded" class="border-t px-4 py-2">
                    <table class="w-full text-xs">
                        <thead>
                            <tr class="border-b">
                                <th class="px-2 py-2 text-left font-medium">Name</th>
                                <th class="px-2 py-2 text-left font-medium">Location</th>
                                <th class="px-2 py-2 text-left font-medium">Queued</th>
                                <th class="px-2 py-2 text-left font-medium">Status</th>
                                <th class="px-2 py-2 text-right font-medium">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="entry in queue"
                                :key="entry.id"
                                class="border-b last:border-0"
                            >
                                <td class="px-2 py-2">
                                    {{ (entry.data.last_name as string) || '—' }},
                                    {{ (entry.data.first_name as string) || '' }}
                                </td>
                                <td class="px-2 py-2">
                                    {{ (entry.data.municipality as string) || '—' }} /
                                    {{ (entry.data.barangay as string) || '—' }}
                                </td>
                                <td class="px-2 py-2">
                                    {{ new Date(entry.queuedAt).toLocaleString('en-PH', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' }) }}
                                </td>
                                <td class="px-2 py-2">
                                    <span
                                        v-if="entry.status === 'pending'"
                                        class="inline-flex items-center rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-700 dark:bg-blue-900 dark:text-blue-300"
                                    >Pending</span>
                                    <span
                                        v-else
                                        class="inline-flex items-center rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-700 dark:bg-red-900 dark:text-red-300"
                                        :title="entry.failureReason"
                                    >Failed</span>
                                </td>
                                <td class="px-2 py-2 text-right">
                                    <div class="flex justify-end gap-1">
                                        <Button
                                            v-if="entry.status === 'failed'"
                                            size="sm"
                                            variant="ghost"
                                            class="h-7 px-2 text-xs"
                                            @click="retrySingle(entry.id)"
                                            :disabled="isSyncing"
                                        >Retry</Button>
                                        <Button
                                            size="sm"
                                            variant="ghost"
                                            class="h-7 px-2 text-xs text-red-600 hover:text-red-700 dark:text-red-400"
                                            @click="removeEntry(entry.id)"
                                        >Remove</Button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="overflow-hidden rounded-xl border">
                <table class="w-full text-sm">
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
                                Civil Status
                            </th>
                            <th class="px-4 py-3 text-left font-medium">
                                Date Added
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
                                    No beneficiaries found matching "{{
                                        search
                                    }}".
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
                            <td class="px-4 py-3 font-medium">
                                {{ fullName(b) }}
                            </td>
                            <td class="px-4 py-3">{{ b.municipality }}</td>
                            <td class="px-4 py-3">{{ b.barangay }}</td>
                            <td class="px-4 py-3">{{ b.civil_status }}</td>
                            <td class="px-4 py-3">
                                {{ formatDate(b.created_at) }}
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

        <!-- Toast -->
        <Transition
            enter-active-class="transition ease-out duration-300"
            enter-from-class="translate-y-2 opacity-0"
            enter-to-class="translate-y-0 opacity-100"
            leave-active-class="transition ease-in duration-200"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="toastVisible"
                class="fixed right-6 bottom-6 z-50 max-w-sm rounded-lg border px-4 py-3 text-sm shadow-lg"
                :class="{
                    'border-green-200 bg-green-50 text-green-800 dark:border-green-800 dark:bg-green-950 dark:text-green-200':
                        toastType === 'success',
                    'border-red-200 bg-red-50 text-red-800 dark:border-red-800 dark:bg-red-950 dark:text-red-200':
                        toastType === 'error',
                    'border-blue-200 bg-blue-50 text-blue-800 dark:border-blue-800 dark:bg-blue-950 dark:text-blue-200':
                        toastType === 'info',
                }"
            >
                {{ toastMessage }}
            </div>
        </Transition>
    </AppLayout>
</template>
