<template>
    <PageShell :title="customer.name || 'Customer'" :back-to="{ name: 'admin.customers' }">
        <div v-if="customer.id">
            <div class="row g-2 mb-3">
                <div class="col-12 col-md-4"><strong>Phone:</strong> {{ customer.phone || '—' }}</div>
                <div class="col-12 col-md-4"><strong>Email:</strong> {{ customer.email || '—' }}</div>
                <div class="col-12 col-md-4"><strong>Address:</strong> {{ customer.address || '—' }}</div>
            </div>

            <div class="totals-box mb-3">
                <div><span>Invoiced</span><span>{{ money(customer.charged) }}</span></div>
                <div><span>Paid</span><span>{{ money(customer.paid) }}</span></div>
                <div><span>Due</span><span>{{ money(customer.due) }}</span></div>
                <div><span>Advance</span><span>{{ money(customer.advance) }}</span></div>
            </div>

            <strong class="d-block mb-2">Sales invoices</strong>
            <div class="table-wrap mb-3">
                <table class="table table-bordered stack-table">
                    <thead>
                        <tr>
                            <th>Number</th>
                            <th>Date</th>
                            <th>Total</th>
                            <th>Paid</th>
                            <th>Due</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="!(customer.sales || []).length">
                            <td colspan="7" class="text-center text-muted">No sales invoices.</td>
                        </tr>
                        <tr v-for="sale in customer.sales" :key="sale.id">
                            <td data-label="Number">{{ sale.number }}</td>
                            <td data-label="Date">{{ String(sale.document_datetime).slice(0, 16) }}</td>
                            <td data-label="Total">{{ money(sale.total) }}</td>
                            <td data-label="Paid">{{ money(sale.paid) }}</td>
                            <td data-label="Due">{{ money(sale.due) }}{{ sale.advance > 0 ? ` / Adv ${money(sale.advance)}` : '' }}</td>
                            <td data-label="Status"><PaymentBadge :status="sale.status" /></td>
                            <td class="stack-actions">
                                <router-link class="btn btn-outline-info btn-sm" :to="{ name: 'admin.sales.show', params: { id: sale.id } }">View</router-link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <strong class="d-block mb-2">Payments</strong>
            <div class="table-wrap mb-3">
                <table class="table table-bordered stack-table">
                    <thead>
                        <tr>
                            <th>Number</th>
                            <th>Date</th>
                            <th>Amount</th>
                            <th>Method</th>
                            <th>Invoice</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="!(customer.payments || []).length">
                            <td colspan="5" class="text-center text-muted">No payments.</td>
                        </tr>
                        <tr v-for="row in customer.payments" :key="row.id">
                            <td data-label="Number">{{ row.number }}</td>
                            <td data-label="Date">{{ String(row.paid_at).slice(0, 16) }}</td>
                            <td data-label="Amount">{{ money(row.amount) }}</td>
                            <td data-label="Method">{{ row.method }}</td>
                            <td data-label="Invoice">{{ row.sale?.number || 'Advance' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="form-actions">
                <router-link class="btn btn-outline-info" :to="{ name: 'admin.customers.edit', params: { id: customer.id } }">Edit</router-link>
                <router-link class="btn btn-primary" :to="{ name: 'admin.payments.create', query: { customer_id: customer.id } }">Receive payment</router-link>
            </div>
        </div>
    </PageShell>
</template>

<script setup>
import axios from 'axios';
import { onMounted, ref } from 'vue';
import { useRoute } from 'vue-router';
import PageShell from '../../../components/PageShell.vue';
import PaymentBadge from '../../../components/PaymentBadge.vue';
import { useSettingsStore } from '../../../stores/settings';

const route = useRoute();
const settings = useSettingsStore();
const customer = ref({});

function money(value) {
    return settings.formatMoney(value);
}

onMounted(async () => {
    const { data } = await axios.get(`/api/admin/customers/${route.params.id}`);
    customer.value = data.data;
});
</script>
