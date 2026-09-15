<template>
    <PageShell :title="id ? 'Edit tax' : 'New tax'">
        <form @submit.prevent="save">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Name</label>
                    <input v-model="form.name" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Rate %</label>
                    <input v-model.number="form.rate_percent" type="number" step="0.01" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Effective from</label>
                    <input v-model="form.effective_from" type="datetime-local" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Effective to (optional)</label>
                    <input v-model="form.effective_to" type="datetime-local" class="form-control">
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
const form = reactive({ name: 'VAT', rate_percent: 15, effective_from: '', effective_to: '' });

function toLocal(value) {
    if (!value) return '';
    return String(value).slice(0, 16);
}

onMounted(async () => {
    if (!id) return;
    const { data } = await axios.get(`/api/admin/taxes/${id}`);
    form.name = data.data.name;
    form.rate_percent = data.data.rate_percent;
    form.effective_from = toLocal(data.data.effective_from);
    form.effective_to = toLocal(data.data.effective_to);
});

async function save() {
    error.value = '';
    try {
        const payload = { ...form, effective_to: form.effective_to || null };
        if (id) await axios.put(`/api/admin/taxes/${id}`, payload);
        else await axios.post('/api/admin/taxes', payload);
        router.push({ name: 'admin.taxes' });
    } catch (e) {
        error.value = Object.values(e.response?.data?.errors || {}).flat().join(' ') || e.response?.data?.message || 'Save failed';
    }
}
</script>
