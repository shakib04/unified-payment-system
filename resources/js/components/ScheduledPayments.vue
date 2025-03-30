<template>
    <div class="scheduled-payments-container">
        <div class="section-header">
            <h1>Scheduled Payments</h1>
            <button class="btn-primary" @click="showAddScheduledPaymentModal = true">
                <i class="fas fa-plus"></i> Schedule New Payment
            </button>
        </div>

        <div class="scheduled-payments-content">
            <div v-if="loading" class="loading-overlay">
                <div class="spinner"></div>
            </div>

            <div v-else-if="scheduledPayments.length === 0" class="no-data">
                <i class="fas fa-calendar-alt"></i>
                <p>No scheduled payments yet</p>
                <button class="btn-primary" @click="showAddScheduledPaymentModal = true">
                    Schedule a Payment
                </button>
            </div>

            <div v-else>
                <div class="filter-controls">
                    <div class="filter-group">
                        <label for="status-filter">Status</label>
                        <select id="status-filter" v-model="filters.status" class="form-control">
                            <option value="">All Statuses</option>
                            <option value="active">Active</option>
                            <option value="paused">Paused</option>
                            <option value="completed">Completed</option>
                            <option value="failed">Failed</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label for="frequency-filter">Frequency</label>
                        <select id="frequency-filter" v-model="filters.frequency" class="form-control">
                            <option value="">All Frequencies</option>
                            <option value="one-time">One-time</option>
                            <option value="weekly">Weekly</option>
                            <option value="monthly">Monthly</option>
                            <option value="quarterly">Quarterly</option>
                            <option value="yearly">Yearly</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label for="payment-type-filter">Payment Type</label>
                        <select id="payment-type-filter" v-model="filters.paymentType" class="form-control">
                            <option value="">All Types</option>
                            <option value="bill">Bill Payment</option>
                            <option value="transfer">Transfer</option>
                            <option value="subscription">Subscription</option>
                        </select>
                    </div>
                </div>

                <div class="scheduled-payments-list">
                    <div
                        v-for="payment in filteredScheduledPayments"
                        :key="payment.id"
                        class="scheduled-payment-card"
                    >
                        <div class="payment-icon" :class="getPaymentTypeClass(payment.payment_type)">
                            <i :class="getPaymentTypeIcon(payment.payment_type)"></i>
                        </div>

                        <div class="payment-details">
                            <h3>{{ payment.name }}</h3>
                            <p class="recipient">To: {{ payment.recipient_name }}</p>

                            <div class="payment-info">
                                <div class="info-item">
                                    <span class="info-label">Amount</span>
                                    <span class="info-value">{{ payment.amount | currency }}</span>
                                </div>

                                <div class="info-item">
                                    <span class="info-label">Frequency</span>
                                    <span class="info-value">{{ formatFrequency(payment.frequency) }}</span>
                                </div>

                                <div class="info-item">
                                    <span class="info-label">Next Payment</span>
                                    <span class="info-value">{{ payment.next_scheduled | formatDate }}</span>
                                </div>
                            </div>

                            <div class="payment-status">
                                <span :class="getStatusClass(payment.status)">{{ payment.status }}</span>
                            </div>
                        </div>

                        <div class="payment-actions">
                            <button
                                v-if="payment.status === 'active'"
                                class="btn-icon"
                                @click="pauseScheduledPayment(payment)"
                                title="Pause"
                            >
                                <i class="fas fa-pause"></i>
                            </button>

                            <button
                                v-if="payment.status === 'paused'"
                                class="btn-icon"
                                @click="resumeScheduledPayment(payment)"
                                title="Resume"
                            >
                                <i class="fas fa-play"></i>
                            </button>

                            <button
                                class="btn-icon"
                                @click="editScheduledPayment(payment)"
                                title="Edit"
                            >
                                <i class="fas fa-pencil-alt"></i>
                            </button>

                            <button
                                class="btn-icon"
                                @click="cancelScheduledPayment(payment)"
                                title="Cancel"
                            >
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add Scheduled Payment Modal -->
        <div v-if="showAddScheduledPaymentModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Schedule a Payment</h2>
                    <button class="close-btn" @click="showAddScheduledPaymentModal = false">&times;</button>
                </div>

                <div class="modal-body">
                    <form @submit.prevent="addScheduledPayment" class="scheduled-payment-form">
                        <div class="form-group">
                            <label for="payment_type">Payment Type</label>
                            <select
                                id="payment_type"
                                v-model="newScheduledPayment.payment_type"
                                required
                                class="form-control"
                                @change="handlePaymentTypeChange"
                            >
                                <option value="">Select Payment Type</option>
                                <option value="bill">Bill Payment</option>
                                <option value="transfer">Bank Transfer</option>
                                <option value="subscription">Subscription</option>
                            </select>
                        </div>

                        <!-- Bill Payment Options -->
                        <div v-if="newScheduledPayment.payment_type === 'bill'" class="form-group">
                            <label for="bill_id">Select Bill</label>
                            <select
                                id="bill_id"
                                v-model="newScheduledPayment.bill_id"
                                required
                                class="form-control"
                                @change="handleBillSelection"
                            >
                                <option value="">Select a Bill</option>
                                <option
                                    v-for="bill in bills"
                                    :key="bill.id"
                                    :value="bill.id"
                                >
                                    {{ bill.biller_name }} - {{ bill.account_number }}
                                </option>
                            </select>
                        </div>

                        <!-- Transfer Options -->
                        <template v-if="newScheduledPayment.payment_type === 'transfer'">
                            <div class="form-group">
                                <label for="recipient_name">Recipient Name</label>
                                <input
                                    type="text"
                                    id="recipient_name"
                                    v-model="newScheduledPayment.recipient_name"
                                    required
                                    class="form-control"
                                />
                            </div>

                            <div class="form-group">
                                <label for="recipient_account">Account Number</label>
                                <input
                                    type="text"
                                    id="recipient_account"
                                    v-model="newScheduledPayment.recipient_account"
                                    required
                                    class="form-control"
                                />
                            </div>

                            <div class="form-group">
                                <label for="recipient_bank">Bank Name</label>
                                <input
                                    type="text"
                                    id="recipient_bank"
                                    v-model="newScheduledPayment.recipient_bank"
                                    required
                                    class="form-control"
                                />
                            </div>
                        </template>

                        <!-- Common Fields -->
                        <div class="form-group">
                            <label for="payment_name">Payment Name</label>
                            <input
                                type="text"
                                id="payment_name"
                                v-model="newScheduledPayment.name"
                                required
                                class="form-control"
                                :placeholder="getPaymentNamePlaceholder()"
                            />
                        </div>

                        <div class="form-group">
                            <label for="payment_amount">Amount (BDT)</label>
                            <input
                                type="number"
                                id="payment_amount"
                                v-model="newScheduledPayment.amount"
                                min="1"
                                step="0.01"
                                required
                                class="form-control"
                            />
                        </div>

                        <div class="form-group">
                            <label for="payment_frequency">Frequency</label>
                            <select
                                id="payment_frequency"
                                v-model="newScheduledPayment.frequency"
                                required
                                class="form-control"
                            >
                                <option value="">Select Frequency</option>
                                <option value="one-time">One-time</option>
                                <option value="weekly">Weekly</option>
                                <option value="monthly">Monthly</option>
                                <option value="quarterly">Quarterly</option>
                                <option value="yearly">Yearly</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="start_date">Start Date</label>
                            <input
                                type="date"
                                id="start_date"
                                v-model="newScheduledPayment.start_date"
                                required
                                class="form-control"
                                :min="getTodayDate()"
                            />
                        </div>

                        <div v-if="newScheduledPayment.frequency !== 'one-time'" class="form-group">
                            <label for="end_date">End Date (Optional)</label>
                            <input
                                type="date"
                                id="end_date"
                                v-model="newScheduledPayment.end_date"
                                class="form-control"
                                :min="newScheduledPayment.start_date"
                            />
                        </div>

                        <div class="form-group">
                            <label for="payment_method">Payment Method</label>
                            <select
                                id="payment_method"
                                v-model="newScheduledPayment.payment_method_id"
                                class="form-control"
                            >
                                <option value="">Select Payment Method</option>
                                <option
                                    v-for="method in paymentMethods"
                                    :key="method.id"
                                    :value="method.id"
                                >
                                    {{ method.type === 'mfs' ? method.account_number : `${method.card_brand} **** ${method.last_four}` }}
                                </option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="bank_account">or Bank Account</label>
                            <select
                                id="bank_account"
                                v-model="newScheduledPayment.bank_account_id"
                                class="form-control"
                            >
                                <option value="">Select Bank Account</option>
                                <option
                                    v-for="account in bankAccounts"
                                    :key="account.id"
                                    :value="account.id"
                                >
                                    {{ account.bank_name }} - {{ maskAccountNumber(account.account_number) }}
                                </option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="description">Description (Optional)</label>
                            <textarea
                                id="description"
                                v-model="newScheduledPayment.description"
                                class="form-control"
                                rows="3"
                            ></textarea>
                        </div>

                        <div class="form-actions">
                            <button type="button" class="btn-secondary" @click="showAddScheduledPaymentModal = false">
                                Cancel
                            </button>
                            <button type="submit" class="btn-primary" :disabled="addingScheduledPayment">
                <span v-if="addingScheduledPayment">
                  <i class="fas fa-spinner fa-spin"></i> Scheduling...
                </span>
                                <span v-else>Schedule Payment</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Cancel Scheduled Payment Confirmation Modal -->
        <div v-if="showCancelModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Cancel Scheduled Payment</h2>
                    <button class="close-btn" @click="showCancelModal = false">&times;</button>
                </div>

                <div class="modal-body">
                    <p>Are you sure you want to cancel this scheduled payment?</p>

                    <div v-if="scheduledPaymentToCancel" class="payment-details-confirm">
                        <p><strong>{{ scheduledPaymentToCancel.name }}</strong></p>
                        <p>To: {{ scheduledPaymentToCancel.recipient_name }}</p>
                        <p>Amount: {{ scheduledPaymentToCancel.amount | currency }}</p>
                        <p>Frequency: {{ formatFrequency(scheduledPaymentToCancel.frequency) }}</p>
                    </div>

                    <div class="confirm-actions">
                        <button class="btn-secondary" @click="showCancelModal = false">Keep it</button>
                        <button class="btn-danger" @click="confirmCancelScheduledPayment" :disabled="cancellingScheduledPayment">
              <span v-if="cancellingScheduledPayment">
                <i class="fas fa-spinner fa-spin"></i> Cancelling...
              </span>
                            <span v-else>Cancel Payment</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    data() {
        return {
            scheduledPayments: [],
            bills: [],
            paymentMethods: [],
            bankAccounts: [],
            loading: true,
            filters: {
                status: '',
                frequency: '',
                paymentType: ''
            },
            showAddScheduledPaymentModal: false,
            showCancelModal: false,
            addingScheduledPayment: false,
            cancellingScheduledPayment: false,
            scheduledPaymentToCancel: null,
            newScheduledPayment: {
                name: '',
                recipient_name: '',
                recipient_account: '',
                recipient_bank: '',
                payment_type: '',
                bill_id: '',
                amount: '',
                currency: 'BDT',
                frequency: '',
                start_date: '',
                end_date: '',
                description: '',
                payment_method_id: '',
                bank_account_id: ''
            }
        };
    },
    computed: {
        filteredScheduledPayments() {
            return this.scheduledPayments.filter(payment => {
                // Apply status filter
                if (this.filters.status && payment.status !== this.filters.status) {
                    return false;
                }

                // Apply frequency filter
                if (this.filters.frequency && payment.frequency !== this.filters.frequency) {
                    return false;
                }

                // Apply payment type filter
                if (this.filters.paymentType && payment.payment_type !== this.filters.paymentType) {
                    return false;
                }

                return true;
            });
        }
    },
    created() {
        this.fetchScheduledPayments();
        this.fetchBills();
        this.fetchPaymentMethods();
        this.fetchBankAccounts();

        // Set default start date to today
        this.newScheduledPayment.start_date = this.getTodayDate();
    },
    methods: {
        fetchScheduledPayments() {
            axios.get('/api/scheduled-payments')
                .then(response => {
                    this.scheduledPayments = response.data.data;
                })
                .catch(error => {
                    console.error('Error fetching scheduled payments', error);
                })
                .finally(() => {
                    this.loading = false;
                });
        },
        fetchBills() {
            axios.get('/api/bills')
                .then(response => {
                    this.bills = response.data.data;
                })
                .catch(error => {
                    console.error('Error fetching bills', error);
                });
        },
        fetchPaymentMethods() {
            axios.get('/api/payment-methods')
                .then(response => {
                    this.paymentMethods = response.data.data;
                })
                .catch(error => {
                    console.error('Error fetching payment methods', error);
                });
        },
        fetchBankAccounts() {
            axios.get('/api/bank-accounts')
                .then(response => {
                    this.bankAccounts = response.data.data;
                })
                .catch(error => {
                    console.error('Error fetching bank accounts', error);
                });
        },
        getPaymentTypeClass(type) {
            const classes = {
                'bill': 'type-bill',
                'transfer': 'type-transfer',
                'subscription': 'type-subscription'
            };

            return classes[type] || 'type-default';
        },
        getPaymentTypeIcon(type) {
            const icons = {
                'bill': 'fas fa-file-invoice',
                'transfer': 'fas fa-exchange-alt',
                'subscription': 'fas fa-sync-alt'
            };

            return icons[type] || 'fas fa-money-bill-wave';
        },
        formatFrequency(frequency) {
            const formats = {
                'one-time': 'One-time',
                'weekly': 'Weekly',
                'monthly': 'Monthly',
                'quarterly': 'Quarterly',
                'yearly': 'Yearly'
            };

            return formats[frequency] || frequency;
        },
        getStatusClass(status) {
            const classes = {
                'active': 'status-active',
                'paused': 'status-paused',
                'completed': 'status-completed',
                'failed': 'status-failed'
            };

            return classes[status] || 'status-default';
        },
        getTodayDate() {
            const today = new Date();
            const year = today.getFullYear();
            const month = String(today.getMonth() + 1).padStart(2, '0');
            const day = String(today.getDate()).padStart(2, '0');

            return `${year}-${month}-${day}`;
        },
        maskAccountNumber(accountNumber) {
            if (!accountNumber) return '';

            // Show only last 4 digits
            const lastFour = accountNumber.slice(-4);
            const masked = '*'.repeat(accountNumber.length - 4) + lastFour;

            return masked;
        },
        getPaymentNamePlaceholder() {
            const types = {
                'bill': 'Monthly Electricity Bill',
                'transfer': 'Rent Payment',
                'subscription': 'Netflix Subscription'
            };

            return types[this.newScheduledPayment.payment_type] || 'Payment Name';
        },
        handlePaymentTypeChange() {
            // Reset fields when payment type changes
            this.newScheduledPayment.bill_id = '';
            this.newScheduledPayment.recipient_name = '';
            this.newScheduledPayment.recipient_account = '';
            this.newScheduledPayment.recipient_bank = '';

            // Auto-generate a name based on payment type
            const namePrefix = {
                'bill': 'Bill Payment',
                'transfer': 'Transfer',
                'subscription': 'Subscription'
            };

            this.newScheduledPayment.name = namePrefix[this.newScheduledPayment.payment_type] || '';
        },
        handleBillSelection() {
            if (this.newScheduledPayment.bill_id) {
                const selectedBill = this.bills.find(bill => bill.id === this.newScheduledPayment.bill_id);

                if (selectedBill) {
                    // Populate recipient fields from the selected bill
                    this.newScheduledPayment.recipient_name = selectedBill.biller_name;
                    this.newScheduledPayment.amount = selectedBill.amount || '';

                    // Update payment name
                    this.newScheduledPayment.name = `${selectedBill.biller_name} Bill Payment`;

                    // Use bill's default payment method if available
                    if (selectedBill.default_payment_method_id) {
                        this.newScheduledPayment.payment_method_id = selectedBill.default_payment_method_id;
                    }

                    // Use bill's default bank account if available
                    if (selectedBill.default_bank_account_id) {
                        this.newScheduledPayment.bank_account_id = selectedBill.default_bank_account_id;
                    }
                }
            }
        },
        addScheduledPayment() {
            // Validate that either payment_method_id or bank_account_id is provided
            if (!this.newScheduledPayment.payment_method_id && !this.newScheduledPayment.bank_account_id) {
                this.$toasted.error('Please select a payment method or bank account');
                return;
            }

            this.addingScheduledPayment = true;

            axios.post('/api/scheduled-payments', this.newScheduledPayment)
                .then(response => {
                    this.$toasted.success('Payment scheduled successfully');
                    this.showAddScheduledPaymentModal = false;
                    this.resetNewScheduledPaymentForm();
                    this.fetchScheduledPayments();
                })
                .catch(error => {
                    const message = error.response?.data?.message || 'Failed to schedule payment';
                    this.$toasted.error(message);
                })
                .finally(() => {
                    this.addingScheduledPayment = false;
                });
        },
        editScheduledPayment(payment) {
            // Implementation can be added for editing scheduled payments
            alert('Edit functionality will be implemented soon.');
        },
        pauseScheduledPayment(payment) {
            axios.post(`/api/scheduled-payments/${payment.id}/pause`)
                .then(response => {
                    this.$toasted.success('Payment schedule paused');
                    this.fetchScheduledPayments();
                })
                .catch(error => {
                    const message = error.response?.data?.message || 'Failed to pause scheduled payment';
                    this.$toasted.error(message);
                });
        },
        resumeScheduledPayment(payment) {
            axios.post(`/api/scheduled-payments/${payment.id}/resume`)
                .then(response => {
                    this.$toasted.success('Payment schedule resumed');
                    this.fetchScheduledPayments();
                })
                .catch(error => {
                    const message = error.response?.data?.message || 'Failed to resume scheduled payment';
                    this.$toasted.error(message);
                });
        },
        cancelScheduledPayment(payment) {
            this.scheduledPaymentToCancel = payment;
            this.showCancelModal = true;
        },
        confirmCancelScheduledPayment() {
            if (!this.scheduledPaymentToCancel) return;

            this.cancellingScheduledPayment = true;

            axios.post(`/api/scheduled-payments/${this.scheduledPaymentToCancel.id}/cancel`)
                .then(response => {
                    this.$toasted.success('Scheduled payment cancelled');
                    this.showCancelModal = false;
                    this.fetchScheduledPayments();
                })
                .catch(error => {
                    const message = error.response?.data?.message || 'Failed to cancel scheduled payment';
                    this.$toasted.error(message);
                })
                .finally(() => {
                    this.cancellingScheduledPayment = false;
                    this.scheduledPaymentToCancel = null;
                });
        },
        resetNewScheduledPaymentForm() {
            this.newScheduledPayment = {
                name: '',
                recipient_name: '',
                recipient_account: '',
                recipient_bank: '',
                payment_type: '',
                bill_id: '',
                amount: '',
                currency: 'BDT',
                frequency: '',
                start_date: this.getTodayDate(),
                end_date: '',
                description: '',
                payment_method_id: '',
                bank_account_id: ''
            };
        }
    },
    filters: {
        currency(value) {
            return '৳ ' + Number(value).toLocaleString('en-BD');
        },
        formatDate(value) {
            if (!value) return 'N/A';

            const date = new Date(value);
            return date.toLocaleDateString('en-BD', { day: 'numeric', month: 'short', year: 'numeric' });
        }
    }
};
</script>

