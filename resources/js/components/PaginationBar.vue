<template>
    <nav v-if="meta.last_page > 1" class="mt-3">
        <ul class="pagination">
            <li class="page-item" :class="{ disabled: meta.current_page <= 1 }">
                <button class="page-link" type="button" @click="$emit('change', meta.current_page - 1)">Prev</button>
            </li>
            <li class="page-item disabled"><span class="page-link">{{ meta.current_page }} / {{ meta.last_page }}</span></li>
            <li class="page-item" :class="{ disabled: meta.current_page >= meta.last_page }">
                <button class="page-link" type="button" @click="$emit('change', meta.current_page + 1)">Next</button>
            </li>
        </ul>
    </nav>
</template>

<script setup>
defineProps({
    meta: { type: Object, default: () => ({ current_page: 1, last_page: 1 }) },
});
defineEmits(['change']);
</script>
