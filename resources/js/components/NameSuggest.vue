<template>
    <div class="product-suggest" @focusout="onFocusOut">
        <input
            ref="inputEl"
            :value="query"
            type="text"
            class="form-control"
            :placeholder="placeholder"
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
                    v-for="(item, index) in suggestions"
                    :key="item.id"
                    :class="{ active: index === highlighted }"
                    @mousedown.prevent="pick(item)"
                >
                    {{ item.name }}
                </li>
                <li
                    v-if="canCreate"
                    :class="{ active: highlighted === suggestions.length }"
                    @mousedown.prevent="useTypedName"
                >
                    Use “{{ trimmedQuery }}” as new {{ noun }}
                </li>
            </ul>
        </Teleport>
    </div>
</template>

<script setup>
import axios from 'axios';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';

const props = defineProps({
    itemId: { type: [Number, String], default: 0 },
    itemName: { type: String, default: '' },
    items: { type: Array, default: () => [] },
    allowCreate: { type: Boolean, default: false },
    searchUrl: { type: String, default: '' },
    createUrl: { type: String, default: '' },
    placeholder: { type: String, default: 'Type to search' },
    noun: { type: String, default: 'item' },
});

const emit = defineEmits(['select']);

const inputEl = ref(null);
const query = ref(props.itemName || '');
const openList = ref(false);
const highlighted = ref(0);
const remoteItems = ref([]);
const menuStyle = ref({});
let searchTimer;
let blurTimer;
let creating = false;

const trimmedQuery = computed(() => query.value.trim());

const catalog = computed(() => {
    const byId = new Map();
    [...props.items, ...remoteItems.value].forEach((item) => {
        if (item?.id) {
            byId.set(item.id, item);
        }
    });
    return [...byId.values()];
});

const suggestions = computed(() => {
    const term = trimmedQuery.value.toLowerCase();
    const list = term
        ? catalog.value.filter((item) => String(item.name || '').toLowerCase().includes(term))
        : catalog.value;

    return list.slice(0, 8);
});

const exactMatch = computed(() => {
    const term = trimmedQuery.value.toLowerCase();
    if (!term) {
        return null;
    }
    return catalog.value.find((item) => String(item.name || '').toLowerCase() === term) || null;
});

const canCreate = computed(() => props.allowCreate && trimmedQuery.value !== '' && !exactMatch.value);

watch(() => props.itemName, (value) => {
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
        width: `${Math.max(rect.width, 180)}px`,
        zIndex: 1080,
    };
}

function onInput(event) {
    query.value = event.target.value;
    openList.value = true;
    highlighted.value = 0;
    positionMenu();
    scheduleSearch();
    syncValue();
}

function syncValue() {
    if (!trimmedQuery.value) {
        emit('select', { id: 0, name: '' });
        return;
    }
    if (exactMatch.value) {
        emit('select', exactMatch.value);
        return;
    }
    if (props.allowCreate) {
        emit('select', { id: 0, name: trimmedQuery.value });
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
        remoteItems.value = [];
        return;
    }
    try {
        const { data } = await axios.get(props.searchUrl, {
            params: { q: trimmedQuery.value, per_page: 20 },
        });
        remoteItems.value = data.data || [];
        syncValue();
    } catch {
        remoteItems.value = [];
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
    const item = suggestions.value[highlighted.value];
    if (item) {
        pick(item);
        return;
    }
    commitTyped();
}

function confirmOnTab() {
    commitTyped();
}

function pick(item) {
    query.value = item.name;
    emit('select', item);
    close();
}

async function useTypedName() {
    const name = trimmedQuery.value;
    if (!name || creating) {
        return;
    }
    query.value = name;
    if (props.createUrl) {
        creating = true;
        try {
            const { data } = await axios.post(props.createUrl, { name });
            const created = data.data || data;
            remoteItems.value = [...remoteItems.value.filter((item) => item.id !== created.id), created];
            pick(created);
            return;
        } catch {
            emit('select', { id: 0, name });
            close();
            return;
        } finally {
            creating = false;
        }
    }
    emit('select', { id: 0, name });
    close();
}

function commitTyped() {
    const name = trimmedQuery.value;
    if (!name) {
        emit('select', { id: 0, name: '' });
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
    query.value = props.itemName || '';
}
</script>