<style scoped>
.scheduled-payments-container {
    max-width: 1152px;
    margin: 0 auto;
    padding: 1.5rem;
    background-color: white;
    border-radius: 0.5rem;
    box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid #e5e7eb;
}

.section-header h1 {
    font-size: 1.5rem;
    font-weight: 700;
    color: #1f2937;
}

.btn-primary {
    display: flex;
    align-items: center;
    background-color: #2563eb;
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 0.375rem;
    transition: background-color 0.15s;
}

.btn-primary:hover {
    background-color: #1d4ed8;
}

.btn-primary i {
    margin-right: 0.5rem;
}

.loading-overlay {
    display: flex;
    justify-content: center;
    align-items: center;
    height: 16rem;
}

.spinner {
    animation: spin 1s linear infinite;
    border-radius: 9999px;
    height: 3rem;
    width: 3rem;
    border-top: 2px solid #3b82f6;
    border-bottom: 2px solid #3b82f6;
}

@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}

.no-data {
    text-align: center;
    padding: 3rem 0;
    background-color: #f9fafb;
    border-radius: 0.5rem;
}

.no-data i {
    font-size: 4rem;
    color: #9ca3af;
    margin-bottom: 1rem;
    display: block;
}

.no-data p {
    font-size: 1.125rem;
    color: #4b5563;
    margin-bottom: 1rem;
}

