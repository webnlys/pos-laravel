<template>
    <div class="d-flex">
        <aside class="sidebar p-3">
            <h5 class="mb-4">Simple POS</h5>
            <nav class="d-grid gap-1">
                <router-link :to="{ name: 'admin.home' }">Dashboard</router-link>
                <router-link :to="{ name: 'admin.products' }">Products</router-link>
                <router-link :to="{ name: 'admin.customers' }">Customers</router-link>
                <router-link :to="{ name: 'admin.suppliers' }">Suppliers</router-link>
                <router-link :to="{ name: 'admin.purchases' }">Purchases</router-link>
                <router-link :to="{ name: 'admin.sales' }">Sales</router-link>
                <router-link :to="{ name: 'admin.quotations' }">Quotations</router-link>
                <router-link :to="{ name: 'admin.payments' }">Payments</router-link>
                <router-link :to="{ name: 'admin.taxes' }">Taxes</router-link>
                <router-link :to="{ name: 'admin.reports' }">Reports</router-link>
                <router-link :to="{ name: 'admin.settings' }">Settings</router-link>
            </nav>
        </aside>
        <main class="flex-grow-1">
            <div class="d-flex justify-content-between align-items-center px-4 py-3 bg-white border-bottom">
                <div>{{ auth.user?.name }}</div>
                <button class="btn btn-outline-secondary btn-sm" @click="onLogout">Logout</button>
            </div>
            <div class="p-4">
                <router-view />
            </div>
        </main>
    </div>
</template>

<script setup>
import { useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';

const auth = useAuthStore();
const router = useRouter();

async function onLogout() {
    await auth.logout();
    router.push({ name: 'login' });
}
</script>
