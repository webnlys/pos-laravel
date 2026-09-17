<template>
    <PageShell title="Business settings">
        <form @submit.prevent="save">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Business name</label>
                    <input v-model="form.name" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Currency</label>
                    <select v-model="form.currency" class="form-select" required>
                        <option v-for="(label, code) in currencyOptions" :key="code" :value="code">
                            {{ code }} — {{ label }}
                        </option>
                    </select>
                    <div class="form-text">This currency is shown on quotations, invoices, and totals.</div>
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
                    <img v-if="logoUrl" :src="logoUrl" alt="logo" class="mt-2 img-fluid" style="max-height: 60px">
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
import { useSettingsStore } from '../../stores/settings';

const settings = useSettingsStore();
const form = reactive({ name: '', email: '', phone: '', address: '', currency: 'AED' });
const currencyOptions = ref({ AED: 'UAE Dirham' });
const logoFile = ref(null);
const logoUrl = ref('');
const error = ref('');

onMounted(async () => {
    const { data } = await axios.get('/api/admin/settings');
    form.name = data.data.name || '';
    form.email = data.data.email || '';
    form.phone = data.data.phone || '';
    form.address = data.data.address || '';
    form.currency = data.data.currency || 'AED';
    currencyOptions.value = data.data.currencies || currencyOptions.value;
    logoUrl.value = data.data.logo;
    settings.apply(data.data);
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
        payload.append('currency', form.currency || 'AED');
        if (logoFile.value) payload.append('logo', logoFile.value);
        const { data } = await axios.post('/api/admin/settings', payload);
        logoUrl.value = data.data.logo;
        form.currency = data.data.currency;
        settings.apply(data.data);
        await Swal.fire({ icon: 'success', title: 'Saved', timer: 1200, showConfirmButton: false });
    } catch (e) {
        error.value = Object.values(e.response?.data?.errors || {}).flat().join(' ') || e.response?.data?.message || 'Save failed';
    }
}
</script>
