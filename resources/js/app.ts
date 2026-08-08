import '../css/app.css';

import { VueQueryPlugin } from '@tanstack/vue-query';
import { createPinia } from 'pinia';
import { createApp } from 'vue';
import App from './App.vue';
import { initializeTheme } from './composables/useAppearance';
import router from './router';

// Set light / dark mode before the app mounts to avoid a flash of the wrong theme...
initializeTheme();

createApp(App).use(createPinia()).use(router).use(VueQueryPlugin).mount('#app');
