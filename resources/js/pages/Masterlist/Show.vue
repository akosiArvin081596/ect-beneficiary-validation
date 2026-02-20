<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft } from 'lucide-vue-next';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { index } from '@/routes/masterlist';
import { type BreadcrumbItem } from '@/types';

interface FamilyMember {
    id: number;
    first_name: string;
    last_name: string;
    middle_name: string | null;
    birth_date: string;
    relationship?: string;
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
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Masterlist', href: index().url },
    { title: `${props.beneficiary.last_name}, ${props.beneficiary.first_name}` },
];

function formatDate(dateStr: string | null): string {
    if (!dateStr) return '—';
    return new Date(dateStr).toLocaleDateString('en-PH', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
}

function memberName(m: FamilyMember): string {
    const parts = [m.last_name, m.first_name];
    if (m.middle_name) parts.push(m.middle_name.charAt(0) + '.');
    return parts.join(', ');
}

function parentName(last: string | null, first: string | null, middle: string | null, ext: string | null): string {
    if (!last && !first) return '—';
    const parts = [last, first].filter(Boolean);
    if (middle) parts.push(middle.charAt(0) + '.');
    if (ext) parts.push(ext);
    return parts.join(', ');
}
</script>

<template>
    <Head :title="`${beneficiary.last_name}, ${beneficiary.first_name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-4">
            <div>
                <Button as-child variant="ghost" size="sm">
                    <Link :href="index().url">
                        <ArrowLeft class="mr-2 h-4 w-4" />
                        Back to Masterlist
                    </Link>
                </Button>
            </div>

            <!-- Personal Info -->
            <Card>
                <CardHeader>
                    <CardTitle>Personal Information</CardTitle>
                </CardHeader>
                <CardContent>
                    <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <div>
                            <dt class="text-sm text-muted-foreground">Last Name</dt>
                            <dd class="font-medium">{{ beneficiary.last_name }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-muted-foreground">First Name</dt>
                            <dd class="font-medium">{{ beneficiary.first_name }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-muted-foreground">Middle Name</dt>
                            <dd class="font-medium">{{ beneficiary.middle_name || '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-muted-foreground">Extension Name</dt>
                            <dd class="font-medium">{{ beneficiary.extension_name || '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-muted-foreground">Sex</dt>
                            <dd class="font-medium">{{ beneficiary.sex }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-muted-foreground">Birth Date</dt>
                            <dd class="font-medium">{{ formatDate(beneficiary.birth_date) }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-muted-foreground">Civil Status</dt>
                            <dd class="font-medium">{{ beneficiary.civil_status }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-muted-foreground">Date Added</dt>
                            <dd class="font-medium">{{ formatDate(beneficiary.created_at) }}</dd>
                        </div>
                    </dl>
                </CardContent>
            </Card>

            <!-- Location -->
            <Card>
                <CardHeader>
                    <CardTitle>Location</CardTitle>
                </CardHeader>
                <CardContent>
                    <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <div>
                            <dt class="text-sm text-muted-foreground">Province</dt>
                            <dd class="font-medium">{{ beneficiary.province }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-muted-foreground">Municipality</dt>
                            <dd class="font-medium">{{ beneficiary.municipality }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-muted-foreground">Barangay</dt>
                            <dd class="font-medium">{{ beneficiary.barangay }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-muted-foreground">Purok</dt>
                            <dd class="font-medium">{{ beneficiary.purok || '—' }}</dd>
                        </div>
                    </dl>
                </CardContent>
            </Card>

            <!-- Classification -->
            <Card>
                <CardHeader>
                    <CardTitle>Classification</CardTitle>
                </CardHeader>
                <CardContent>
                    <dl class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div>
                            <dt class="text-sm text-muted-foreground">Damage Classification</dt>
                            <dd class="font-medium">{{ beneficiary.classify_extent_of_damaged_house }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-muted-foreground">NHTS-PR Classification</dt>
                            <dd class="font-medium">{{ beneficiary.nhts_pr_classification || '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-muted-foreground">Applicable Sectors</dt>
                            <dd class="flex flex-wrap gap-1">
                                <Badge
                                    v-for="sector in beneficiary.applicable_sector"
                                    :key="sector"
                                    variant="secondary"
                                >
                                    {{ sector }}
                                </Badge>
                                <span v-if="!beneficiary.applicable_sector?.length" class="font-medium">—</span>
                            </dd>
                        </div>
                    </dl>
                </CardContent>
            </Card>

            <!-- Father -->
            <Card v-if="beneficiary.living_with_father">
                <CardHeader>
                    <CardTitle>Father</CardTitle>
                </CardHeader>
                <CardContent>
                    <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <dt class="text-sm text-muted-foreground">Name</dt>
                            <dd class="font-medium">
                                {{ parentName(beneficiary.father_last_name, beneficiary.father_first_name, beneficiary.father_middle_name, beneficiary.father_extension_name) }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm text-muted-foreground">Birth Date</dt>
                            <dd class="font-medium">{{ formatDate(beneficiary.father_birth_date) }}</dd>
                        </div>
                    </dl>
                </CardContent>
            </Card>

            <!-- Mother -->
            <Card v-if="beneficiary.living_with_mother">
                <CardHeader>
                    <CardTitle>Mother</CardTitle>
                </CardHeader>
                <CardContent>
                    <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <dt class="text-sm text-muted-foreground">Name</dt>
                            <dd class="font-medium">
                                {{ parentName(beneficiary.mother_last_name, beneficiary.mother_first_name, beneficiary.mother_middle_name, null) }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm text-muted-foreground">Birth Date</dt>
                            <dd class="font-medium">{{ formatDate(beneficiary.mother_birth_date) }}</dd>
                        </div>
                    </dl>
                </CardContent>
            </Card>

            <!-- Spouse -->
            <Card v-if="beneficiary.living_with_spouse">
                <CardHeader>
                    <CardTitle>Spouse</CardTitle>
                </CardHeader>
                <CardContent>
                    <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <dt class="text-sm text-muted-foreground">Name</dt>
                            <dd class="font-medium">
                                {{ parentName(beneficiary.spouse_last_name, beneficiary.spouse_first_name, beneficiary.spouse_middle_name, beneficiary.spouse_extension_name) }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm text-muted-foreground">Birth Date</dt>
                            <dd class="font-medium">{{ formatDate(beneficiary.spouse_birth_date) }}</dd>
                        </div>
                    </dl>
                </CardContent>
            </Card>

            <!-- Siblings -->
            <Card v-if="beneficiary.siblings.length > 0">
                <CardHeader>
                    <CardTitle>Siblings ({{ beneficiary.siblings.length }})</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b">
                                    <th class="px-4 py-2 text-left font-medium">Name</th>
                                    <th class="px-4 py-2 text-left font-medium">Birth Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="s in beneficiary.siblings"
                                    :key="s.id"
                                    class="border-b last:border-0"
                                >
                                    <td class="px-4 py-2">{{ memberName(s) }}</td>
                                    <td class="px-4 py-2">{{ formatDate(s.birth_date) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </CardContent>
            </Card>

            <!-- Children -->
            <Card v-if="beneficiary.children.length > 0">
                <CardHeader>
                    <CardTitle>Children ({{ beneficiary.children.length }})</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b">
                                    <th class="px-4 py-2 text-left font-medium">Name</th>
                                    <th class="px-4 py-2 text-left font-medium">Birth Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="c in beneficiary.children"
                                    :key="c.id"
                                    class="border-b last:border-0"
                                >
                                    <td class="px-4 py-2">{{ memberName(c) }}</td>
                                    <td class="px-4 py-2">{{ formatDate(c.birth_date) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </CardContent>
            </Card>

            <!-- Relatives -->
            <Card v-if="beneficiary.relatives.length > 0">
                <CardHeader>
                    <CardTitle>Relatives ({{ beneficiary.relatives.length }})</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b">
                                    <th class="px-4 py-2 text-left font-medium">Name</th>
                                    <th class="px-4 py-2 text-left font-medium">Birth Date</th>
                                    <th class="px-4 py-2 text-left font-medium">Relationship</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="r in beneficiary.relatives"
                                    :key="r.id"
                                    class="border-b last:border-0"
                                >
                                    <td class="px-4 py-2">{{ memberName(r) }}</td>
                                    <td class="px-4 py-2">{{ formatDate(r.birth_date) }}</td>
                                    <td class="px-4 py-2">{{ r.relationship }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
