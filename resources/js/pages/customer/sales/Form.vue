<template>
    <DocumentEditor kind="sale" role="customer" :id="route.params.id" :title="route.params.id ? 'Edit sale' : 'New sale'" />
</template>
<script setup>
import { useRoute } from 'vue-router';
import DocumentEditor from '../../../components/DocumentEditor.vue';
const route = useRoute();
</script>
