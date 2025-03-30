<template>
    <div class="app-container">
        <nav v-if="isAuthenticated" class="main-nav">
            <div class="nav-brand">
                <router-link to="/">
                    <span class="brand-name">Unified Banking</span>
                </router-link>
            </div>

            <div class="nav-links">
                <router-link to="/" class="nav-link" active-class="active">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </router-link>

                <router-link to="/bank-accounts" class="nav-link" active-class="active">
                    <i class="fas fa-university"></i> Bank Accounts
                </router-link>

                <router-link to="/payment-methods" class="nav-link" active-class="active">
                    <i class="fas fa-credit-card"></i> Payment Methods
                </router-link>

                <router-link to="/bills" class="nav-link" active-class="active">
                    <i class="fas fa-file-invoice"></i> Pay Bills
                </router-link>

                <router-link to="/scheduled-payments" class="nav-link" active-class="active">
                    <i class="fas fa-clock"></i> Scheduled Payments
                </router-link>
            </div>

            <div class="user-menu">
                <div class="dropdown">
                    <button class="dropdown-toggle">
                        <span>{{ userName }}</span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="dropdown-menu">
                        <a href="#" @click.prevent="goToProfile" class="dropdown-item">
                            <i class="fas fa-user"></i> My Profile
                        </a>
                        <a href="#" @click.prevent="logout" class="dropdown-item">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </div>
                </div>
            </div>
        </nav>

        <main class="main-content">
            <router-view></router-view>
        </main>
    </div>
</template>

<script>
import axios from 'axios';

export default {
    data() {
        return {
            user: null
        };
    },
    computed: {
        isAuthenticated() {
            return !!localStorage.getItem('token');
        },
        userName() {
            if (this.user) {
                return this.user.name;
            }

            const storedUser = localStorage.getItem('user');
            if (storedUser) {
                try {
                    const parsedUser = JSON.parse(storedUser);
                    return parsedUser.name || 'User';
                } catch (e) {
                    return 'User';
                }
            }

            return 'User';
        }
    },
    created() {
        if (this.isAuthenticated) {
            this.fetchUserProfile();
        }
    },
    methods: {
        fetchUserProfile() {
            axios.get('/api/user')
                .then(response => {
                    this.user = response.data;
                })
                .catch(error => {
                    console.error('Error fetching user profile:', error);

                    // If unauthorized, clean up and redirect to login
                    if (error.response && error.response.status === 401) {
                        this.handleUnauthorized();
                    }
                });
        },
        goToProfile() {
            // Implement profile page navigation
            alert('Profile page will be implemented soon.');
        },
        logout() {
            axios.post('/api/logout')
                .then(() => {
                    this.handleUnauthorized();
                })
                .catch(error => {
                    console.error('Error during logout:', error);
                    // Force logout anyway
                    this.handleUnauthorized();
                });
        },
        handleUnauthorized() {
            // Clear authentication data
            localStorage.removeItem('token');
            localStorage.removeItem('user');

            // Redirect to login
            // this.$router.push('/login');
            window.location.href = '/login'; // Force page reload to reinitialize app state
        }
    }
};
</script>

<style>
/* Global styles */
* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}

body {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
    font-size: 16px;
    line-height: 1.5;
    color: #212529;
    background-color: #f8f9fa;
}

/* App layout */
.app-container {
    display: flex;
    flex-direction: column;
    min-height: 100vh;
}

/* Navigation */
.main-nav {
    display: flex;
    align-items: center;
    padding: 0 1.5rem;
    background-color: #fff;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    height: 64px;
    position: sticky;
    top: 0;
    z-index: 100;
}

.nav-brand {
    margin-right: 2rem;
}

.nav-brand a {
    text-decoration: none;
}

.brand-name {
    font-size: 1.25rem;
    font-weight: 700;
    color: #007bff;
}

.nav-links {
    display: flex;
    flex-grow: 1;
    gap: 0.5rem;
}

.nav-link {
    display: flex;
    align-items: center;
    padding: 0.75rem 1rem;
    color: #495057;
    text-decoration: none;
    border-radius: 4px;
    transition: all 0.2s;
}

.nav-link i {
    margin-right: 0.5rem;
}

.nav-link:hover {
    background-color: #f8f9fa;
    color: #007bff;
}

.nav-link.active {
    background-color: rgba(0, 123, 255, 0.1);
    color: #007bff;
}

.user-menu {
    margin-left: auto;
}

.dropdown {
    position: relative;
}

.dropdown-toggle {
    display: flex;
    align-items: center;
    padding: 0.5rem 1rem;
    background: none;
    border: none;
    color: #495057;
    font-size: 1rem;
    cursor: pointer;
}

.dropdown-toggle span {
    margin-right: 0.5rem;
}

.dropdown:hover .dropdown-menu {
    display: block;
}

.dropdown-menu {
    display: none;
    position: absolute;
    top: 100%;
    right: 0;
    background-color: #fff;
    min-width: 180px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    border-radius: 4px;
    padding: 0.5rem 0;
    z-index: 10;
}

.dropdown-item {
    display: flex;
    align-items: center;
    padding: 0.5rem 1rem;
    color: #212529;
    text-decoration: none;
}

.dropdown-item i {
    margin-right: 0.5rem;
    width: 16px;
    text-align: center;
}

.dropdown-item:hover {
    background-color: #f8f9fa;
}

/* Main content */
.main-content {
    flex-grow: 1;
    padding: 1rem;
}
</style>
