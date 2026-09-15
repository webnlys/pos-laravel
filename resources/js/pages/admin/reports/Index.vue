<template>
    <div class="page-card overflow-hidden">
        <div class="row g-0">
            <aside class="col-md-3 report-sidebar p-3 no-print">
                <h5 class="mb-3">Reports</h5>
                <div class="d-grid gap-1 mb-3">
                    <button
                        v-for="tab in tabs"
                        :key="tab.key"
                        class="btn btn-sm text-start"
                        :class="tab.key === active ? 'btn-primary' : 'btn-outline-secondary'"
                        @click="active = tab.key"
                    >{{ tab.label }}</button>
                </div>
                <form @submit.prevent="generate">
                    <div class="mb-2">
                        <label class="form-label">Start date</label>
                        <input v-model="filters.start_date" type="date" class="form-control">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">End date</label>
                        <input v-model="filters.end_date" type="date" class="form-control">
                    </div>
                    <div v-if="active === 'purchase'" class="mb-2">
                        <label class="form-label">Supplier</label>
                        <select v-model="filters.supplier_id" class="form-select">
                            <option value="">All</option>
                            <option v-for="s in suppliers" :key="s.id" :value="s.id">{{ s.name }}</option>
                        </select>
                    </div>
                    <div v-if="active === 'sales'" class="mb-2">
                        <label class="form-label">Customer</label>
                        <select v-model="filters.customer_id" class="form-select">
                            <option value="">All</option>
                            <option v-for="c in customers" :key="c.id" :value="c.id">{{ c.name }}</option>
                        </select>
                    </div>
                    <div v-if="['purchase','sales','item-profit-loss'].includes(active)" class="mb-2">
                        <label class="form-label">Product</label>
                        <select v-model="filters.product_id" class="form-select">
                            <option value="">All</option>
                            <option v-for="p in products" :key="p.id" :value="p.id">{{ p.name }}</option>
                        </select>
                    </div>
                    <div v-if="active === 'product-list'" class="mb-2">
                        <label class="form-label">Search</label>
                        <input v-model="filters.q" class="form-control">
                    </div>
                    <button class="btn btn-primary w-100">Generate Report</button>
                </form>
            </aside>
            <div class="col-md-9 p-3 report-print">
                <div class="d-flex justify-content-between align-items-center mb-3 no-print">
                    <h4 class="mb-0">{{ report.title || 'Select a report' }}</h4>
                    <div class="d-flex gap-2">
                        <button class="btn btn-outline-secondary btn-sm" @click="print">Print / PDF</button>
                        <a class="btn btn-outline-secondary btn-sm" :href="exportUrl('csv')">CSV</a>
                        <a class="btn btn-outline-secondary btn-sm" :href="exportUrl('xlsx')">XLSX</a>
                    </div>
                </div>
                <p v-if="report.start_date" class="text-muted">{{ report.start_date }} to {{ report.end_date }}</p>
                <table v-if="report.rows" class="table table-bordered report-table">
                    <thead>
                        <tr>
                            <th v-for="col in report.columns" :key="col">{{ col }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(row, i) in report.rows" :key="i">
                            <td v-for="(val, key) in row" :key="key">{{ val }}</td>
                        </tr>
                        <tr v-if="!report.rows.length">
                            <td :colspan="report.columns.length">No data</td>
                        </tr>
                    </tbody>
                    <tfoot v-if="report.totals">
                        <tr class="fw-bold">
                            <td>Totals</td>
                            <td :colspan="Math.max(report.columns.length - 1, 1)">
                                <span v-for="(val, key) in report.totals" :key="key" class="me-3">{{ key }}: {{ val }}</span>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</template>

<script setup>
import axios from 'axios';
import { onMounted, reactive, ref } from 'vue';

const tabs = [
    { key: 'purchase', label: 'Purchase' },
    { key: 'sales', label: 'Sales' },
    { key: 'invoice-profit-loss', label: 'Invoice P&L' },
    { key: 'item-profit-loss', label: 'Item P&L' },
    { key: 'total-profit-loss', label: 'Total P&L' },
    { key: 'product-list', label: 'Product List' },
    { key: 'stock-summary', label: 'Stock Summary' },
    { key: 'valuation', label: 'Valuation' },
];

const today = new Date().toISOString().slice(0, 10);
const active = ref('sales');
const report = ref({});
const products = ref([]);
const customers = ref([]);
const suppliers = ref([]);
const filters = reactive({
    start_date: today,
    end_date: today,
    supplier_id: '',
    customer_id: '',
    product_id: '',
    q: '',
});

onMounted(async () => {
    const [p, c, s] = await Promise.all([
        axios.get('/api/admin/products', { params: { per_page: 100 } }),
        axios.get('/api/admin/customers', { params: { per_page: 100 } }),
        axios.get('/api/admin/suppliers', { params: { per_page: 100 } }),
    ]);
    products.value = p.data.data;
    customers.value = c.data.data;
    suppliers.value = s.data.data;
});

function params() {
    return {
        start_date: filters.start_date,
        end_date: filters.end_date,
        supplier_id: filters.supplier_id || undefined,
        customer_id: filters.customer_id || undefined,
        product_id: filters.product_id || undefined,
        q: filters.q || undefined,
    };
}

async function generate() {
    const { data } = await axios.get(`/api/admin/reports/${active.value}`, { params: params() });
    report.value = data;
}

function exportUrl(type) {
    const query = new URLSearchParams({ ...params(), exportType: type });
    return `/api/admin/reports/${active.value}?${query.toString()}`;
}

function print() {
    window.print();
}
</script>
