import '../css/app.css';

import { VueQueryPlugin } from '@tanstack/vue-query';
import { createPinia } from 'pinia';
import { createApp } from 'vue';
import App from './App.vue';
import { initializeTheme } from './composables/useAppearance';
import router, { prepareScrollRestore } from './router';

// Set light / dark mode before the app mounts to avoid a flash of the wrong theme...
initializeTheme();

// Restore the saved scroll position before the first paint so a reload doesn't
// flash at the top and then jump down to where the user was.
prepareScrollRestore();

createApp(App).use(createPinia()).use(router).use(VueQueryPlugin).mount('#app');
