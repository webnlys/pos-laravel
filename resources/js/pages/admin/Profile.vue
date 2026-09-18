<template>
    <PageShell title="Profile settings">
        <h6 class="mb-3">Account details</h6>
        <form @submit.prevent="save">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Name</label>
                    <input v-model="form.name" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Email</label>
                    <input v-model="form.email" type="email" class="form-control" required>
                </div>
            </div>
            <div v-if="error" class="alert alert-danger mt-3">{{ error }}</div>
            <button class="btn btn-primary mt-3">Save</button>
        </form>

        <hr class="my-4">

        <h6 class="mb-3">Change password</h6>
        <form @submit.prevent="changePassword">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Current password</label>
                    <input v-model="passwordForm.current_password" type="password" class="form-control" required autocomplete="current-password">
                </div>
                <div class="col-md-4">
                    <label class="form-label">New password</label>
                    <input v-model="passwordForm.password" type="password" class="form-control" required minlength="8" autocomplete="new-password">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Confirm new password</label>
                    <input v-model="passwordForm.password_confirmation" type="password" class="form-control" required minlength="8" autocomplete="new-password">
                </div>
            </div>
            <div v-if="passwordError" class="alert alert-danger mt-3">{{ passwordError }}</div>
            <button class="btn btn-primary mt-3">Update password</button>
        </form>
    </PageShell>
</template>

<script setup>
import axios from 'axios';
import { onMounted, reactive, ref } from 'vue';
import Swal from 'sweetalert2';
import PageShell from '../../components/PageShell.vue';
import { useAuthStore } from '../../stores/auth';

const auth = useAuthStore();
const form = reactive({ name: '', email: '' });
const error = ref('');

const passwordForm = reactive({ current_password: '', password: '', password_confirmation: '' });
const passwordError = ref('');

onMounted(() => {
    form.name = auth.user?.name || '';
    form.email = auth.user?.email || '';
});

async function save() {
    error.value = '';
    try {
        const { data } = await axios.put('/api/profile', { name: form.name, email: form.email });
        auth.user = data.data ?? data;
        await Swal.fire({ icon: 'success', title: 'Saved', timer: 1200, showConfirmButton: false });
    } catch (e) {
        error.value = Object.values(e.response?.data?.errors || {}).flat().join(' ') || e.response?.data?.message || 'Save failed';
    }
}

async function changePassword() {
    passwordError.value = '';
    try {
        await axios.put('/api/profile/password', passwordForm);
        passwordForm.current_password = '';
        passwordForm.password = '';
        passwordForm.password_confirmation = '';
        await Swal.fire({ icon: 'success', title: 'Password updated', timer: 1200, showConfirmButton: false });
    } catch (e) {
        passwordError.value = Object.values(e.response?.data?.errors || {}).flat().join(' ') || e.response?.data?.message || 'Update failed';
    }
}
</script>
