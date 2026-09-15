<template>
    <div>
        <div class="d-flex align-items-center flex-wrap gap-3 justify-content-between mb-3">
            <h4 class="mb-0">{{ title }}</h4>
            <router-link v-if="createTo" class="btn btn-primary" :to="createTo">Add</router-link>
        </div>
        <div class="page-card p-3">
            <slot />
        </div>
    </div>
</template>

<script setup>
defineProps({
    title: { type: String, required: true },
    createTo: { type: [Object, String], default: null },
});
</script>
