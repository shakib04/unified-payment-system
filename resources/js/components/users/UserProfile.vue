<template>
    <div class="profile-container">
        <div class="profile-header">
            <h1>My Profile</h1>
            <p>Manage your personal information and account settings</p>
        </div>

        <div v-if="loading" class="loading-overlay">
            <div class="spinner"></div>
        </div>

        <div v-else class="profile-content">
            <div class="profile-section">
                <div class="profile-photo">
                    <img
                        :src="userPhotoUrl"
                        alt="Profile Photo"
                        class="photo"
                    />
                    <button class="change-photo-btn" @click="triggerFileInput">
                        <i class="fas fa-camera"></i> Change Photo
                    </button>
                    <input
                        type="file"
                        ref="fileInput"
                        style="display: none"
                        accept="image/*"
                        @change="handleFileUpload"
                    />
                </div>

                <div class="profile-info">
                    <form @submit.prevent="updateProfile" class="profile-form">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="name">Full Name</label>
                                <input
                                    type="text"
                                    id="name"
                                    v-model="form.name"
                                    class="form-control"
                                    required
                                />
                            </div>

                            <div class="form-group">
                                <label for="email">Email Address</label>
                                <input
                                    type="email"
                                    id="email"
                                    v-model="form.email"
                                    class="form-control"
                                    required
                                />
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="phone">Phone Number</label>
                                <input
                                    type="tel"
                                    id="phone"
                                    v-model="form.phone"
                                    class="form-control"
                                    placeholder="e.g. 01XXXXXXXXX"
                                />
                            </div>

                            <div class="form-group">
                                <label for="national_id">National ID</label>
                                <input
                                    type="text"
                                    id="national_id"
                                    v-model="form.national_id"
                                    class="form-control"
                                />
                            </div>
                        </div>

                        <div class="form-actions">
                            <button
                                type="submit"
                                class="btn-primary"
                                :disabled="updating"
                            >
                <span v-if="updating">
                  <i class="fas fa-spinner fa-spin"></i> Updating...
                </span>
                                <span v-else>Update Profile</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="profile-section">
                <h2>Change Password</h2>

                <form @submit.prevent="updatePassword" class="password-form">
                    <div class="form-group">
                        <label for="current_password">Current Password</label>
                        <input
                            type="password"
                            id="current_password"
                            v-model="passwordForm.current_password"
                            class="form-control"
                            required
                        />
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="new_password">New Password</label>
                            <input
                                type="password"
                                id="new_password"
                                v-model="passwordForm.new_password"
                                class="form-control"
                                required
                                minlength="8"
                            />
                        </div>

                        <div class="form-group">
                            <label for="new_password_confirmation">Confirm New Password</label>
                            <input
                                type="password"
                                id="new_password_confirmation"
                                v-model="passwordForm.new_password_confirmation"
                                class="form-control"
                                required
                                minlength="8"
                            />
                        </div>
                    </div>

                    <div class="password-strength" v-if="passwordForm.new_password">
                        <div class="strength-meter">
                            <div
                                class="strength-bar"
                                :style="{ width: passwordStrength.score + '%' }"
                                :class="passwordStrength.class"
                            ></div>
                        </div>
                        <span class="strength-text" :class="passwordStrength.class">
              {{ passwordStrength.text }}
            </span>
                    </div>

                    <div class="form-actions">
                        <button
                            type="submit"
                            class="btn-primary"
                            :disabled="updatingPassword || !passwordsMatch || !passwordForm.new_password"
                        >
              <span v-if="updatingPassword">
                <i class="fas fa-spinner fa-spin"></i> Updating...
              </span>
                            <span v-else>Update Password</span>
                        </button>
                    </div>
                </form>
            </div>

            <div class="profile-section">
                <h2>Account Statistics</h2>

                <div class="statistics-grid">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-university"></i>
                        </div>
                        <div class="stat-details">
                            <span class="stat-value">{{ statistics.bankAccountsCount }}</span>
                            <span class="stat-label">Bank Accounts</span>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-credit-card"></i>
                        </div>
                        <div class="stat-details">
                            <span class="stat-value">{{ statistics.paymentMethodsCount }}</span>
                            <span class="stat-label">Payment Methods</span>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-exchange-alt"></i>
                        </div>
                        <div class="stat-details">
                            <span class="stat-value">{{ statistics.transactionsCount }}</span>
                            <span class="stat-label">Transactions</span>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <div class="stat-details">
                            <span class="stat-value">{{ statistics.scheduledPaymentsCount }}</span>
                            <span class="stat-label">Scheduled Payments</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import axios from 'axios';

