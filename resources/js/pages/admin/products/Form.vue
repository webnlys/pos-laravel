<template>
    <PageShell :title="id ? 'Edit product' : 'New product'">
        <form @submit.prevent="save">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Name</label>
                    <input v-model="form.name" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">SKU</label>
                    <input v-model="form.sku" class="form-control" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Sale price</label>
                    <input v-model.number="form.sale_price" type="number" step="0.01" class="form-control" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Cost price</label>
                    <input v-model.number="form.cost_price" type="number" step="0.01" class="form-control">
                </div>
                <div class="col-md-4">
                    <div class="form-check mt-4">
                        <input id="active" v-model="form.is_active" class="form-check-input" type="checkbox">
                        <label class="form-check-label" for="active">Active</label>
                    </div>
                </div>
            </div>
            <div v-if="error" class="alert alert-danger mt-3">{{ error }}</div>
            <button class="btn btn-primary mt-3">Save</button>
        </form>
    </PageShell>
</template>

<script setup>
import axios from 'axios';
import { onMounted, reactive, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import PageShell from '../../../components/PageShell.vue';

const route = useRoute();
const router = useRouter();
const id = route.params.id;
const error = ref('');
const form = reactive({
    name: '',
    sku: '',
    sale_price: 0,
    cost_price: 0,
    stock_qty: 0,
    is_active: true,
});

onMounted(async () => {
    if (!id) return;
    const { data } = await axios.get(`/api/admin/products/${id}`);
    Object.assign(form, data.data);
});

async function save() {
    error.value = '';
    try {
        if (id) {
            await axios.put(`/api/admin/products/${id}`, form);
        } else {
            await axios.post('/api/admin/products', form);
        }
        router.push({ name: 'admin.products' });
    } catch (e) {
        error.value = Object.values(e.response?.data?.errors || {}).flat().join(' ') || e.response?.data?.message || 'Save failed';
    }
}
</script>
