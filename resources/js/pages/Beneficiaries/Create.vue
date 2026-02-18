<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { onUnmounted, ref, watch } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useDraft } from '@/composables/useDraft';
import { isOnline, useOfflineQueue } from '@/composables/useOfflineQueue';
import AppLayout from '@/layouts/AppLayout.vue';
import { index, store } from '@/routes/beneficiaries';
import { type BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Beneficiaries', href: index().url },
    { title: 'Add Beneficiary', href: store().url },
];

interface PersonEntry {
    last_name: string;
    first_name: string;
    middle_name: string;
    birth_date: string;
}

interface RelativeEntry extends PersonEntry {
    relationship: string;
}

function makePersonEntry(): PersonEntry {
    return { last_name: '', first_name: '', middle_name: '', birth_date: '' };
}

function makeRelativeEntry(): RelativeEntry {
    return { last_name: '', first_name: '', middle_name: '', birth_date: '', relationship: '' };
}

function resizeArray<T>(arr: T[], size: number, factory: () => T): T[] {
    const result = [...arr];
    while (result.length < size) result.push(factory());
    return result.slice(0, size);
}

const APPLICABLE_SECTORS = ['Senior Citizen', 'PWD', 'Solo Parent', 'Indigenous People'];

const form = useForm({
    // Basic info
    timestamp: '',
    province: '',
    municipality: '',
    barangay: '',
    purok: '',
    last_name: '',
    first_name: '',
    middle_name: '',
    extension_name: '',
    sex: '',
    birth_date: '',
    classify_extent_of_damaged_house: '',
    nhts_pr_classification: '',
    applicable_sector: [] as string[],
    civil_status: '',

    // Father
    living_with_father: false,
    father_last_name: '',
    father_first_name: '',
    father_middle_name: '',
    father_extension_name: '',
    father_birth_date: '',

    // Mother
    living_with_mother: false,
    mother_last_name: '',
    mother_first_name: '',
    mother_middle_name: '',
    mother_birth_date: '',

    // Siblings
    living_with_siblings: false,
    siblings_count: 0,
    siblings: [] as PersonEntry[],

    // Spouse
    living_with_spouse: false,
    spouse_last_name: '',
    spouse_first_name: '',
    spouse_middle_name: '',
    spouse_extension_name: '',
    spouse_birth_date: '',

    // Children (18+)
    living_with_children: false,
    children_count: 0,
    children: [] as PersonEntry[],

    // Relatives
    living_with_relatives: false,
    relatives_count: 0,
    relatives: [] as RelativeEntry[],
});

// Clear fields when toggled off
watch(() => form.living_with_father, (val) => {
    if (!val) {
        form.father_last_name = '';
        form.father_first_name = '';
        form.father_middle_name = '';
        form.father_extension_name = '';
        form.father_birth_date = '';
    }
});

watch(() => form.living_with_mother, (val) => {
    if (!val) {
        form.mother_last_name = '';
        form.mother_first_name = '';
        form.mother_middle_name = '';
        form.mother_birth_date = '';
    }
});

watch(() => form.living_with_siblings, (val) => {
    if (!val) {
        form.siblings_count = 0;
        form.siblings = [];
    }
});

watch(() => form.siblings_count, (count) => {
    form.siblings = resizeArray(form.siblings, Number(count), makePersonEntry);
});

watch(() => form.living_with_spouse, (val) => {
    if (!val) {
        form.spouse_last_name = '';
        form.spouse_first_name = '';
        form.spouse_middle_name = '';
        form.spouse_extension_name = '';
        form.spouse_birth_date = '';
    }
});

watch(() => form.living_with_children, (val) => {
    if (!val) {
        form.children_count = 0;
        form.children = [];
    }
});

watch(() => form.children_count, (count) => {
    form.children = resizeArray(form.children, Number(count), makePersonEntry);
});

watch(() => form.living_with_relatives, (val) => {
    if (!val) {
        form.relatives_count = 0;
        form.relatives = [];
    }
});

watch(() => form.relatives_count, (count) => {
    form.relatives = resizeArray(form.relatives, Number(count), makeRelativeEntry);
});

function toggleSector(sector: string) {
    const idx = form.applicable_sector.indexOf(sector);
    if (idx === -1) {
        form.applicable_sector.push(sector);
    } else {
        form.applicable_sector.splice(idx, 1);
    }
}

