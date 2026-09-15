<template>
    <PageShell title="Products" :create-to="{ name: 'admin.products.create' }">
        <form class="d-flex align-items-end gap-3 flex-wrap mb-3" @submit.prevent="load(1)">
            <div>
                <label class="form-label">Search</label>
                <input v-model="q" class="form-control" placeholder="Name or SKU">
            </div>
            <button class="btn btn-primary">Search</button>
        </form>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>SKU</th>
                    <th>Sale</th>
                    <th>Cost</th>
                    <th>Stock</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="row in rows" :key="row.id">
                    <td>{{ row.name }}</td>
                    <td>{{ row.sku }}</td>
                    <td>{{ row.sale_price }}</td>
                    <td>{{ row.cost_price }}</td>
                    <td>{{ row.stock_qty }}</td>
                    <td>
                        <span class="badge rounded-pill" :class="row.is_active ? 'text-bg-success' : 'text-bg-secondary'">
                            {{ row.is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </td>
                    <td>
                        <div class="d-flex gap-2">
                            <router-link class="btn btn-outline-info btn-sm" :to="{ name: 'admin.products.edit', params: { id: row.id } }">Edit</router-link>
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
    const { data } = await axios.get('/api/admin/products', { params: { q: q.value, page } });
    rows.value = data.data;
    meta.value = data.meta;
}

async function destroy(id) {
    const ok = await Swal.fire({ title: 'Delete product?', icon: 'warning', showCancelButton: true });
    if (!ok.isConfirmed) return;
    await axios.delete(`/api/admin/products/${id}`);
    load(meta.value.current_page);
}

onMounted(() => load());
</script>
