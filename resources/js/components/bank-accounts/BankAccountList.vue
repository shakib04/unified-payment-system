<template>
    <div class="bank-accounts-container">
        <div class="section-header">
            <h1>Bank Accounts</h1>
            <button class="btn-primary" @click="showAddBankAccountModal = true">
                <i class="fas fa-plus"></i> Add Account
            </button>
        </div>

        <div class="bank-accounts-grid">
            <div v-if="loading" class="loading-overlay">
                <div class="spinner"></div>
            </div>

            <div v-else-if="bankAccounts.length === 0" class="no-data">
                <i class="fas fa-university"></i>
                <p>No bank accounts linked yet</p>
            </div>

            <div v-else class="bank-account-cards">
                <div v-for="account in bankAccounts" :key="account.id" class="bank-account-card">
                    <div class="bank-logo">
                        <img :src="getBankLogo(account.bank_name)" :alt="account.bank_name" />
                    </div>

                    <div class="account-details">
                        <h3>{{ account.bank_name }}</h3>
                        <p>{{ account.account_name }}</p>
                        <p class="account-number">{{ maskAccountNumber(account.account_number) }}</p>
                        <p class="account-type">{{ formatAccountType(account.account_type) }}</p>
                        <span v-if="account.is_primary" class="primary-badge">Primary</span>
                        <span v-if="!account.is_active" class="inactive-badge">Inactive</span>
                    </div>

                    <div class="account-actions">
                        <button class="btn-icon" @click="editBankAccount(account)">
                            <i class="fas fa-pencil-alt"></i>
                        </button>
                        <button class="btn-icon" @click="removeBankAccount(account)">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                        <button v-if="!account.is_primary" class="btn-icon" @click="setAsPrimary(account)">
                            <i class="far fa-star"></i>
                        </button>
                    </div>

                    <div class="account-balance">
                        <span class="balance-label">Balance</span>
                        <span class="balance-amount">{{ account.balance | currency }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add Bank Account Modal -->
        <div v-if="showAddBankAccountModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Link Bank Account</h2>
                    <button class="close-btn" @click="showAddBankAccountModal = false">&times;</button>
                </div>

                <div class="modal-body">
                    <form @submit.prevent="addBankAccount" class="bank-account-form">
                        <div class="form-group">
                            <label for="bank_name">Bank Name</label>
                            <select
                                id="bank_name"
                                v-model="newBankAccount.bank_name"
                                required
                                class="form-control"
                            >
                                <option value="">Select Bank</option>
                                <option v-for="bank in bankList" :key="bank" :value="bank">
                                    {{ bank }}
                                </option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="account_number">Account Number</label>
                            <input
                                type="text"
                                id="account_number"
                                v-model="newBankAccount.account_number"
                                required
                                class="form-control"
                            />
                        </div>

                        <div class="form-group">
                            <label for="account_name">Account Holder Name</label>
                            <input
                                type="text"
                                id="account_name"
                                v-model="newBankAccount.account_name"
                                required
                                class="form-control"
                            />
                        </div>

                        <div class="form-group">
                            <label for="account_type">Account Type</label>
                            <select
                                id="account_type"
                                v-model="newBankAccount.account_type"
                                required
                                class="form-control"
                            >
                                <option value="">Select Account Type</option>
                                <option value="savings">Savings</option>
                                <option value="current">Current</option>
                                <option value="fixed">Fixed Deposit</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="branch_name">Branch Name</label>
                            <input
                                type="text"
                                id="branch_name"
                                v-model="newBankAccount.branch_name"
                                class="form-control"
                            />
                        </div>

                        <div class="form-group">
                            <label for="routing_number">Routing Number</label>
                            <input
                                type="text"
                                id="routing_number"
                                v-model="newBankAccount.routing_number"
                                class="form-control"
                            />
                        </div>

                        <div class="form-group">
                            <div class="checkbox-control">
                                <input
                                    type="checkbox"
                                    id="set_primary"
                                    v-model="newBankAccount.is_primary"
                                />
                                <label for="set_primary">Set as primary account</label>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="button" class="btn-secondary" @click="showAddBankAccountModal = false">
                                Cancel
                            </button>
                            <button type="submit" class="btn-primary" :disabled="addingBankAccount">
                <span v-if="addingBankAccount">
                  <i class="fas fa-spinner fa-spin"></i> Adding...
                </span>
                                <span v-else>Link Account</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Remove Bank Account Confirmation Modal -->
        <div v-if="showRemoveModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Remove Bank Account</h2>
                    <button class="close-btn" @click="showRemoveModal = false">&times;</button>
                </div>

                <div class="modal-body">
                    <p>Are you sure you want to remove this bank account?</p>

                    <div v-if="bankAccountToRemove" class="account-details-confirm">
                        <p>
                            <strong>{{ bankAccountToRemove.bank_name }}:</strong>
                            {{ maskAccountNumber(bankAccountToRemove.account_number) }}
                        </p>
                        <p>{{ bankAccountToRemove.account_name }} ({{ bankAccountToRemove.account_type }})</p>
                    </div>

                    <div class="confirm-actions">
                        <button class="btn-secondary" @click="showRemoveModal = false">Cancel</button>
                        <button class="btn-danger" @click="confirmRemoveBankAccount" :disabled="removingBankAccount">
              <span v-if="removingBankAccount">
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
            bankAccounts: [],
            loading: true,
            showAddBankAccountModal: false,
            showRemoveModal: false,
            addingBankAccount: false,
            removingBankAccount: false,
            bankAccountToRemove: null,
            newBankAccount: {
                bank_name: '',
                account_number: '',
                account_name: '',
                account_type: '',
                branch_name: '',
                routing_number: '',
                is_primary: false
            },
            bankList: [
                'BRAC Bank',
                'Dutch-Bangla Bank',
                'Eastern Bank',
                'Islami Bank Bangladesh',
                'Janata Bank',
                'Sonali Bank',
                'Standard Chartered Bank',
                'Pubali Bank',
                'City Bank',
                'Prime Bank',
                'Mutual Trust Bank',
                'NCC Bank',
                'Southeast Bank',
                'Bank Asia',
                'Trust Bank',
                'AB Bank',
                'Dhaka Bank',
                'EXIM Bank',
                'National Bank',
                'One Bank',
                'Social Islami Bank',
                'United Commercial Bank',
                'Uttara Bank',
                'Rupali Bank',
                'Agrani Bank',
                'Basic Bank',
                'First Security Islami Bank',
                'Mercantile Bank',
                'Premier Bank',
                'Jamuna Bank',
                'Shahjalal Islami Bank'
            ]
        };
    },
    created() {
        this.fetchBankAccounts();
    },
    methods: {
        fetchBankAccounts() {
            axios.get('/api/bank-accounts')
                .then(response => {
                    if (response.data.success) {
                        this.bankAccounts = response.data.data;
                    } else {
                        console.error('Error in API response:', response.data);
                    }
                })
                .catch(error => {
                    console.error('Error fetching bank accounts:', error);
                })
                .finally(() => {
                    this.loading = false;
                });
        },
        getBankLogo(bankName) {
            // Map of bank names to logo images
            const logoMap = {
                'BRAC Bank': '/img/banks/brac.png',
                'Dutch-Bangla Bank': '/img/banks/dbbl.png',
                'Eastern Bank': '/img/banks/ebl.png',
                'Islami Bank Bangladesh': '/img/banks/ibbl.png',
                'Janata Bank': '/img/banks/janata.png',
                'Sonali Bank': '/img/banks/sonali.png',
                'Standard Chartered Bank': '/img/banks/scb.png',
                'Pubali Bank': '/img/banks/pubali.png',
                'City Bank': '/img/banks/city.png',
                'Prime Bank': '/img/banks/prime.png'
            };

            return logoMap[bankName] || '/img/banks/default.png';
        },
        maskAccountNumber(accountNumber) {
            if (!accountNumber) return '';

            // Show only last 4 digits
            const lastFour = accountNumber.slice(-4);
            const masked = '*'.repeat(accountNumber.length - 4) + lastFour;

            return masked;
        },
        formatAccountType(type) {
            const types = {
                'savings': 'Savings Account',
                'current': 'Current Account',
                'fixed': 'Fixed Deposit'
            };

            return types[type] || type;
        },
        addBankAccount() {
            this.addingBankAccount = true;

            axios.post('/api/bank-accounts', this.newBankAccount)
                .then(response => {
                    if (response.data.success) {
                        // Show success message
                        alert('Bank account linked successfully');

                        // Close modal and reset form
                        this.showAddBankAccountModal = false;
                        this.resetNewBankAccountForm();

                        // Refresh bank accounts list
                        this.fetchBankAccounts();
                    } else {
                        alert(response.data.message || 'Failed to link bank account');
                    }
                })
                .catch(error => {
                    const message = error.response?.data?.message || 'Failed to link bank account';
                    alert(message);
                })
                .finally(() => {
                    this.addingBankAccount = false;
                });
        },
        editBankAccount(account) {
            // Redirect to edit page
            window.location.href = `/bank-accounts/${account.id}/edit`;
        },
        removeBankAccount(account) {
            this.bankAccountToRemove = account;
            this.showRemoveModal = true;
        },
        confirmRemoveBankAccount() {
            if (!this.bankAccountToRemove) return;

            this.removingBankAccount = true;

            axios.delete(`/api/bank-accounts/${this.bankAccountToRemove.id}`)
                .then(response => {
                    if (response.data.success) {
                        alert('Bank account removed successfully');
                        this.showRemoveModal = false;
                        this.fetchBankAccounts();
                    } else {
                        alert(response.data.message || 'Failed to remove bank account');
                    }
                })
                .catch(error => {
                    const message = error.response?.data?.message || 'Failed to remove bank account';
                    alert(message);
                })
                .finally(() => {
                    this.removingBankAccount = false;
                    this.bankAccountToRemove = null;
                });
        },
        setAsPrimary(account) {
            axios.post(`/api/bank-accounts/${account.id}/set-primary`)
                .then(response => {
                    if (response.data.success) {
                        alert('Primary bank account updated');
                        this.fetchBankAccounts();
                    } else {
                        alert(response.data.message || 'Failed to update primary bank account');
                    }
                })
                .catch(error => {
                    const message = error.response?.data?.message || 'Failed to update primary bank account';
                    alert(message);
                });
        },
        resetNewBankAccountForm() {
            this.newBankAccount = {
                bank_name: '',
                account_number: '',
                account_name: '',
                account_type: '',
                branch_name: '',
                routing_number: '',
                is_primary: false
            };
        }
    },
    filters: {
        currency(value) {
            if (value === undefined || value === null) return '৳ 0';
            return '৳ ' + Number(value).toLocaleString('en-BD');
        }
    }
}
</script>

