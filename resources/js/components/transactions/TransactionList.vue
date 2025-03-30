<template>
    <div class="transaction-list">
        <div v-if="loading" class="loading-overlay">
            <div class="spinner"></div>
        </div>

        <div v-else-if="transactions.length === 0" class="no-data">
            <i class="fas fa-receipt"></i>
            <p>No transactions yet</p>
        </div>

        <ul v-else class="transaction-items">
            <li v-for="transaction in transactions" :key="transaction.id" class="transaction-item">
                <div class="transaction-icon" :class="getTransactionTypeClass(transaction.transaction_type)">
                    <i :class="getTransactionTypeIcon(transaction.transaction_type)"></i>
                </div>

                <div class="transaction-details">
                    <div class="transaction-primary">
                        <h4>{{ getTransactionTitle(transaction) }}</h4>
                        <span :class="getAmountClass(transaction)">{{ transaction.amount | currency }}</span>
                    </div>

                    <div class="transaction-secondary">
                        <span class="transaction-date">{{ transaction.created_at | formatDate }}</span>
                        <span class="transaction-status" :class="getStatusClass(transaction.status)">
              {{ transaction.status }}
            </span>
                    </div>
                </div>
            </li>
        </ul>
    </div>
</template>

<script>
export default {
    props: {
        transactions: {
            type: Array,
            required: true
        },
        loading: {
            type: Boolean,
            default: false
        }
    },
    methods: {
        getTransactionTypeClass(type) {
            const classes = {
                'payment': 'type-payment',
                'transfer': 'type-transfer',
                'deposit': 'type-deposit',
                'withdrawal': 'type-withdrawal'
            };

            return classes[type] || 'type-default';
        },
        getTransactionTypeIcon(type) {
            const icons = {
                'payment': 'fas fa-shopping-cart',
                'transfer': 'fas fa-exchange-alt',
                'deposit': 'fas fa-arrow-down',
                'withdrawal': 'fas fa-arrow-up'
            };

            return icons[type] || 'fas fa-receipt';
        },
        getTransactionTitle(transaction) {
            if (transaction.description) {
                return transaction.description;
            }

            const titles = {
                'payment': 'Payment',
                'transfer': `Transfer to ${transaction.recipient_name || 'Account'}`,
                'deposit': 'Deposit',
                'withdrawal': 'Withdrawal'
            };

            return titles[transaction.transaction_type] || 'Transaction';
        },
        getAmountClass(transaction) {
            const types = {
                'payment': 'amount-negative',
                'transfer': 'amount-negative',
                'deposit': 'amount-positive',
                'withdrawal': 'amount-negative'
            };

            return types[transaction.transaction_type] || 'amount-default';
        },
        getStatusClass(status) {
            const classes = {
                'completed': 'status-success',
                'pending': 'status-pending',
                'processing': 'status-processing',
                'failed': 'status-failed'
            };

            return classes[status] || 'status-default';
        }
    },
    filters: {
        currency(value) {
            return '৳ ' + Number(value).toLocaleString('en-BD');
        },
        formatDate(value) {
            if (!value) return '';

            const date = new Date(value);
            return date.toLocaleDateString('en-BD', {day: 'numeric', month: 'short', year: 'numeric'});
        }
    }
};
</script>
