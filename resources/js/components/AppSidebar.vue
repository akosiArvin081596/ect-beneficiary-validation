<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    BookOpen,
    ClipboardList,
    Folder,
    GitCompareArrows,
    LayoutGrid,
    Sparkles,
    Trash2,
    Users,
} from 'lucide-vue-next';
import { computed } from 'vue';
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import { index as beneficiariesIndex } from '@/routes/beneficiaries';
import { index as dataCleansingIndex } from '@/routes/data-cleansing';
import { index as deduplicationIndex } from '@/routes/deduplication';
import { index as masterlistIndex } from '@/routes/masterlist';
import { index as trashIndex } from '@/routes/trash';
import { type NavItem } from '@/types';
import AppLogo from './AppLogo.vue';

const page = usePage();

const mainNavItems = computed<NavItem[]>(() => {
    const items: NavItem[] = [
        {
            title: 'Dashboard',
            href: dashboard(),
            icon: LayoutGrid,
        },
        {
            title: 'Beneficiaries',
            href: beneficiariesIndex(),
            icon: Users,
        },
    ];

    if (page.props.auth.user.is_admin) {
        items.push({
            title: 'Data Cleansing',
            href: dataCleansingIndex(),
            icon: Sparkles,
        });
        items.push({
            title: 'Deduplication',
            href: deduplicationIndex(),
            icon: GitCompareArrows,
        });
        items.push({
            title: 'Masterlist',
            href: masterlistIndex(),
            icon: ClipboardList,
        });
        items.push({
            title: 'Trash',
            href: trashIndex(),
            icon: Trash2,
        });
    }

    return items;
});

const footerNavItems: NavItem[] = [
    {
        title: 'Github Repo',
        href: 'https://github.com/laravel/vue-starter-kit',
        icon: Folder,
    },
    {
        title: 'Documentation',
        href: 'https://laravel.com/docs/starter-kits#vue',
        icon: BookOpen,
    },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
