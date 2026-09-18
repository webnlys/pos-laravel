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
                <div v-else class="col-md-4">
                    <label class="form-label">Customer</label>
                    <div class="field-with-action">
                        <select v-model.number="form.customer_id" class="form-select" required>
                            <option :value="0">Select</option>
                            <option v-for="c in customers" :key="c.id" :value="c.id">
                            {{ c.name }}<template v-if="kind === 'sale'"> (due {{ money(c.due) }}<template v-if="c.advance > 0">, adv {{ money(c.advance) }}</template>)</template>
                        </option>
                        </select>
                        <button
                            v-if="isLineDocument"
                            type="button"
                            class="btn btn-outline-primary add-customer-btn flex-shrink-0"
                            title="Add customer"
                            @click="openCustomerModal"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14" />
                            </svg>
                            Add
                        </button>
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Date & time</label>
                    <input v-model="form.document_datetime" type="datetime-local" class="form-control" @change="previewTax">
                </div>
                <div v-if="kind !== 'purchase' && !isLineDocument" class="col-md-4">
                    <label class="form-label">Discount</label>
                    <input v-model.number="form.discount" type="number" min="0" step="0.01" class="form-control" @change="previewTax">
                </div>
            </div>

            <LineItems
                :items="form.items"
                :products="products"
                :taxes="taxes"
                :units="units"
                :show-cost="kind === 'purchase'"
                :quotation-mode="isLineDocument"
                :show-tax-column="showLineTaxColumn"
                allow-create
                search-url="/api/admin/products"
            />

            <div v-if="kind === 'quotation'" class="row g-3 mt-1">
                <div class="col-md-4">
                    <label class="form-label">Tax</label>
                    <select v-model="form.tax_mode" class="form-select" @change="previewTax">
                        <option value="none">No tax</option>
                        <option value="per_item">Per item tax (VAT column)</option>
                        <option value="overall">Overall tax (on subtotal)</option>
                    </select>
                </div>
                <div v-if="form.tax_mode === 'overall'" class="col-md-4">
                    <label class="form-label">Overall tax</label>
                    <select v-model.number="form.overall_tax_id" class="form-select" @change="previewTax">
                        <option :value="null">Select tax</option>
                        <option v-for="tax in taxes" :key="tax.id" :value="tax.id">{{ tax.name }} ({{ tax.rate_percent }}%)</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Overall discount ({{ settings.currency }})</label>
                    <input v-model.number="form.overall_discount" type="text" inputmode="decimal" class="form-control" @change="previewTax">
                </div>
            </div>

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
                        <template v-if="kind === 'sale'">
                            <div>Paid: {{ money(paymentPreview.paid) }}</div>
                            <div>Due: {{ money(paymentPreview.due) }}</div>
                            <div v-if="paymentPreview.advance > 0">Advance: {{ money(paymentPreview.advance) }}</div>
                            <div class="mt-2"><PaymentBadge :status="paymentPreview.status" /></div>
                        </template>
                    </div>
                </div>
            </div>

            <div v-if="kind === 'sale'" class="page-card p-3 mt-3">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                    <strong>Receive payment</strong>
                    <div class="d-flex flex-wrap gap-2">
                        <button type="button" class="btn btn-outline-secondary btn-sm" @click="payDue">Pay due</button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" @click="payFull">Pay full</button>
                    </div>
                </div>
                <p class="text-muted mb-3">Enter cash received now. Less than the total is partial; more than the total is kept as advance.</p>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Amount received</label>
                        <input v-model.number="payment.amount" type="number" min="0" step="0.01" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Method</label>
                        <select v-model="payment.method" class="form-select">
                            <option value="cash">Cash</option>
                            <option value="bank">Bank</option>
                            <option value="cheque">Cheque</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Notes</label>
                        <input v-model="payment.notes" class="form-control" placeholder="Optional">
                    </div>
                </div>
                <p v-if="recordedPaid > 0" class="text-muted mt-2 mb-0">Already paid on this invoice: {{ money(recordedPaid) }}</p>
            </div>

            <div v-if="error" class="alert alert-danger mt-3">{{ error }}</div>
            <div class="form-actions mt-3">
                <button class="btn btn-primary" :disabled="saving">{{ saving ? 'Saving...' : 'Save' }}</button>
                <a v-if="id && isLineDocument" class="btn btn-outline-dark" :href="`${resource}/${id}/pdf`" target="_blank">Print</a>
                <button
                    v-if="kind === 'quotation' && id && !convertedSaleId"
                    type="button"
                    class="btn btn-outline-success"
                    :disabled="converting"
                    @click="convertToSale"
                >
                    {{ converting ? 'Converting...' : 'Convert to sale' }}
                </button>
            </div>
        </form>
    </PageShell>

    <div v-if="showCustomerModal" class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,.45)">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <form @submit.prevent="saveCustomer">
                    <div class="modal-header">
                        <h5 class="modal-title">Add customer</h5>
                        <button type="button" class="btn-close" @click="closeCustomerModal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Name</label>
                            <input v-model="customerForm.name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input v-model="customerForm.email" type="email" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Phone</label>
                            <input v-model="customerForm.phone" class="form-control">
                        </div>
                        <div class="mb-0">
                            <label class="form-label">Address</label>
                            <textarea v-model="customerForm.address" class="form-control"></textarea>
                        </div>
                        <div v-if="customerError" class="alert alert-danger mt-3 mb-0">{{ customerError }}</div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" @click="closeCustomerModal">Cancel</button>
                        <button class="btn btn-primary" :disabled="savingCustomer">{{ savingCustomer ? 'Saving...' : 'Save customer' }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import axios from 'axios';
import Swal from 'sweetalert2';
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { useRouter } from 'vue-router';
import LineItems from './LineItems.vue';
import PageShell from './PageShell.vue';
import PaymentBadge from './PaymentBadge.vue';
import { paymentSummary } from '../utils/paymentStatus';
import { useSettingsStore } from '../stores/settings';

const props = defineProps({
    kind: { type: String, required: true },
    id: { type: [String, Number], default: null },
    title: { type: String, required: true },
});

const router = useRouter();
const settings = useSettingsStore();
const products = ref([]);
const customers = ref([]);
const suppliers = ref([]);
const taxes = ref([]);
const units = ref([]);
const error = ref('');
const saving = ref(false);
const converting = ref(false);
const convertedSaleId = ref(null);
const recordedPaid = ref(0);
const payment = reactive({
    amount: 0,
    method: 'cash',
    notes: '',
});
const showCustomerModal = ref(false);
const savingCustomer = ref(false);
const customerError = ref('');
const totals = ref({ subtotal: 0, discount: 0, tax_total: 0, total: 0, taxes: [] });
const form = reactive({
    customer_id: 0,
    supplier_id: 0,
    document_datetime: new Date().toISOString().slice(0, 16),
    discount: 0,
    tax_mode: 'per_item',
    overall_discount: '',
    overall_tax_id: null,
    notes: '',
    items: [{ key: 1, product_id: 0, product_name: '', unit_id: 0, unit_name: '', quantity: 1, unit_price: '', unit_cost: 0, discount: '', tax_id: null }],
});
const customerForm = reactive({ name: '', phone: '', email: '', address: '' });

const isLineDocument = computed(() => props.kind === 'quotation' || props.kind === 'sale');
const showLineTaxColumn = computed(() => props.kind !== 'quotation' || form.tax_mode === 'per_item');
const resource = `/api/admin/${props.kind === 'purchase' ? 'purchases' : props.kind === 'sale' ? 'sales' : 'quotations'}`;
const paymentPreview = computed(() => paymentSummary(totals.value.total, recordedPaid.value + Number(payment.amount || 0)));

onMounted(async () => {
    const [{ data: productData }] = await Promise.all([
        axios.get('/api/admin/products', { params: { per_page: 100 } }),
        props.kind !== 'purchase'
            ? axios.get('/api/admin/customers', { params: { per_page: 100 } }).then((r) => { customers.value = r.data.data; })
            : Promise.resolve(),
        props.kind === 'purchase'
            ? axios.get('/api/admin/suppliers', { params: { per_page: 100 } }).then((r) => { suppliers.value = r.data.data; })
            : Promise.resolve(),
        isLineDocument.value
            ? axios.get('/api/taxes').then((r) => { taxes.value = r.data.data; })
            : Promise.resolve(),
        axios.get('/api/admin/units', { params: { per_page: 100 } }).then((r) => { units.value = r.data.data; }),
    ]);
    products.value = productData.data;

    if (props.id) {
        const { data } = await axios.get(`${resource}/${props.id}`);
        const doc = data.data;
        form.customer_id = doc.customer_id || 0;
        form.supplier_id = doc.supplier_id || 0;
        form.document_datetime = String(doc.document_datetime).slice(0, 16);
        form.discount = doc.discount || 0;
        if (props.kind === 'quotation') {
            form.tax_mode = doc.tax_mode || 'per_item';
            form.overall_discount = doc.overall_discount || '';
            form.overall_tax_id = doc.overall_tax_id || null;
        }
        form.notes = doc.notes || '';
        convertedSaleId.value = doc.converted_sale_id || null;
        recordedPaid.value = Number(doc.paid || 0);
        payment.amount = 0;
        payment.method = 'cash';
        payment.notes = '';
        form.items = (doc.items || []).map((item, i) => ({
            key: i + 1,
            product_id: item.product_id,
            product_name: item.product_name || '',
            unit_id: item.unit_id || 0,
            unit_name: item.unit_name || '',
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
    return settings.formatMoney(value);
}

function emptyCustomerForm() {
    Object.assign(customerForm, { name: '', phone: '', email: '', address: '' });
}

function openCustomerModal() {
    emptyCustomerForm();
    customerError.value = '';
    showCustomerModal.value = true;
}

function closeCustomerModal() {
    showCustomerModal.value = false;
    customerError.value = '';
    emptyCustomerForm();
}

async function saveCustomer() {
    customerError.value = '';
    savingCustomer.value = true;
    try {
        const { data } = await axios.post('/api/admin/customers', { ...customerForm });
        const customer = data.data;
        customers.value = [...customers.value.filter((c) => c.id !== customer.id), customer]
            .sort((a, b) => String(a.name).localeCompare(String(b.name)));
        form.customer_id = Number(customer.id);
        closeCustomerModal();
    } catch (e) {
        customerError.value = Object.values(e.response?.data?.errors || {}).flat().join(' ') || e.response?.data?.message || 'Save failed';
    } finally {
        savingCustomer.value = false;
    }
}

function filledItems() {
    return form.items.filter((item) => (item.product_id || String(item.product_name || '').trim()) && item.quantity);
}

async function previewTax() {
    if (props.kind === 'purchase') return;
    const items = filledItems();
    if (!items.length) return;
    const payload = {
        items: items.map((i) => ({
            quantity: i.quantity,
            unit_price: i.unit_price === '' ? 0 : i.unit_price,
            discount: i.discount || 0,
            tax_id: i.tax_id || null,
        })),
    };
    if (props.kind === 'quotation') {
        payload.tax_mode = form.tax_mode;
        payload.overall_discount = form.overall_discount || 0;
        payload.overall_tax_id = form.tax_mode === 'overall' ? (form.overall_tax_id || null) : null;
    }
    const { data } = await axios.post('/api/tax-preview', payload);
    totals.value = data;
}

function documentPayload() {
    const payload = {
        document_datetime: form.document_datetime,
        notes: form.notes,
        items: filledItems().map((i) => ({
            product_id: i.product_id || null,
            product_name: i.product_name || null,
            unit_id: i.unit_id || null,
            unit_name: i.unit_name || null,
            quantity: i.quantity,
            unit_price: i.unit_price === '' ? null : i.unit_price,
            unit_cost: i.unit_cost === '' ? null : i.unit_cost,
            discount: i.discount || 0,
            tax_id: i.tax_id || null,
        })),
    };
    if (!isLineDocument.value) payload.discount = form.discount || 0;
    if (props.kind === 'quotation') {
        payload.tax_mode = form.tax_mode;
        payload.overall_discount = form.overall_discount || 0;
        payload.overall_tax_id = form.tax_mode === 'overall' ? (form.overall_tax_id || null) : null;
    }
    if (props.kind === 'purchase') payload.supplier_id = form.supplier_id;
    else payload.customer_id = form.customer_id;
    if (props.kind === 'sale' && Number(payment.amount || 0) > 0) {
        payload.payment = {
            amount: payment.amount,
            method: payment.method,
            notes: payment.notes || null,
        };
    }
    return payload;
}

function remainingDue() {
    return Math.max(0, Math.round(((Number(totals.value.total) || 0) - recordedPaid.value) * 100) / 100);
}

function payDue() {
    payment.amount = remainingDue();
}

function payFull() {
    payment.amount = Math.max(0, Math.round(((Number(totals.value.total) || 0)) * 100) / 100);
}

async function save() {
    error.value = '';
    saving.value = true;
    try {
        const payload = documentPayload();
        if (props.id) await axios.put(`${resource}/${props.id}`, payload);
        else await axios.post(resource, payload);

        const listName = props.kind === 'purchase' ? 'admin.purchases' : props.kind === 'sale' ? 'admin.sales' : 'admin.quotations';
        router.push({ name: listName });
    } catch (e) {
        error.value = Object.values(e.response?.data?.errors || {}).flat().join(' ') || e.response?.data?.message || 'Save failed';
    } finally {
        saving.value = false;
    }
}

async function convertToSale() {
    const ok = await Swal.fire({
        title: 'Convert to sales invoice?',
        text: 'Current changes will be saved, then the sales invoice will open for review.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Convert',
    });
    if (!ok.isConfirmed) return;

    error.value = '';
    converting.value = true;
    try {
        await axios.put(`${resource}/${props.id}`, documentPayload());
        const { data } = await axios.post(`${resource}/${props.id}/convert`);
        convertedSaleId.value = data.data.id;
        await router.push({ name: 'admin.sales.edit', params: { id: data.data.id } });
    } catch (e) {
        error.value = Object.values(e.response?.data?.errors || {}).flat().join(' ') || e.response?.data?.message || 'Convert failed';
    } finally {
        converting.value = false;
    }
}
</script>

<style scoped>
.add-customer-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.4rem;
    min-height: 42px;
    padding: 0.5rem 0.85rem;
    font-size: 1rem;
    font-weight: 600;
    white-space: nowrap;
}
</style>
