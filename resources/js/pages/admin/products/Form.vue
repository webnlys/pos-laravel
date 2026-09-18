<template>
    <PageShell :title="id ? 'Edit product' : 'New product'">
        <form @submit.prevent="save">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Name</label>
                    <input v-model="form.name" class="form-control" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">SKU</label>
                    <input v-model="form.sku" class="form-control" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Unit</label>
                    <NameSuggest
                        :item-id="form.unit_id"
                        :item-name="form.unit_name"
                        :items="units"
                        allow-create
                        search-url="/api/admin/units"
                        create-url="/api/admin/units"
                        placeholder="Piece, Roo"
                        noun="unit"
                        @select="onUnit"
                    />
                </div>
                <div class="col-md-4">
                    <label class="form-label">Sale price</label>
                    <input v-model.number="form.sale_price" type="number" step="0.01" class="form-control" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Cost price</label>
                    <input v-model.number="form.cost_price" type="number" step="0.01" class="form-control">
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <div class="form-check mb-2">
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
import NameSuggest from '../../../components/NameSuggest.vue';
import PageShell from '../../../components/PageShell.vue';

const route = useRoute();
const router = useRouter();
const id = route.params.id;
const error = ref('');
const units = ref([]);
const form = reactive({
    name: '',
    sku: '',
    unit_id: 0,
    unit_name: '',
    sale_price: 0,
    cost_price: 0,
    stock_qty: 0,
    is_active: true,
});

onMounted(async () => {
    const { data: unitData } = await axios.get('/api/admin/units', { params: { per_page: 100 } });
    units.value = unitData.data;
    if (!id) return;
    const { data } = await axios.get(`/api/admin/products/${id}`);
    form.name = data.data.name || '';
    form.sku = data.data.sku || '';
    form.unit_id = data.data.unit_id || 0;
    form.unit_name = data.data.unit_name || data.data.unit?.name || '';
    form.sale_price = data.data.sale_price;
    form.cost_price = data.data.cost_price;
    form.is_active = data.data.is_active;
});

function onUnit(unit) {
    form.unit_id = Number(unit?.id || 0);
    form.unit_name = unit?.name || '';
    if (unit?.id && !units.value.some((item) => item.id === unit.id)) {
        units.value = [...units.value, unit].sort((a, b) => String(a.name).localeCompare(String(b.name)));
    }
}

async function save() {
    error.value = '';
    try {
        const payload = {
            name: form.name,
            sku: form.sku,
            unit_id: form.unit_id || null,
            unit_name: form.unit_name || null,
            sale_price: form.sale_price,
            cost_price: form.cost_price,
            is_active: form.is_active,
        };
        if (id) {
            await axios.put(`/api/admin/products/${id}`, payload);
        } else {
            await axios.post('/api/admin/products', payload);
        }
        router.push({ name: 'admin.products' });
    } catch (e) {
        error.value = Object.values(e.response?.data?.errors || {}).flat().join(' ') || e.response?.data?.message || 'Save failed';
    }
}
</script>
