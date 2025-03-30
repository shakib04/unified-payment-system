<template>
    <div class="upcoming-bills-list">
        <div v-if="loading" class="loading-overlay">
            <div class="spinner"></div>
        </div>

        <div v-else-if="bills.length === 0" class="no-data">
            <i class="fas fa-file-invoice-dollar"></i>
            <p>No upcoming bills</p>
        </div>

        <ul v-else class="bills-items">
            <li v-for="bill in bills" :key="bill.id" class="bill-item">
                <div class="bill-icon" :class="getBillTypeClass(bill.bill_type)">
                    <i :class="getBillTypeIcon(bill.bill_type)"></i>
                </div>

                <div class="bill-details">
                    <div class="bill-primary">
                        <h4>{{ bill.biller_name }}</h4>
                        <span class="bill-amount">{{ bill.amount | currency }}</span>
                    </div>

                    <div class="bill-secondary">
                        <div class="bill-info">
                            <span class="account-number">{{ bill.account_number }}</span>
                            <span class="due-date">Due: {{ bill.due_date | formatDate }}</span>
                        </div>
                        <div class="bill-actions">
                            <button @click="payBill(bill)" class="btn-pay">Pay Now</button>
                        </div>
                    </div>
                </div>
            </li>
        </ul>
    </div>
</template>

<script>
export default {
    props: {
        bills: {
            type: Array,
            required: true
        },
        loading: {
            type: Boolean,
            default: false
        }
    },
    methods: {
        getBillTypeClass(type) {
            const classes = {
                'electricity': 'type-electricity',
                'water': 'type-water',
                'gas': 'type-gas',
                'internet': 'type-internet',
                'phone': 'type-phone',
                'tv': 'type-tv',
                'education': 'type-education',
                'loan': 'type-loan',
                'credit_card': 'type-credit-card'
            };

            return classes[type] || 'type-default';
        },
        getBillTypeIcon(type) {
            const icons = {
                'electricity': 'fas fa-bolt',
                'water': 'fas fa-tint',
                'gas': 'fas fa-fire',
                'internet': 'fas fa-wifi',
                'phone': 'fas fa-mobile-alt',
                'tv': 'fas fa-tv',
                'education': 'fas fa-graduation-cap',
                'loan': 'fas fa-money-bill-wave',
                'credit_card': 'fas fa-credit-card'
            };

            return icons[type] || 'fas fa-file-invoice-dollar';
        },
        payBill(bill) {
            // Emit an event that the parent component can listen for
            this.$emit('pay-bill', bill);
        },
        formatDaysRemaining(dueDate) {
            if (!dueDate) return '';

            const today = new Date();
            today.setHours(0, 0, 0, 0);

            const due = new Date(dueDate);
            due.setHours(0, 0, 0, 0);

            const diffTime = due - today;
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

            if (diffDays < 0) {
                return `Overdue by ${Math.abs(diffDays)} days`;
            } else if (diffDays === 0) {
                return 'Due today';
            } else if (diffDays === 1) {
                return 'Due tomorrow';
            } else {
                return `Due in ${diffDays} days`;
            }
        }
    },
    filters: {
        currency(value) {
            if (value == null) return '৳ -';
            return '৳ ' + Number(value).toLocaleString('en-BD');
        },
        formatDate(value) {
            if (!value) return '';

            const date = new Date(value);
            return date.toLocaleDateString('en-BD', { day: 'numeric', month: 'short', year: 'numeric' });
        }
    }
}
</script>

<style scoped>
.upcoming-bills-list {
    position: relative;
    min-height: 200px;
}

.loading-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: rgba(255, 255, 255, 0.7);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 1;
}

.spinner {
    width: 40px;
    height: 40px;
    border: 4px solid rgba(0, 0, 0, 0.1);
    border-radius: 50%;
    border-top-color: #3498db;
    animation: spin 1s ease-in-out infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

.no-data {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 3rem 1rem;
    color: #6c757d;
    text-align: center;
}

.no-data i {
    font-size: 2.5rem;
    margin-bottom: 1rem;
}

.bills-items {
    list-style: none;
    padding: 0;
    margin: 0;
}

.bill-item {
    display: flex;
    padding: 1rem;
    border-bottom: 1px solid #eee;
    transition: background-color 0.2s;
}

.bill-item:hover {
    background-color: #f8f9fa;
}

.bill-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 48px;
    height: 48px;
    border-radius: 50%;
    background-color: #e9ecef;
    margin-right: 1rem;
    flex-shrink: 0;
}

.bill-details {
    flex: 1;
    min-width: 0;
}

.bill-primary {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.25rem;
}

.bill-primary h4 {
    margin: 0;
    font-size: 1rem;
    font-weight: 600;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.bill-amount {
    font-weight: 600;
    font-size: 1rem;
    white-space: nowrap;
}

.bill-secondary {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    font-size: 0.875rem;
    color: #6c757d;
}

.bill-info {
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

.account-number, .due-date {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.btn-pay {
    background-color: #28a745;
    color: white;
    border: none;
    border-radius: 4px;
    padding: 0.375rem 0.75rem;
    font-size: 0.875rem;
    cursor: pointer;
    transition: background-color 0.2s;
}

.btn-pay:hover {
    background-color: #218838;
}

/* Bill type colors */
.type-electricity {
    background-color: #ffc107;
    color: #212529;
}

.type-water {
    background-color: #17a2b8;
    color: white;
}

.type-gas {
    background-color: #fd7e14;
    color: white;
}

.type-internet {
    background-color: #6610f2;
    color: white;
}

.type-phone {
    background-color: #20c997;
    color: white;
}

.type-tv {
    background-color: #e83e8c;
    color: white;
}

.type-education {
    background-color: #007bff;
    color: white;
}

.type-loan {
    background-color: #6c757d;
    color: white;
}

.type-credit-card {
    background-color: #dc3545;
    color: white;
}

.type-default {
    background-color: #495057;
    color: white;
}
</style>
