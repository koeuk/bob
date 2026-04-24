<script setup>
import AppearanceTabs from '@/components/appearance-tabs.vue';
import HeadingSmall from '@/components/heading-small.vue';
import AppLayout from '@/layouts/app-layout.vue';
import SettingsLayout from '@/layouts/settings-layout.vue';
import { Head } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Appearance" />
    <AppLayout>
        <SettingsLayout>
            <div class="space-y-6">
                <HeadingSmall title="Appearance" description="Update the appearance of the app" />
                <AppearanceTabs />
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
