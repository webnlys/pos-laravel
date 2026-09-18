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
                <div class="col-12">
                    <label class="form-label">Invoice tagline</label>
                    <textarea v-model="form.tagline" class="form-control" rows="2" maxlength="500"></textarea>
                    <div class="form-text">Shown in the middle of quotation and invoice PDFs. Leave blank to hide it.</div>
                </div>
                <div class="col-12">
                    <label class="form-label">Invoice terms and conditions</label>
                    <div v-for="(line, index) in termLines" :key="index" class="d-flex gap-2 mb-2 align-items-center">
                        <input
                            v-model="termLines[index]"
                            class="form-control"
                            maxlength="500"
                            :placeholder="`Point ${index + 1}`"
                        >
                        <button
                            v-if="termLines.length > 1"
                            type="button"
                            class="btn btn-outline-danger"
                            @click="removeTerm(index)"
                            aria-label="Remove term"
                        >−</button>
                        <button
                            v-if="index === termLines.length - 1"
                            type="button"
                            class="btn btn-outline-primary"
                            @click="addTerm"
                            aria-label="Add term"
                        >+</button>
                    </div>
                    <div class="form-text">Each line is a bullet on quotation and invoice PDFs. Press + to add another point.</div>
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
const form = reactive({ name: '', email: '', phone: '', address: '', currency: 'AED', tagline: '' });
const termLines = ref(['']);
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
    form.tagline = data.data.tagline || '';
    termLines.value = splitTerms(data.data.invoice_terms);
    currencyOptions.value = data.data.currencies || currencyOptions.value;
    logoUrl.value = data.data.logo;
    settings.apply(data.data);
});

function onFile(e) {
    logoFile.value = e.target.files[0];
}

function splitTerms(value) {
    const lines = String(value || '')
        .split(/\r?\n/)
        .map((line) => line.trim())
        .filter(Boolean);
    return lines.length ? lines : [''];
}

function addTerm() {
    termLines.value.push('');
}

function removeTerm(index) {
    termLines.value.splice(index, 1);
    if (!termLines.value.length) {
        termLines.value = [''];
    }
}

function joinedTerms() {
    return termLines.value.map((line) => line.trim()).filter(Boolean).join('\n');
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
        payload.append('tagline', form.tagline || '');
        payload.append('invoice_terms', joinedTerms());
        if (logoFile.value) payload.append('logo', logoFile.value);
        const { data } = await axios.post('/api/admin/settings', payload);
        logoUrl.value = data.data.logo;
        form.currency = data.data.currency;
        form.tagline = data.data.tagline || '';
        termLines.value = splitTerms(data.data.invoice_terms);
        settings.apply(data.data);
        await Swal.fire({ icon: 'success', title: 'Saved', timer: 1200, showConfirmButton: false });
    } catch (e) {
        error.value = Object.values(e.response?.data?.errors || {}).flat().join(' ') || e.response?.data?.message || 'Save failed';
    }
}
</script>

<style scoped>
.form-label {
    font-weight: 700;
}
</style>
