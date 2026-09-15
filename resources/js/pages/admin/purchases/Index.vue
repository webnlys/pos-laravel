<template>
    <PageShell title="Purchases" :create-to="{ name: 'admin.purchases.create' }">
        <form class="d-flex align-items-end gap-3 flex-wrap mb-3" @submit.prevent="load(1)">
            <div>
                <label class="form-label">Search</label>
                <input v-model="q" class="form-control">
            </div>
            <button class="btn btn-primary">Search</button>
        </form>
        <table class="table table-bordered">
            <thead><tr><th>Number</th><th>Date</th><th>Supplier</th><th>Total</th><th></th></tr></thead>
            <tbody>
                <tr v-for="row in rows" :key="row.id">
                    <td>{{ row.number }}</td>
                    <td>{{ String(row.document_datetime).slice(0, 16) }}</td>
                    <td>{{ row.supplier?.name }}</td>
                    <td>{{ row.total }}</td>
                    <td>
                        <div class="d-flex gap-2">
                            <router-link class="btn btn-outline-info btn-sm" :to="{ name: 'admin.purchases.edit', params: { id: row.id } }">Edit</router-link>
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
    const { data } = await axios.get('/api/admin/purchases', { params: { q: q.value, page } });
    rows.value = data.data;
    meta.value = data.meta;
}

async function destroy(id) {
    const ok = await Swal.fire({ title: 'Delete purchase? Stock will be reversed.', icon: 'warning', showCancelButton: true });
    if (!ok.isConfirmed) return;
    try {
        await axios.delete(`/api/admin/purchases/${id}`);
        load(meta.value.current_page);
    } catch (e) {
        Swal.fire('Error', e.response?.data?.message || 'Could not delete', 'error');
    }
}

onMounted(() => load());
</script>
