<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    CalendarDays,
    Carrot,
    ClipboardList,
    CookingPot,
    LayoutGrid,
} from 'lucide-vue-next';

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
import { index as ingredientsIndex } from '@/routes/ingredients';
import { index as mealPlansIndex } from '@/routes/meal-plans';
import { index as recipesIndex } from '@/routes/recipes';
import { index as shoppingListsIndex } from '@/routes/shopping-lists';
import { type NavItem } from '@/types';

import AppLogo from './AppLogo.vue';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'Recipes',
        href: recipesIndex(),
        icon: CookingPot,
    },
    {
        title: 'Ingredients',
        href: ingredientsIndex(),
        icon: Carrot,
    },
    {
        title: 'Meal Plans',
        href: mealPlansIndex(),
        icon: CalendarDays,
    },
    {
        title: 'Shopping Lists',
        href: shoppingListsIndex(),
        icon: ClipboardList,
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
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
