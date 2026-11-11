<script setup lang="ts">
// Applications now live on the dashboard. Keep old URLs (and old notifications) working.
definePageMeta({
    middleware: defineNuxtRouteMiddleware((to) =>
        navigateTo(
            { path: "/creator/dashboard", query: to.query, hash: "#applications" },
            { redirectCode: 301 },
        ),
    ),
});
</script>

<template><div /></template>
