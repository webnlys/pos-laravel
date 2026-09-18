<template>
    <PageShell title="Units" :create-to="{ name: 'admin.units.create' }">
        <p class="text-muted">Units such as Piece, Kg, or Liter. Products and quotation lines can pick a unit, or type a new one to add it here.</p>
        <form class="filter-form mb-3" @submit.prevent="load(1)">
            <div class="filter-field">
                <label class="form-label">Search</label>
                <input v-model="q" class="form-control" placeholder="Unit name">
            </div>
            <button class="btn btn-primary">Search</button>
        </form>
        <div class="table-wrap">
            <table class="table table-bordered stack-table">
                <thead>
                    <tr><th>Name</th><th></th></tr>
                </thead>
                <tbody>
                    <tr v-if="!rows.length">
                        <td colspan="2" class="text-center text-muted">No units found.</td>
                    </tr>
                    <tr v-for="row in rows" :key="row.id">
                        <td data-label="Name">{{ row.name }}</td>
                        <td class="stack-actions">
                            <div class="mobile-actions">
                                <router-link class="btn btn-outline-info btn-sm" :to="{ name: 'admin.units.edit', params: { id: row.id } }">Edit</router-link>
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
    const { data } = await axios.get('/api/admin/units', { params: { q: q.value, page, per_page: perPage.value } });
    rows.value = data.data;
    meta.value = data.meta || {};
}

async function destroy(id) {
    const ok = await Swal.fire({ title: 'Delete unit?', icon: 'warning', showCancelButton: true });
    if (!ok.isConfirmed) return;
    await axios.delete(`/api/admin/units/${id}`);
    load(meta.value.current_page);
}

onMounted(() => load());
</script>
