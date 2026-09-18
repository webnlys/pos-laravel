<template>
    <div>
        <div class="page-heading">
            <div class="page-heading-start">
                <button
                    v-if="backTo"
                    type="button"
                    class="page-back"
                    aria-label="Back"
                    @click="goBack"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19 8 12l7-7" />
                    </svg>
                    <span>Back</span>
                </button>
                <h4 class="mb-0 page-title">{{ title }}</h4>
            </div>
            <router-link v-if="createTo" class="btn btn-primary" :to="createTo">Add</router-link>
        </div>
        <div class="page-card page-card-body">
            <slot />
        </div>
    </div>
</template>

<script setup>
import { useRouter } from 'vue-router';

const props = defineProps({
    title: { type: String, required: true },
    createTo: { type: [Object, String], default: null },
    backTo: { type: [Object, String], default: null },
});

const router = useRouter();

function goBack() {
    if (window.history.state?.back != null) {
        router.back();
        return;
    }

    router.push(props.backTo);
}
</script>

<style scoped>
.page-heading {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 0.75rem;
    justify-content: space-between;
    margin-bottom: 1rem;
}

.page-heading-start {
    display: flex;
    align-items: center;
    gap: 0.65rem;
    min-width: 0;
    flex: 1 1 12rem;
}

.page-title {
    min-width: 0;
    word-break: break-word;
}

.page-back {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.3rem;
    flex-shrink: 0;
    min-height: 42px;
    padding: 0.35rem 0.75rem 0.35rem 0.55rem;
    border: 1px solid #d1d5db;
    border-radius: 0.5rem;
    background: #fff;
    color: #374151;
    font-size: 0.9rem;
    font-weight: 600;
    line-height: 1;
    touch-action: manipulation;
}

.page-back:hover,
.page-back:focus-visible {
    background: #f3f4f6;
    color: #111827;
}

@media (min-width: 768px) {
    .page-back {
        min-height: 36px;
    }
}
</style>
