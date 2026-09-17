<template>
    <div class="min-vh-100 py-4">
        <div class="container" style="max-width: 1100px">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h4 class="mb-0">Guest quotation</h4>
                    <p class="text-muted mb-0">Create a quotation without signing in.</p>
                </div>
                <router-link class="btn btn-outline-secondary" :to="{ name: 'login' }">Back to login</router-link>
            </div>

            <div class="page-card p-4">
                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Date & time</label>
                        <input v-model="form.document_datetime" type="datetime-local" class="form-control">
                    </div>
                </div>

                <LineItems
                    :items="form.items"
                    :products="products"
                    :taxes="taxes"
                    quotation-mode
                />

                <div class="row mt-3">
                    <div class="col-md-6">
                        <label class="form-label">Notes</label>
                        <textarea v-model="form.notes" class="form-control"></textarea>
                    </div>
                    <div class="col-md-6">
                        <div class="page-card p-3 border">
                            <div>Subtotal: {{ money(totals.subtotal) }}</div>
                            <div>Discount: {{ money(totals.discount) }}</div>
                            <div v-for="tax in totals.taxes" :key="tax.name">{{ tax.name }} ({{ tax.rate_percent }}%): {{ money(tax.amount) }}</div>
                            <div class="fw-bold">Total: {{ money(totals.total) }}</div>
                        </div>
                    </div>
                </div>

                <div v-if="error" class="alert alert-danger mt-3">{{ error }}</div>
                <div v-if="success" class="alert alert-success mt-3">{{ success }}</div>

                <div class="d-flex gap-2 mt-3">
                    <button type="button" class="btn btn-primary" :disabled="saving" @click="openModal('save')">Save</button>
                    <button type="button" class="btn btn-outline-dark" :disabled="saving" @click="openModal('print')">Print</button>
                </div>
            </div>
        </div>

        <div v-if="showModal" class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,.45)">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form @submit.prevent="submit">
                        <div class="modal-header">
                            <h5 class="modal-title">Your contact details</h5>
                            <button type="button" class="btn-close" @click="showModal = false"></button>
                        </div>
                        <div class="modal-body">
                            <p class="text-muted">Enter email and phone so we can attach this quotation to your account if it already exists.</p>
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input v-model="contact.email" type="email" class="form-control" required>
                            </div>
                            <div class="mb-0">
                                <label class="form-label">Phone</label>
                                <input v-model="contact.phone" class="form-control" required>
                            </div>
                            <div v-if="modalError" class="alert alert-danger mt-3 mb-0">{{ modalError }}</div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" @click="showModal = false">Cancel</button>
                            <button class="btn btn-primary" :disabled="saving">{{ saving ? 'Saving...' : (intent === 'print' ? 'Save & print' : 'Save quotation') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import axios from 'axios';
import { computed, onMounted, reactive, ref, watch } from 'vue';
import LineItems from '../../components/LineItems.vue';
import { quotationTotals } from '../../utils/quotationMath';

const products = ref([]);
const taxes = ref([]);
const error = ref('');
const success = ref('');
const modalError = ref('');
const saving = ref(false);
const showModal = ref(false);
const intent = ref('save');
const contact = reactive({ email: '', phone: '' });
const form = reactive({
    document_datetime: new Date().toISOString().slice(0, 16),
    notes: '',
    items: [{ key: 1, product_id: 0, quantity: 1, unit_price: 0, unit_cost: 0, discount: 0, tax_id: null }],
});

const totals = computed(() => quotationTotals(
    form.items.filter((item) => item.product_id),
    taxes.value,
));

onMounted(async () => {
    await axios.get('/sanctum/csrf-cookie');
    const [productRes, taxRes] = await Promise.all([
        axios.get('/api/guest/products'),
        axios.get('/api/guest/taxes'),
    ]);
    products.value = productRes.data.data;
    taxes.value = taxRes.data.data;
});

watch(() => form.items, () => {
    error.value = '';
}, { deep: true });

function money(value) {
    return Number(value || 0).toFixed(2);
}

function openModal(nextIntent) {
    error.value = '';
    success.value = '';
    modalError.value = '';
    const items = form.items.filter((item) => item.product_id);
    if (!items.length) {
        error.value = 'Add at least one product.';
        return;
    }
    intent.value = nextIntent;
    showModal.value = true;
}

async function submit() {
    modalError.value = '';
    saving.value = true;
    try {
        const { data } = await axios.post('/api/guest/quotations', {
            email: contact.email,
            phone: contact.phone,
            document_datetime: form.document_datetime,
            notes: form.notes,
            items: form.items.filter((item) => item.product_id).map((item) => ({
                product_id: item.product_id,
                quantity: item.quantity,
                unit_price: item.unit_price,
                discount: item.discount || 0,
                tax_id: item.tax_id || null,
            })),
        });
        showModal.value = false;
        success.value = `Quotation ${data.data.number} saved.`;
        if (intent.value === 'print' && data.pdf_url) {
            window.open(data.pdf_url, '_blank');
        }
        form.items = [{ key: Date.now(), product_id: 0, quantity: 1, unit_price: 0, unit_cost: 0, discount: 0, tax_id: null }];
        form.notes = '';
    } catch (e) {
        modalError.value = Object.values(e.response?.data?.errors || {}).flat().join(' ') || e.response?.data?.message || 'Save failed';
    } finally {
        saving.value = false;
    }
}
</script>
