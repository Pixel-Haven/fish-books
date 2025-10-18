<template>
    <div :class="['bg-card text-card-foreground flex flex-col gap-6 rounded-xl border py-6 shadow-sm', $attrs.class]"
        :style="$attrs.style as string">
        <slot></slot>
    </div>
</template>

<script setup lang="ts">
defineOptions({
    inheritAttrs: false
})
</script>
