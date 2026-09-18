<template>
    <PageShell title="Payments received" :create-to="{ name: 'admin.payments.create' }">
        <form class="d-flex align-items-end gap-3 flex-wrap mb-3" @submit.prevent="load(1)">
            <div>
                <label class="form-label">Search</label>
                <input v-model="q" class="form-control">
            </div>
            <button class="btn btn-primary">Search</button>
        </form>
        <table class="table table-bordered">
            <thead><tr><th>Number</th><th>Date</th><th>Customer</th><th>Amount</th><th>Method</th><th>Invoice</th><th></th></tr></thead>
            <tbody>
                <tr v-if="!rows.length">
                    <td colspan="7" class="text-center text-muted">No payments found.</td>
                </tr>
                <tr v-for="row in rows" :key="row.id">
                    <td>{{ row.number }}</td>
                    <td>{{ String(row.paid_at).slice(0, 16) }}</td>
                    <td>{{ row.customer?.name }}</td>
                    <td>{{ row.amount }}</td>
                    <td>{{ row.method }}</td>
                    <td>{{ row.sale?.number || '-' }}</td>
                    <td>
                        <div class="d-flex gap-2">
                            <router-link class="btn btn-outline-info btn-sm" :to="{ name: 'admin.payments.edit', params: { id: row.id } }">Edit</router-link>
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
import { onMounted, ref } from 'vue';
import Swal from 'sweetalert2';
import PageShell from '../../../components/PageShell.vue';
import PaginationBar from '../../../components/PaginationBar.vue';

const rows = ref([]);
const meta = ref({});
const q = ref('');
const perPage = ref(15);

async function load(page = 1) {
    const { data } = await axios.get('/api/admin/payments', { params: { q: q.value, page, per_page: perPage.value } });
    rows.value = data.data;
    meta.value = data.meta;
}

async function destroy(id) {
    const ok = await Swal.fire({ title: 'Delete payment?', icon: 'warning', showCancelButton: true });
    if (!ok.isConfirmed) return;
    await axios.delete(`/api/admin/payments/${id}`);
    load(meta.value.current_page);
}

onMounted(() => load());
</script>
