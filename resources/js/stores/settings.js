import axios from 'axios';
import { defineStore } from 'pinia';
import { ref } from 'vue';

export const useSettingsStore = defineStore('settings', () => {
    const currency = ref('AED');
    const name = ref('');
    const currencies = ref({});

    function formatMoney(value) {
        const amount = Number(value || 0).toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        });
        return `${currency.value} ${amount}`;
    }

    function apply(data = {}) {
        currency.value = data.currency || 'AED';
        name.value = data.name || '';
        if (data.currencies) {
            currencies.value = data.currencies;
        }
        setDocumentTitle(name.value);
    }

    function setDocumentTitle(value) {
        if (typeof document === 'undefined') {
            return;
        }

        const brand = String(value || '').trim();
        const title = brand ? `${brand} - dashboard` : 'dashboard';
        document.title = title;

        const meta = document.querySelector('meta[name="title"]');
        if (meta) {
            meta.setAttribute('content', title);
        }
    }

    async function fetchSettings() {
        const { data } = await axios.get('/api/business');
        apply(data.data);
        return data.data;
    }

    return { currency, name, currencies, formatMoney, apply, fetchSettings };
});
