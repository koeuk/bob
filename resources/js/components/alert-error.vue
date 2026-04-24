<script setup lang="ts">
import Alert from '@/components/ui/Alert.vue';
import AlertDescription from '@/components/ui/AlertDescription.vue';
import AlertTitle from '@/components/ui/AlertTitle.vue';
import { AlertCircle } from 'lucide-vue-next';

defineProps<{ title?: string; errors?: string[] | string | null }>();
</script>

<template>
    <Alert v-if="errors && (Array.isArray(errors) ? errors.length : true)" variant="destructive" class="flex items-start gap-2">
        <AlertCircle />
        <div>
            <AlertTitle v-if="title">{{ title }}</AlertTitle>
            <AlertDescription>
                <template v-if="Array.isArray(errors)">
                    <ul class="list-disc pl-4"><li v-for="(e, i) in errors" :key="i">{{ e }}</li></ul>
                </template>
                <template v-else>{{ errors }}</template>
            </AlertDescription>
        </div>
    </Alert>
</template>
