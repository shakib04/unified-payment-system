<template>
    <div class="bill-payment-container">
        <div class="bill-payment-header">
            <h1>Pay Your Bills</h1>
            <p>Select a biller to continue</p>
        </div>

        <!-- Bill Categories -->
        <div class="bill-categories">
            <div
                v-for="category in categories"
                :key="category.id"
                class="category-card"
                :class="{ 'active': selectedCategory && selectedCategory.id === category.id }"
                @click="selectCategory(category)"
            >
                <div class="category-icon">
                    <i :class="category.icon"></i>
                </div>
                <div class="category-name">{{ category.name }}</div>
            </div>
        </div>

        <!-- Billers Section -->
        <div v-if="selectedCategory" class="billers-section">
            <h2>{{ selectedCategory.name }} Service Providers</h2>

            <div class="billers-grid">
                <div
                    v-for="biller in filteredBillers"
                    :key="biller.id"
                    class="biller-card"
                    :class="{ 'active': selectedBiller && selectedBiller.id === biller.id }"
                    @click="selectBiller(biller)"
                >
                    <div class="biller-logo">
                        <img :src="biller.logo_url" :alt="biller.name" />
                    </div>
                    <div class="biller-name">{{ biller.name }}</div>
                </div>
            </div>
        </div>

        <!-- Bill Payment Form -->
        <div v-if="selectedBiller" class="bill-form-section">
            <h2>Pay {{ selectedBiller.name }} Bill</h2>

            <form @submit.prevent="submitBillPayment" class="bill-payment-form">
                <div class="form-group">
                    <label for="account_number">Account Number</label>
                    <input
                        type="text"
                        id="account_number"
                        v-model="billForm.account_number"
                        required
                        class="form-control"
                        placeholder="Enter your account number"
                    />
                </div>

                <div class="form-group">
                    <label for="bill_amount">Amount (BDT)</label>
                    <input
                        type="number"
                        id="bill_amount"
                        v-model.number="billForm.amount"
                        min="1"
                        step="0.01"
                        required
                        class="form-control"
                        placeholder="Enter bill amount"
                    />
                </div>

                <div class="form-group">
                    <label for="payment_method">Payment Method</label>
                    <select
                        id="payment_method"
                        v-model="billForm.payment_method_id"
                        required
                        class="form-control"
                    >
                        <option value="">Select a payment method</option>
                        <option
                            v-for="method in paymentMethods"
                            :key="method.id"
                            :value="method.id"
                        >
                            {{ formatPaymentMethod(method) }}
                        </option>
                    </select>
                </div>

                <div class="form-group">
                    <div class="checkbox-control">
                        <input
                            type="checkbox"
                            id="save_bill"
                            v-model="billForm.save_bill"
                        />
                        <label for="save_bill">Save this bill for future payments</label>
                    </div>
                </div>

                <div v-if="billForm.save_bill" class="form-group">
                    <div class="checkbox-control">
                        <input
                            type="checkbox"
                            id="auto_pay"
                            v-model="billForm.auto_pay"
                        />
                        <label for="auto_pay">Enable auto-pay for this bill</label>
                    </div>
                </div>

                <div class="bill-payment-actions">
                    <button
                        type="button"
                        class="btn-secondary"
                        @click="resetForm"
                    >
                        Cancel
                    </button>
                    <button
                        type="submit"
                        class="btn-primary"
                        :disabled="!isFormValid"
                    >
                        Pay Now
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

<script>
export default {
    data() {
        return {
            categories: [
                { id: 1, name: 'Electricity', icon: 'fas fa-bolt' },
                { id: 2, name: 'Water', icon: 'fas fa-tint' },
                { id: 3, name: 'Gas', icon: 'fas fa-fire' },
                { id: 4, name: 'Internet', icon: 'fas fa-wifi' },
                { id: 5, name: 'Mobile', icon: 'fas fa-mobile-alt' },
                { id: 6, name: 'TV', icon: 'fas fa-tv' },
                { id: 7, name: 'Education', icon: 'fas fa-graduation-cap' },
                { id: 8, name: 'Other', icon: 'fas fa-ellipsis-h' }
            ],
            billers: [],
            paymentMethods: [],
            selectedCategory: null,
            selectedBiller: null,
            billForm: {
                account_number: '',
                amount: null,
                payment_method_id: '',
                save_bill: false,
                auto_pay: false
            }
        };
    },
    computed: {
        filteredBillers() {
            if (!this.selectedCategory) return [];
            return this.billers.filter(biller =>
                biller.category_id === this.selectedCategory.id
            );
        },
        isFormValid() {
            return this.selectedBiller &&
                this.billForm.account_number &&
                this.billForm.amount > 0 &&
                this.billForm.payment_method_id;
        }
    },
    created() {
        this.fetchInitialData();
    },
    methods: {
        fetchInitialData() {
            Promise.all([
                this.fetchBillers(),
                this.fetchPaymentMethods()
            ]).catch(error => {
                console.error('Failed to load initial data:', error);
                // Use a toast or alert to show error
                alert('Failed to load bill payment options');
            });
        },
        fetchBillers() {
            return axios.get('/api/billers')
                .then(response => {
                    this.billers = response.data.data || [];
                });
        },
        fetchPaymentMethods() {
            return axios.get('/api/payment-methods')
                .then(response => {
                    this.paymentMethods = response.data.data || [];
                });
        },
        selectCategory(category) {
            // Toggle category selection
            this.selectedCategory = this.selectedCategory?.id === category.id
                ? null
                : category;

            // Reset biller and form when category changes
            this.selectedBiller = null;
            this.resetForm();
        },
        selectBiller(biller) {
            // Toggle biller selection
            this.selectedBiller = this.selectedBiller?.id === biller.id
                ? null
                : biller;

            // Reset form when biller changes
            this.resetForm();
        },
        formatPaymentMethod(method) {
            return method.type === 'mfs'
                ? method.account_number
                : `${method.card_brand || 'Card'} **** ${method.last_four}`;
        },
        resetForm() {
            // Reset form fields
            this.billForm = {
                account_number: '',
                amount: null,
                payment_method_id: '',
                save_bill: false,
                auto_pay: false
            };
        },
        submitBillPayment() {
            // Validate form before submission
            if (!this.isFormValid) {
                alert('Please complete all required fields');
                return;
            }

            // Prepare payment data
            const paymentData = {
                biller_id: this.selectedBiller.id,
                biller_name: this.selectedBiller.name,
                bill_type: '1',
                account_number: this.billForm.account_number,
                amount: this.billForm.amount,
                payment_method_id: this.billForm.payment_method_id,
                save_bill: this.billForm.save_bill,
                auto_pay: this.billForm.auto_pay
            };

            // Submit payment
            axios.post('/api/bills', paymentData)
                .then(response => {
                    // Handle successful payment
                    alert('Payment submitted successfully');
                    this.resetForm();
                })
                .catch(error => {
                    // Handle payment error
                    alert(error.response?.data?.message || 'Payment failed');
                });
        }
    }
};
</script>