<style scoped>
/* Add your styles here if not already included in your global CSS */
.bank-accounts-container {
    margin: 20px auto;
    max-width: 1200px;
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.btn-primary {
    background-color: #4CAF50;
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 4px;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
}

.loading-overlay {
    display: flex;
    justify-content: center;
    align-items: center;
    height: 200px;
}

.spinner {
    border: 4px solid #f3f3f3;
    border-top: 4px solid #3498db;
    border-radius: 50%;
    width: 30px;
    height: 30px;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.no-data {
    text-align: center;
    padding: 40px;
    background-color: #f9f9f9;
    border-radius: 8px;
}

.bank-account-cards {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
}

.bank-account-card {
    border: 1px solid #ddd;
    border-radius: 8px;
    overflow: hidden;
    background-color: #fff;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.bank-logo {
    background-color: #f5f5f5;
    padding: 20px;
    text-align: center;
    height: 100px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.bank-logo img {
    max-width: 100%;
    max-height: 60px;
}

.account-details {
    padding: 15px;
}

.account-details h3 {
    margin-top: 0;
    margin-bottom: 10px;
    font-size: 18px;
}

.account-number {
    font-family: monospace;
    margin: 5px 0;
}

.account-type {
    color: #666;
    margin: 5px 0;
}

.primary-badge {
    display: inline-block;
    background-color: #4CAF50;
    color: white;
    font-size: 12px;
    padding: 3px 8px;
    border-radius: 10px;
    margin-top: 5px;
}

.inactive-badge {
    display: inline-block;
    background-color: #f44336;
    color: white;
    font-size: 12px;
    padding: 3px 8px;
    border-radius: 10px;
    margin-top: 5px;
    margin-left: 5px;
}

.account-actions {
    display: flex;
    justify-content: flex-end;
    padding: 10px 15px;
    gap: 10px;
}

.btn-icon {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: #f5f5f5;
    border: none;
    cursor: pointer;
}

.btn-icon:hover {
    background-color: #e0e0e0;
}

.account-balance {
    background-color: #f9f9f9;
    padding: 10px 15px;
    display: flex;
    justify-content: space-between;
    border-top: 1px solid #eee;
}

.balance-amount {
    font-weight: bold;
    color: #2E7D32;
}

/* Modal styles */
.modal {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: rgba(0,0,0,0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
}

.modal-content {
    background-color: #fff;
    border-radius: 8px;
    max-width: 500px;
    width: 90%;
    max-height: 90vh;
    overflow-y: auto;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 20px;
    border-bottom: 1px solid #eee;
}

.modal-header h2 {
    margin: 0;
    font-size: 20px;
}

.close-btn {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: #666;
}

.modal-body {
    padding: 20px;
}

.form-group {
    margin-bottom: 15px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 500;
}

.form-control {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 16px;
}

.checkbox-control {
    display: flex;
    align-items: center;
}

.checkbox-control input {
    margin-right: 8px;
}

.form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    margin-top: 20px;
}

.btn-secondary {
    background-color: #f5f5f5;
    color: #333;
    border: 1px solid #ddd;
    padding: 8px 16px;
    border-radius: 4px;
    cursor: pointer;
}

.btn-danger {
    background-color: #f44336;
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 4px;
    cursor: pointer;
}

.account-details-confirm {
    background-color: #f5f5f5;
    padding: 10px;
    border-radius: 4px;
    margin: 15px 0;
}

.confirm-actions {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    margin-top: 20px;
}
</style>
