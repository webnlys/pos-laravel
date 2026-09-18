<template>
    <PageShell title="Products" :create-to="{ name: 'admin.products.create' }">
        <form class="filter-form mb-3" @submit.prevent="load(1)">
            <div class="filter-field">
                <label class="form-label">Search</label>
                <input v-model="q" class="form-control" placeholder="Name or SKU">
            </div>
            <button class="btn btn-primary">Search</button>
        </form>
        <div class="table-wrap">
            <table class="table table-bordered stack-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>SKU</th>
                        <th>Unit</th>
                        <th>Sale</th>
                        <th>Cost</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="!rows.length">
                        <td colspan="7" class="text-center text-muted">No products found.</td>
                    </tr>
                    <tr v-for="row in rows" :key="row.id">
                        <td data-label="Name">{{ row.name }}</td>
                        <td data-label="SKU">{{ row.sku }}</td>
                        <td data-label="Unit">{{ row.unit_name || '—' }}</td>
                        <td data-label="Sale">{{ money(row.sale_price) }}</td>
                        <td data-label="Cost">{{ money(row.cost_price) }}</td>
                        <td data-label="Status">
                            <span class="badge rounded-pill" :class="row.is_active ? 'text-bg-success' : 'text-bg-secondary'">
                                {{ row.is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td class="stack-actions">
                            <div class="mobile-actions">
                                <router-link class="btn btn-outline-info btn-sm" :to="{ name: 'admin.products.edit', params: { id: row.id } }">Edit</router-link>
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
import { onMounted, ref } from 'vue';
import Swal from 'sweetalert2';
import PageShell from '../../../components/PageShell.vue';
import PaginationBar from '../../../components/PaginationBar.vue';
import { useSettingsStore } from '../../../stores/settings';

const settings = useSettingsStore();
const rows = ref([]);
const meta = ref({});
const q = ref('');
const perPage = ref(15);

function money(value) {
    return settings.formatMoney(value);
}

async function load(page = 1) {
    const { data } = await axios.get('/api/admin/products', { params: { q: q.value, page, per_page: perPage.value } });
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
