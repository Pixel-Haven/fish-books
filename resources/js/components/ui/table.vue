<template>
    <div class="relative w-full overflow-x-auto">
        <table :class="['w-full caption-bottom text-sm', $attrs.class]">
            <slot></slot>
        </table>
    </div>
</template>

<script setup lang="ts">
defineOptions({
    inheritAttrs: false
})
</script>
