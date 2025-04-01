import './bootstrap';
import {createApp} from 'vue';
import {createRouter, createWebHistory} from 'vue-router';
import App from './App.vue';
// Import polyfill first
import './polyfills.js';
import '../css/app.css';

// Import Toast
import {toast} from 'vue3-toastify';
import 'vue3-toastify/dist/index.css';

// Import your components
import {routes} from "./routes.js";

// Create the router
const router = createRouter({
    history: createWebHistory(),
    routes
});

// Create a main App.vue component if it doesn't exist
// resources/js/App.vue
const app = createApp(App);

app.use(router);

// Add the toast compatibility layer
app.config.globalProperties.$toasted = {
    success: (message) => toast.success(message),
    error: (message) => toast.error(message),
    info: (message) => toast.info(message),
    warning: (message) => toast.warning(message)
};

// Mount the app
app.mount('#app');
