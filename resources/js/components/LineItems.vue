<template>
    <div>
        <div class="table-responsive">
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
                            <input class="form-control" :value="amount(line).toFixed(2)" disabled>
                        </td>
                        <td>
                            <button type="button" class="btn btn-outline-danger" @click="remove(line)">X</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <button type="button" class="btn btn-outline-primary btn-sm" @click="add">Add item</button>
    </div>
</template>

<script setup>
import { lineAmount } from '../utils/quotationMath';

const props = defineProps({
    items: { type: Array, required: true },
    products: { type: Array, default: () => [] },
    taxes: { type: Array, default: () => [] },
    showCost: { type: Boolean, default: false },
    quotationMode: { type: Boolean, default: false },
});

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
