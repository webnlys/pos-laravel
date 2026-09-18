<template>
    <PageShell title="Quotations" :create-to="{ name: 'admin.quotations.create' }">
        <form class="filter-form mb-3" @submit.prevent="load(1)">
            <div class="filter-field">
                <label class="form-label">Search</label>
                <input v-model="q" class="form-control" placeholder="Number or customer">
            </div>
            <button class="btn btn-primary">Search</button>
        </form>
        <div class="table-wrap">
            <table class="table table-bordered stack-table">
                <thead><tr><th>Number</th><th>Date</th><th>Customer</th><th>Total ({{ settings.currency }})</th><th></th></tr></thead>
                <tbody>
                    <tr v-if="!rows.length">
                        <td colspan="5" class="text-center text-muted">No quotations found.</td>
                    </tr>
                    <tr v-for="row in rows" :key="row.id">
                        <td data-label="Number">{{ row.number }}</td>
                        <td data-label="Date">{{ String(row.document_datetime).slice(0, 16) }}</td>
                        <td data-label="Customer">{{ row.customer?.name }}</td>
                        <td data-label="Total">{{ (row.total) }}</td>
                        <td class="stack-actions">
                            <div class="mobile-actions">
                                <router-link class="btn btn-outline-info btn-sm" :to="{ name: 'admin.quotations.show', params: { id: row.id } }">View</router-link>
                                <a class="btn btn-outline-dark btn-sm" :href="`/api/admin/quotations/${row.id}/pdf`" target="_blank">PDF</a>
                                <router-link v-if="!row.converted_sale_id" class="btn btn-outline-info btn-sm" :to="{ name: 'admin.quotations.edit', params: { id: row.id } }">Edit</router-link>
                                <button v-if="!row.converted_sale_id" class="btn btn-primary btn-sm" :disabled="convertingId === row.id" @click="convert(row)">
                                    {{ convertingId === row.id ? 'Converting...' : 'Convert' }}
                                </button>
                                <router-link v-else class="btn btn-outline-success btn-sm" :to="{ name: 'admin.sales.edit', params: { id: row.converted_sale_id } }">Invoice</router-link>
                                <button v-if="!row.converted_sale_id" class="btn btn-outline-danger btn-sm" @click="destroy(row.id)">Delete</button>
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
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import PageShell from '../../../components/PageShell.vue';
import PaginationBar from '../../../components/PaginationBar.vue';
import { usePagedList } from '../../../composables/usePagedList';
import { useSettingsStore } from '../../../stores/settings';

const router = useRouter();
const settings = useSettingsStore();
const convertingId = ref(null);
const { rows, meta, q, perPage, load } = usePagedList('/api/admin/quotations');

function money(value) {
    return settings.formatMoney(value);
}

async function convert(row) {
    const ok = await Swal.fire({
        title: 'Convert to sales invoice?',
        text: 'The sales invoice will open so you can review and save it.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Convert',
    });
    if (!ok.isConfirmed) return;

    convertingId.value = row.id;
    try {
        const { data } = await axios.post(`/api/admin/quotations/${row.id}/convert`);
        await router.push({ name: 'admin.sales.edit', params: { id: data.data.id } });
    } catch (e) {
        await Swal.fire({
            icon: 'error',
            title: 'Convert failed',
            text: Object.values(e.response?.data?.errors || {}).flat().join(' ') || e.response?.data?.message || 'Convert failed',
        });
    } finally {
        convertingId.value = null;
    }
}

async function destroy(id) {
    const ok = await Swal.fire({ title: 'Delete quotation?', icon: 'warning', showCancelButton: true });
    if (!ok.isConfirmed) return;
    await axios.delete(`/api/admin/quotations/${id}`);
    load(meta.value.current_page);
}
</script>
