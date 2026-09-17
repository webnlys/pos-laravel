<template>
    <PageShell :title="doc.number || 'Quotation'">
        <div v-if="doc.id">
            <div class="row g-2 mb-3">
                <div class="col-12 col-md-4"><strong>Customer:</strong> {{ doc.customer?.name }}</div>
                <div class="col-12 col-md-4"><strong>Date:</strong> {{ String(doc.document_datetime).slice(0, 16) }}</div>
                <div class="col-12 col-md-4"><strong>Status:</strong> {{ doc.status }}</div>
            </div>
            <div class="table-wrap">
                <table class="table table-bordered stack-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Qty</th>
                            <th>Price</th>
                            <th>Discount</th>
                            <th>Tax</th>
                            <th>Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in doc.items" :key="item.id">
                            <td data-label="Product">{{ item.product_name }}</td>
                            <td data-label="Qty">{{ item.quantity }}</td>
                            <td data-label="Price">{{ item.unit_price }}</td>
                            <td data-label="Discount">{{ item.discount }}</td>
                            <td data-label="Tax">{{ item.tax_name ? `${item.tax_name} (${item.tax_rate_percent}%)` : '—' }}</td>
                            <td data-label="Amount">{{ item.line_total }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="totals-box mb-3">
                <div><span>Subtotal</span><span>{{ doc.subtotal }}</span></div>
                <div><span>Discount</span><span>{{ doc.discount }}</span></div>
                <div><span>Tax</span><span>{{ doc.tax_total }}</span></div>
                <div class="fw-bold"><span>Total</span><span>{{ doc.total }}</span></div>
            </div>
            <div class="form-actions">
                <a class="btn btn-outline-dark" :href="`/api/admin/quotations/${doc.id}/pdf`" target="_blank">Print</a>
                <router-link class="btn btn-outline-info" :to="{ name: 'admin.quotations.edit', params: { id: doc.id } }">Edit</router-link>
                <button class="btn btn-outline-danger" @click="destroy">Delete</button>
            </div>
            <div v-if="error" class="alert alert-danger mt-3">{{ error }}</div>
        </div>
    </PageShell>
</template>

<script setup>
import axios from 'axios';
import { onMounted, ref } from 'vue';
import Swal from 'sweetalert2';
import { useRoute, useRouter } from 'vue-router';
import PageShell from '../../../components/PageShell.vue';

const route = useRoute();
const router = useRouter();
const doc = ref({});
const error = ref('');

onMounted(async () => {
    const { data } = await axios.get(`/api/admin/quotations/${route.params.id}`);
    doc.value = data.data;
});

async function destroy() {
    const ok = await Swal.fire({ title: 'Delete quotation?', icon: 'warning', showCancelButton: true });
    if (!ok.isConfirmed) return;
    await axios.delete(`/api/admin/quotations/${doc.value.id}`);
    router.push({ name: 'admin.quotations' });
}
</script>
