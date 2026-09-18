<template>
    <span class="badge rounded-pill" :class="paymentStatusClass(status)">{{ paymentStatusLabel(status) }}</span>
</template>

<script setup>
import { paymentStatusClass, paymentStatusLabel } from '../utils/paymentStatus';

defineProps({
    status: { type: String, default: 'unpaid' },
});
</script>
