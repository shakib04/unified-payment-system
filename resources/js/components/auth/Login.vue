<!-- resources/js/components/auth/Login.vue -->
<template>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <h1>Welcome Back</h1>
                <p>Sign in to your account</p>
            </div>

            <form @submit.prevent="login" class="login-form">
                <div class="alert alert-danger" v-if="error">
                    {{ error }}
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <div class="input-wrapper">
                        <i class="fas fa-envelope"></i>
                        <input
                            type="email"
                            id="email"
                            v-model="credentials.email"
                            placeholder="your.email@example.com"
                            required
                            :disabled="isLoading"
                        />
                    </div>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock"></i>
                        <input
                            :type="showPassword ? 'text' : 'password'"
                            id="password"
                            v-model="credentials.password"
                            placeholder="Enter your password"
                            required
                            :disabled="isLoading"
                        />
                        <i
                            :class="showPassword ? 'fas fa-eye-slash' : 'fas fa-eye'"
                            class="password-toggle"
                            @click="showPassword = !showPassword"
                        ></i>
                    </div>
                </div>

                <div class="form-options">
                    <div class="remember-me">
                        <input type="checkbox" id="remember" v-model="credentials.remember" :disabled="isLoading" />
                        <label for="remember">Remember me</label>
                    </div>
                    <a href="#" class="forgot-password">Forgot password?</a>
                </div>

                <button type="submit" class="login-button" :disabled="isLoading">
          <span v-if="isLoading">
            <i class="fas fa-spinner fa-spin"></i> Signing in...
          </span>
                    <span v-else>Sign In</span>
                </button>

                <div class="login-footer">
                    <p>Don't have an account? <router-link to="/register">Register now</router-link></p>
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
            credentials: {
                email: '',
                password: '',
                remember: false
            },
            isLoading: false,
            error: null,
            showPassword: false
        };
    },
    methods: {
        login() {
            this.isLoading = true;
            this.error = null;

            axios.post('/api/login', this.credentials)
                .then(response => {
                    // Check if response is successful
                    if (response.data.success) {
                        // Store the token in local storage
                        localStorage.setItem('token', response.data.data.token);

                        // Store user info
                        localStorage.setItem('user', JSON.stringify(response.data.data.user));

                        // Set the Authorization header for future requests
                        axios.defaults.headers.common['Authorization'] = `Bearer ${response.data.data.token}`;

                        // Check if two-factor authentication is required
                        if (response.data.requires_two_factor) {
                            this.$router.push('/verify-two-factor');
                        } else {
                            // Redirect to dashboard
                            // this.$router.push('/');
                            window.location.href = '/'; // Force page reload to reinitialize app state
                        }
                    } else {
                        this.error = response.data.message || 'Login failed. Please try again.';
                    }
                })
                .catch(error => {
                    if (error.response) {
                        // The request was made and the server responded with a status code
                        // that falls out of the range of 2xx
                        this.error = error.response.data.message || 'Invalid credentials';
                    } else if (error.request) {
                        // The request was made but no response was received
                        this.error = 'No response from server. Please try again later.';
                    } else {
                        // Something happened in setting up the request that triggered an Error
                        this.error = 'An error occurred. Please try again.';
                    }
                })
                .finally(() => {
                    this.isLoading = false;
                });
        }
    }
};
</script>

<style scoped>
.login-container {
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 100vh;
    background-color: #f8f9fa;
    padding: 1rem;
}

.login-card {
    width: 100%;
    max-width: 420px;
    background-color: white;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    border-radius: 8px;
    padding: 2rem;
}

.login-header {
    text-align: center;
    margin-bottom: 2rem;
}

.login-header h1 {
    margin: 0;
    color: #212529;
    font-size: 1.75rem;
    font-weight: 700;
}

.login-header p {
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

.form-options {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
}

.remember-me {
    display: flex;
    align-items: center;
}

.remember-me input {
    margin-right: 0.5rem;
}

.forgot-password {
    color: #007bff;
    text-decoration: none;
}

.forgot-password:hover {
    text-decoration: underline;
}

.login-button {
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

.login-button:hover {
    background-color: #0069d9;
}

.login-button:disabled {
    background-color: #6c757d;
    cursor: not-allowed;
}

.login-footer {
    text-align: center;
    margin-top: 1.5rem;
    color: #6c757d;
}

.login-footer a {
    color: #007bff;
    text-decoration: none;
}

.login-footer a:hover {
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
