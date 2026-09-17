<template>
    <div>
        <div v-if="isDesktop" class="table-wrap">
            <table class="table table-bordered align-middle">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th style="width: 90px">Qty</th>
                        <th style="width: 130px">{{ showCost ? 'Unit cost' : 'Price' }}</th>
                        <th v-if="quotationMode" style="width: 120px">Discount</th>
                        <th v-if="quotationMode" style="width: 180px">Tax</th>
                        <th style="width: 130px">Amount</th>
                        <th style="width: 60px"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="line in items" :key="line.key">
                        <td>
                            <select v-model.number="line.product_id" class="form-select" @change="onProduct(line)">
                                <option :value="0">Select product</option>
                                <option v-for="p in products" :key="p.id" :value="p.id">{{ p.name }} ({{ p.sku }})</option>
                            </select>
                        </td>
                        <td>
                            <input v-model.number="line.quantity" type="number" min="1" class="form-control">
                        </td>
                        <td>
                            <input v-if="showCost" v-model.number="line.unit_cost" type="number" min="0" step="0.01" class="form-control">
                            <input v-else v-model.number="line.unit_price" type="number" min="0" step="0.01" class="form-control">
                        </td>
                        <td v-if="quotationMode">
                            <input v-model.number="line.discount" type="number" min="0" step="0.01" class="form-control">
                        </td>
                        <td v-if="quotationMode">
                            <select v-model="line.tax_id" class="form-select">
                                <option :value="null">No tax</option>
                                <option v-for="tax in taxes" :key="tax.id" :value="tax.id">{{ tax.name }} ({{ tax.rate_percent }}%)</option>
                            </select>
                        </td>
                        <td>
                            <input class="form-control" :value="money(amount(line))" disabled>
                        </td>
                        <td>
                            <button type="button" class="btn btn-outline-danger" @click="remove(line)">X</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-else class="line-cards">
            <article v-for="(line, index) in items" :key="line.key" class="line-card">
                <strong class="d-block mb-2">Item {{ index + 1 }}</strong>
                <label class="form-label">Product</label>
                <select v-model.number="line.product_id" class="form-select mb-2" @change="onProduct(line)">
                    <option :value="0">Select product</option>
                    <option v-for="p in products" :key="p.id" :value="p.id">{{ p.name }} ({{ p.sku }})</option>
                </select>
                <div class="row g-2">
                    <div class="col-4">
                        <label class="form-label">Qty</label>
                        <input v-model.number="line.quantity" type="number" min="1" class="form-control">
                    </div>
                    <div class="col-8">
                        <label class="form-label">{{ showCost ? 'Unit cost' : 'Price' }}</label>
                        <input v-if="showCost" v-model.number="line.unit_cost" type="number" min="0" step="0.01" class="form-control">
                        <input v-else v-model.number="line.unit_price" type="number" min="0" step="0.01" class="form-control">
                    </div>
                    <div v-if="quotationMode" class="col-6">
                        <label class="form-label">Discount</label>
                        <input v-model.number="line.discount" type="number" min="0" step="0.01" class="form-control">
                    </div>
                    <div v-if="quotationMode" class="col-6">
                        <label class="form-label">Tax</label>
                        <select v-model="line.tax_id" class="form-select">
                            <option :value="null">No tax</option>
                            <option v-for="tax in taxes" :key="tax.id" :value="tax.id">{{ tax.name }} ({{ tax.rate_percent }}%)</option>
                        </select>
                    </div>
                </div>
                <div class="line-card-amount">
                    <div>Amount <strong>{{ money(amount(line)) }}</strong></div>
                    <button type="button" class="line-remove-btn" @click="remove(line)">Remove</button>
                </div>
            </article>
        </div>

        <button type="button" class="btn btn-outline-primary mt-2 add-item-btn" @click="add">Add item</button>
    </div>
</template>

<script setup>
import { onMounted, onUnmounted, ref } from 'vue';
import { lineAmount } from '../utils/quotationMath';
import { useSettingsStore } from '../stores/settings';

const props = defineProps({
    items: { type: Array, required: true },
    products: { type: Array, default: () => [] },
    taxes: { type: Array, default: () => [] },
    showCost: { type: Boolean, default: false },
    quotationMode: { type: Boolean, default: false },
});

const isDesktop = ref(typeof window !== 'undefined' && window.matchMedia('(min-width: 768px)').matches);
let mediaQuery;
const settings = useSettingsStore();

function money(value) {
    return settings.formatMoney(value);
}

onMounted(() => {
    mediaQuery = window.matchMedia('(min-width: 768px)');
    isDesktop.value = mediaQuery.matches;
    mediaQuery.addEventListener('change', onBreakpoint);
});

onUnmounted(() => {
    mediaQuery?.removeEventListener('change', onBreakpoint);
});

function onBreakpoint(event) {
    isDesktop.value = event.matches;
}

function add() {
    props.items.push({
        key: Date.now() + Math.random(),
        product_id: 0,
        quantity: 1,
        unit_price: 0,
        unit_cost: 0,
        discount: 0,
        tax_id: null,
    });
}

function remove(line) {
    const i = props.items.indexOf(line);
    if (i >= 0 && props.items.length > 1) {
        props.items.splice(i, 1);
    }
}

function onProduct(line) {
    const p = props.products.find((x) => x.id === Number(line.product_id));
    if (!p) {
        return;
    }
    line.unit_price = p.sale_price;
    line.unit_cost = p.cost_price;
}

function amount(line) {
    if (props.showCost) {
        return (Number(line.quantity) || 0) * (Number(line.unit_cost) || 0);
    }
    if (props.quotationMode) {
        return lineAmount(line, props.taxes);
    }
    return (Number(line.quantity) || 0) * (Number(line.unit_price) || 0);
}
</script>
