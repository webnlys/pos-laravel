<template>
    <div class="product-suggest" @focusout="onFocusOut">
        <input
            ref="inputEl"
            :value="query"
            type="text"
            class="form-control"
            placeholder="Type product name"
            autocomplete="off"
            @input="onInput"
            @focus="open"
            @keydown.down.prevent="move(1)"
            @keydown.up.prevent="move(-1)"
            @keydown.enter.prevent="confirmHighlighted"
            @keydown.tab="confirmOnTab"
            @keydown.escape.prevent="close"
        >
        <Teleport to="body">
            <ul
                v-if="openList && (suggestions.length || canCreate)"
                class="product-suggest-list"
                :style="menuStyle"
            >
                <li
                    v-for="(product, index) in suggestions"
                    :key="product.id"
                    :class="{ active: index === highlighted }"
                    @mousedown.prevent="pick(product)"
                >
                    <span>{{ product.name }}</span>
                    <small v-if="product.sku" class="text-muted">{{ product.sku }}</small>
                </li>
                <li
                    v-if="canCreate"
                    :class="{ active: highlighted === suggestions.length }"
                    @mousedown.prevent="useTypedName"
                >
                    Use “{{ trimmedQuery }}” as new product
                </li>
            </ul>
        </Teleport>
    </div>
</template>

<script setup>
import axios from 'axios';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';

const props = defineProps({
    productId: { type: [Number, String], default: 0 },
    productName: { type: String, default: '' },
    products: { type: Array, default: () => [] },
    allowCreate: { type: Boolean, default: false },
    searchUrl: { type: String, default: '' },
});

const emit = defineEmits(['select']);

const inputEl = ref(null);
const query = ref(props.productName || '');
const openList = ref(false);
const highlighted = ref(0);
const remoteProducts = ref([]);
const menuStyle = ref({});
let searchTimer;
let blurTimer;

const trimmedQuery = computed(() => query.value.trim());

const catalog = computed(() => {
    const byId = new Map();
    [...props.products, ...remoteProducts.value].forEach((product) => {
        if (product?.id) {
            byId.set(product.id, product);
        }
    });
    return [...byId.values()];
});

const suggestions = computed(() => {
    const term = trimmedQuery.value.toLowerCase();
    const list = term
        ? catalog.value.filter((product) => {
            const name = String(product.name || '').toLowerCase();
            const sku = String(product.sku || '').toLowerCase();
            return name.includes(term) || sku.includes(term);
        })
        : catalog.value;

    return list.slice(0, 8);
});

const exactMatch = computed(() => {
    const term = trimmedQuery.value.toLowerCase();
    if (!term) {
        return null;
    }
    return catalog.value.find((product) => String(product.name || '').toLowerCase() === term) || null;
});

const canCreate = computed(() => props.allowCreate && trimmedQuery.value !== '' && !exactMatch.value);

watch(() => props.productName, (value) => {
    if (document.activeElement === inputEl.value && openList.value) {
        return;
    }
    query.value = value || '';
});

watch(suggestions, () => {
    highlighted.value = 0;
});

onMounted(() => {
    window.addEventListener('scroll', positionMenu, true);
    window.addEventListener('resize', positionMenu);
});

onBeforeUnmount(() => {
    clearTimeout(searchTimer);
    clearTimeout(blurTimer);
    window.removeEventListener('scroll', positionMenu, true);
    window.removeEventListener('resize', positionMenu);
});

function positionMenu() {
    const rect = inputEl.value?.getBoundingClientRect();
    if (!rect) {
        return;
    }
    menuStyle.value = {
        position: 'fixed',
        top: `${rect.bottom + 2}px`,
        left: `${rect.left}px`,
        width: `${Math.max(rect.width, 220)}px`,
        zIndex: 1080,
    };
}

function onInput(event) {
    query.value = event.target.value;
    openList.value = true;
    highlighted.value = 0;
    positionMenu();
    scheduleSearch();
    syncLine();
}

function syncLine() {
    if (!trimmedQuery.value) {
        emit('select', { id: 0, name: '', sale_price: null, cost_price: null });
        return;
    }
    if (exactMatch.value) {
        emit('select', exactMatch.value);
        return;
    }
    if (props.allowCreate) {
        emit('select', { id: 0, name: trimmedQuery.value, sale_price: null, cost_price: null });
    }
}

function scheduleSearch() {
    if (!props.searchUrl) {
        return;
    }
    clearTimeout(searchTimer);
    searchTimer = setTimeout(searchRemote, 220);
}

async function searchRemote() {
    if (!props.searchUrl || !trimmedQuery.value) {
        remoteProducts.value = [];
        return;
    }
    try {
        const { data } = await axios.get(props.searchUrl, {
            params: { q: trimmedQuery.value, per_page: 20 },
        });
        remoteProducts.value = data.data || [];
        syncLine();
    } catch {
        remoteProducts.value = [];
    }
}

function open() {
    openList.value = true;
    highlighted.value = 0;
    positionMenu();
    if (trimmedQuery.value) {
        scheduleSearch();
    }
}

function close() {
    openList.value = false;
}

function onFocusOut() {
    clearTimeout(blurTimer);
    blurTimer = setTimeout(() => {
        commitTyped();
        close();
    }, 120);
}

function move(step) {
    const count = suggestions.value.length + (canCreate.value ? 1 : 0);
    if (!count) {
        return;
    }
    openList.value = true;
    positionMenu();
    highlighted.value = (highlighted.value + step + count) % count;
}

function confirmHighlighted() {
    if (!openList.value) {
        commitTyped();
        return;
    }
    if (canCreate.value && highlighted.value === suggestions.value.length) {
        useTypedName();
        return;
    }
    const product = suggestions.value[highlighted.value];
    if (product) {
        pick(product);
        return;
    }
    commitTyped();
}

function confirmOnTab() {
    commitTyped();
}

function pick(product) {
    query.value = product.name;
    emit('select', product);
    close();
}

function useTypedName() {
    const name = trimmedQuery.value;
    if (!name) {
        return;
    }
    query.value = name;
    emit('select', { id: 0, name, sale_price: null, cost_price: null });
    close();
}

function commitTyped() {
    const name = trimmedQuery.value;
    if (!name) {
        emit('select', { id: 0, name: '', sale_price: null, cost_price: null });
        return;
    }
    if (exactMatch.value) {
        pick(exactMatch.value);
        return;
    }
    if (props.allowCreate) {
        useTypedName();
        return;
    }
    query.value = props.productName || '';
}
</script>