export default {
    data() {
        return {
            loading: true,
            updating: false,
            updatingPassword: false,
            form: {
                name: '',
                email: '',
                phone: '',
                national_id: ''
            },
            passwordForm: {
                current_password: '',
                new_password: '',
                new_password_confirmation: ''
            },
            statistics: {
                bankAccountsCount: 0,
                paymentMethodsCount: 0,
                transactionsCount: 0,
                scheduledPaymentsCount: 0
            },
            userPhotoUrl: '/img/default-profile.png'
        };
    },
    computed: {
        passwordsMatch() {
            return this.passwordForm.new_password === this.passwordForm.new_password_confirmation;
        },
        passwordStrength() {
            const password = this.passwordForm.new_password;

            if (!password) {
                return { score: 0, text: '', class: '' };
            }

            let score = 0;

            // Length check
            if (password.length >= 8) score += 20;
            if (password.length >= 12) score += 10;

            // Complexity checks
            if (/[A-Z]/.test(password)) score += 15;
            if (/[a-z]/.test(password)) score += 15;
            if (/[0-9]/.test(password)) score += 15;
            if (/[^A-Za-z0-9]/.test(password)) score += 25;

            let strengthClass = '';
            let strengthText = '';

            if (score < 30) {
                strengthClass = 'weak';
                strengthText = 'Weak';
            } else if (score < 60) {
                strengthClass = 'medium';
                strengthText = 'Medium';
            } else {
                strengthClass = 'strong';
                strengthText = 'Strong';
            }

            return {
                score,
                text: strengthText,
                class: strengthClass
            };
        }
    },
    created() {
        this.fetchUserProfile();
        this.fetchUserStatistics();
    },
    methods: {
        fetchUserProfile() {
            axios.get('/api/profile')
                .then(response => {
                    const userData = response.data.data;
                    this.form.name = userData.name;
                    this.form.email = userData.email;
                    this.form.phone = userData.phone || '';
                    this.form.national_id = userData.national_id || '';

                    if (userData.profile_photo_path) {
                        this.userPhotoUrl = userData.profile_photo_path;
                    }

                    this.loading = false;
                })
                .catch(error => {
                    console.error('Error fetching user profile:', error);
                    this.$toasted.error('Failed to load profile data');
                    this.loading = false;
                });
        },
        fetchUserStatistics() {
            axios.get('/api/profile/statistics')
                .then(response => {
                    this.statistics = response.data.data;
                })
                .catch(error => {
                    console.error('Error fetching user statistics:', error);
                });
        },
        updateProfile() {
            this.updating = true;

            axios.put('/api/profile', this.form)
                .then(response => {
                    this.$toasted.success('Profile updated successfully');
                })
                .catch(error => {
                    const message = error.response?.data?.message || 'Failed to update profile';
                    this.$toasted.error(message);
                })
                .finally(() => {
                    this.updating = false;
                });
        },
        updatePassword() {
            if (!this.passwordsMatch) {
                this.$toasted.error('Passwords do not match');
                return;
            }

            this.updatingPassword = true;

            axios.put('/api/profile/password', this.passwordForm)
                .then(response => {
                    this.$toasted.success('Password updated successfully');
                    this.passwordForm = {
                        current_password: '',
                        new_password: '',
                        new_password_confirmation: ''
                    };
                })
                .catch(error => {
                    const message = error.response?.data?.message || 'Failed to update password';
                    this.$toasted.error(message);
                })
                .finally(() => {
                    this.updatingPassword = false;
                });
        },
        triggerFileInput() {
            this.$refs.fileInput.click();
        },
        handleFileUpload(event) {
            const file = event.target.files[0];
            if (!file) return;

            const formData = new FormData();
            formData.append('profile_photo', file);

            axios.post('/api/profile/photo', formData, {
                headers: {
                    'Content-Type': 'multipart/form-data'
                }
            })
                .then(response => {
                    this.userPhotoUrl = response.data.data.photo_url;
                    this.$toasted.success('Profile photo updated');
                })
                .catch(error => {
                    const message = error.response?.data?.message || 'Failed to upload photo';
                    this.$toasted.error(message);
                });
        }
    }
};
</script>

