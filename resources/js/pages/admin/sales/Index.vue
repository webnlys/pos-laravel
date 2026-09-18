<template>
    <PageShell title="Sales" :create-to="{ name: 'admin.sales.create' }">
        <form class="filter-form mb-3" @submit.prevent="load(1)">
            <div class="filter-field">
                <label class="form-label">Search</label>
                <input v-model="q" class="form-control" placeholder="Number or customer">
            </div>
            <button class="btn btn-primary">Search</button>
        </form>
        <div class="table-wrap">
            <table class="table table-bordered stack-table">
                <thead><tr><th>Number</th><th>Date</th><th>Customer</th><th>Total</th><th></th></tr></thead>
                <tbody>
                    <tr v-if="!rows.length">
                        <td colspan="5" class="text-center text-muted">No sales found.</td>
                    </tr>
                    <tr v-for="row in rows" :key="row.id">
                        <td data-label="Number">{{ row.number }}</td>
                        <td data-label="Date">{{ String(row.document_datetime).slice(0, 16) }}</td>
                        <td data-label="Customer">{{ row.customer?.name }}</td>
                        <td data-label="Total">{{ money(row.total) }}</td>
                        <td class="stack-actions">
                            <div class="mobile-actions">
                                <router-link class="btn btn-outline-info btn-sm" :to="{ name: 'admin.sales.show', params: { id: row.id } }">View</router-link>
                                <a class="btn btn-outline-dark btn-sm" :href="`/api/admin/sales/${row.id}/pdf`" target="_blank">PDF</a>
                                <router-link class="btn btn-outline-info btn-sm" :to="{ name: 'admin.sales.edit', params: { id: row.id } }">Edit</router-link>
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
const { rows, meta, q, perPage, load } = usePagedList('/api/admin/sales');

function money(value) {
    return settings.formatMoney(value);
}

async function destroy(id) {
    const ok = await Swal.fire({ title: 'Delete sales invoice?', icon: 'warning', showCancelButton: true });
    if (!ok.isConfirmed) return;
    await axios.delete(`/api/admin/sales/${id}`);
    load(meta.value.current_page);
}
</script>
