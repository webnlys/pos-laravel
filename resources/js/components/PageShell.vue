<template>
    <div>
        <div class="d-flex align-items-center flex-wrap gap-2 justify-content-between mb-3">
            <h4 class="mb-0 page-title">{{ title }}</h4>
            <router-link v-if="createTo" class="btn btn-primary" :to="createTo">Add</router-link>
        </div>
        <div class="page-card page-card-body">
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

<style scoped>
.page-title {
    word-break: break-word;
}
</style>
