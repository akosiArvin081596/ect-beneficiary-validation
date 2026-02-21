<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Plus, Trash2 } from 'lucide-vue-next';
import { computed, watch } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { index } from '@/routes/masterlist';
import { type BreadcrumbItem } from '@/types';

interface FamilyMember {
    id?: number;
    first_name: string;
    last_name: string;
    middle_name: string;
    birth_date: string;
    relationship?: string;
}

interface MunicipalityOption {
    name: string;
    barangays: string[];
}

interface ProvinceOption {
    name: string;
    municipalities: MunicipalityOption[];
}

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
    living_with_father: boolean;
    father_last_name: string | null;
    father_first_name: string | null;
    father_middle_name: string | null;
    father_extension_name: string | null;
    father_birth_date: string | null;
    living_with_mother: boolean;
    mother_last_name: string | null;
    mother_first_name: string | null;
    mother_middle_name: string | null;
    mother_birth_date: string | null;
    living_with_spouse: boolean;
    spouse_last_name: string | null;
    spouse_first_name: string | null;
    spouse_middle_name: string | null;
    spouse_extension_name: string | null;
    spouse_birth_date: string | null;
    living_with_siblings: boolean;
    living_with_children: boolean;
    living_with_relatives: boolean;
    siblings: FamilyMember[];
    children: FamilyMember[];
    relatives: FamilyMember[];
    created_at: string;
}