function err(key: string): string | undefined {
    return (form.errors as Record<string, string>)[key];
}

const selectClass = 'border-input bg-background text-foreground h-9 w-full rounded-md border px-3 py-1 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]';
const sectionClass = 'rounded-xl border p-6 space-y-4';
const sectionTitleClass = 'text-base font-semibold';
const radioGroupClass = 'flex items-center gap-6';
const radioClass = 'flex items-center gap-2 cursor-pointer';

// ── Draft ────────────────────────────────────────────────────────────────────
const { hasDraft, saveDraft, clearDraft, restoreDraft } = useDraft();
const showDraftBanner = ref(hasDraft.value);

function handleRestoreDraft() {
    const saved = restoreDraft();
    if (saved) Object.entries(saved).forEach(([k, v]) => { if (k in form) (form as unknown as Record<string, unknown>)[k] = v; });
    showDraftBanner.value = false;
    clearDraft();
}

function handleDiscardDraft() {
    clearDraft();
    showDraftBanner.value = false;
}

// ── Auto-save draft (1.5s debounce) ─────────────────────────────────────────
let draftTimer: ReturnType<typeof setTimeout> | null = null;
let pauseDraftSave = false;

watch(
    () => form.data(),
    (data) => {
        if (pauseDraftSave) return;
        if (draftTimer) clearTimeout(draftTimer);
        draftTimer = setTimeout(() => saveDraft(data as Record<string, unknown>), 1500);
    },
    { deep: true },
);
onUnmounted(() => {
    if (draftTimer) clearTimeout(draftTimer);
});

// ── Offline queue ────────────────────────────────────────────────────────────
const { enqueue, lastSyncMessage } = useOfflineQueue();

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

// ── Offline-aware submit ─────────────────────────────────────────────────────
function submit() {
    if (!isOnline.value) {
        enqueue(form.data() as Record<string, unknown>);
        clearDraft();
        pauseDraftSave = true;
        form.reset();
        setTimeout(() => { pauseDraftSave = false; }, 2000);
        return;
    }
    const route = store();
    form.submit(route.method, route.url, { onSuccess: () => clearDraft() });
}
</script>

