<template>
    <div class="pagination-bar">
        <div class="pagination-info">
            Showing {{ meta.from || 0 }}–{{ meta.to || 0 }} of {{ meta.total || 0 }}
        </div>
        <div class="pagination-controls">
            <label class="pagination-size">
                <span class="form-label mb-0">Per page</span>
                <select class="form-select" :value="currentPerPage" @change="changePerPage">
                    <option v-for="size in pageSizes" :key="size" :value="size">{{ size }}</option>
                </select>
            </label>
            <nav v-if="lastPage > 1" aria-label="Table pagination">
                <ul class="pagination mb-0">
                    <li class="page-item" :class="{ disabled: currentPage <= 1 }">
                        <button class="page-link" type="button" :disabled="currentPage <= 1" @click="go(1)">First</button>
                    </li>
                    <li class="page-item" :class="{ disabled: currentPage <= 1 }">
                        <button class="page-link" type="button" :disabled="currentPage <= 1" @click="go(currentPage - 1)">Prev</button>
                    </li>
                    <li v-for="page in pages" :key="page" class="page-item" :class="{ active: page === currentPage }">
                        <button class="page-link" type="button" @click="go(page)">{{ page }}</button>
                    </li>
                    <li class="page-item" :class="{ disabled: currentPage >= lastPage }">
                        <button class="page-link" type="button" :disabled="currentPage >= lastPage" @click="go(currentPage + 1)">Next</button>
                    </li>
                    <li class="page-item" :class="{ disabled: currentPage >= lastPage }">
                        <button class="page-link" type="button" :disabled="currentPage >= lastPage" @click="go(lastPage)">Last</button>
                    </li>
                </ul>
            </nav>
        </div>
    </div>
</template>

<script setup>
import { computed, nextTick } from 'vue';

const props = defineProps({
    meta: { type: Object, default: () => ({}) },
    perPage: { type: Number, default: 15 },
});

const emit = defineEmits(['change', 'update:perPage']);

const pageSizes = [10, 15, 25, 50];

const currentPage = computed(() => Number(props.meta.current_page || 1));
const lastPage = computed(() => Number(props.meta.last_page || 1));
const currentPerPage = computed(() => Number(props.meta.per_page || props.perPage || 15));

const pages = computed(() => {
    const last = lastPage.value;
    const current = currentPage.value;
    const windowSize = 5;
    let start = Math.max(1, current - 2);
    let end = Math.min(last, start + windowSize - 1);
    start = Math.max(1, end - windowSize + 1);
    const list = [];
    for (let page = start; page <= end; page += 1) {
        list.push(page);
    }
    return list;
});

function go(page) {
    const next = Math.min(Math.max(1, Number(page) || 1), lastPage.value);
    if (next === currentPage.value) {
        return;
    }
    emit('change', next);
}

async function changePerPage(event) {
    const value = Number(event.target.value) || 15;
    emit('update:perPage', value);
    await nextTick();
    emit('change', 1);
}
</script>
