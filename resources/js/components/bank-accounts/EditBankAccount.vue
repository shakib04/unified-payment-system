<template>
    <div class="edit-bank-account-container">
        <div class="page-header">
            <h1>Edit Bank Account</h1>
            <p>Update your bank account information</p>
        </div>

        <div class="form-container">
            <div v-if="loading" class="loading-overlay">
                <div class="spinner"></div>
            </div>

            <div v-if="error" class="alert alert-danger">
                {{ error }}
            </div>

            <div v-if="success" class="alert alert-success">
                {{ success }}
            </div>

            <form v-if="bankAccount" @submit.prevent="updateBankAccount" class="bank-account-form">
                <div class="bank-info">
                    <div class="bank-logo">
                        <img :src="getBankLogo(bankAccount.bank_name)" :alt="bankAccount.bank_name" />
                    </div>
                    <div class="bank-details">
                        <h2>{{ bankAccount.bank_name }}</h2>
                        <p class="account-number">Account: {{ maskAccountNumber(bankAccount.account_number) }}</p>
                    </div>
                </div>

                <div class="form-section">
                    <h3>Account Details</h3>

                    <div class="form-group">
                        <label for="account_name">Account Holder Name</label>
                        <input
                            type="text"
                            id="account_name"
                            v-model="formData.account_name"
                            class="form-control"
                            required
                        />
                    </div>

                    <div class="form-group">
                        <label for="account_type">Account Type</label>
                        <select
                            id="account_type"
                            v-model="formData.account_type"
                            class="form-control"
                            required
                        >
                            <option value="savings">Savings Account</option>
                            <option value="current">Current Account</option>
                            <option value="fixed">Fixed Deposit Account</option>
                        </select>
                    </div>

                    <div class="form-row">
                        <div class="form-group half">
                            <label for="branch_name">Branch Name</label>
                            <input
                                type="text"
                                id="branch_name"
                                v-model="formData.branch_name"
                                class="form-control"
                            />
                        </div>

                        <div class="form-group half">
                            <label for="routing_number">Routing Number</label>
                            <input
                                type="text"
                                id="routing_number"
                                v-model="formData.routing_number"
                                class="form-control"
                            />
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="checkbox-control">
                            <input
                                type="checkbox"
                                id="is_primary"
                                v-model="formData.is_primary"
                            />
                            <label for="is_primary">Set as primary bank account</label>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="checkbox-control">
                            <input
                                type="checkbox"
                                id="is_active"
                                v-model="formData.is_active"
                            />
                            <label for="is_active">Account is active</label>
                        </div>
                    </div>
                </div>

                <div class="danger-zone">
                    <h3>Danger Zone</h3>
                    <div class="danger-actions">
                        <button type="button" class="btn-danger" @click="confirmDeleteAccount">
                            <i class="fas fa-trash-alt"></i> Delete Account
                        </button>
                    </div>
                </div>

                <div class="form-actions">
                    <a href="#/bank-accounts" class="btn-secondary">Cancel</a>
                    <button type="submit" class="btn-primary" :disabled="saving">
            <span v-if="saving">
              <i class="fas fa-spinner fa-spin"></i> Saving...
            </span>
                        <span v-else>Save Changes</span>
                    </button>
                </div>
            </form>

            <div v-else-if="!loading" class="not-found">
                <i class="fas fa-exclamation-triangle"></i>
                <h2>Account Not Found</h2>
                <p>The bank account you're looking for does not exist or you don't have permission to access it.</p>
                <a href="/bank-accounts" class="btn-primary">Back to Accounts</a>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <div v-if="showDeleteModal" class="modal-overlay">
            <div class="modal-container">
                <div class="modal-header">
                    <h3>Confirm Delete</h3>
                    <button class="close-btn" @click="showDeleteModal = false">&times;</button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this bank account?</p>
                    <p class="warning"><i class="fas fa-exclamation-triangle"></i> This action cannot be undone.</p>

                    <div v-if="bankAccount" class="account-details">
                        <p><strong>Bank:</strong> {{ bankAccount.bank_name }}</p>
                        <p><strong>Account:</strong> {{ maskAccountNumber(bankAccount.account_number) }}</p>
                        <p><strong>Name:</strong> {{ bankAccount.account_name }}</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn-secondary" @click="showDeleteModal = false">Cancel</button>
                    <button class="btn-danger" @click="deleteAccount" :disabled="deleting">
            <span v-if="deleting">
              <i class="fas fa-spinner fa-spin"></i> Deleting...
            </span>
                        <span v-else>Delete Account</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    data() {
        return {
            bankAccount: null,
            formData: {
                account_name: '',
                account_type: '',
                branch_name: '',
                routing_number: '',
                swift_code: '',
                is_primary: false,
                is_active: true
            },
            loading: true,
            saving: false,
            deleting: false,
            error: null,
            success: null,
            showDeleteModal: false
        };
    },
    created() {
        // Get account ID from URL
        const accountId = this.getAccountIdFromUrl();
        if (accountId) {
            this.fetchBankAccount(accountId);
        } else {
            this.error = 'No account ID provided in the URL';
            this.loading = false;
        }
    },
    methods: {
        getAccountIdFromUrl() {
            // Extract account ID from URL
            // This assumes URL pattern like /bank-accounts/1/edit
            const path = window.location.pathname;
            const matches = path.match(/\/bank-accounts\/(\d+)\/edit/);
            return matches ? matches[1] : null;
        },
        fetchBankAccount(accountId) {
            axios.get(`/api/bank-accounts/${accountId}`)
                .then(response => {
                    if (response.data.success) {
                        // Check if response has nested data structure
                        const accountData = response.data.data.bank_account || response.data.data;
                        this.bankAccount = accountData;

                        // Initialize form data from bank account
                        this.formData = {
                            account_name: this.bankAccount.account_name,
                            account_type: this.bankAccount.account_type,
                            branch_name: this.bankAccount.branch_name || '',
                            routing_number: this.bankAccount.routing_number || '',
                            swift_code: this.bankAccount.swift_code || '',
                            is_primary: this.bankAccount.is_primary,
                            is_active: this.bankAccount.is_active
                        };
                    } else {
                        this.error = response.data.message || 'Failed to load bank account';
                    }
                })
                .catch(error => {
                    this.error = error.response?.data?.message || 'An error occurred while loading the bank account';
                    console.error('Error fetching bank account:', error);
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
        updateBankAccount() {
            this.saving = true;
            this.error = null;
            this.success = null;

            axios.put(`/api/bank-accounts/${this.bankAccount.id}`, this.formData)
                .then(response => {
                    if (response.data.success) {
                        this.success = 'Bank account updated successfully';
                        this.bankAccount = response.data.data;

                        // Update the form data with the new values
                        this.formData = {
                            account_name: this.bankAccount.account_name,
                            account_type: this.bankAccount.account_type,
                            branch_name: this.bankAccount.branch_name || '',
                            routing_number: this.bankAccount.routing_number || '',
                            swift_code: this.bankAccount.swift_code || '',
                            is_primary: this.bankAccount.is_primary,
                            is_active: this.bankAccount.is_active
                        };

                        // Clear success message after 3 seconds
                        setTimeout(() => {
                            this.success = null;
                        }, 3000);

                        this.$router.push('/bank-accounts');

                    } else {
                        this.error = response.data.message || 'Failed to update bank account';
                    }
                })
                .catch(error => {
                    this.error = error.response?.data?.message || 'An error occurred while updating the bank account';
                    console.error('Error updating bank account:', error);
                })
                .finally(() => {
                    this.saving = false;
                });
        },
        confirmDeleteAccount() {
            this.showDeleteModal = true;
        },
        deleteAccount() {
            this.deleting = true;

            axios.delete(`/api/bank-accounts/${this.bankAccount.id}`)
                .then(response => {
                    if (response.data.success) {
                        alert('Bank account deleted successfully');
                        window.location.href = '#/bank-accounts';
                    } else {
                        this.error = response.data.message || 'Failed to delete bank account';
                        this.showDeleteModal = false;
                    }
                })
                .catch(error => {
                    this.error = error.response?.data?.message || 'An error occurred while deleting the bank account';
                    console.error('Error deleting bank account:', error);
                    this.showDeleteModal = false;
                })
                .finally(() => {
                    this.deleting = false;
                });
        }
    }
};
</script>

<style scoped>
.edit-bank-account-container {
    max-width: 800px;
    margin: 0 auto;
    padding: 2rem 1rem;
}

.page-header {
    margin-bottom: 2rem;
    text-align: center;
}

.page-header h1 {
    font-size: 1.75rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
    color: #212529;
}

.page-header p {
    color: #6c757d;
    font-size: 1rem;
}

.form-container {
    position: relative;
    background-color: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    padding: 2rem;
}

.loading-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: rgba(255, 255, 255, 0.8);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 10;
    border-radius: 8px;
}

.spinner {
    width: 40px;
    height: 40px;
    border: 4px solid rgba(0, 0, 0, 0.1);
    border-radius: 50%;
    border-top-color: #007bff;
    animation: spin 1s ease infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

.alert {
    padding: 1rem;
    margin-bottom: 1rem;
    border-radius: 4px;
}

.alert-danger {
    background-color: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.alert-success {
    background-color: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.bank-info {
    display: flex;
    align-items: center;
    margin-bottom: 2rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px solid #e9ecef;
}

.bank-logo {
    width: 80px;
    height: 80px;
    margin-right: 1.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
}

.bank-logo img {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
}

.bank-details h2 {
    font-size: 1.25rem;
    font-weight: 600;
    margin: 0 0 0.5rem 0;
    color: #343a40;
}

.account-number {
    color: #6c757d;
    font-size: 0.875rem;
    margin: 0;
}

.form-section {
    margin-bottom: 2rem;
}

.form-section h3 {
    font-size: 1.125rem;
    font-weight: 600;
    margin-bottom: 1.25rem;
    padding-bottom: 0.5rem;
    border-bottom: 1px solid #e9ecef;
    color: #343a40;
}

.form-group {
    margin-bottom: 1.25rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
    color: #343a40;
}

.form-row {
    display: flex;
    gap: 1rem;
}

.form-group.half {
    flex: 1;
}

.form-control {
    display: block;
    width: 100%;
    padding: 0.75rem 1rem;
    font-size: 1rem;
    border: 1px solid #ced4da;
    border-radius: 4px;
    transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
}

.form-control:focus {
    border-color: #007bff;
    outline: 0;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

.checkbox-control {
    display: flex;
    align-items: center;
}

.checkbox-control input {
    margin-right: 0.5rem;
}

.danger-zone {
    margin-bottom: 2rem;
    padding: 1.5rem;
    border: 1px solid #f5c6cb;
    border-radius: 4px;
    background-color: #fff8f8;
}

.danger-zone h3 {
    color: #721c24;
    font-size: 1.125rem;
    font-weight: 600;
    margin-top: 0;
    margin-bottom: 1rem;
}

.danger-actions {
    display: flex;
    justify-content: flex-start;
}

.btn-danger {
    background-color: #dc3545;
    color: white;
    border: none;
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
    border-radius: 4px;
    cursor: pointer;
    transition: background-color 0.15s ease-in-out;
    display: flex;
    align-items: center;
}

.btn-danger:hover {
    background-color: #c82333;
}

.btn-danger i {
    margin-right: 0.5rem;
}

.form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 1rem;
    margin-top: 2rem;
}

.btn-primary, .btn-secondary {
    display: inline-block;
    padding: 0.75rem 1.5rem;
    font-size: 1rem;
    font-weight: 500;
    text-align: center;
    text-decoration: none;
    border-radius: 4px;
    transition: all 0.2s;
    cursor: pointer;
}

.btn-primary {
    background-color: #007bff;
    color: #fff;
    border: none;
}

.btn-primary:hover {
    background-color: #0069d9;
}

.btn-primary:disabled {
    background-color: #6c757d;
    cursor: not-allowed;
}

.btn-secondary {
    background-color: #f8f9fa;
    color: #343a40;
    border: 1px solid #dee2e6;
}

.btn-secondary:hover {
    background-color: #e9ecef;
}

.not-found {
    text-align: center;
    padding: 3rem 1rem;
}

.not-found i {
    font-size: 3rem;
    color: #6c757d;
    margin-bottom: 1rem;
}

.not-found h2 {
    font-size: 1.5rem;
    margin-bottom: 1rem;
    color: #343a40;
}

.not-found p {
    color: #6c757d;
    margin-bottom: 2rem;
}

/* Modal Styles */
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 100;
}

.modal-container {
    background-color: white;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
    width: 100%;
    max-width: 500px;
    overflow: hidden;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 1.5rem;
    border-bottom: 1px solid #e9ecef;
}

.modal-header h3 {
    margin: 0;
    font-size: 1.25rem;
    font-weight: 600;
    color: #343a40;
}

.close-btn {
    background: none;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
    color: #6c757d;
}

.modal-body {
    padding: 1.5rem;
}

.modal-body p {
    margin-top: 0;
    margin-bottom: 1rem;
}

.modal-body .warning {
    color: #856404;
    background-color: #fff3cd;
    border-radius: 4px;
    padding: 0.75rem 1rem;
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
}

.modal-body .warning i {
    margin-right: 0.5rem;
    color: #856404;
}

.modal-body .account-details {
    background-color: #f8f9fa;
    padding: 1rem;
    border-radius: 4px;
    margin-top: 1rem;
}

.modal-body .account-details p {
    margin: 0.25rem 0;
}

.modal-footer {
    display: flex;
    justify-content: flex-end;
    padding: 1rem 1.5rem;
    border-top: 1px solid #e9ecef;
    gap: 1rem;
}
</style>
