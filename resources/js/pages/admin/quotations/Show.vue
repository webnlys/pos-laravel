<template>
    <PageShell :title="doc.number || 'Quotation'">
        <div v-if="doc.id">
            <p><strong>Customer:</strong> {{ doc.customer?.name }}</p>
            <p><strong>Date:</strong> {{ String(doc.document_datetime).slice(0, 16) }}</p>
            <p><strong>Status:</strong> {{ doc.status }}</p>
            <table class="table table-bordered">
                <thead><tr><th>Item</th><th>Qty</th><th>Price</th><th>Total</th></tr></thead>
                <tbody>
                    <tr v-for="item in doc.items" :key="item.id">
                        <td>{{ item.product_name }}</td>
                        <td>{{ item.quantity }}</td>
                        <td>{{ item.unit_price }}</td>
                        <td>{{ item.line_total }}</td>
                    </tr>
                </tbody>
            </table>
            <p>Subtotal {{ doc.subtotal }} · Discount {{ doc.discount }} · Tax {{ doc.tax_total }} · <strong>Total {{ doc.total }}</strong></p>
            <a class="btn btn-outline-dark" :href="`/api/admin/quotations/${doc.id}/pdf`" target="_blank">PDF</a>
        </div>
    </PageShell>
</template>

<script setup>
import axios from 'axios';
import { onMounted, ref } from 'vue';
import { useRoute } from 'vue-router';
import PageShell from '../../../components/PageShell.vue';

const route = useRoute();
const doc = ref({});

onMounted(async () => {
    const { data } = await axios.get(`/api/admin/quotations/${route.params.id}`);
    doc.value = data.data;
});
</script>
