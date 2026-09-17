<template>
    <PageShell title="Quotations" :create-to="{ name: 'admin.quotations.create' }">
        <form class="d-flex align-items-end gap-3 flex-wrap mb-3" @submit.prevent="load(1)">
            <div>
                <label class="form-label">Search</label>
                <input v-model="q" class="form-control">
            </div>
            <button class="btn btn-primary">Search</button>
        </form>
        <table class="table table-bordered">
            <thead><tr><th>Number</th><th>Date</th><th>Customer</th><th>Total</th><th>Status</th><th></th></tr></thead>
            <tbody>
                <tr v-for="row in rows" :key="row.id">
                    <td>{{ row.number }}</td>
                    <td>{{ String(row.document_datetime).slice(0, 16) }}</td>
                    <td>{{ row.customer?.name }}</td>
                    <td>{{ row.total }}</td>
                    <td><span class="badge rounded-pill text-bg-secondary">{{ row.status }}</span></td>
                    <td>
                        <div class="d-flex gap-2 flex-wrap">
                            <router-link class="btn btn-outline-info btn-sm" :to="{ name: 'admin.quotations.show', params: { id: row.id } }">View</router-link>
                            <a class="btn btn-outline-dark btn-sm" :href="`/api/admin/quotations/${row.id}/pdf`" target="_blank">PDF</a>
                            <router-link class="btn btn-outline-info btn-sm" :to="{ name: 'admin.quotations.edit', params: { id: row.id } }">Edit</router-link>
                            <button class="btn btn-outline-danger btn-sm" @click="destroy(row.id)">Delete</button>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
        <PaginationBar :meta="meta" @change="load" />
    </PageShell>
</template>

<script setup>
import axios from 'axios';
import { onMounted, ref } from 'vue';
import Swal from 'sweetalert2';
import PageShell from '../../../components/PageShell.vue';
import PaginationBar from '../../../components/PaginationBar.vue';

const rows = ref([]);
const meta = ref({});
const q = ref('');

async function load(page = 1) {
    const { data } = await axios.get('/api/admin/quotations', { params: { q: q.value, page } });
    rows.value = data.data;
    meta.value = data.meta;
}

async function destroy(id) {
    const ok = await Swal.fire({ title: 'Delete quotation?', icon: 'warning', showCancelButton: true });
    if (!ok.isConfirmed) return;
    await axios.delete(`/api/admin/quotations/${id}`);
    load(meta.value.current_page);
}

onMounted(() => load());
</script>