<template>
    <Head title="Add Beneficiary" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-4">
            <div class="flex items-center justify-between">
                <h1 class="text-xl font-semibold">Add Beneficiary</h1>
            </div>

            <form @submit.prevent="submit" class="space-y-6">

                <!-- Draft restore banner -->
                <div
                    v-if="showDraftBanner"
                    class="flex items-center justify-between rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:border-amber-800 dark:bg-amber-950 dark:text-amber-200"
                >
                    <span>You have an unsaved draft. Restore it?</span>
                    <div class="flex gap-3">
                        <button type="button" @click="handleRestoreDraft" class="font-medium underline underline-offset-2">Restore</button>
                        <button type="button" @click="handleDiscardDraft" class="opacity-60 hover:opacity-100">Discard</button>
                    </div>
                </div>

                <!-- Basic Information -->
                <div :class="sectionClass">
                    <h2 :class="sectionTitleClass">Basic Information</h2>

                    <!-- Date -->
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <div class="space-y-1">
                            <Label for="timestamp">Date of Interview</Label>
                            <Input id="timestamp" v-model="form.timestamp" type="date" />
                            <InputError :message="err('timestamp')" />
                        </div>
                    </div>

                    <!-- Location -->
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <div class="space-y-1">
                            <Label for="province">Province</Label>
                            <Input id="province" v-model="form.province" placeholder="Province" />
                            <InputError :message="err('province')" />
                        </div>
                        <div class="space-y-1">
                            <Label for="municipality">Municipality</Label>
                            <Input id="municipality" v-model="form.municipality" placeholder="Municipality" />
                            <InputError :message="err('municipality')" />
                        </div>
                        <div class="space-y-1">
                            <Label for="barangay">Barangay</Label>
                            <Input id="barangay" v-model="form.barangay" placeholder="Barangay" />
                            <InputError :message="err('barangay')" />
                        </div>
                        <div class="space-y-1">
                            <Label for="purok">Purok</Label>
                            <Input id="purok" v-model="form.purok" placeholder="Purok (optional)" />
                            <InputError :message="err('purok')" />
                        </div>
                    </div>

                    <!-- Name -->
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <div class="space-y-1">
                            <Label for="last_name">Last Name</Label>
                            <Input id="last_name" v-model="form.last_name" placeholder="Last name" />
                            <InputError :message="err('last_name')" />
                        </div>
                        <div class="space-y-1">
                            <Label for="first_name">First Name</Label>
                            <Input id="first_name" v-model="form.first_name" placeholder="First name" />
                            <InputError :message="err('first_name')" />
                        </div>
                        <div class="space-y-1">
                            <Label for="middle_name">Middle Name</Label>
                            <Input id="middle_name" v-model="form.middle_name" placeholder="Middle name" />
                            <InputError :message="err('middle_name')" />
                        </div>
                        <div class="space-y-1">
                            <Label for="extension_name">Extension (Jr., Sr., etc.)</Label>
                            <Input id="extension_name" v-model="form.extension_name" placeholder="e.g. Jr." />
                            <InputError :message="err('extension_name')" />
                        </div>
                    </div>

                    <!-- Sex & Birth date -->
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <div class="space-y-1">
                            <Label>Sex</Label>
                            <div :class="radioGroupClass">
                                <label :class="radioClass">
                                    <input type="radio" v-model="form.sex" value="Male" class="accent-primary" />
                                    Male
                                </label>
                                <label :class="radioClass">
                                    <input type="radio" v-model="form.sex" value="Female" class="accent-primary" />
                                    Female
                                </label>
                            </div>
                            <InputError :message="err('sex')" />
                        </div>
                        <div class="space-y-1">
                            <Label for="birth_date">Birth Date</Label>
                            <Input id="birth_date" v-model="form.birth_date" type="date" />
                            <InputError :message="err('birth_date')" />
                        </div>
                    </div>

                    <!-- Classifications -->
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <div class="space-y-1">
                            <Label for="classify_extent_of_damaged_house">Extent of Damaged House</Label>
                            <select id="classify_extent_of_damaged_house" v-model="form.classify_extent_of_damaged_house" :class="selectClass">
                                <option value="" disabled>Select...</option>
                                <option value="Totally Damaged">Totally Damaged</option>
                                <option value="Partially Damaged">Partially Damaged</option>
                            </select>
                            <InputError :message="err('classify_extent_of_damaged_house')" />
                        </div>
                        <div class="space-y-1">
                            <Label for="nhts_pr_classification">NHTS-PR Classification</Label>
                            <select id="nhts_pr_classification" v-model="form.nhts_pr_classification" :class="selectClass">
                                <option value="" disabled>Select...</option>
                                <option value="Poor">Poor</option>
                                <option value="Near Poor">Near Poor</option>
                                <option value="Not Poor">Not Poor</option>
                            </select>
                            <InputError :message="err('nhts_pr_classification')" />
                        </div>
                        <div class="space-y-1">
                            <Label for="civil_status">Civil Status</Label>
                            <select id="civil_status" v-model="form.civil_status" :class="selectClass">
                                <option value="" disabled>Select...</option>
                                <option value="Single">Single</option>
                                <option value="Married">Married</option>
                                <option value="Widowed">Widowed</option>
                                <option value="Separated">Separated</option>
                                <option value="Annulled">Annulled</option>
                            </select>
                            <InputError :message="err('civil_status')" />
                        </div>
                    </div>

                    <!-- Applicable Sector -->
                    <div class="space-y-2">
                        <Label>Applicable Sector</Label>
                        <div class="flex flex-wrap gap-4">
                            <label v-for="sector in APPLICABLE_SECTORS" :key="sector" :class="radioClass">
                                <input
                                    type="checkbox"
                                    :value="sector"
                                    :checked="form.applicable_sector.includes(sector)"
                                    @change="toggleSector(sector)"
                                    class="accent-primary"
                                />
                                {{ sector }}
                            </label>
                        </div>
                        <InputError :message="err('applicable_sector')" />
                    </div>
                </div>

                <!-- Father -->
                <div :class="sectionClass">
                    <h2 :class="sectionTitleClass">Father</h2>
                    <div class="space-y-1">
                        <Label>Living with Father?</Label>
                        <div :class="radioGroupClass">
                            <label :class="radioClass">
                                <input type="radio" :value="true" v-model="form.living_with_father" class="accent-primary" />
                                Yes
                            </label>
                            <label :class="radioClass">
                                <input type="radio" :value="false" v-model="form.living_with_father" class="accent-primary" />
                                No
                            </label>
                        </div>
                        <InputError :message="err('living_with_father')" />
                    </div>

                    <div v-if="form.living_with_father" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <div class="space-y-1">
                            <Label for="father_last_name">Last Name</Label>
                            <Input id="father_last_name" v-model="form.father_last_name" placeholder="Last name" />
                            <InputError :message="err('father_last_name')" />
                        </div>
                        <div class="space-y-1">
                            <Label for="father_first_name">First Name</Label>
                            <Input id="father_first_name" v-model="form.father_first_name" placeholder="First name" />
                            <InputError :message="err('father_first_name')" />
                        </div>
                        <div class="space-y-1">
                            <Label for="father_middle_name">Middle Name</Label>
                            <Input id="father_middle_name" v-model="form.father_middle_name" placeholder="Middle name" />
                            <InputError :message="err('father_middle_name')" />
                        </div>
                        <div class="space-y-1">
                            <Label for="father_extension_name">Extension</Label>
                            <Input id="father_extension_name" v-model="form.father_extension_name" placeholder="e.g. Jr." />
                            <InputError :message="err('father_extension_name')" />
                        </div>
                        <div class="space-y-1">
                            <Label for="father_birth_date">Birth Date</Label>
                            <Input id="father_birth_date" v-model="form.father_birth_date" type="date" />
                            <InputError :message="err('father_birth_date')" />
                        </div>
                    </div>
                </div>

                <!-- Mother -->
                <div :class="sectionClass">
                    <h2 :class="sectionTitleClass">Mother</h2>
                    <div class="space-y-1">
                        <Label>Living with Mother?</Label>
                        <div :class="radioGroupClass">
                            <label :class="radioClass">
                                <input type="radio" :value="true" v-model="form.living_with_mother" class="accent-primary" />
                                Yes
                            </label>
                            <label :class="radioClass">
                                <input type="radio" :value="false" v-model="form.living_with_mother" class="accent-primary" />
                                No
                            </label>
                        </div>
                        <InputError :message="err('living_with_mother')" />
                    </div>

                    <div v-if="form.living_with_mother" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <div class="space-y-1">
                            <Label for="mother_last_name">Last Name</Label>
                            <Input id="mother_last_name" v-model="form.mother_last_name" placeholder="Last name" />
                            <InputError :message="err('mother_last_name')" />
                        </div>
                        <div class="space-y-1">
                            <Label for="mother_first_name">First Name</Label>
                            <Input id="mother_first_name" v-model="form.mother_first_name" placeholder="First name" />
                            <InputError :message="err('mother_first_name')" />
                        </div>
                        <div class="space-y-1">
                            <Label for="mother_middle_name">Middle Name</Label>
                            <Input id="mother_middle_name" v-model="form.mother_middle_name" placeholder="Middle name" />
                            <InputError :message="err('mother_middle_name')" />
                        </div>
                        <div class="space-y-1">
                            <Label for="mother_birth_date">Birth Date</Label>
                            <Input id="mother_birth_date" v-model="form.mother_birth_date" type="date" />
                            <InputError :message="err('mother_birth_date')" />
                        </div>
                    </div>
                </div>

                <!-- Siblings -->
                <div :class="sectionClass">
                    <h2 :class="sectionTitleClass">Siblings</h2>
                    <div class="space-y-1">
                        <Label>Living with Siblings?</Label>
                        <div :class="radioGroupClass">
                            <label :class="radioClass">
                                <input type="radio" :value="true" v-model="form.living_with_siblings" class="accent-primary" />
                                Yes
                            </label>
                            <label :class="radioClass">
                                <input type="radio" :value="false" v-model="form.living_with_siblings" class="accent-primary" />
                                No
                            </label>
                        </div>
                        <InputError :message="err('living_with_siblings')" />
                    </div>

                    <div v-if="form.living_with_siblings" class="space-y-4">
                        <div class="w-32 space-y-1">
                            <Label for="siblings_count">Number of Siblings</Label>
                            <Input id="siblings_count" v-model.number="form.siblings_count" type="number" min="1" max="20" />
                            <InputError :message="err('siblings_count')" />
                        </div>

                        <div v-for="(sibling, i) in form.siblings" :key="i" class="rounded-lg border p-4">
                            <p class="mb-3 text-sm font-medium text-muted-foreground">Sibling {{ i + 1 }}</p>
                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                                <div class="space-y-1">
                                    <Label>Last Name</Label>
                                    <Input v-model="sibling.last_name" placeholder="Last name" />
                                    <InputError :message="err(`siblings.${i}.last_name`)" />
                                </div>
                                <div class="space-y-1">
                                    <Label>First Name</Label>
                                    <Input v-model="sibling.first_name" placeholder="First name" />
                                    <InputError :message="err(`siblings.${i}.first_name`)" />
                                </div>
                                <div class="space-y-1">
                                    <Label>Middle Name</Label>
                                    <Input v-model="sibling.middle_name" placeholder="Middle name" />
                                    <InputError :message="err(`siblings.${i}.middle_name`)" />
                                </div>
                                <div class="space-y-1">
                                    <Label>Birth Date</Label>
                                    <Input v-model="sibling.birth_date" type="date" />
                                    <InputError :message="err(`siblings.${i}.birth_date`)" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Spouse -->
                <div :class="sectionClass">
                    <h2 :class="sectionTitleClass">Spouse</h2>
                    <div class="space-y-1">
                        <Label>Living with Spouse?</Label>
                        <div :class="radioGroupClass">
                            <label :class="radioClass">
                                <input type="radio" :value="true" v-model="form.living_with_spouse" class="accent-primary" />
                                Yes
                            </label>
                            <label :class="radioClass">
                                <input type="radio" :value="false" v-model="form.living_with_spouse" class="accent-primary" />
                                No
                            </label>
                        </div>
                        <InputError :message="err('living_with_spouse')" />
                    </div>

                    <div v-if="form.living_with_spouse" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <div class="space-y-1">
                            <Label for="spouse_last_name">Last Name</Label>
                            <Input id="spouse_last_name" v-model="form.spouse_last_name" placeholder="Last name" />
                            <InputError :message="err('spouse_last_name')" />
                        </div>
                        <div class="space-y-1">
                            <Label for="spouse_first_name">First Name</Label>
                            <Input id="spouse_first_name" v-model="form.spouse_first_name" placeholder="First name" />
                            <InputError :message="err('spouse_first_name')" />
                        </div>
                        <div class="space-y-1">
                            <Label for="spouse_middle_name">Middle Name</Label>
                            <Input id="spouse_middle_name" v-model="form.spouse_middle_name" placeholder="Middle name" />
                            <InputError :message="err('spouse_middle_name')" />
                        </div>
                        <div class="space-y-1">
                            <Label for="spouse_extension_name">Extension</Label>
                            <Input id="spouse_extension_name" v-model="form.spouse_extension_name" placeholder="e.g. Jr." />
                            <InputError :message="err('spouse_extension_name')" />
                        </div>
                        <div class="space-y-1">
                            <Label for="spouse_birth_date">Birth Date</Label>
                            <Input id="spouse_birth_date" v-model="form.spouse_birth_date" type="date" />
                            <InputError :message="err('spouse_birth_date')" />
                        </div>
                    </div>
                </div>

                <!-- Children -->
                <div :class="sectionClass">
                    <h2 :class="sectionTitleClass">Children (18 years old and above)</h2>
                    <div class="space-y-1">
                        <Label>Living with Children (18+)?</Label>
                        <div :class="radioGroupClass">
                            <label :class="radioClass">
                                <input type="radio" :value="true" v-model="form.living_with_children" class="accent-primary" />
                                Yes
                            </label>
                            <label :class="radioClass">
                                <input type="radio" :value="false" v-model="form.living_with_children" class="accent-primary" />
                                No
                            </label>
                        </div>
                        <InputError :message="err('living_with_children')" />
                    </div>

                    <div v-if="form.living_with_children" class="space-y-4">
                        <div class="w-32 space-y-1">
                            <Label for="children_count">Number of Children</Label>
                            <Input id="children_count" v-model.number="form.children_count" type="number" min="1" max="20" />
                            <InputError :message="err('children_count')" />
                        </div>

                        <div v-for="(child, i) in form.children" :key="i" class="rounded-lg border p-4">
                            <p class="mb-3 text-sm font-medium text-muted-foreground">Child {{ i + 1 }}</p>
                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                                <div class="space-y-1">
                                    <Label>Last Name</Label>
                                    <Input v-model="child.last_name" placeholder="Last name" />
                                    <InputError :message="err(`children.${i}.last_name`)" />
                                </div>
                                <div class="space-y-1">
                                    <Label>First Name</Label>
                                    <Input v-model="child.first_name" placeholder="First name" />
                                    <InputError :message="err(`children.${i}.first_name`)" />
                                </div>
                                <div class="space-y-1">
                                    <Label>Middle Name</Label>
                                    <Input v-model="child.middle_name" placeholder="Middle name" />
                                    <InputError :message="err(`children.${i}.middle_name`)" />
                                </div>
                                <div class="space-y-1">
                                    <Label>Birth Date</Label>
                                    <Input v-model="child.birth_date" type="date" />
                                    <InputError :message="err(`children.${i}.birth_date`)" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Relatives -->
                <div :class="sectionClass">
                    <h2 :class="sectionTitleClass">Other Relatives</h2>
                    <div class="space-y-1">
                        <Label>Living with Other Relatives?</Label>
                        <div :class="radioGroupClass">
                            <label :class="radioClass">
                                <input type="radio" :value="true" v-model="form.living_with_relatives" class="accent-primary" />
                                Yes
                            </label>
                            <label :class="radioClass">
                                <input type="radio" :value="false" v-model="form.living_with_relatives" class="accent-primary" />
                                No
                            </label>
                        </div>
                        <InputError :message="err('living_with_relatives')" />
                    </div>

                    <div v-if="form.living_with_relatives" class="space-y-4">
                        <div class="w-32 space-y-1">
                            <Label for="relatives_count">Number of Relatives</Label>
                            <Input id="relatives_count" v-model.number="form.relatives_count" type="number" min="1" max="20" />
                            <InputError :message="err('relatives_count')" />
                        </div>

                        <div v-for="(relative, i) in form.relatives" :key="i" class="rounded-lg border p-4">
                            <p class="mb-3 text-sm font-medium text-muted-foreground">Relative {{ i + 1 }}</p>
                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                                <div class="space-y-1">
                                    <Label>Last Name</Label>
                                    <Input v-model="relative.last_name" placeholder="Last name" />
                                    <InputError :message="err(`relatives.${i}.last_name`)" />
                                </div>
                                <div class="space-y-1">
                                    <Label>First Name</Label>
                                    <Input v-model="relative.first_name" placeholder="First name" />
                                    <InputError :message="err(`relatives.${i}.first_name`)" />
                                </div>
                                <div class="space-y-1">
                                    <Label>Middle Name</Label>
                                    <Input v-model="relative.middle_name" placeholder="Middle name" />
                                    <InputError :message="err(`relatives.${i}.middle_name`)" />
                                </div>
                                <div class="space-y-1">
                                    <Label>Birth Date</Label>
                                    <Input v-model="relative.birth_date" type="date" />
                                    <InputError :message="err(`relatives.${i}.birth_date`)" />
                                </div>
                                <div class="space-y-1">
                                    <Label>Relationship</Label>
                                    <Input v-model="relative.relationship" placeholder="e.g. Uncle, Cousin" />
                                    <InputError :message="err(`relatives.${i}.relationship`)" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Submit -->
                <div class="flex justify-end gap-3 pb-6">
                    <Button type="button" variant="outline" as-child>
                        <a :href="index().url">Cancel</a>
                    </Button>
                    <Button type="submit" :disabled="form.processing">
                        <template v-if="!isOnline">Save Offline</template>
                        <template v-else-if="form.processing">Saving...</template>
                        <template v-else>Save Beneficiary</template>
                    </Button>
                </div>

            </form>
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
                class="fixed bottom-6 right-6 z-50 max-w-sm rounded-lg border px-4 py-3 text-sm shadow-lg"
                :class="{
                    'border-green-200 bg-green-50 text-green-800 dark:border-green-800 dark:bg-green-950 dark:text-green-200': toastType === 'success',
                    'border-red-200 bg-red-50 text-red-800 dark:border-red-800 dark:bg-red-950 dark:text-red-200': toastType === 'error',
                    'border-blue-200 bg-blue-50 text-blue-800 dark:border-blue-800 dark:bg-blue-950 dark:text-blue-200': toastType === 'info',
                }"
            >
                {{ toastMessage }}
            </div>
        </Transition>
    </AppLayout>
</template>
