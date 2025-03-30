<template>
    <div class="payment-methods-container">
        <div class="section-header">
            <h1>Payment Methods</h1>
            <button class="btn-primary" @click="showAddPaymentMethodModal = true">
                <i class="fas fa-plus"></i> Add New
            </button>
        </div>

        <div class="payment-methods-grid">
            <div v-if="loading" class="loading-overlay">
                <div class="spinner"></div>
            </div>

            <div v-else-if="paymentMethods.length === 0" class="no-data">
                <i class="fas fa-credit-card"></i>
                <p>No payment methods added yet</p>
            </div>

            <div v-else class="payment-method-cards">
                <!-- MFS Payment Methods -->
                <div v-if="mfsPaymentMethods.length > 0" class="payment-method-section">
                    <h2>Mobile Financial Services</h2>

                    <div class="method-cards">
                        <div v-for="method in mfsPaymentMethods" :key="method.id" class="method-card">
                            <div class="method-logo">
                                <img :src="getMfsLogo(method)" :alt="method.payment_gateway?.name || 'MFS Provider'"/>
                            </div>

                            <div class="method-details">
                                <h3>{{ method.payment_gateway?.name || 'MFS Provider' }}</h3>
                                <p>{{ maskAccount(method.account_number) }}</p>
                                <span v-if="method.is_default" class="default-badge">Default</span>
                            </div>

                            <div class="method-actions">
                                <button class="btn-icon" @click="editPaymentMethod(method)">
                                    <i class="fas fa-pencil-alt"></i>
                                </button>
                                <button class="btn-icon" @click="removePaymentMethod(method)">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                                <button v-if="!method.is_default" class="btn-icon" @click="setAsDefault(method)">
                                    <i class="far fa-star"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Card Payment Methods -->
                <div v-if="cardPaymentMethods.length > 0" class="payment-method-section">
                    <h2>Cards</h2>

                    <div class="method-cards">
                        <div v-for="method in cardPaymentMethods" :key="method.id" class="method-card card-type">
                            <div class="card-brand-logo">
                                <img :src="getCardLogo(method)" :alt="method.card_brand || 'Card'"/>
                            </div>

                            <div class="method-details">
                                <h3>{{ method.card_brand || 'Card' }}</h3>
                                <p>**** **** **** {{ method.last_four }}</p>
                                <p class="card-expiry">Expires: {{ method.expiry_month }}/{{ method.expiry_year }}</p>
                                <span v-if="method.is_default" class="default-badge">Default</span>
                            </div>

                            <div class="method-actions">
                                <button class="btn-icon" @click="editPaymentMethod(method)">
                                    <i class="fas fa-pencil-alt"></i>
                                </button>
                                <button class="btn-icon" @click="removePaymentMethod(method)">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                                <button v-if="!method.is_default" class="btn-icon" @click="setAsDefault(method)">
                                    <i class="far fa-star"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add Payment Method Modal -->
        <div v-if="showAddPaymentMethodModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Add Payment Method</h2>
                    <button class="close-btn" @click="showAddPaymentMethodModal = false">&times;</button>
                </div>

                <div class="modal-body">
                    <div class="payment-type-selector">
                        <div
                            class="payment-type"
                            :class="{ active: newPaymentMethod.type === 'mfs' }"
                            @click="newPaymentMethod.type = 'mfs'"
                        >
                            <i class="fas fa-mobile-alt"></i>
                            <span>Mobile Financial Service</span>
                        </div>

                        <div
                            class="payment-type"
                            :class="{ active: newPaymentMethod.type === 'card' }"
                            @click="newPaymentMethod.type = 'card'"
                        >
                            <i class="fas fa-credit-card"></i>
                            <span>Credit/Debit Card</span>
                        </div>
                    </div>

                    <form @submit.prevent="addPaymentMethod" class="payment-method-form">
                        <!-- MFS Form Fields -->
                        <div v-if="newPaymentMethod.type === 'mfs'" class="form-fields">
                            <div class="form-group">
                                <label for="payment_gateway">Payment Provider</label>
                                <select
                                    id="payment_gateway"
                                    v-model="newPaymentMethod.payment_gateway_id"
                                    required
                                    class="form-control"
                                >
                                    <option value="">Select Provider</option>
                                    <option
                                        v-for="gateway in mfsGateways"
                                        :key="gateway.id"
                                        :value="gateway.id"
                                    >
                                        {{ gateway.name }}
                                    </option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="account_number">Account Number</label>
                                <input
                                    type="text"
                                    id="account_number"
                                    v-model="newPaymentMethod.account_number"
                                    required
                                    class="form-control"
                                    placeholder="e.g. 01XXXXXXXXX"
                                />
                            </div>

                            <div class="form-group">
                                <label for="account_holder_name">Account Holder Name</label>
                                <input
                                    type="text"
                                    id="account_holder_name"
                                    v-model="newPaymentMethod.account_holder_name"
                                    required
                                    class="form-control"
                                />
                            </div>
                        </div>

                        <!-- Card Form Fields -->
                        <div v-if="newPaymentMethod.type === 'card'" class="form-fields">
                            <div class="form-group">
                                <label for="card_number">Card Number</label>
                                <input
                                    type="text"
                                    id="card_number"
                                    v-model="newPaymentMethod.card_number"
                                    required
                                    class="form-control"
                                    placeholder="XXXX XXXX XXXX XXXX"
                                />
                            </div>

                            <div class="form-row">
                                <div class="form-group half">
                                    <label for="expiry_month">Expiry Month</label>
                                    <select
                                        id="expiry_month"
                                        v-model="newPaymentMethod.expiry_month"
                                        required
                                        class="form-control"
                                    >
                                        <option value="">MM</option>
                                        <option v-for="month in 12" :key="month"
                                                :value="String(month).padStart(2, '0')">
                                            {{ String(month).padStart(2, '0') }}
                                        </option>
                                    </select>
                                </div>

                                <div class="form-group half">
                                    <label for="expiry_year">Expiry Year</label>
                                    <select
                                        id="expiry_year"
                                        v-model="newPaymentMethod.expiry_year"
                                        required
                                        class="form-control"
                                    >
                                        <option value="">YY</option>
                                        <option v-for="year in 10" :key="year"
                                                :value="String((new Date().getFullYear() + year) % 100).padStart(2, '0')">
                                            {{ String((new Date().getFullYear() + year) % 100).padStart(2, '0') }}
                                        </option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="cvv">CVV</label>
                                <input
                                    type="password"
                                    id="cvv"
                                    v-model="newPaymentMethod.cvv"
                                    required
                                    class="form-control"
                                    placeholder="XXX"
                                    maxlength="4"
                                />
                            </div>

                            <div class="form-group">
                                <label for="card_holder_name">Card Holder Name</label>
                                <input
                                    type="text"
                                    id="card_holder_name"
                                    v-model="newPaymentMethod.account_holder_name"
                                    required
                                    class="form-control"
                                />
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="checkbox-control">
                                <input
                                    type="checkbox"
                                    id="set_default"
                                    v-model="newPaymentMethod.is_default"
                                />
                                <label for="set_default">Set as default payment method</label>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="button" class="btn-secondary" @click="showAddPaymentMethodModal = false">
                                Cancel
                            </button>
                            <button type="submit" class="btn-primary" :disabled="addingPaymentMethod">
                                <span v-if="addingPaymentMethod">
                                    <i class="fas fa-spinner fa-spin"></i> Adding...
                                </span>
                                <span v-else>Add Payment Method</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Remove Payment Method Confirmation Modal -->
        <div v-if="showRemoveModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Remove Payment Method</h2>
                    <button class="close-btn" @click="showRemoveModal = false">&times;</button>
                </div>

                <div class="modal-body">
                    <p>Are you sure you want to remove this payment method?</p>

                    <div v-if="paymentMethodToRemove" class="method-details-confirm">
                        <p v-if="paymentMethodToRemove.type === 'mfs'">
                            <strong>{{ paymentMethodToRemove.payment_gateway?.name || 'MFS Provider' }}:</strong>
                            {{ maskAccount(paymentMethodToRemove.account_number) }}
                        </p>
                        <p v-else>
                            <strong>{{ paymentMethodToRemove.card_brand || 'Card' }}:</strong>
                            **** {{ paymentMethodToRemove.last_four }}
                        </p>
                    </div>

                    <div class="confirm-actions">
                        <button class="btn-secondary" @click="showRemoveModal = false">Cancel</button>
                        <button class="btn-danger" @click="confirmRemovePaymentMethod"
                                :disabled="removingPaymentMethod">
                            <span v-if="removingPaymentMethod">
                                <i class="fas fa-spinner fa-spin"></i> Removing...
                            </span>
                            <span v-else>Remove</span>
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
            paymentMethods: [],
            paymentGateways: [],
            loading: true,
            showAddPaymentMethodModal: false,
            showRemoveModal: false,
            addingPaymentMethod: false,
            removingPaymentMethod: false,
            paymentMethodToRemove: null,
            newPaymentMethod: {
                type: 'mfs',
                payment_gateway_id: '',
                account_number: '',
                account_holder_name: '',
                card_number: '',
                expiry_month: '',
                expiry_year: '',
                cvv: '',
                is_default: false
            }
        };
    },
    computed: {
        mfsPaymentMethods() {
            return this.paymentMethods.filter(method => method.type === 'mfs');
        },
        cardPaymentMethods() {
            return this.paymentMethods.filter(method => method.type === 'card');
        },
        mfsGateways() {
            // Ensure paymentGateways exists and is an array before filtering
            return (this.paymentGateways || []).filter(gateway =>
                ['bkash', 'rocket', 'nagad', 'upay'].includes(gateway.code)
            );
        },
        cardGateways() {
            // Ensure paymentGateways exists and is an array before filtering
            return (this.paymentGateways || []).filter(gateway =>
                ['sslcommerz', 'stripe'].includes(gateway.code)
            );
        }
    },
    created() {
        // Fetch payment gateways first, then fetch payment methods
        this.fetchPaymentGateways()
            .then(() => this.fetchPaymentMethods())
            .catch(error => {
                console.error('Error initializing payment data', error);
                this.loading = false;
            });
    },
    methods: {
        fetchPaymentGateways() {
            return new Promise((resolve, reject) => {
                axios.get('/api/payment-gateways')
                    .then(response => {
                        this.paymentGateways = response.data.data || [];
                        resolve();
                    })
                    .catch(error => {
                        console.error('Error fetching payment gateways', error);
                        this.paymentGateways = [];
                        reject(error);
                    });
            });
        },
        fetchPaymentMethods() {
            axios.get('/api/payment-methods')
                .then(response => {
                    this.paymentMethods = response.data.data || [];
                })
                .catch(error => {
                    console.error('Error fetching payment methods', error);
                    this.paymentMethods = [];
                })
                .finally(() => {
                    this.loading = false;
                });
        },
        getMfsLogo(method) {
            const logoMap = {
                'bkash': '/img/gateways/bkash.png',
                'rocket': '/img/gateways/rocket.png',
                'nagad': '/img/gate',
                'upay': '/img/gateways/upay.png'
            };

            return logoMap[method.payment_gateway?.code] || '/img/gateways/default.png';
        },
        getCardLogo(method) {
            const logoMap = {
                'visa': '/img/cards/visa.png',
                'mastercard': '/img/cards/mastercard.png',
                'amex': '/img/cards/amex.png',
                'discover': '/img/cards/discover.png'
            };

            return logoMap[(method.card_brand || '').toLowerCase()] || '/img/cards/default.png';
        },
        maskAccount(accountNumber) {
            if (!accountNumber) return '';

            // Show only last 4 digits
            const lastFour = accountNumber.slice(-4);
            const masked = '*'.repeat(Math.max(0, accountNumber.length - 4)) + lastFour;

            return masked;
        },
        addPaymentMethod() {
            this.addingPaymentMethod = true;

            let paymentData = {
                type: this.newPaymentMethod.type,
                payment_gateway_id: this.newPaymentMethod.payment_gateway_id,
                account_holder_name: this.newPaymentMethod.account_holder_name,
                is_default: this.newPaymentMethod.is_default
            };

            if (this.newPaymentMethod.type === 'mfs') {
                paymentData.account_number = this.newPaymentMethod.account_number;
            } else {
                paymentData.card_number = this.newPaymentMethod.card_number;
                paymentData.expiry_month = this.newPaymentMethod.expiry_month;
                paymentData.expiry_year = this.newPaymentMethod.expiry_year;
                paymentData.cvv = this.newPaymentMethod.cvv;
            }

            axios.post('/api/payment-methods', paymentData)
                .then(response => {
                    // Success message
                    this.$toasted.success('Payment method added successfully');

                    // Close modal and reset form
                    this.showAddPaymentMethodModal = false;
                    this.resetNewPaymentMethodForm();

                    // Refresh payment methods list
                    this.fetchPaymentMethods();
                })
                .catch(error => {
                    const message = error.response?.data?.message || 'Failed to add payment method';
                    this.$toasted.error(message);
                })
                .finally(() => {
                    this.addingPaymentMethod = false;
                });
        },
        editPaymentMethod(method) {
            // Placeholder for future edit functionality
            this.$toasted.info('Edit functionality will be implemented soon');
        },
        removePaymentMethod(method) {
            this.paymentMethodToRemove = method;
            this.showRemoveModal = true;
        },
        confirmRemovePaymentMethod() {
            if (!this.paymentMethodToRemove) return;

            this.removingPaymentMethod = true;

            axios.delete(`/api/payment-methods/${this.paymentMethodToRemove.id}`)
                .then(response => {
                    this.$toasted.success('Payment method removed successfully');
                    this.showRemoveModal = false;
                    this.fetchPaymentMethods();
                })
                .catch(error => {
                    const message = error.response?.data?.message || 'Failed to remove payment method';
                    this.$toasted.error(message);
                })
                .finally(() => {
                    this.removingPaymentMethod = false;
                    this.paymentMethodToRemove = null;
                });
        },
        setAsDefault(method) {
            axios.post(`/api/payment-methods/${method.id}/set-default`)
                .then(response => {
                    this.$toasted.success('Default payment method updated');
                    this.fetchPaymentMethods();
                })
                .catch(error => {
                    const message = error.response?.data?.message || 'Failed to update default payment method';
                    this.$toasted.error(message);
                });
        },
        resetNewPaymentMethodForm() {
            this.newPaymentMethod = {
                type: 'mfs',
                payment_gateway_id: '',
                account_number: '',
                account_holder_name: '',
                card_number: '',
                expiry_month: '',
                expiry_year: '',
                cvv: '',
                is_default: false
            };
        }
    }
};
</script>


