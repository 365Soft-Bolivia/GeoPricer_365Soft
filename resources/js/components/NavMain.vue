<script setup lang="ts">
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { urlIsActive } from '@/lib/utils';
import { type NavItem } from '@/types';
import { Link, router, usePage } from '@inertiajs/vue3';

defineProps<{
    items: NavItem[];
}>();

const page = usePage();

const navigate = (href: string) => {
    router.visit(href);
};
</script>

<template>
    <SidebarGroup class="px-2 py-0">
        <SidebarGroupLabel>Plataforma</SidebarGroupLabel>

        <SidebarMenu>
            <SidebarMenuItem v-for="item in items" :key="item.title">
                <button
                    type="button"
                    @click="navigate(item.href)"
                    class="flex w-full items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 cursor-pointer"
                    :class="{ 'bg-accent text-accent-foreground': urlIsActive(item.href, page.url) }"
                >
                    <component :is="item.icon" class="size-4" />
                    <span>{{ item.title }}</span>
                </button>
            </SidebarMenuItem>
        </SidebarMenu>
    </SidebarGroup>
</template>
