<!-- resources/js/components/auth/Register.vue -->
<template>
    <div class="register-container">
        <div class="register-card">
            <div class="register-header">
                <h1>Create Account</h1>
                <p>Join the unified banking platform</p>
            </div>

            <form @submit.prevent="register" class="register-form">
                <div class="alert alert-danger" v-if="error">
                    {{ error }}
                </div>

                <div class="form-group">
                    <label for="name">Full Name</label>
                    <div class="input-wrapper">
                        <i class="fas fa-user"></i>
                        <input
                            type="text"
                            id="name"
                            v-model="formData.name"
                            placeholder="Enter your full name"
                            required
                            :disabled="isLoading"
                        />
                    </div>
                    <span class="error-message" v-if="errors.name">{{ errors.name[0] }}</span>
                </div>

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <div class="input-wrapper">
                        <i class="fas fa-envelope"></i>
                        <input
                            type="email"
                            id="email"
                            v-model="formData.email"
                            placeholder="your.email@example.com"
                            required
                            :disabled="isLoading"
                        />
                    </div>
                    <span class="error-message" v-if="errors.email">{{ errors.email[0] }}</span>
                </div>

                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <div class="input-wrapper">
                        <i class="fas fa-phone"></i>
                        <input
                            type="tel"
                            id="phone"
                            v-model="formData.phone"
                            placeholder="01XXXXXXXXX"
                            required
                            :disabled="isLoading"
                        />
                    </div>
                    <span class="error-message" v-if="errors.phone">{{ errors.phone[0] }}</span>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock"></i>
                        <input
                            :type="showPassword ? 'text' : 'password'"
                            id="password"
                            v-model="formData.password"
                            placeholder="Create a strong password"
                            required
                            :disabled="isLoading"
                        />
                        <i
                            :class="showPassword ? 'fas fa-eye-slash' : 'fas fa-eye'"
                            class="password-toggle"
                            @click="showPassword = !showPassword"
                        ></i>
                    </div>
                    <span class="error-message" v-if="errors.password">{{ errors.password[0] }}</span>
                </div>

                <div class="form-group">
                    <label for="password_confirmation">Confirm Password</label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock"></i>
                        <input
                            :type="showPassword ? 'text' : 'password'"
                            id="password_confirmation"
                            v-model="formData.password_confirmation"
                            placeholder="Confirm your password"
                            required
                            :disabled="isLoading"
                        />
                    </div>
                </div>

                <div class="form-group">
                    <label for="national_id">National ID (optional)</label>
                    <div class="input-wrapper">
                        <i class="fas fa-id-card"></i>
                        <input
                            type="text"
                            id="national_id"
                            v-model="formData.national_id"
                            placeholder="Enter your National ID"
                            :disabled="isLoading"
                        />
                    </div>
                    <span class="error-message" v-if="errors.national_id">{{ errors.national_id[0] }}</span>
                </div>

                <div class="terms-agreement">
                    <input
                        type="checkbox"
                        id="agree_terms"
                        v-model="formData.agree_terms"
                        required
                        :disabled="isLoading"
                    />
                    <label for="agree_terms">
                        I agree to the <a href="#" @click.prevent="showTerms">Terms of Service</a> and <a href="#" @click.prevent="showPrivacy">Privacy Policy</a>
                    </label>
                </div>

                <button type="submit" class="register-button" :disabled="isLoading || !formData.agree_terms">
          <span v-if="isLoading">
            <i class="fas fa-spinner fa-spin"></i> Creating Account...
          </span>
                    <span v-else>Create Account</span>
                </button>

                <div class="register-footer">
                    <p>Already have an account? <router-link to="/login">Sign in</router-link></p>
                </div>
            </form>
        </div>
    </div>
</template>

<script>
import axios from 'axios';

