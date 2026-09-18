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
                    <label class="form-label">Website</label>
                    <input v-model="form.website" class="form-control" placeholder="https://example.com">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Logo</label>
                    <input type="file" class="form-control" accept="image/*" @change="onLogoFile">
                    <img v-if="logoUrl" :src="logoUrl" alt="logo" class="mt-2 img-fluid" style="max-height: 60px">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Signature</label>
                    <input type="file" class="form-control" accept="image/*" @change="onSignatureFile">
                    <img v-if="signatureUrl" :src="signatureUrl" alt="signature" class="mt-2 img-fluid" style="max-height: 60px">
                    <div class="form-text">Shown above the "Authorized Signature" line at the bottom of quotation and invoice PDFs.</div>
                </div>
                <div class="col-12">
                    <label class="form-label">Address</label>
                    <textarea v-model="form.address" class="form-control"></textarea>
                </div>
                <div class="col-12">
                    <label class="form-label">Tagline</label>
                    <textarea v-model="form.tagline" class="form-control" rows="2" maxlength="500"></textarea>
                    <div class="form-text">Shown under the company name on quotation and invoice PDFs. Leave blank to hide it.</div>
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
                    <div class="form-text">Each line is a bullet on invoice PDFs. Press + to add another point.</div>
                </div>
                <div class="col-12">
                    <label class="form-label">Quotation terms and conditions</label>
                    <div v-for="(line, index) in quotationTermLines" :key="index" class="d-flex gap-2 mb-2 align-items-center">
                        <input
                            v-model="quotationTermLines[index]"
                            class="form-control"
                            maxlength="500"
                            :placeholder="`Point ${index + 1}`"
                        >
                        <button
                            v-if="quotationTermLines.length > 1"
                            type="button"
                            class="btn btn-outline-danger"
                            @click="removeQuotationTerm(index)"
                            aria-label="Remove term"
                        >−</button>
                        <button
                            v-if="index === quotationTermLines.length - 1"
                            type="button"
                            class="btn btn-outline-primary"
                            @click="addQuotationTerm"
                            aria-label="Add term"
                        >+</button>
                    </div>
                    <div class="form-text">Each line is a bullet on quotation PDFs. Press + to add another point.</div>
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
const form = reactive({ name: '', email: '', phone: '', website: '', address: '', currency: 'AED', tagline: '' });
const termLines = ref(['']);
const quotationTermLines = ref(['']);
const currencyOptions = ref({ AED: 'UAE Dirham' });
const logoFile = ref(null);
const logoUrl = ref('');
const signatureFile = ref(null);
const signatureUrl = ref('');
const error = ref('');

onMounted(async () => {
    const { data } = await axios.get('/api/admin/settings');
    form.name = data.data.name || '';
    form.email = data.data.email || '';
    form.phone = data.data.phone || '';
    form.website = data.data.website || '';
    form.address = data.data.address || '';
    form.currency = data.data.currency || 'AED';
    form.tagline = data.data.tagline || '';
    termLines.value = splitTerms(data.data.invoice_terms);
    quotationTermLines.value = splitTerms(data.data.quotation_terms);
    currencyOptions.value = data.data.currencies || currencyOptions.value;
    logoUrl.value = data.data.logo;
    signatureUrl.value = data.data.signature;
    settings.apply(data.data);
});

function onLogoFile(e) {
    logoFile.value = e.target.files[0];
}

function onSignatureFile(e) {
    signatureFile.value = e.target.files[0];
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

function addQuotationTerm() {
    quotationTermLines.value.push('');
}

function removeQuotationTerm(index) {
    quotationTermLines.value.splice(index, 1);
    if (!quotationTermLines.value.length) {
        quotationTermLines.value = [''];
    }
}

function joinedQuotationTerms() {
    return quotationTermLines.value.map((line) => line.trim()).filter(Boolean).join('\n');
}

async function save() {
    error.value = '';
    try {
        const payload = new FormData();
        payload.append('name', form.name || '');
        payload.append('email', form.email || '');
        payload.append('phone', form.phone || '');
        payload.append('website', form.website || '');
        payload.append('address', form.address || '');
        payload.append('currency', form.currency || 'AED');
        payload.append('tagline', form.tagline || '');
        payload.append('invoice_terms', joinedTerms());
        payload.append('quotation_terms', joinedQuotationTerms());
        if (logoFile.value) payload.append('logo', logoFile.value);
        if (signatureFile.value) payload.append('signature', signatureFile.value);
        const { data } = await axios.post('/api/admin/settings', payload);
        logoUrl.value = data.data.logo;
        signatureUrl.value = data.data.signature;
        form.currency = data.data.currency;
        form.tagline = data.data.tagline || '';
        termLines.value = splitTerms(data.data.invoice_terms);
        quotationTermLines.value = splitTerms(data.data.quotation_terms);
        settings.apply(data.data);
        await Swal.fire({ icon: 'success', title: 'Saved', timer: 1200, showConfirmButton: false });
    } catch (e) {
        error.value = Object.values(e.response?.data?.errors || {}).flat().join(' ') || e.response?.data?.message || 'Save failed';
    }
}
</script>
