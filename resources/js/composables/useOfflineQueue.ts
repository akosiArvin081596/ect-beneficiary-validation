import { useOnline } from '@vueuse/core';
import { computed, ref } from 'vue';

export interface OfflineQueueEntry {
    id: string;
    queuedAt: string;
    status: 'pending' | 'failed';
    failureReason?: string;
    data: Record<string, unknown>;
}

const STORAGE_KEY = 'beneficiary_offline_queue';
const SYNC_URL = '/beneficiaries/offline-sync';

// ── module-level state ──────────────────────────────────────────────────────
const queue = ref<OfflineQueueEntry[]>(load());
const isSyncing = ref(false);
const lastSyncMessage = ref<{
    text: string;
    type: 'success' | 'error' | 'info';
} | null>(null);

export const isOnline = useOnline();

// ── helpers ─────────────────────────────────────────────────────────────────
function load(): OfflineQueueEntry[] {
    try {
        return JSON.parse(localStorage.getItem(STORAGE_KEY) ?? '[]');
    } catch {
        return [];
    }
}

function persist() {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(queue.value));
}

function updateEntry(id: string, patch: Partial<OfflineQueueEntry>) {
    queue.value = queue.value.map((e) =>
        e.id === id ? { ...e, ...patch } : e,
    );
    persist();
}

function firstError(body: { errors?: Record<string, string[]> }): string {
    const key = Object.keys(body?.errors ?? {})[0];
    return key
        ? (body.errors![key][0] ?? 'Validation error')
        : 'Validation error';
}

// ── syncAll at module level so the 'online' listener can reach it ────────────
async function syncAll(): Promise<void> {
    if (isSyncing.value) return;
    const pending = queue.value.filter((e) => e.status === 'pending');
    if (!pending.length) return;

    isSyncing.value = true;
    const csrf =
        document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')
            ?.content ?? '';
    let synced = 0;

    for (const entry of pending) {
        try {
            const res = await fetch(SYNC_URL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrf,
                },
                body: JSON.stringify(entry.data),
            });

            if (res.ok) {
                queue.value = queue.value.filter((e) => e.id !== entry.id);
                persist();
                synced++;
            } else if (res.status === 422) {
                const body = await res.json();
                updateEntry(entry.id, {
                    status: 'failed',
                    failureReason: firstError(body),
                });
            } else if (res.status === 401 || res.status === 419) {
                updateEntry(entry.id, {
                    status: 'failed',
                    failureReason:
                        'Session expired — refresh the page and retry.',
                });
                break;
            }
            // 5xx: leave pending, retry next time
        } catch {
            // network error — leave pending
        }
    }

    if (synced > 0) {
        lastSyncMessage.value = {
            text: `${synced} record(s) synced successfully.`,
            type: 'success',
        };
    }
    isSyncing.value = false;
}

// register 'online' listener once — syncAll is now in scope
window.addEventListener('online', () => syncAll());

// ── composable ──────────────────────────────────────────────────────────────
export function useOfflineQueue() {
    const pendingCount = computed(
        () => queue.value.filter((e) => e.status === 'pending').length,
    );
    const failedCount = computed(
        () => queue.value.filter((e) => e.status === 'failed').length,
    );

    function enqueue(data: Record<string, unknown>): void {
        queue.value = [
            ...queue.value,
            {
                id: crypto.randomUUID(),
                queuedAt: new Date().toISOString(),
                status: 'pending',
                data,
            },
        ];
        persist();
        lastSyncMessage.value = {
            text: 'Saved offline. Will sync when connected.',
            type: 'info',
        };
    }

    function retryFailed(): void {
        queue.value = queue.value.map((e) =>
            e.status === 'failed'
                ? { ...e, status: 'pending', failureReason: undefined }
                : e,
        );
        persist();
        syncAll();
    }

    return {
        queue,
        pendingCount,
        failedCount,
        isSyncing,
        lastSyncMessage,
        enqueue,
        syncAll,
        retryFailed,
    };
}
