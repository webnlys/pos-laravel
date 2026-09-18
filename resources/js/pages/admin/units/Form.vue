<template>
    <PageShell :title="id ? 'Edit unit' : 'New unit'">
        <form @submit.prevent="save">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Name</label>
                    <input v-model="form.name" class="form-control" required placeholder="Piece, Kg, Liter">
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
const form = reactive({ name: '' });

onMounted(async () => {
    if (!id) return;
    const { data } = await axios.get(`/api/admin/units/${id}`);
    form.name = data.data.name;
});

async function save() {
    error.value = '';
    try {
        if (id) await axios.put(`/api/admin/units/${id}`, form);
        else await axios.post('/api/admin/units', form);
        router.push({ name: 'admin.units' });
    } catch (e) {
        error.value = Object.values(e.response?.data?.errors || {}).flat().join(' ') || e.response?.data?.message || 'Save failed';
    }
}
</script>
