import './bootstrap';
import { createApp } from 'vue';
import { createRouter, createWebHistory } from 'vue-router';
import App from './App.vue';
// Import polyfill first
import './polyfills.js';
import '../css/app.css';

// Import Toast
import { toast } from 'vue3-toastify';
import 'vue3-toastify/dist/index.css';

// Import your components
import Dashboard from './components/Dashboard.vue';
import BankAccountList from './components/bank-accounts/BankAccountList.vue';
import PaymentMethodList from './components/payment-methods/PaymentMethodList.vue';
import BillPayment from './components/BillPayment.vue';
import ScheduledPayments from './components/ScheduledPayments.vue';
import TransactionList from './components/transactions/TransactionList.vue';
import Login from "./components/auth/Login.vue";
import Register from "./components/auth/Register.vue";
import EditBankAccount from "./components/EditBankAccount.vue";

// Create the router
const router = createRouter({
    history: createWebHistory(),
    routes: [
        { path: '/', component: Dashboard },
        { path: '/bank-accounts', component: BankAccountList },
        { path: '/bank-accounts/new', component: EditBankAccount },
        { path: '/payment-methods', component: PaymentMethodList },
        { path: '/bills', component: BillPayment },
        { path: '/scheduled-payments', component: ScheduledPayments },
        {
            path: '/login',
            name: 'login',
            component: Login,
            meta: { requiresAuth: false }
        },
        {
            path: '/register',
            name: 'register',
            component: Register,
            meta: { requiresAuth: false }
        },
        {
            path: '/bank-accounts/:id/edit',
            name: 'edit-bank-account',
            component: EditBankAccount
        }
    ]
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
