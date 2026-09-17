<template>
    <AppShell brand="Quotations">
        <template #nav>
            <router-link :to="{ name: 'admin.quotations' }">Quotations</router-link>
            <router-link :to="{ name: 'admin.products' }">Products</router-link>
            <router-link :to="{ name: 'admin.customers' }">Customers</router-link>
            <router-link :to="{ name: 'admin.taxes' }">Taxes</router-link>
            <router-link :to="{ name: 'admin.settings' }">Settings</router-link>
        </template>
        <router-view />
    </AppShell>
</template>

<script setup>
import AppShell from './AppShell.vue';
</script>