export default {
    data() {
        return {
            formData: {
                name: '',
                email: '',
                phone: '',
                password: '',
                password_confirmation: '',
                national_id: '',
                agree_terms: false
            },
            isLoading: false,
            error: null,
            errors: {},
            showPassword: false
        };
    },
    methods: {
        register() {
            this.isLoading = true;
            this.error = null;
            this.errors = {};

            axios.post('/api/register', this.formData)
                .then(response => {
                    if (response.data.success) {
                        // Store token and user data
                        localStorage.setItem('token', response.data.data.token);
                        localStorage.setItem('user', JSON.stringify(response.data.data.user));

                        // Set auth header for future requests
                        axios.defaults.headers.common['Authorization'] = `Bearer ${response.data.data.token}`;

                        // Show success message
                        alert('Registration successful! Welcome to the Unified Banking System.');

                        // Redirect to dashboard
                        this.$router.push('/');
                    } else {
                        this.error = response.data.message || 'Registration failed. Please try again.';
                    }
                })
                .catch(error => {
                    if (error.response) {
                        if (error.response.status === 422) {
                            // Validation errors
                            this.errors = error.response.data.errors || {};
                            this.error = 'Please correct the errors in the form.';
                        } else {
                            this.error = error.response.data.message || 'Registration failed. Please try again.';
                        }
                    } else if (error.request) {
                        this.error = 'No response from server. Please try again later.';
                    } else {
                        this.error = 'An error occurred. Please try again.';
                    }
                })
                .finally(() => {
                    this.isLoading = false;
                });
        },
        showTerms() {
            alert('Terms of Service will be displayed here.');
        },
        showPrivacy() {
            alert('Privacy Policy will be displayed here.');
        }
    }
};
</script>

<style scoped>
.register-container {
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 100vh;
    background-color: #f8f9fa;
    padding: 1rem;
}

.register-card {
    width: 100%;
    max-width: 480px;
    background-color: white;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    border-radius: 8px;
    padding: 2rem;
}

.register-header {
    text-align: center;
    margin-bottom: 2rem;
}

.register-header h1 {
    margin: 0;
    color: #212529;
    font-size: 1.75rem;
    font-weight: 700;
}

.register-header p {
    margin-top: 0.5rem;
    color: #6c757d;
}

.form-group {
    margin-bottom: 1.25rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    color: #343a40;
    font-weight: 500;
}

.input-wrapper {
    position: relative;
    display: flex;
    align-items: center;
}

.input-wrapper input {
    flex: 1;
    padding: 0.75rem 1rem 0.75rem 2.5rem;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    font-size: 1rem;
    transition: border-color 0.15s ease-in-out;
    width: 100%;
}

.input-wrapper input:focus {
    outline: none;
    border-color: #007bff;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

.input-wrapper i:not(.password-toggle) {
    position: absolute;
    left: 0.75rem;
    color: #adb5bd;
}

.password-toggle {
    position: absolute;
    right: 0.75rem;
    color: #adb5bd;
    cursor: pointer;
}

.error-message {
    display: block;
    color: #dc3545;
    font-size: 0.875rem;
    margin-top: 0.25rem;
}

.terms-agreement {
    display: flex;
    align-items: flex-start;
    margin-bottom: 1.5rem;
}

.terms-agreement input {
    margin-right: 0.5rem;
    margin-top: 0.25rem;
}

.terms-agreement a {
    color: #007bff;
    text-decoration: none;
}

.terms-agreement a:hover {
    text-decoration: underline;
}

.register-button {
    width: 100%;
    padding: 0.75rem 1rem;
    background-color: #007bff;
    color: white;
    border: none;
    border-radius: 4px;
    font-size: 1rem;
    font-weight: 500;
    cursor: pointer;
    transition: background-color 0.15s ease-in-out;
}

.register-button:hover {
    background-color: #0069d9;
}

.register-button:disabled {
    background-color: #6c757d;
    cursor: not-allowed;
}

.register-footer {
    text-align: center;
    margin-top: 1.5rem;
    color: #6c757d;
}

.register-footer a {
    color: #007bff;
    text-decoration: none;
}

.register-footer a:hover {
    text-decoration: underline;
}

.alert {
    padding: 0.75rem 1.25rem;
    margin-bottom: 1rem;
    border: 1px solid transparent;
    border-radius: 0.25rem;
}

.alert-danger {
    color: #721c24;
    background-color: #f8d7da;
    border-color: #f5c6cb;
}
</style>