.filter-controls {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    margin-bottom: 1.5rem;
    padding: 1rem;
    background-color: #f9fafb;
    border-radius: 0.5rem;
}

.filter-group {
    flex: 1;
    min-width: 200px;
}

.filter-group label {
    display: block;
    font-size: 0.875rem;
    font-weight: 500;
    margin-bottom: 0.5rem;
    color: #4b5563;
}

.form-control {
    width: 100%;
    padding: 0.5rem 0.75rem;
    border: 1px solid #d1d5db;
    border-radius: 0.375rem;
    font-size: 0.875rem;
}

.form-control:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.5);
}

.scheduled-payments-list {
    display: grid;
    gap: 1rem;
}

.scheduled-payment-card {
    display: flex;
    background-color: white;
    border: 1px solid #e5e7eb;
    border-radius: 0.5rem;
    padding: 1rem;
    position: relative;
    transition: box-shadow 0.2s, transform 0.2s;
}

.scheduled-payment-card:hover {
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    transform: translateY(-2px);
}

.payment-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 3rem;
    height: 3rem;
    border-radius: 50%;
    margin-right: 1rem;
    flex-shrink: 0;
}

.type-bill {
    background-color: #eff6ff;
    color: #1d4ed8;
}

.type-transfer {
    background-color: #ecfdf5;
    color: #065f46;
}

