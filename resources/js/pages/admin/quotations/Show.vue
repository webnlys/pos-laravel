<template>
    <PageShell :title="doc.number || 'Quotation'">
        <div v-if="doc.id">
            <p><strong>Customer:</strong> {{ doc.customer?.name }}</p>
            <p><strong>Date:</strong> {{ String(doc.document_datetime).slice(0, 16) }}</p>
            <p><strong>Status:</strong> {{ doc.status }}</p>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Qty</th>
                        <th>Sale price</th>
                        <th>Discount</th>
                        <th>Tax</th>
                        <th>Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="item in doc.items" :key="item.id">
                        <td>{{ item.product_name }}</td>
                        <td>{{ item.quantity }}</td>
                        <td>{{ item.unit_price }}</td>
                        <td>{{ item.discount }}</td>
                        <td>{{ item.tax_name ? `${item.tax_name} (${item.tax_rate_percent}%)` : '—' }}</td>
                        <td>{{ item.line_total }}</td>
                    </tr>
                </tbody>
            </table>
            <p>Subtotal {{ doc.subtotal }} · Discount {{ doc.discount }} · Tax {{ doc.tax_total }} · <strong>Total {{ doc.total }}</strong></p>
            <div class="d-flex gap-2 flex-wrap">
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
