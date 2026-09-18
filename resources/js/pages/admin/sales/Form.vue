<template>
    <DocumentEditor kind="sale" :id="route.params.id" :title="route.params.id ? 'Edit sales invoice' : 'New sales invoice'" />
</template>
<script setup>
import { useRoute } from 'vue-router';
import DocumentEditor from '../../../components/DocumentEditor.vue';
const route = useRoute();
</script>
