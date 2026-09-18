<template>
    <PageShell title="Sales" :create-to="{ name: 'admin.sales.create' }">
        <form class="d-flex align-items-end gap-3 flex-wrap mb-3" @submit.prevent="load(1)">
            <div>
                <label class="form-label">Search</label>
                <input v-model="q" class="form-control">
            </div>
            <button class="btn btn-primary">Search</button>
        </form>
        <table class="table table-bordered">
            <thead><tr><th>Number</th><th>Date</th><th>Customer</th><th>Total</th><th>Paid</th><th>Due</th><th></th></tr></thead>
            <tbody>
                <tr v-if="!rows.length">
                    <td colspan="7" class="text-center text-muted">No sales found.</td>
                </tr>
                <tr v-for="row in rows" :key="row.id">
                    <td>{{ row.number }}</td>
                    <td>{{ String(row.document_datetime).slice(0, 16) }}</td>
                    <td>{{ row.customer?.name }}</td>
                    <td>{{ row.total }}</td>
                    <td>{{ row.paid }}</td>
                    <td>{{ row.due }}</td>
                    <td>
                        <div class="d-flex gap-2">
                            <a class="btn btn-outline-dark btn-sm" :href="`/api/admin/sales/${row.id}/pdf`" target="_blank">PDF</a>
                            <router-link class="btn btn-outline-info btn-sm" :to="{ name: 'admin.sales.edit', params: { id: row.id } }">Edit</router-link>
                            <button class="btn btn-outline-danger btn-sm" @click="destroy(row.id)">Delete</button>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
        <PaginationBar v-model:per-page="perPage" :meta="meta" @change="load" />
    </PageShell>
</template>

<script setup>
import axios from 'axios';
import Swal from 'sweetalert2';
import PageShell from '../../../components/PageShell.vue';
import PaginationBar from '../../../components/PaginationBar.vue';
import { usePagedList } from '../../../composables/usePagedList';

const { rows, meta, q, perPage, load } = usePagedList('/api/admin/sales');

async function destroy(id) {
    const ok = await Swal.fire({ title: 'Delete sale? Stock will be restored.', icon: 'warning', showCancelButton: true });
    if (!ok.isConfirmed) return;
    await axios.delete(`/api/admin/sales/${id}`);
    load(meta.value.current_page);
}
</script>
