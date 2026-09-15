<template>
    <div>
        <div v-for="line in items" :key="line.key" class="row g-2 align-items-end mb-2">
            <div class="col-md-5">
                <label class="form-label">Product</label>
                <select v-model.number="line.product_id" class="form-select" @change="onProduct(line)">
                    <option :value="0">Select product</option>
                    <option v-for="p in products" :key="p.id" :value="p.id">{{ p.name }} ({{ p.sku }})</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Qty</label>
                <input v-model.number="line.quantity" type="number" min="1" class="form-control">
            </div>
            <div class="col-md-2">
                <label class="form-label">{{ showCost ? 'Unit cost' : 'Unit price' }}</label>
                <input v-if="showCost" v-model.number="line.unit_cost" type="number" min="0" step="0.01" class="form-control">
                <input v-else v-model.number="line.unit_price" type="number" min="0" step="0.01" class="form-control">
            </div>
            <div class="col-md-2">
                <label class="form-label">Line</label>
                <input class="form-control" :value="lineTotal(line)" disabled>
            </div>
            <div class="col-md-1">
                <button type="button" class="btn btn-outline-danger" @click="remove(line)">X</button>
            </div>
        </div>
        <button type="button" class="btn btn-outline-primary btn-sm" @click="add">Add item</button>
    </div>
</template>

<script setup>
const props = defineProps({
    items: { type: Array, required: true },
    products: { type: Array, default: () => [] },
    showCost: { type: Boolean, default: false },
});

function add() {
    props.items.push({
        key: Date.now() + Math.random(),
        product_id: 0,
        quantity: 1,
        unit_price: 0,
        unit_cost: 0,
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

function lineTotal(line) {
    const price = props.showCost ? line.unit_cost : line.unit_price;
    return ((line.quantity || 0) * (price || 0)).toFixed(2);
}
</script>