<style scoped>
.profile-container {
    max-width: 800px;
    margin: 0 auto;
    padding: 1.5rem;
}

.profile-header {
    text-align: center;
    margin-bottom: 2rem;
}

.profile-header h1 {
    font-size: 1.75rem;
    font-weight: 700;
    color: #1f2937;
    margin-bottom: 0.5rem;
}

.profile-header p {
    color: #6b7280;
}

.loading-overlay {
    display: flex;
    justify-content: center;
    align-items: center;
    height: 200px;
}

.spinner {
    width: 40px;
    height: 40px;
    border: 4px solid rgba(0, 0, 0, 0.1);
    border-left-color: #3b82f6;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}

.profile-content {
    display: flex;
    flex-direction: column;
    gap: 2rem;
}

.profile-section {
    background-color: white;
    border-radius: 0.5rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    padding: 1.5rem;
}

.profile-section h2 {
    font-size: 1.25rem;
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 1.5rem;
    padding-bottom: 0.5rem;
    border-bottom: 1px solid #e5e7eb;
}

.profile-photo {
    display: flex;
    flex-direction: column;
    align-items: center;
    margin-bottom: 1.5rem;
}

.photo {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    object-fit: cover;
    border: 3px solid #e5e7eb;
    margin-bottom: 1rem;
}

.change-photo-btn {
    background-color: #f3f4f6;
    color: #4b5563;
    border: none;
    padding: 0.5rem 1rem;
    border-radius: 0.375rem;
    font-size: 0.875rem;
    cursor: pointer;
    transition: all 0.2s;
}

.change-photo-btn:hover {
    background-color: #e5e7eb;
    color: #1f2937;
}

.profile-form,
.password-form {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.form-row {
    display: flex;
    gap: 1rem;
}

@media (max-width: 640px) {
    .form-row {
        flex-direction: column;
    }
}

.form-group {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.form-group label {
    font-size: 0.875rem;
    font-weight: 500;
    color: #4b5563;
}

.form-control {
    padding: 0.625rem;
    border: 1px solid #d1d5db;
    border-radius: 0.375rem;
    font-size: 1rem;
}

.form-control:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.25);
}

.form-actions {
    display: flex;
    justify-content: flex-end;
    margin-top: 0.5rem;
}

.btn-primary {
    background-color: #3b82f6;
    color: white;
    padding: 0.625rem 1.25rem;
    border: none;
    border-radius: 0.375rem;
    font-size: 0.875rem;
    font-weight: 500;
    cursor: pointer;
    transition: background-color 0.2s;
}

.btn-primary:hover {
    background-color: #2563eb;
}

.btn-primary:disabled {
    background-color: #93c5fd;
    cursor: not-allowed;
}

.password-strength {
    margin-top: 0.5rem;
}

.strength-meter {
    height: 6px;
    background-color: #e5e7eb;
    border-radius: 3px;
    margin-bottom: 4px;
}

.strength-bar {
    height: 100%;
    border-radius: 3px;
    transition: width 0.3s ease;
}

.strength-bar.weak {
    background-color: #ef4444;
}

.strength-bar.medium {
    background-color: #f59e0b;
}

.strength-bar.strong {
    background-color: #10b981;
}

.strength-text {
    font-size: 0.75rem;
}

.strength-text.weak {
    color: #ef4444;
}

.strength-text.medium {
    color: #f59e0b;
}

.strength-text.strong {
    color: #10b981;
}

.statistics-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1rem;
}

@media (min-width: 640px) {
    .statistics-grid {
        grid-template-columns: repeat(4, 1fr);
    }
}

.stat-card {
    background-color: #f9fafb;
    border-radius: 0.5rem;
    padding: 1.25rem;
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    transition: transform 0.2s;
}

.stat-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
}

.stat-icon {
    font-size: 1.5rem;
    color: #3b82f6;
    margin-bottom: 0.75rem;
}

.stat-value {
    font-size: 1.5rem;
    font-weight: 700;
    color: #1f2937;
    display: block;
}

.stat-label {
    font-size: 0.875rem;
    color: #6b7280;
}
</style>