.type-subscription {
    background-color: #f3e8ff;
    color: #6d28d9;
}

.type-default {
    background-color: #f3f4f6;
    color: #4b5563;
}

.payment-icon i {
    font-size: 1.25rem;
}

.payment-details {
    flex: 1;
}

.payment-details h3 {
    font-size: 1.125rem;
    font-weight: 600;
    color: #1f2937;
    margin: 0 0 0.25rem 0;
}

.payment-details .recipient {
    color: #6b7280;
    font-size: 0.875rem;
    margin-bottom: 0.75rem;
}

.payment-info {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    margin-bottom: 0.5rem;
}

.info-item {
    font-size: 0.875rem;
}

.info-label {
    color: #6b7280;
    margin-right: 0.5rem;
}

.info-value {
    font-weight: 500;
    color: #1f2937;
}

.payment-status {
    font-size: 0.75rem;
    font-weight: 500;
    padding: 0.25rem 0.5rem;
    border-radius: 9999px;
    display: inline-block;
}

.status-active {
    background-color: #ecfdf5;
    color: #065f46;
}

.status-paused {
    background-color: #fff7ed;
    color: #9a3412;
}

.status-completed {
    background-color: #eff6ff;
    color: #1e40af;
}

.status-failed {
    background-color: #fef2f2;
    color: #b91c1c;
}

