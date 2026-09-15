<template>
    <DocumentEditor kind="purchase" :id="route.params.id" :title="route.params.id ? 'Edit purchase' : 'New purchase'" />
</template>
<script setup>
import { useRoute } from 'vue-router';
import DocumentEditor from '../../../components/DocumentEditor.vue';
const route = useRoute();
</script>
