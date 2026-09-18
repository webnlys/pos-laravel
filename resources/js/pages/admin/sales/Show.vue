<template>
    <PageShell :title="doc.number || 'Sales invoice'" :back-to="{ name: 'admin.sales' }">
        <div v-if="doc.id">
            <div class="row g-2 mb-3">
                <div class="col-12 col-md-4"><strong>Customer:</strong> {{ doc.customer?.name }}</div>
                <div class="col-12 col-md-4"><strong>Date:</strong> {{ String(doc.document_datetime).slice(0, 16) }}</div>
                <div class="col-12 col-md-4"><strong>Status:</strong> <PaymentBadge :status="doc.status" /></div>
            </div>
            <div class="table-wrap">
                <table class="table table-bordered stack-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Qty</th>
                            <th>Unit</th>
                            <th>Price ({{ settings.currency }})</th>
                            <th>Discount ({{ settings.currency }})</th>
                            <th>Tax</th>
                            <th>Amount ({{ settings.currency }})</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in doc.items" :key="item.id">
                            <td data-label="Product">{{ item.product_name }}</td>
                            <td data-label="Qty">{{ item.quantity }}</td>
                            <td data-label="Unit">{{ item.unit_name || '—' }}</td>
                            <td data-label="Price">{{ (item.unit_price) }}</td>
                            <td data-label="Discount">{{ (item.discount) }}</td>
                            <td data-label="Tax">{{ item.tax_name ? `${item.tax_name} (${item.tax_rate_percent}%)` : '—' }}</td>
                            <td data-label="Amount">{{ (item.line_total) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="totals-box mb-3">
                <div><span>Subtotal</span><span>{{ money(doc.subtotal) }}</span></div>
                <div><span>Discount</span><span>{{ money(doc.discount) }}</span></div>
                <div><span>Tax</span><span>{{ money(doc.tax_total) }}</span></div>
                <div class="fw-bold"><span>Total</span><span>{{ money(doc.total) }}</span></div>
                <div><span>Paid</span><span>{{ money(doc.paid) }}</span></div>
                <div><span>Due</span><span>{{ money(doc.due) }}</span></div>
                <div v-if="doc.advance > 0"><span>Advance</span><span>{{ money(doc.advance) }}</span></div>
            </div>

            <div class="page-card p-3 mb-3">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                    <strong>Receive payment</strong>
                    <button type="button" class="btn btn-outline-secondary btn-sm" @click="payment.amount = Number(doc.due || 0)">Pay due</button>
                </div>
                <p class="text-muted mb-3">Pay less for a partial invoice, or pay more to keep the extra as advance on this invoice.</p>
                <form class="row g-3 align-items-end" @submit.prevent="receivePayment">
                    <div class="col-md-4">
                        <label class="form-label">Amount</label>
                        <input v-model.number="payment.amount" type="number" min="0.01" step="0.01" class="form-control" required>
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
                        <button class="btn btn-primary w-100" :disabled="savingPayment">{{ savingPayment ? 'Saving...' : 'Receive' }}</button>
                    </div>
                </form>
            </div>

            <div v-if="(doc.payments || []).length" class="mb-3">
                <strong class="d-block mb-2">Payments</strong>
                <div class="table-wrap">
                    <table class="table table-bordered stack-table">
                        <thead>
                            <tr>
                                <th>Number</th>
                                <th>Date</th>
                                <th>Method</th>
                                <th>Amount ({{ settings.currency }})</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="row in doc.payments" :key="row.id">
                                <td data-label="Number">{{ row.number }}</td>
                                <td data-label="Date">{{ String(row.paid_at).slice(0, 16) }}</td>
                                <td data-label="Method">{{ row.method }}</td>
                                <td data-label="Amount">{{ (row.amount) }}</td>
                                <td class="stack-actions">
                                    <button class="btn btn-outline-danger btn-sm" @click="destroyPayment(row.id)">Delete</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="form-actions">
                <a class="btn btn-outline-dark" :href="`/api/admin/sales/${doc.id}/pdf`" target="_blank">Print</a>
                <router-link class="btn btn-outline-info" :to="{ name: 'admin.sales.edit', params: { id: doc.id } }">Edit</router-link>
                <button class="btn btn-outline-danger" @click="destroy">Delete</button>
            </div>
            <div v-if="error" class="alert alert-danger mt-3">{{ error }}</div>
        </div>
    </PageShell>
</template>

<script setup>
import axios from 'axios';
import { onMounted, reactive, ref } from 'vue';
import Swal from 'sweetalert2';
import { useRoute, useRouter } from 'vue-router';
import PageShell from '../../../components/PageShell.vue';
import PaymentBadge from '../../../components/PaymentBadge.vue';
import { useSettingsStore } from '../../../stores/settings';

const route = useRoute();
const router = useRouter();
const settings = useSettingsStore();
const doc = ref({});
const error = ref('');
const savingPayment = ref(false);
const payment = reactive({
    amount: 0,
    method: 'cash',
});

function money(value) {
    return settings.formatMoney(value);
}

async function load() {
    const { data } = await axios.get(`/api/admin/sales/${route.params.id}`);
    doc.value = data.data;
    payment.amount = Number(data.data.due || 0) || Number(data.data.total || 0);
}

onMounted(load);

async function receivePayment() {
    error.value = '';
    savingPayment.value = true;
    try {
        await axios.post('/api/admin/payments', {
            customer_id: doc.value.customer_id,
            sale_id: doc.value.id,
            amount: payment.amount,
            method: payment.method,
        });
        await load();
    } catch (e) {
        error.value = Object.values(e.response?.data?.errors || {}).flat().join(' ') || e.response?.data?.message || 'Payment failed';
    } finally {
        savingPayment.value = false;
    }
}

async function destroyPayment(id) {
    const ok = await Swal.fire({ title: 'Delete payment?', icon: 'warning', showCancelButton: true });
    if (!ok.isConfirmed) return;
    await axios.delete(`/api/admin/payments/${id}`);
    await load();
}

async function destroy() {
    const ok = await Swal.fire({ title: 'Delete sales invoice?', icon: 'warning', showCancelButton: true });
    if (!ok.isConfirmed) return;
    await axios.delete(`/api/admin/sales/${doc.value.id}`);
    router.push({ name: 'admin.sales' });
}
</script>
