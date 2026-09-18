<template>
    <PageShell :title="id ? 'Edit payment' : 'Receive payment'" :back-to="{ name: 'admin.payments' }">
        <form @submit.prevent="save">
            <p class="text-muted">Leave invoice empty to record a customer advance. Link an invoice to apply the payment there; extra over the total is kept as advance on that invoice.</p>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Customer</label>
                    <select v-model.number="form.customer_id" class="form-select" required>
                        <option :value="0">Select</option>
                        <option v-for="c in customers" :key="c.id" :value="c.id">
                            {{ c.name }} (due {{ money(c.due) }}<template v-if="c.advance > 0">, adv {{ money(c.advance) }}</template>)
                        </option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Invoice (optional)</label>
                    <select v-model="form.sale_id" class="form-select">
                        <option value="">Advance — no invoice</option>
                        <option v-for="s in sales" :key="s.id" :value="s.id">
                            {{ s.number }} — total {{ money(s.total) }}, due {{ money(s.due) }}
                        </option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Amount</label>
                    <input v-model.number="form.amount" type="number" min="0.01" step="0.01" class="form-control" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Method</label>
                    <select v-model="form.method" class="form-select">
                        <option value="cash">Cash</option>
                        <option value="bank">Bank</option>
                        <option value="cheque">Cheque</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Paid at</label>
                    <input v-model="form.paid_at" type="datetime-local" class="form-control">
                </div>
                <div class="col-12">
                    <label class="form-label">Notes</label>
                    <textarea v-model="form.notes" class="form-control"></textarea>
                </div>
            </div>
            <div v-if="error" class="alert alert-danger mt-3">{{ error }}</div>
            <div class="form-actions mt-3">
                <button class="btn btn-primary">Save</button>
            </div>
        </form>
    </PageShell>
</template>

<script setup>
import axios from 'axios';
import { onMounted, reactive, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import PageShell from '../../../components/PageShell.vue';
import { useSettingsStore } from '../../../stores/settings';

const route = useRoute();
const router = useRouter();
const settings = useSettingsStore();
const id = route.params.id;
const error = ref('');
const customers = ref([]);
const sales = ref([]);
const form = reactive({
    customer_id: 0,
    sale_id: '',
    amount: 0,
    method: 'cash',
    paid_at: new Date().toISOString().slice(0, 16),
    notes: '',
});

function money(value) {
    return settings.formatMoney(value);
}

onMounted(async () => {
    const { data } = await axios.get('/api/admin/customers', { params: { per_page: 100 } });
    customers.value = data.data;
    if (id) {
        const res = await axios.get(`/api/admin/payments/${id}`);
        const row = res.data.data;
        form.customer_id = row.customer_id;
        form.sale_id = row.sale_id || '';
        form.amount = row.amount;
        form.method = row.method;
        form.paid_at = String(row.paid_at).slice(0, 16);
        form.notes = row.notes || '';
    } else if (route.query.customer_id) {
        form.customer_id = Number(route.query.customer_id);
    }
});

watch(() => form.customer_id, async (customerId) => {
    if (!customerId) {
        sales.value = [];
        return;
    }
    const { data } = await axios.get('/api/admin/sales', { params: { customer_id: customerId, per_page: 50 } });
    sales.value = data.data;
});

async function save() {
    error.value = '';
    try {
        const payload = { ...form, sale_id: form.sale_id || null };
        if (id) await axios.put(`/api/admin/payments/${id}`, payload);
        else await axios.post('/api/admin/payments', payload);
        router.push({ name: 'admin.payments' });
    } catch (e) {
        error.value = Object.values(e.response?.data?.errors || {}).flat().join(' ') || e.response?.data?.message || 'Save failed';
    }
}
</script>
