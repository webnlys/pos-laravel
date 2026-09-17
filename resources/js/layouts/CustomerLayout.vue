<template>
    <AppShell brand="Customer">
        <template #nav>
            <router-link :to="{ name: 'customer.quotations' }">Quotations</router-link>
        </template>
        <router-view />
    </AppShell>
</template>

<script setup>
import AppShell from './AppShell.vue';
</script>
