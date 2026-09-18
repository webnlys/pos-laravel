<template>
    <div>
        <div v-if="isDesktop" class="table-wrap">
            <table class="table table-bordered align-middle">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th style="width: 90px">Qty</th>
                        <th v-if="quotationMode" style="width: 130px">Unit</th>
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
                            <ProductSuggest
                                :product-id="line.product_id"
                                :product-name="line.product_name"
                                :products="products"
                                :allow-create="allowCreate"
                                :search-url="searchUrl"
                                @select="(product) => onProduct(line, product)"
                            />
                        </td>
                        <td>
                            <input v-model.number="line.quantity" type="number" min="1" class="form-control">
                        </td>
                        <td v-if="quotationMode">
                            <NameSuggest
                                :item-id="line.unit_id"
                                :item-name="line.unit_name"
                                :items="units"
                                allow-create
                                search-url="/api/admin/units"
                                create-url="/api/admin/units"
                                placeholder="Unit"
                                noun="unit"
                                @select="(unit) => onUnit(line, unit)"
                            />
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
                <div class="mb-2">
                    <ProductSuggest
                        :product-id="line.product_id"
                        :product-name="line.product_name"
                        :products="products"
                        :allow-create="allowCreate"
                        :search-url="searchUrl"
                        @select="(product) => onProduct(line, product)"
                    />
                </div>
                <div class="row g-2">
                    <div class="col-4">
                        <label class="form-label">Qty</label>
                        <input v-model.number="line.quantity" type="number" min="1" class="form-control">
                    </div>
                    <div v-if="quotationMode" class="col-4">
                        <label class="form-label">Unit</label>
                        <NameSuggest
                            :item-id="line.unit_id"
                            :item-name="line.unit_name"
                            :items="units"
                            allow-create
                            search-url="/api/admin/units"
                            create-url="/api/admin/units"
                            placeholder="Unit"
                            noun="unit"
                            @select="(unit) => onUnit(line, unit)"
                        />
                    </div>
                    <div :class="quotationMode ? 'col-4' : 'col-8'">
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
import ProductSuggest from './ProductSuggest.vue';
import NameSuggest from './NameSuggest.vue';
import { lineAmount } from '../utils/quotationMath';
import { useSettingsStore } from '../stores/settings';

const props = defineProps({
    items: { type: Array, required: true },
    products: { type: Array, default: () => [] },
    taxes: { type: Array, default: () => [] },
    units: { type: Array, default: () => [] },
    showCost: { type: Boolean, default: false },
    quotationMode: { type: Boolean, default: false },
    allowCreate: { type: Boolean, default: false },
    searchUrl: { type: String, default: '' },
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

function emptyLine() {
    return {
        key: Date.now() + Math.random(),
        product_id: 0,
        product_name: '',
        unit_id: 0,
        unit_name: '',
        quantity: 1,
        unit_price: 0,
        unit_cost: 0,
        discount: 0,
        tax_id: null,
    };
}

function add() {
    props.items.push(emptyLine());
}

function remove(line) {
    const i = props.items.indexOf(line);
    if (i >= 0 && props.items.length > 1) {
        props.items.splice(i, 1);
    }
}

function onProduct(line, product) {
    const nextId = Number(product?.id || 0);
    const changedProduct = nextId !== Number(line.product_id);
    line.product_id = nextId;
    line.product_name = product?.name || '';
    if (changedProduct) {
        const unitName = product?.unit_name || product?.unit?.name || '';
        line.unit_id = Number(product?.unit_id || product?.unit?.id || 0);
        line.unit_name = unitName;
    }
    if (changedProduct && nextId && product.sale_price != null) {
        line.unit_price = product.sale_price;
        line.unit_cost = product.cost_price;
    }
}

function onUnit(line, unit) {
    line.unit_id = Number(unit?.id || 0);
    line.unit_name = unit?.name || '';
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

function hasProduct(line) {
    return Boolean(line.product_id || (line.product_name && String(line.product_name).trim()));
}

defineExpose({ hasProduct });
</script>
