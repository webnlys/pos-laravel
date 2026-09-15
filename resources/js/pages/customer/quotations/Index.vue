<template>
    <PageShell title="My quotations" :create-to="{ name: 'customer.quotations.create' }">
        <table class="table table-bordered">
            <thead><tr><th>Number</th><th>Date</th><th>Total</th><th>Status</th><th></th></tr></thead>
            <tbody>
                <tr v-for="row in rows" :key="row.id">
                    <td>{{ row.number }}</td>
                    <td>{{ String(row.document_datetime).slice(0, 16) }}</td>
                    <td>{{ row.total }}</td>
                    <td><span class="badge rounded-pill text-bg-secondary">{{ row.status }}</span></td>
                    <td>
                        <div class="d-flex gap-2">
                            <a class="btn btn-outline-dark btn-sm" :href="`/api/customer/quotations/${row.id}/pdf`" target="_blank">PDF</a>
                            <router-link v-if="!row.converted_sale_id" class="btn btn-outline-info btn-sm" :to="{ name: 'customer.quotations.edit', params: { id: row.id } }">Edit</router-link>
                            <button v-if="!row.converted_sale_id" class="btn btn-outline-success btn-sm" @click="convert(row.id)">Convert to sale</button>
                            <button v-if="!row.converted_sale_id" class="btn btn-outline-danger btn-sm" @click="destroy(row.id)">Delete</button>
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
import { useRouter } from 'vue-router';
import PageShell from '../../../components/PageShell.vue';
import PaginationBar from '../../../components/PaginationBar.vue';

const router = useRouter();
const rows = ref([]);
const meta = ref({});

async function load(page = 1) {
    const { data } = await axios.get('/api/customer/quotations', { params: { page } });
    rows.value = data.data;
    meta.value = data.meta;
}

async function destroy(id) {
    const ok = await Swal.fire({ title: 'Delete quotation?', icon: 'warning', showCancelButton: true });
    if (!ok.isConfirmed) return;
    await axios.delete(`/api/customer/quotations/${id}`);
    load(meta.value.current_page);
}

async function convert(id) {
    const ok = await Swal.fire({ title: 'Convert to sale? Stock will be deducted.', icon: 'question', showCancelButton: true });
    if (!ok.isConfirmed) return;
    try {
        await axios.post(`/api/customer/quotations/${id}/convert`);
        router.push({ name: 'customer.sales' });
    } catch (e) {
        Swal.fire('Error', Object.values(e.response?.data?.errors || {}).flat().join(' ') || e.response?.data?.message || 'Failed', 'error');
    }
}

onMounted(() => load());
</script>
