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
                    <tr><th>Name</th><th>Phone</th><th>Email</th><th></th></tr>
                </thead>
                <tbody>
                    <tr v-if="!rows.length">
                        <td colspan="4" class="text-center text-muted">No customers found.</td>
                    </tr>
                    <tr v-for="row in rows" :key="row.id">
                        <td data-label="Name">{{ row.name }}</td>
                        <td data-label="Phone">{{ row.phone }}</td>
                        <td data-label="Email">{{ row.email }}</td>
                        <td class="stack-actions">
                            <div class="mobile-actions">
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
import { onMounted, ref } from 'vue';
import Swal from 'sweetalert2';
import PageShell from '../../../components/PageShell.vue';
import PaginationBar from '../../../components/PaginationBar.vue';

const rows = ref([]);
const meta = ref({});
const q = ref('');
const perPage = ref(15);

async function load(page = 1) {
    const { data } = await axios.get('/api/admin/customers', { params: { q: q.value, page, per_page: perPage.value } });
    rows.value = data.data;
    meta.value = data.meta;
}

async function destroy(id) {
    const ok = await Swal.fire({ title: 'Delete customer?', icon: 'warning', showCancelButton: true });
    if (!ok.isConfirmed) return;
    await axios.delete(`/api/admin/customers/${id}`);
    load(meta.value.current_page);
}

onMounted(() => load());
</script>