<style scoped>
.payment-methods-container {
    max-width: 1152px;
    margin-left: auto;
    margin-right: auto;
    padding-left: 1rem;
    padding-right: 1rem;
    padding-top: 1.5rem;
    padding-bottom: 1.5rem;
    background-color: white;
    box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
    border-radius: 0.5rem;
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
    padding-left: 1rem;
    padding-right: 1rem;
    padding-top: 0.5rem;
    padding-bottom: 0.5rem;
    border-radius: 0.375rem;
    transition-property: background-color;
    transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
    transition-duration: 150ms;
}

.btn-primary:hover {
    background-color: #1d4ed8;
}

.btn-primary i {
    margin-right: 0.5rem;
}

.payment-methods-grid {
    margin-top: 1.5rem;
    margin-bottom: 1.5rem;
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
    padding-top: 3rem;
    padding-bottom: 3rem;
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

.payment-method-section {
    background-color: white;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    border-radius: 0.5rem;
    padding: 1.5rem;
}

.payment-method-section h2 {
    font-size: 1.25rem;
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 1px solid #e5e7eb;
}

.method-cards {
    display: grid;
    grid-template-columns: repeat(1, minmax(0, 1fr));
    gap: 1.5rem;
}

@media (min-width: 768px) {
    .method-cards {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (min-width: 1024px) {
    .method-cards {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }
}

.method-card {
    background-color: white;
    border: 1px solid #e5e7eb;
    border-radius: 0.5rem;
    box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
    padding: 1.5rem;
    position: relative;
    transition-property: all;
    transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
    transition-duration: 150ms;
}

.method-card:hover {
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.method-logo, .card-brand-logo {
    margin-bottom: 1rem;
    display: flex;
    justify-content: center;
}

.method-logo img, .card-brand-logo img {
    height: 3rem;
    width: 3rem;
    object-fit: contain;
}

.method-details {
    text-align: center;
}

.method-details h3 {
    font-size: 1.125rem;
    font-weight: 600;
    color: #1f2937;
}

.method-details p {
    color: #4b5563;
}

.default-badge {
    position: absolute;
    top: 0.5rem;
    right: 0.5rem;
    background-color: #10b981;
    color: white;
    font-size: 0.75rem;
    padding-left: 0.5rem;
    padding-right: 0.5rem;
    padding-top: 0.25rem;
    padding-bottom: 0.25rem;
    border-radius: 9999px;
}

.method-actions {
    display: flex;
    justify-content: center;
    margin-top: 1rem;
}

.method-actions > * + * {
    margin-left: 0.5rem;
}

.btn-icon {
    color: #4b5563;
    padding: 0.5rem;
    border-radius: 9999px;
    transition-property: background-color, color;
    transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
    transition-duration: 150ms;
}

.btn-icon:hover {
    color: #2563eb;
    background-color: #eff6ff;
}

.modal {
    position: fixed;
    inset: 0;
    z-index: 50;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow-x: hidden;
    overflow-y: auto;
    outline: none;
}

.modal-content {
    position: relative;
    width: 100%;
    max-width: 28rem;
    margin-left: auto;
    margin-right: auto;
    margin-top: 1.5rem;
    margin-bottom: 1.5rem;
    background-color: white;
    border-radius: 0.5rem;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
}

.modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1.25rem;
    border-bottom: 1px solid #e5e7eb;
}

.modal-header h2 {
    font-size: 1.25rem;
    font-weight: 600;
    color: #1f2937;
}

.close-btn {
    color: #9ca3af;
    font-size: 1.875rem;
    line-height: 1;
}

.close-btn:hover {
    color: #4b5563;
}

.modal-body {
    padding: 1.5rem;
}

.payment-type-selector {
    display: flex;
    margin-bottom: 1.5rem;
}

.payment-type-selector > * + * {
    margin-left: 1rem;
}

.payment-type {
    flex: 1 1 0%;
    text-align: center;
    padding-top: 0.75rem;
    padding-bottom: 0.75rem;
    border: 1px solid #d1d5db;
    border-radius: 0.375rem;
    cursor: pointer;
    transition-property: background-color;
    transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
    transition-duration: 150ms;
}

.payment-type:hover {
    background-color: #f9fafb;
}

.payment-type.active {
    background-color: #eff6ff;
    border-color: #3b82f6;
    color: #1d4ed8;
}

.payment-type i {
    display: block;
    font-size: 1.875rem;
    margin-bottom: 0.5rem;
    color: #4b5563;
}

.payment-type.active i {
    color: #2563eb;
}

.payment-method-form > * + * {
    margin-top: 1rem;
}

.form-group > * + * {
    margin-top: 0.5rem;
}

.form-group label {
    display: block;
    font-size: 0.875rem;
    font-weight: 500;
    color: #374151;
}

.form-control {
    display: block;
    width: 100%;
    padding: 0.5rem 0.75rem;
    border: 1px solid #d1d5db;
    border-radius: 0.375rem;
    box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
}

.form-control:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.5);
}

.form-row {
    display: flex;
}

.form-row > * + * {
    margin-left: 1rem;
}

.form-row .form-group.half {
    flex: 1 1 0%;
}

.checkbox-control {
    display: flex;
    align-items: center;
}

.checkbox-control > * + * {
    margin-left: 0.5rem;
}

.checkbox-control input {
    height: 1rem;
    width: 1rem;
    color: #2563eb;
    border-color: #d1d5db;
    border-radius: 0.25rem;
}

.form-actions {
    display: flex;
    justify-content: space-between;
    margin-top: 1.5rem;
}

.btn-secondary {
    padding: 0.5rem 1rem;
    border: 1px solid #d1d5db;
    color: #374151;
    border-radius: 0.375rem;
    transition-property: background-color;
    transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
    transition-duration: 150ms;
}

.btn-secondary:hover {
    background-color: #f9fafb;
}

.btn-danger {
    padding: 0.5rem 1rem;
    background-color: #dc2626;
    color: white;
    border-radius: 0.375rem;
    transition-property: background-color;
    transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
    transition-duration: 150ms;
}

.btn-danger:hover {
    background-color: #b91c1c;
}
</style>
