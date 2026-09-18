<template>
    <PageShell title="Customers" :create-to="{ name: 'admin.customers.create' }">
        <form class="filter-form mb-3" @submit.prevent="load(1)">
            <div class="filter-field">
                <label class="form-label">Search</label>
                <input v-model="q" class="form-control" placeholder="Name, phone, email">
            </div>
            <button class="btn btn-primary">Search</button>
        </form>
        <div class="table-wrap">
            <table class="table table-bordered stack-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Phone</th>
                        <th>Balance ({{ settings.currency }})</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="!rows.length">
                        <td colspan="4" class="text-center text-muted">No customers found.</td>
                    </tr>
                    <tr v-for="row in rows" :key="row.id">
                        <td data-label="Name">{{ row.name }}</td>
                        <td data-label="Phone">{{ row.phone || '—' }}</td>
                        <td data-label="Balance" :class="balanceClass(row)">{{ balanceText(row) }}</td>
                        <td class="stack-actions">
                            <div class="mobile-actions">
                                <router-link class="btn btn-outline-info btn-sm" :to="{ name: 'admin.customers.show', params: { id: row.id } }">Details</router-link>
                                <router-link class="btn btn-outline-info btn-sm" :to="{ name: 'admin.customers.edit', params: { id: row.id } }">Edit</router-link>
                                <button class="btn btn-outline-danger btn-sm" @click="destroy(row.id)">Delete</button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <PaginationBar v-model:per-page="perPage" :meta="meta" @change="load" />
    </PageShell>
</template>

<script setup>
import axios from 'axios';
import Swal from 'sweetalert2';
import PageShell from '../../../components/PageShell.vue';
import PaginationBar from '../../../components/PaginationBar.vue';
import { usePagedList } from '../../../composables/usePagedList';
import { useSettingsStore } from '../../../stores/settings';

const settings = useSettingsStore();
const { rows, meta, q, perPage, load } = usePagedList('/api/admin/customers');

function money(value) {
    return settings.formatMoney(value);
}

function balanceText(row) {
    if (Number(row.advance || 0) > 0) {
        return `${(row.advance)} credit`;
    }

    if (Number(row.due || 0) > 0) {
        return `${(row.due)} due`;
    }

    return (0);
}

function balanceClass(row) {
    if (Number(row.advance || 0) > 0) {
        return 'balance-credit';
    }

    if (Number(row.due || 0) > 0) {
        return 'balance-due';
    }

    return 'balance-settled';
}

async function destroy(id) {
    const ok = await Swal.fire({ title: 'Delete customer?', icon: 'warning', showCancelButton: true });
    if (!ok.isConfirmed) return;
    await axios.delete(`/api/admin/customers/${id}`);
    load(meta.value.current_page);
}
</script>

<style scoped>
.balance-credit {
    color: #15803d;
    font-weight: 600;
}

.balance-due {
    color: #dc2626;
    font-weight: 600;
}

.balance-settled {
    color: inherit;
    font-weight: 500;
}
</style>
