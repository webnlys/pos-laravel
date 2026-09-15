<template>
    <PageShell title="Taxes" :create-to="{ name: 'admin.taxes.create' }">
        <p class="text-muted">Rates apply to quotations and sales whose document date falls inside the window.</p>
        <table class="table table-bordered">
            <thead><tr><th>Name</th><th>Rate %</th><th>From</th><th>To</th><th></th></tr></thead>
            <tbody>
                <tr v-for="row in rows" :key="row.id">
                    <td>{{ row.name }}</td>
                    <td>{{ row.rate_percent }}</td>
                    <td>{{ formatDate(row.effective_from) }}</td>
                    <td>{{ row.effective_to ? formatDate(row.effective_to) : 'Open' }}</td>
                    <td>
                        <div class="d-flex gap-2">
                            <router-link class="btn btn-outline-info btn-sm" :to="{ name: 'admin.taxes.edit', params: { id: row.id } }">Edit</router-link>
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

function formatDate(value) {
    return value ? String(value).replace('T', ' ').slice(0, 16) : '';
}

async function load(page = 1) {
    const { data } = await axios.get('/api/admin/taxes', { params: { page } });
    rows.value = data.data;
    meta.value = data.meta;
}

async function destroy(id) {
    const ok = await Swal.fire({ title: 'Delete tax?', icon: 'warning', showCancelButton: true });
    if (!ok.isConfirmed) return;
    await axios.delete(`/api/admin/taxes/${id}`);
    load(meta.value.current_page);
}

onMounted(() => load());
</script>
