<template>
    <PageShell title="Business settings">
        <form @submit.prevent="save">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Business name</label>
                    <input v-model="form.name" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Email</label>
                    <input v-model="form.email" type="email" class="form-control">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Phone</label>
                    <input v-model="form.phone" class="form-control">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Logo</label>
                    <input type="file" class="form-control" accept="image/*" @change="onFile">
                    <img v-if="logoUrl" :src="logoUrl" alt="logo" class="mt-2" style="max-height: 60px">
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
import Swal from 'sweetalert2';
import PageShell from '../../components/PageShell.vue';

const form = reactive({ name: '', email: '', phone: '', address: '' });
const logoFile = ref(null);
const logoUrl = ref('');
const error = ref('');

onMounted(async () => {
    const { data } = await axios.get('/api/admin/settings');
    Object.assign(form, data.data);
    logoUrl.value = data.data.logo;
});

function onFile(e) {
    logoFile.value = e.target.files[0];
}

async function save() {
    error.value = '';
    try {
        const payload = new FormData();
        payload.append('name', form.name || '');
        payload.append('email', form.email || '');
        payload.append('phone', form.phone || '');
        payload.append('address', form.address || '');
        if (logoFile.value) payload.append('logo', logoFile.value);
        const { data } = await axios.post('/api/admin/settings', payload);
        logoUrl.value = data.data.logo;
        await Swal.fire({ icon: 'success', title: 'Saved', timer: 1200, showConfirmButton: false });
    } catch (e) {
        error.value = Object.values(e.response?.data?.errors || {}).flat().join(' ') || e.response?.data?.message || 'Save failed';
    }
}
</script>