.status-default {
    background-color: #f3f4f6;
    color: #4b5563;
}

.payment-actions {
    display: flex;
    align-items: flex-start;
    gap: 0.5rem;
}

.btn-icon {
    color: #6b7280;
    width: 2rem;
    height: 2rem;
    border-radius: 9999px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background-color 0.15s, color 0.15s;
}

.btn-icon:hover {
    background-color: #f3f4f6;
    color: #1f2937;
}

.modal {
    position: fixed;
    inset: 0;
    z-index: 50;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: rgba(0, 0, 0, 0.5);
    overflow-y: auto;
}

.modal-content {
    width: 100%;
    max-width: 32rem;
    margin: 1rem;
    background-color: white;
    border-radius: 0.5rem;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 1.5rem;
    border-bottom: 1px solid #e5e7eb;
}

.modal-header h2 {
    font-size: 1.25rem;
    font-weight: 600;
    color: #1f2937;
}

.close-btn {
    font-size: 1.5rem;
    line-height: 1;
    color: #9ca3af;
    background: none;
    border: none;
    cursor: pointer;
}

.close-btn:hover {
    color: #1f2937;
}

.modal-body {
    padding: 1.5rem;
    max-height: 70vh;
    overflow-y: auto;
}

.scheduled-payment-form {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.form-group {
    margin-bottom: 1rem;
}

.form-group label {
    display: block;
    font-size: 0.875rem;
    font-weight: 500;
    margin-bottom: 0.5rem;
    color: #4b5563;
}

textarea.form-control {
    resize: vertical;
}

.form-actions {
    display: flex;
    justify-content: space-between;
    margin-top: 1.5rem;
}

.btn-secondary {
    padding: 0.5rem 1rem;
    background-color: white;
    color: #4b5563;
    border: 1px solid #d1d5db;
    border-radius: 0.375rem;
    transition: background-color 0.15s;
}

.btn-secondary:hover {
    background-color: #f9fafb;
}

.btn-danger {
    padding: 0.5rem 1rem;
    background-color: #ef4444;
    color: white;
    border-radius: 0.375rem;
    transition: background-color 0.15s;
}

.btn-danger:hover {
    background-color: #dc2626;
}

.payment-details-confirm {
    background-color: #f9fafb;
    padding: 1rem;
    border-radius: 0.375rem;
    margin: 1rem 0;
}

.confirm-actions {
    display: flex;
    justify-content: flex-end;
    gap: 1rem;
    margin-top: 1.5rem;
}

@media (max-width: 640px) {
    .payment-info {
        flex-direction: column;
        gap: 0.5rem;
    }

    .scheduled-payment-card {
        flex-direction: column;
    }

    .payment-icon {
        margin-bottom: 1rem;
        margin-right: 0;
        align-self: center;
    }

    .payment-actions {
        margin-top: 1rem;
        justify-content: center;
    }
}
</style>
