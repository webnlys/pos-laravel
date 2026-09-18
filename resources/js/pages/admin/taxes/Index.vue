<template>
    <PageShell title="Taxes" :create-to="{ name: 'admin.taxes.create' }">
        <p class="text-muted">Select a tax type on each quotation line. The listed rate is applied to that product after discount.</p>
        <form class="filter-form mb-3" @submit.prevent="load(1)">
            <div class="filter-field">
                <label class="form-label">Search</label>
                <input v-model="q" class="form-control" placeholder="Tax name">
            </div>
            <button class="btn btn-primary">Search</button>
        </form>
        <div class="table-wrap">
            <table class="table table-bordered stack-table">
                <thead><tr><th>Name</th><th>Rate %</th><th>From</th><th>To</th><th></th></tr></thead>
                <tbody>
                    <tr v-if="!rows.length">
                        <td colspan="5" class="text-center text-muted">No taxes found.</td>
                    </tr>
                    <tr v-for="row in rows" :key="row.id">
                        <td data-label="Name">{{ row.name }}</td>
                        <td data-label="Rate %">{{ row.rate_percent }}</td>
                        <td data-label="From">{{ formatDate(row.effective_from) }}</td>
                        <td data-label="To">{{ row.effective_to ? formatDate(row.effective_to) : 'Open' }}</td>
                        <td class="stack-actions">
                            <div class="mobile-actions">
                                <router-link class="btn btn-outline-info btn-sm" :to="{ name: 'admin.taxes.edit', params: { id: row.id } }">Edit</router-link>
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
import Swal from 'sweetalert2';
import PageShell from '../../../components/PageShell.vue';
import PaginationBar from '../../../components/PaginationBar.vue';
import { usePagedList } from '../../../composables/usePagedList';

const { rows, meta, q, perPage, load } = usePagedList('/api/admin/taxes');

function formatDate(value) {
    return value ? String(value).replace('T', ' ').slice(0, 16) : '';
}

async function destroy(id) {
    const ok = await Swal.fire({ title: 'Delete tax?', icon: 'warning', showCancelButton: true });
    if (!ok.isConfirmed) return;
    await axios.delete(`/api/admin/taxes/${id}`);
    load(meta.value.current_page);
}
</script>
