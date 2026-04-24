<script setup lang="ts">
defineProps<{ class?: string }>();
</script>

<template>
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 40 42" fill="currentColor" :class="$props.class">
        <path fill-rule="evenodd" d="M17.2 5.653a5.9 5.9 0 0 1 5.599 0l12.3 6.572a5.898 5.898 0 0 1 3.109 5.197v7.156a5.898 5.898 0 0 1-3.109 5.197l-12.3 6.572a5.9 5.9 0 0 1-5.599 0l-12.3-6.572a5.898 5.898 0 0 1-3.109-5.197v-7.156A5.898 5.898 0 0 1 4.9 12.225l12.3-6.572ZM20 10.83l-11.46 6.123L20 23.076l11.46-6.123L20 10.83Z" clip-rule="evenodd"/>
    </svg>
</template>
