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
    }

    async function fetchSettings() {
        const { data } = await axios.get('/api/business');
        apply(data.data);
        return data.data;
    }

    return { currency, name, currencies, formatMoney, apply, fetchSettings };
});
