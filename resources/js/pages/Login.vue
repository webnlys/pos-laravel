<template>
    <div class="min-vh-100 d-flex align-items-center justify-content-center">
        <div class="page-card p-4" style="width: 420px">
            <h4 class="mb-3">Simple POS</h4>
            <p class="text-muted">Sign in to continue</p>
            <div v-if="error" class="alert alert-danger py-2">{{ error }}</div>
            <form @submit.prevent="submit">
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input v-model="email" type="email" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <input v-model="password" type="password" class="form-control" required>
                </div>
                <button class="btn btn-primary w-100" :disabled="auth.loading">
                    {{ auth.loading ? 'Signing in...' : 'Login' }}
                </button>
            </form>
            <p class="small text-muted mt-3 mb-0">Admin: admin@simplepos.test / password</p>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';

const auth = useAuthStore();
const router = useRouter();
const email = ref('admin@simplepos.test');
const password = ref('password');
const error = ref('');

async function submit() {
    error.value = '';
    try {
        const user = await auth.login(email.value, password.value);
        router.push(user.role === 'admin' ? { name: 'admin.home' } : { name: 'customer.quotations' });
    } catch (e) {
        error.value = e.response?.data?.message || e.response?.data?.errors?.email?.[0] || 'Login failed';
    }
}
</script>