const props = defineProps<{
    beneficiary: Beneficiary;
    locations: ProvinceOption[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Masterlist', href: index().url },
    { title: `${props.beneficiary.last_name}, ${props.beneficiary.first_name}` },
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

const APPLICABLE_SECTORS = [
    '4Ps', 'Farmer', 'Fisherfolk', 'Indigenous People', 'Senior Citizen',
    'Solo Parent', 'Pregnant Women', 'Lactating Mother', 'PWD',
    'Out-of-School Youth', 'Former Rebel/Decommissioned Combatant',
    'YAKAP Bayan/Drug Surrenderee', 'LGBTQIA+', 'Other',
];

const b = props.beneficiary;

const form = useForm({
    _method: 'put' as const,
    province: b.province,
    municipality: b.municipality,
    barangay: b.barangay,
    purok: b.purok ?? '',
    last_name: b.last_name,
    first_name: b.first_name,
    middle_name: b.middle_name ?? '',
    extension_name: b.extension_name ?? '',
    sex: b.sex,
    birth_date: b.birth_date ? b.birth_date.substring(0, 10) : '',
    classify_extent_of_damaged_house: b.classify_extent_of_damaged_house,
    nhts_pr_classification: b.nhts_pr_classification ?? '',
    applicable_sector: b.applicable_sector ?? ([] as string[]),
    civil_status: b.civil_status,
    living_with_father: b.living_with_father,
    father_last_name: b.father_last_name ?? '',
    father_first_name: b.father_first_name ?? '',
    father_middle_name: b.father_middle_name ?? '',
    father_extension_name: b.father_extension_name ?? '',
    father_birth_date: b.father_birth_date ? b.father_birth_date.substring(0, 10) : '',
    living_with_mother: b.living_with_mother,
    mother_last_name: b.mother_last_name ?? '',
    mother_first_name: b.mother_first_name ?? '',
    mother_middle_name: b.mother_middle_name ?? '',
    mother_birth_date: b.mother_birth_date ? b.mother_birth_date.substring(0, 10) : '',
    living_with_siblings: b.living_with_siblings,
    siblings_count: b.siblings.length,
    siblings: b.siblings.map((s) => ({
        last_name: s.last_name,
        first_name: s.first_name,
        middle_name: s.middle_name ?? '',
        birth_date: s.birth_date ? s.birth_date.substring(0, 10) : '',
    })) as PersonEntry[],
    living_with_spouse: b.living_with_spouse,
    spouse_last_name: b.spouse_last_name ?? '',
    spouse_first_name: b.spouse_first_name ?? '',
    spouse_middle_name: b.spouse_middle_name ?? '',
    spouse_extension_name: b.spouse_extension_name ?? '',
    spouse_birth_date: b.spouse_birth_date ? b.spouse_birth_date.substring(0, 10) : '',
    living_with_children: b.living_with_children,
    children_count: b.children.length,
    children: b.children.map((c) => ({
        last_name: c.last_name,
        first_name: c.first_name,
        middle_name: c.middle_name ?? '',
        birth_date: c.birth_date ? c.birth_date.substring(0, 10) : '',
    })) as PersonEntry[],
    living_with_relatives: b.living_with_relatives,
    relatives_count: b.relatives.length,
    relatives: b.relatives.map((r) => ({
        last_name: r.last_name,
        first_name: r.first_name,
        middle_name: r.middle_name ?? '',
        birth_date: r.birth_date ? r.birth_date.substring(0, 10) : '',
        relationship: r.relationship ?? '',
    })) as RelativeEntry[],
});

// ── Cascading location selects ───────────────────────────────────────────────
const filteredMunicipalities = computed(() => {
    if (!form.province) return [];
    const province = props.locations.find((p) => p.name === form.province);
    return province ? province.municipalities : [];
});

const filteredBarangays = computed(() => {
    if (!form.municipality) return [];
    const municipality = filteredMunicipalities.value.find((m) => m.name === form.municipality);
    return municipality ? municipality.barangays : [];
});

let pauseLocationWatchers = true;
// Unpause after initial render
setTimeout(() => { pauseLocationWatchers = false; }, 0);

watch(() => form.province, () => {
    if (pauseLocationWatchers) return;
    form.municipality = '';
    form.barangay = '';
});

watch(() => form.municipality, () => {
    if (pauseLocationWatchers) return;
    form.barangay = '';
});

// Auto-toggle Senior Citizen when age >= 60
watch(() => form.birth_date, (val) => {
    if (!val) return;
    const today = new Date();
    const birth = new Date(val);
    let age = today.getFullYear() - birth.getFullYear();
    const m = today.getMonth() - birth.getMonth();
    if (m < 0 || (m === 0 && today.getDate() < birth.getDate())) age--;
    const hasSenior = form.applicable_sector.includes('Senior Citizen');
    if (age >= 60 && !hasSenior) {
        form.applicable_sector.push('Senior Citizen');
    } else if (age < 60 && hasSenior) {
        form.applicable_sector.splice(form.applicable_sector.indexOf('Senior Citizen'), 1);
    }
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

function submit() {
    form.post(`/masterlist/${props.beneficiary.id}`, {
        preserveScroll: true,
    });
}

const selectClass =
    'border-input bg-background text-foreground h-9 w-full rounded-md border px-3 py-1 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]';
const sectionTitleClass = 'text-base font-semibold';
const radioGroupClass = 'flex items-center gap-6';
const radioClass = 'flex items-center gap-2 cursor-pointer';
</script>

<template>
    <Head :title="`Edit — ${beneficiary.last_name}, ${beneficiary.first_name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-4">
            <div class="flex items-center justify-between">
                <Button as-child variant="ghost" size="sm">
                    <Link :href="index().url">
                        <ArrowLeft class="mr-2 h-4 w-4" />
                        Back to Masterlist
                    </Link>
                </Button>
                <Button @click="submit" :disabled="form.processing" size="sm">
                    <template v-if="form.processing">Saving...</template>
                    <template v-else>Save Changes</template>
                </Button>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <!-- Personal Information -->
                <Card>
                    <CardHeader>
                        <CardTitle>Personal Information</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4">
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
                            <div class="space-y-1">
                                <Label for="civil_status">Civil Status</Label>
                                <select id="civil_status" v-model="form.civil_status" :class="selectClass">
                                    <option value="" disabled>Select...</option>
                                    <option value="Single">Single</option>
                                    <option value="Married">Married</option>
                                    <option value="Common Law">Common Law</option>
                                    <option value="Widowed">Widowed</option>
                                    <option value="Separated">Separated</option>
                                    <option value="Annulled">Annulled</option>
                                </select>
                                <InputError :message="err('civil_status')" />
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Location -->
                <Card>
                    <CardHeader>
                        <CardTitle>Location</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                            <div class="space-y-1">
                                <Label for="province">Province</Label>
                                <select id="province" v-model="form.province" :class="selectClass">
                                    <option value="" disabled>Select province...</option>
                                    <option v-for="p in locations" :key="p.name" :value="p.name">{{ p.name }}</option>
                                </select>
                                <InputError :message="err('province')" />
                            </div>
                            <div class="space-y-1">
                                <Label for="municipality">Municipality</Label>
                                <select id="municipality" v-model="form.municipality" :class="selectClass" :disabled="!form.province">
                                    <option value="" disabled>Select municipality...</option>
                                    <option v-for="m in filteredMunicipalities" :key="m.name" :value="m.name">{{ m.name }}</option>
                                </select>
                                <InputError :message="err('municipality')" />
                            </div>
                            <div class="space-y-1">
                                <Label for="barangay">Barangay</Label>
                                <select id="barangay" v-model="form.barangay" :class="selectClass" :disabled="!form.municipality">
                                    <option value="" disabled>Select barangay...</option>
                                    <option v-for="bg in filteredBarangays" :key="bg" :value="bg">{{ bg }}</option>
                                </select>
                                <InputError :message="err('barangay')" />
                            </div>
                            <div class="space-y-1">
                                <Label for="purok">Purok</Label>
                                <Input id="purok" v-model="form.purok" placeholder="Purok" />
                                <InputError :message="err('purok')" />
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Classification -->
                <Card>
                    <CardHeader>
                        <CardTitle>Classification</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                            <div class="space-y-1">
                                <Label for="classify_extent_of_damaged_house">Extent of Damaged House</Label>
                                <select id="classify_extent_of_damaged_house" v-model="form.classify_extent_of_damaged_house" :class="selectClass">
                                    <option value="" disabled>Select...</option>
                                    <option value="Totally Damaged (Severely)">Totally Damaged (Severely)</option>
                                    <option value="Partially Damaged (Slightly)">Partially Damaged (Slightly)</option>
                                </select>
                                <InputError :message="err('classify_extent_of_damaged_house')" />
                            </div>
                            <div class="space-y-1">
                                <Label for="nhts_pr_classification">NHTS-PR Classification</Label>
                                <select id="nhts_pr_classification" v-model="form.nhts_pr_classification" :class="selectClass">
                                    <option value="">None</option>
                                    <option value="Poor">Poor</option>
                                    <option value="Near Poor">Near Poor</option>
                                    <option value="Not Poor">Not Poor</option>
                                </select>
                                <InputError :message="err('nhts_pr_classification')" />
                            </div>
                        </div>

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
                    </CardContent>
                </Card>

                <!-- Father -->
                <Card>
                    <CardHeader>
                        <CardTitle>Father</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4">
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
                    </CardContent>
                </Card>

                <!-- Mother -->
                <Card>
                    <CardHeader>
                        <CardTitle>Mother</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4">
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
                    </CardContent>
                </Card>

                <!-- Spouse -->
                <Card>
                    <CardHeader>
                        <CardTitle>Spouse</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4">
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
                    </CardContent>
                </Card>

                <!-- Siblings -->
                <Card>
                    <CardHeader>
                        <CardTitle>Siblings</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4">
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
                    </CardContent>
                </Card>

                <!-- Children -->
                <Card>
                    <CardHeader>
                        <CardTitle>Children (18 years old and above)</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4">
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
                    </CardContent>
                </Card>

                <!-- Relatives -->
                <Card>
                    <CardHeader>
                        <CardTitle>Other Relatives</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4">
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
                    </CardContent>
                </Card>

                <!-- Submit -->
                <div class="flex justify-end gap-3 pb-6">
                    <Button type="button" variant="outline" as-child>
                        <Link :href="index().url">Cancel</Link>
                    </Button>
                    <Button type="submit" :disabled="form.processing">
                        <template v-if="form.processing">Saving...</template>
                        <template v-else>Save Changes</template>
                    </Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
