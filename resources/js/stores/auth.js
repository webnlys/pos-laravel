import axios from 'axios';
import { defineStore } from 'pinia';
import { computed, ref } from 'vue';

export const useAuthStore = defineStore('auth', () => {
    const user = ref(null);
    const loading = ref(false);

    const isAdmin = computed(() => user.value?.role === 'admin');
    const isCustomer = computed(() => user.value?.role === 'customer');

    async function fetchUser() {
        try {
            const { data } = await axios.get('/api/me');
            user.value = data.data ?? data;
            return user.value;
        } catch {
            user.value = null;
            return null;
        }
    }

    async function login(email, password) {
        loading.value = true;
        try {
            await axios.get('/sanctum/csrf-cookie');
            const { data } = await axios.post('/api/login', { email, password });
            user.value = data.data ?? data;
            return user.value;
        } finally {
            loading.value = false;
        }
    }

    async function logout() {
        await axios.post('/api/logout');
        user.value = null;
    }

    return { user, loading, isAdmin, isCustomer, fetchUser, login, logout };
});
