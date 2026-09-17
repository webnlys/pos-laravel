<template>
    <PageShell title="My quotations" :create-to="{ name: 'customer.quotations.create' }">
        <div class="table-wrap">
            <table class="table table-bordered stack-table">
                <thead><tr><th>Number</th><th>Date</th><th>Total</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    <tr v-for="row in rows" :key="row.id">
                        <td data-label="Number">{{ row.number }}</td>
                        <td data-label="Date">{{ String(row.document_datetime).slice(0, 16) }}</td>
                        <td data-label="Total">{{ money(row.total) }}</td>
                        <td data-label="Status"><span class="badge rounded-pill text-bg-secondary">{{ row.status }}</span></td>
                        <td class="stack-actions">
                            <div class="mobile-actions">
                                <a class="btn btn-outline-dark btn-sm" :href="`/api/customer/quotations/${row.id}/pdf`" target="_blank">PDF</a>
                                <router-link class="btn btn-outline-info btn-sm" :to="{ name: 'customer.quotations.edit', params: { id: row.id } }">Edit</router-link>
                                <button class="btn btn-outline-danger btn-sm" @click="destroy(row.id)">Delete</button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <PaginationBar :meta="meta" @change="load" />
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

function money(value) {
    return settings.formatMoney(value);
}

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

onMounted(() => load());
</script>
