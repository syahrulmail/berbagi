<template>
    <div class="p-4">
        <h2 class="text-lg font-bold text-primary-600">Vue 3 + Tailwind OK</h2>
        <p class="text-sm text-primary-800">Count: {{ count }}</p>
        <button class="btn btn-primary mt-2" @click="count++">Tambah</button>
    </div>
</template>

<script>
export default {
    name: 'TestWidget',
    data() {
        return { count: 0 };
    },
};
</script>