<style scoped>
.bill-payment-container {
    max-width: 1152px;
    margin: 0 auto;
    padding: 1.5rem;
    background-color: white;
    border-radius: 0.5rem;
    box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
}

.bill-payment-header {
    text-align: center;
    margin-bottom: 2rem;
}

.bill-payment-header h1 {
    font-size: 1.875rem;
    font-weight: 700;
    color: #1f2937;
    margin-bottom: 0.5rem;
}

.bill-payment-header p {
    font-size: 1.125rem;
    color: #6b7280;
}

.bill-categories {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
}

.category-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 1rem;
    background-color: white;
    border: 1px solid #e5e7eb;
    border-radius: 0.5rem;
    transition: all 0.2s ease;
    cursor: pointer;
}

.category-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.category-card.active {
    border-color: #3b82f6;
    background-color: #eff6ff;
}

.category-icon {
    font-size: 1.875rem;
    margin-bottom: 0.75rem;
    color: #4b5563;
}

.category-card.active .category-icon {
    color: #2563eb;
}

.category-name {
    font-size: 0.875rem;
    font-weight: 500;
    text-align: center;
}

.billers-section {
    margin-bottom: 2rem;
}

.billers-section h2 {
    font-size: 1.25rem;
    font-weight: 600;
    margin-bottom: 1rem;
    color: #1f2937;
}

.billers-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: 1rem;
}

.biller-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 1rem;
    background-color: white;
    border: 1px solid #e5e7eb;
    border-radius: 0.5rem;
    transition: all 0.2s ease;
    cursor: pointer;
}

.biller-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.biller-card.active {
    border-color: #3b82f6;
    background-color: #eff6ff;
}

.biller-logo {
    height: 4rem;
    width: 4rem;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 0.75rem;
}

.biller-logo img {
    max-height: 100%;
    max-width: 100%;
    object-fit: contain;
}

.biller-name {
    font-size: 0.875rem;
    font-weight: 500;
    text-align: center;
}

.bill-form-section {
    max-width: 32rem;
    margin: 0 auto;
    padding: 1.5rem;
    background-color: #f9fafb;
    border-radius: 0.5rem;
}

.bill-form-section h2 {
    font-size: 1.25rem;
    font-weight: 600;
    margin-bottom: 1.5rem;
    color: #1f2937;
    text-align: center;
}

.bill-payment-form {
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

.form-control {
    width: 100%;
    padding: 0.625rem 0.75rem;
    font-size: 0.875rem;
    line-height: 1.25rem;
    color: #1f2937;
    background-color: white;
    border: 1px solid #d1d5db;
    border-radius: 0.375rem;
}

.form-control:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.5);
}

.checkbox-control {
    display: flex;
    align-items: center;
}

.checkbox-control input[type="checkbox"] {
    height: 1rem;
    width: 1rem;
    color: #3b82f6;
    margin-right: 0.5rem;
}

.checkbox-control label {
    margin-bottom: 0;
}

.bill-payment-actions {
    display: flex;
    justify-content: space-between;
    margin-top: 1.5rem;
}

.btn-secondary {
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
    font-weight: 500;
    color: #374151;
    background-color: white;
    border: 1px solid #d1d5db;
    border-radius: 0.375rem;
    cursor: pointer;
    transition: background-color 0.2s ease;
}

.btn-secondary:hover {
    background-color: #f3f4f6;
}

.btn-primary {
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
    font-weight: 500;
    color: white;
    background-color: #3b82f6;
    border: 1px solid transparent;
    border-radius: 0.375rem;
    cursor: pointer;
    transition: background-color 0.2s ease;
}

.btn-primary:hover {
    background-color: #2563eb;
}

.btn-primary:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

@media (max-width: 640px) {
    .bill-categories {
        grid-template-columns: repeat(2, 1fr);
    }

    .billers-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}
</style>
