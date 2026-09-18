<template>
    <AppShell :brand="settings.name || 'Quotations'">
        <template #nav>
            <router-link :to="{ name: 'admin.quotations' }">Quotations</router-link>
            <router-link :to="{ name: 'admin.sales' }">Sales</router-link>
            <router-link :to="{ name: 'admin.products' }">Products</router-link>
            <router-link :to="{ name: 'admin.units' }">Units</router-link>
            <router-link :to="{ name: 'admin.customers' }">Customers</router-link>
            <router-link :to="{ name: 'admin.taxes' }">Taxes</router-link>
            <router-link :to="{ name: 'admin.settings' }">Settings</router-link>
        </template>
        <router-view />
    </AppShell>
</template>

<script setup>
import AppShell from './AppShell.vue';
import { useSettingsStore } from '../stores/settings';

const settings = useSettingsStore();
</script>
