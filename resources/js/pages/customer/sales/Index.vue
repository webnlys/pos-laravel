<template>
    <PageShell title="My sales" :create-to="{ name: 'customer.sales.create' }">
        <table class="table table-bordered">
            <thead><tr><th>Number</th><th>Date</th><th>Total</th><th>Paid</th><th>Due</th><th></th></tr></thead>
            <tbody>
                <tr v-for="row in rows" :key="row.id">
                    <td>{{ row.number }}</td>
                    <td>{{ String(row.document_datetime).slice(0, 16) }}</td>
                    <td>{{ row.total }}</td>
                    <td>{{ row.paid }}</td>
                    <td>{{ row.due }}</td>
                    <td>
                        <div class="d-flex gap-2">
                            <a class="btn btn-outline-dark btn-sm" :href="`/api/customer/sales/${row.id}/pdf`" target="_blank">PDF</a>
                            <router-link class="btn btn-outline-info btn-sm" :to="{ name: 'customer.sales.edit', params: { id: row.id } }">Edit</router-link>
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

async function load(page = 1) {
    const { data } = await axios.get('/api/customer/sales', { params: { page } });
    rows.value = data.data;
    meta.value = data.meta;
}

async function destroy(id) {
    const ok = await Swal.fire({ title: 'Delete sale? Stock will be restored.', icon: 'warning', showCancelButton: true });
    if (!ok.isConfirmed) return;
    await axios.delete(`/api/customer/sales/${id}`);
    load(meta.value.current_page);
}

onMounted(() => load());
</script>
