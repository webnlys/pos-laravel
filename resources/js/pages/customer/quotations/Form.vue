<template>
    <DocumentEditor kind="quotation" role="customer" :id="route.params.id" :title="route.params.id ? 'Edit quotation' : 'New quotation'" />
</template>
<script setup>
import { useRoute } from 'vue-router';
import DocumentEditor from '../../../components/DocumentEditor.vue';
const route = useRoute();
</script>
