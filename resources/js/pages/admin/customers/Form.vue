<template>
    <PageShell :title="id ? 'Edit customer' : 'New customer'">
        <form @submit.prevent="save">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Name</label>
                    <input v-model="form.name" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Phone</label>
                    <input v-model="form.phone" class="form-control">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Login email</label>
                    <input v-model="form.email" type="email" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Password {{ id ? '(leave blank to keep)' : '' }}</label>
                    <input v-model="form.password" type="password" class="form-control" :required="!id">
                </div>
                <div class="col-12">
                    <label class="form-label">Address</label>
                    <textarea v-model="form.address" class="form-control"></textarea>
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
const form = reactive({ name: '', phone: '', email: '', password: '', address: '' });

onMounted(async () => {
    if (!id) return;
    const { data } = await axios.get(`/api/admin/customers/${id}`);
    Object.assign(form, data.data, { password: '' });
});

async function save() {
    error.value = '';
    try {
        const payload = { ...form };
        if (id && !payload.password) delete payload.password;
        if (id) await axios.put(`/api/admin/customers/${id}`, payload);
        else await axios.post('/api/admin/customers', payload);
        router.push({ name: 'admin.customers' });
    } catch (e) {
        error.value = Object.values(e.response?.data?.errors || {}).flat().join(' ') || e.response?.data?.message || 'Save failed';
    }
}
</script>
