import { computed, ref } from 'vue';

const DRAFT_KEY = 'beneficiary_draft';

const _draft = ref<Record<string, unknown> | null>(loadFromStorage());

function loadFromStorage() {
    try {
        const r = localStorage.getItem(DRAFT_KEY);
        return r ? JSON.parse(r) : null;
    } catch {
        return null;
    }
}

export function useDraft() {
    const hasDraft = computed(() => _draft.value !== null);

    function saveDraft(data: Record<string, unknown>) {
        _draft.value = { ...data };
        try {
            localStorage.setItem(DRAFT_KEY, JSON.stringify(_draft.value));
        } catch {
            // Storage full — silently fail; draft stays in memory only
        }
    }

    function clearDraft() {
        _draft.value = null;
        localStorage.removeItem(DRAFT_KEY);
    }

    function restoreDraft() {
        return _draft.value;
    }

    return { hasDraft, saveDraft, clearDraft, restoreDraft };
}
