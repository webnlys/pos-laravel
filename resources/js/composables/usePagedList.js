import axios from 'axios';
import { ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';

const DEFAULT_PER_PAGE = 15;
const PAGE_SIZES = [10, 15, 25, 50];
const QUERY_KEYS = ['page', 'per_page', 'q'];

function toPositiveInt(value, fallback) {
    const n = Number(value);

    return Number.isInteger(n) && n > 0 ? n : fallback;
}

function allowedPerPage(value) {
    const n = toPositiveInt(value, DEFAULT_PER_PAGE);

    return PAGE_SIZES.includes(n) ? n : DEFAULT_PER_PAGE;
}

function queryValue(value) {
    return Array.isArray(value) ? value[0] : value;
}

function listQueryKey(query) {
    return `${queryValue(query.page) || ''}|${queryValue(query.per_page) || ''}|${queryValue(query.q) || ''}`;
}

function buildQuery({ page, perPage, q }) {
    const query = {};
    const search = String(q || '').trim();
    const nextPage = toPositiveInt(page, 1);
    const nextPerPage = allowedPerPage(perPage);

    if (search) {
        query.q = search;
    }
    if (nextPage > 1) {
        query.page = String(nextPage);
    }
    if (nextPerPage !== DEFAULT_PER_PAGE) {
        query.per_page = String(nextPerPage);
    }

    return query;
}

export function usePagedList(endpoint) {
    const route = useRoute();
    const router = useRouter();
    const rows = ref([]);
    const meta = ref({});
    const q = ref(typeof queryValue(route.query.q) === 'string' ? queryValue(route.query.q) : '');
    const perPage = ref(allowedPerPage(route.query.per_page));

    async function fetchRows() {
        const { data } = await axios.get(endpoint, {
            params: {
                q: queryValue(route.query.q) || '',
                page: toPositiveInt(route.query.page, 1),
                per_page: allowedPerPage(route.query.per_page),
            },
        });
        rows.value = data.data;
        meta.value = data.meta || {};
    }

    async function load(page) {
        const extras = Object.fromEntries(
            Object.entries(route.query).filter(([key]) => !QUERY_KEYS.includes(key)),
        );
        const query = {
            ...extras,
            ...buildQuery({
                page: page ?? route.query.page,
                perPage: perPage.value,
                q: q.value,
            }),
        };

        if (listQueryKey(query) === listQueryKey(route.query)) {
            await fetchRows();
            return;
        }

        await router.replace({ path: route.path, query });
    }

    watch(
        () => listQueryKey(route.query),
        () => {
            q.value = typeof queryValue(route.query.q) === 'string' ? queryValue(route.query.q) : '';
            perPage.value = allowedPerPage(route.query.per_page);
            fetchRows();
        },
        { immediate: true },
    );

    return { rows, meta, q, perPage, load };
}
