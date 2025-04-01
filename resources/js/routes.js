import UserProfile from "./components/users/UserProfile.vue";
import Dashboard from "./components/Dashboard.vue";
import BankAccountList from "./components/bank-accounts/BankAccountList.vue";
import EditBankAccount from "./components/bank-accounts/EditBankAccount.vue";
import PaymentMethodList from "./components/payment-methods/PaymentMethodList.vue";
import BillPayment from "./components/BillPayment.vue";
import ScheduledPayments from "./components/ScheduledPayments.vue";
import Login from "./components/auth/Login.vue";
import Register from "./components/auth/Register.vue";

export const routes = [
    {path: '/', component: Dashboard},
    {path: '/bank-accounts', component: BankAccountList},
    {path: '/bank-accounts/new', component: EditBankAccount},
    {path: '/payment-methods', component: PaymentMethodList},
    {path: '/bills', component: BillPayment},
    {path: '/scheduled-payments', component: ScheduledPayments},
    {
        path: '/login',
        name: 'login',
        component: Login,
        meta: {requiresAuth: false}
    },
    {
        path: '/register',
        name: 'register',
        component: Register,
        meta: {requiresAuth: false}
    },
    {
        path: '/bank-accounts/:id/edit',
        name: 'edit-bank-account',
        component: EditBankAccount
    },
    {
        path: '/profile',
        name: 'profile',
        component: UserProfile
    }
]
