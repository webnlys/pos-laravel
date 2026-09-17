<template>
    <PageShell :title="title">
        <form @submit.prevent="save">
            <div class="row g-3 mb-3">
                <div v-if="kind === 'purchase'" class="col-md-4">
                    <label class="form-label">Supplier</label>
                    <select v-model.number="form.supplier_id" class="form-select" required>
                        <option :value="0">Select</option>
                        <option v-for="s in suppliers" :key="s.id" :value="s.id">{{ s.name }}</option>
                    </select>
                </div>
                <div v-else-if="role === 'admin'" class="col-md-4">
                    <label class="form-label">Customer</label>
                    <select v-model.number="form.customer_id" class="form-select" required>
                        <option :value="0">Select</option>
                        <option v-for="c in customers" :key="c.id" :value="c.id">{{ c.name }}</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Date & time</label>
                    <input v-model="form.document_datetime" type="datetime-local" class="form-control" @change="previewTax">
                </div>
                <div v-if="kind !== 'purchase' && kind !== 'quotation'" class="col-md-4">
                    <label class="form-label">Discount</label>
                    <input v-model.number="form.discount" type="number" min="0" step="0.01" class="form-control" @change="previewTax">
                </div>
            </div>

            <LineItems
                :items="form.items"
                :products="products"
                :taxes="taxes"
                :show-cost="kind === 'purchase'"
                :quotation-mode="kind === 'quotation'"
            />

            <div class="row mt-3">
                <div class="col-md-6">
                    <label class="form-label">Notes</label>
                    <textarea v-model="form.notes" class="form-control"></textarea>
                </div>
                <div v-if="kind !== 'purchase'" class="col-md-6">
                    <div class="page-card p-3">
                        <div>Subtotal: {{ money(totals.subtotal) }}</div>
                        <div>Discount: {{ money(totals.discount) }}</div>
                        <div v-for="tax in totals.taxes" :key="tax.name">{{ tax.name }} ({{ tax.rate_percent }}%): {{ money(tax.amount) }}</div>
                        <div class="fw-bold">Total: {{ money(totals.total) }}</div>
                    </div>
                </div>
            </div>

            <div v-if="error" class="alert alert-danger mt-3">{{ error }}</div>
            <div class="d-flex gap-2 mt-3">
                <button class="btn btn-primary" :disabled="saving">{{ saving ? 'Saving...' : 'Save' }}</button>
                <a v-if="id && kind === 'quotation'" class="btn btn-outline-dark" :href="`${resource}/${id}/pdf`" target="_blank">Print</a>
            </div>
        </form>
    </PageShell>
</template>

<script setup>
import axios from 'axios';
import { onMounted, reactive, ref, watch } from 'vue';
import { useRouter } from 'vue-router';
import LineItems from './LineItems.vue';
import PageShell from './PageShell.vue';

const props = defineProps({
    kind: { type: String, required: true },
    role: { type: String, default: 'admin' },
    id: { type: [String, Number], default: null },
    title: { type: String, required: true },
});

const router = useRouter();
const products = ref([]);
const customers = ref([]);
const suppliers = ref([]);
const taxes = ref([]);
const error = ref('');
const saving = ref(false);
const totals = ref({ subtotal: 0, discount: 0, tax_total: 0, total: 0, taxes: [] });
const form = reactive({
    customer_id: 0,
    supplier_id: 0,
    document_datetime: new Date().toISOString().slice(0, 16),
    discount: 0,
    notes: '',
    items: [{ key: 1, product_id: 0, quantity: 1, unit_price: 0, unit_cost: 0, discount: 0, tax_id: null }],
});

const base = props.role === 'admin' ? '/api/admin' : '/api/customer';
const resource = `${base}/${props.kind === 'purchase' ? 'purchases' : props.kind === 'sale' ? 'sales' : 'quotations'}`;

onMounted(async () => {
    const productUrl = props.role === 'admin' ? '/api/admin/products' : '/api/customer/products';
    const [{ data: productData }] = await Promise.all([
        axios.get(productUrl, { params: { per_page: 100 } }),
        props.role === 'admin' && props.kind !== 'purchase'
            ? axios.get('/api/admin/customers', { params: { per_page: 100 } }).then((r) => { customers.value = r.data.data; })
            : Promise.resolve(),
        props.kind === 'purchase'
            ? axios.get('/api/admin/suppliers', { params: { per_page: 100 } }).then((r) => { suppliers.value = r.data.data; })
            : Promise.resolve(),
        props.kind === 'quotation'
            ? axios.get('/api/taxes').then((r) => { taxes.value = r.data.data; })
            : Promise.resolve(),
    ]);
    products.value = productData.data;

    if (props.id) {
        const { data } = await axios.get(`${resource}/${props.id}`);
        const doc = data.data;
        form.customer_id = doc.customer_id || 0;
        form.supplier_id = doc.supplier_id || 0;
        form.document_datetime = String(doc.document_datetime).slice(0, 16);
        form.discount = doc.discount || 0;
        form.notes = doc.notes || '';
        form.items = (doc.items || []).map((item, i) => ({
            key: i + 1,
            product_id: item.product_id,
            quantity: item.quantity,
            unit_price: Number(item.unit_price || item.unit_cost || 0),
            unit_cost: Number(item.unit_cost || 0),
            discount: Number(item.discount || 0),
            tax_id: item.tax_id ?? null,
        }));
    }

    previewTax();
});

watch(() => form.items, previewTax, { deep: true });

function money(value) {
    return Number(value || 0).toFixed(2);
}

async function previewTax() {
    if (props.kind === 'purchase') return;
    const items = form.items.filter((i) => i.product_id && i.quantity);
    if (!items.length) return;
    const { data } = await axios.post('/api/tax-preview', {
        items: items.map((i) => ({
            quantity: i.quantity,
            unit_price: i.unit_price,
            discount: i.discount || 0,
            tax_id: i.tax_id || null,
        })),
    });
    totals.value = data;
}

async function save() {
    error.value = '';
    saving.value = true;
    try {
        const payload = {
            document_datetime: form.document_datetime,
            notes: form.notes,
            items: form.items.filter((i) => i.product_id).map((i) => ({
                product_id: i.product_id,
                quantity: i.quantity,
                unit_price: i.unit_price,
                unit_cost: i.unit_cost,
                discount: i.discount || 0,
                tax_id: i.tax_id || null,
            })),
        };
        if (props.kind !== 'quotation') payload.discount = form.discount || 0;
        if (props.kind === 'purchase') payload.supplier_id = form.supplier_id;
        else if (props.role === 'admin') payload.customer_id = form.customer_id;

        if (props.id) await axios.put(`${resource}/${props.id}`, payload);
        else await axios.post(resource, payload);

        const listName = props.role === 'admin'
            ? (props.kind === 'purchase' ? 'admin.purchases' : props.kind === 'sale' ? 'admin.sales' : 'admin.quotations')
            : (props.kind === 'sale' ? 'customer.sales' : 'customer.quotations');
        router.push({ name: listName });
    } catch (e) {
        error.value = Object.values(e.response?.data?.errors || {}).flat().join(' ') || e.response?.data?.message || 'Save failed';
    } finally {
        saving.value = false;
    }
}
</script>
