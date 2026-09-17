<template>
    <div class="app-shell">
        <div v-if="navOpen" class="sidebar-backdrop" @click="navOpen = false"></div>
        <aside id="app-sidebar" class="sidebar p-3" :class="{ open: navOpen }">
            <div class="sidebar-brand">
                <h5 class="mb-0">{{ brand }}</h5>
                <button type="button" class="sidebar-close" aria-label="Close menu" @click="navOpen = false">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <nav class="d-grid gap-1 mt-3" @click="navOpen = false">
                <slot name="nav" />
            </nav>
        </aside>
        <div class="app-content">
            <header class="app-topbar">
                <button
                    type="button"
                    class="sidebar-toggle"
                    :aria-expanded="navOpen"
                    aria-controls="app-sidebar"
                    aria-label="Open menu"
                    @click="navOpen = true"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
                <div class="app-topbar-user">{{ auth.user?.name }}</div>
                <button class="btn btn-outline-secondary btn-sm logout-btn" @click="onLogout">Logout</button>
            </header>
            <main class="app-main">
                <slot />
            </main>
        </div>
    </div>
</template>

<script setup>
import { onBeforeUnmount, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';

defineProps({
    brand: { type: String, required: true },
});

const auth = useAuthStore();
const router = useRouter();
const route = useRoute();
const navOpen = ref(false);

watch(() => route.fullPath, () => {
    navOpen.value = false;
});

watch(navOpen, (open) => {
    document.body.classList.toggle('nav-open', open);
    if (open) {
        window.addEventListener('keydown', onKeydown);
    } else {
        window.removeEventListener('keydown', onKeydown);
    }
});

onBeforeUnmount(() => {
    document.body.classList.remove('nav-open');
    window.removeEventListener('keydown', onKeydown);
});

function onKeydown(event) {
    if (event.key === 'Escape') {
        navOpen.value = false;
    }
}

async function onLogout() {
    await auth.logout();
    router.push({ name: 'login' });
}
</script>
