<template>
    <div class="dashboard">
        <!-- Header Section -->
        <section class="dashboard-header mb-6">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Welcome back, {{ user.name }}</h1>
                    <p class="text-gray-600">Here's your financial overview</p>
                </div>
                <div class="flex items-center">
                    <button @click="fetchDashboardData" class="btn-refresh">
                        <i class="fas fa-sync-alt mr-2"></i> Refresh
                    </button>
                </div>
            </div>
        </section>

        <!-- Stats Cards Section -->
        <section class="dashboard-stats mb-8">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Balance Card -->
                <div class="stat-card bg-white p-4 rounded-lg shadow">
                    <div class="flex items-center mb-2">
                        <div class="stat-icon rounded-full p-3 bg-blue-100 text-blue-500 mr-3">
                            <i class="fas fa-wallet"></i>
                        </div>
                        <h3 class="text-gray-600 font-medium">Total Balance</h3>
                    </div>
                    <p class="stat-value text-2xl font-bold text-gray-900">{{ totalBalance | currency }}</p>
                    <p class="text-sm text-gray-500 mt-1">Across all accounts</p>
                </div>

                <!-- Monthly Spending Card -->
                <div class="stat-card bg-white p-4 rounded-lg shadow">
                    <div class="flex items-center mb-2">
                        <div class="stat-icon rounded-full p-3 bg-red-100 text-red-500 mr-3">
                            <i class="fas fa-exchange-alt"></i>
                        </div>
                        <h3 class="text-gray-600 font-medium">This Month</h3>
                    </div>
                    <p class="stat-value text-2xl font-bold text-gray-900">{{ monthlySpending | currency }}</p>
                    <p class="text-sm text-gray-500 mt-1">{{ getSpendingTrend }}</p>
                </div>

                <!-- Upcoming Bills Card -->
                <div class="stat-card bg-white p-4 rounded-lg shadow">
                    <div class="flex items-center mb-2">
                        <div class="stat-icon rounded-full p-3 bg-yellow-100 text-yellow-500 mr-3">
                            <i class="fas fa-file-invoice"></i>
                        </div>
                        <h3 class="text-gray-600 font-medium">Upcoming Bills</h3>
                    </div>
                    <p class="stat-value text-2xl font-bold text-gray-900">{{ upcomingBillsCount }}</p>
                    <p class="text-sm text-gray-500 mt-1">Due in the next 7 days</p>
                </div>

                <!-- Net Savings Card -->
                <div class="stat-card bg-white p-4 rounded-lg shadow">
                    <div class="flex items-center mb-2">
                        <div class="stat-icon rounded-full p-3 bg-green-100 text-green-500 mr-3">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <h3 class="text-gray-600 font-medium">Net Savings</h3>
                    </div>
                    <p :class="['stat-value text-2xl font-bold', netSavings >= 0 ? 'text-green-600' : 'text-red-600']">
                        {{ netSavings | currency }}
                    </p>
                    <p class="text-sm text-gray-500 mt-1">{{ getSavingsTrend }}</p>
                </div>
            </div>
        </section>

        <!-- Main Content Section -->
        <div class="flex flex-col lg:flex-row gap-6">
            <!-- Left Column -->
            <div class="flex-1">
                <!-- Transactions Section -->
                <section class="transactions-section bg-white rounded-lg shadow mb-6">
                    <div class="section-header px-4 py-3 border-b flex justify-between items-center">
                        <h2 class="text-lg font-semibold text-gray-800">Recent Transactions</h2>
                        <router-link to="/transactions" class="text-blue-500 hover:text-blue-700 text-sm font-medium">
                            View All <i class="fas fa-chevron-right ml-1 text-xs"></i>
                        </router-link>
                    </div>
                    <div class="section-content p-4">
                        <transaction-list
                            :transactions="recentTransactions"
                            :loading="loadingTransactions"
                            @click="viewTransaction"
                        />
                        <div v-if="!loadingTransactions && recentTransactions.length === 0" class="text-center py-8 text-gray-500">
                            No recent transactions found
                        </div>
                    </div>
                </section>

                <!-- Spending by Category Chart -->
                <section class="chart-section bg-white rounded-lg shadow">
                    <div class="section-header px-4 py-3 border-b">
                        <h2 class="text-lg font-semibold text-gray-800">Spending by Category</h2>
                        <select v-model="spendingTimeframe" class="text-sm border rounded px-2 py-1 mt-2">
                            <option value="week">This Week</option>
                            <option value="month">This Month</option>
                            <option value="quarter">This Quarter</option>
                            <option value="year">This Year</option>
                        </select>
                    </div>
                    <div class="section-content p-4">
                        <spending-chart
                            :data="spendingData"
                            :loading="!spendingData.length"
                            :timeframe="spendingTimeframe"
                        />
                    </div>
                </section>
            </div>

            <!-- Right Column -->
            <div class="flex-1">
                <!-- Upcoming Bills Section -->
                <section class="bills-section bg-white rounded-lg shadow mb-6">
                    <div class="section-header px-4 py-3 border-b flex justify-between items-center">
                        <h2 class="text-lg font-semibold text-gray-800">Upcoming Bills</h2>
                        <router-link to="/bills" class="text-blue-500 hover:text-blue-700 text-sm font-medium">
                            View All <i class="fas fa-chevron-right ml-1 text-xs"></i>
                        </router-link>
                    </div>
                    <div class="section-content p-4">
                        <upcoming-bills-list
                            :bills="upcomingBills"
                            :loading="loadingBills"
                            @pay-bill="payBill"
                        />
                        <div v-if="!loadingBills && upcomingBills.length === 0" class="text-center py-8 text-gray-500">
                            No upcoming bills found
                        </div>
                    </div>
                </section>

                <!-- Monthly Overview Chart -->
                <section class="chart-section bg-white rounded-lg shadow">
                    <div class="section-header px-4 py-3 border-b flex justify-between items-center">
                        <h2 class="text-lg font-semibold text-gray-800">Monthly Overview</h2>
                        <div class="flex space-x-2">
                            <button
                                @click="monthlyChartType = 'income-expense'"
                                :class="['px-2 py-1 text-xs rounded', monthlyChartType === 'income-expense' ? 'bg-blue-500 text-white' : 'bg-gray-200']"
                            >
                                Income vs Expense
                            </button>
                            <button
                                @click="monthlyChartType = 'balance'"
                                :class="['px-2 py-1 text-xs rounded', monthlyChartType === 'balance' ? 'bg-blue-500 text-white' : 'bg-gray-200']"
                            >
                                Balance
                            </button>
                        </div>
                    </div>
                    <div class="section-content p-4">
                        <monthly-chart
                            :data="monthlyData"
                            :chart-type="monthlyChartType"
                            :loading="!monthlyData.length"
                        />
                    </div>
                </section>
            </div>
        </div>
    </div>
</template>

<script>
import TransactionList from './transactions/TransactionList.vue';
import UpcomingBillsList from './bills/UpcomingBillsList.vue';
import SpendingChart from './charts/SpendingChart.vue';
import MonthlyChart from './charts/MonthlyChart.vue';

export default {
    name: 'DashboardView',

    components: {
        TransactionList,
        UpcomingBillsList,
        SpendingChart,
        MonthlyChart
    },

    data() {
        return {
            user: {},
            totalBalance: 0,
            monthlySpending: 0,
            upcomingBillsCount: 0,
            netSavings: 0,
            recentTransactions: [],
            upcomingBills: [],
            spendingData: [],
            monthlyData: [],
            spendingTimeframe: 'month',
            monthlyChartType: 'income-expense',
            loadingTransactions: true,
            loadingBills: true,
            prevMonthSpending: 0,
            prevMonthSavings: 0
        };
    },

    computed: {
        getSpendingTrend() {
            if (!this.prevMonthSpending) return 'From current spending';

            const diff = this.monthlySpending - this.prevMonthSpending;
            const percentage = (diff / this.prevMonthSpending) * 100;

            if (diff > 0) {
                return `${percentage.toFixed(1)}% more than last month`;
            } else if (diff < 0) {
                return `${Math.abs(percentage).toFixed(1)}% less than last month`;
            } else {
                return 'Same as last month';
            }
        },

        getSavingsTrend() {
            if (!this.prevMonthSavings) return 'From current savings';

            const diff = this.netSavings - this.prevMonthSavings;
            const percentage = this.prevMonthSavings ? (diff / Math.abs(this.prevMonthSavings)) * 100 : 0;

            if (diff > 0) {
                return `${percentage.toFixed(1)}% increase from last month`;
            } else if (diff < 0) {
                return `${Math.abs(percentage).toFixed(1)}% decrease from last month`;
            } else {
                return 'Same as last month';
            }
        }
    },

    created() {
        this.fetchDashboardData();
    },

    methods: {
        async fetchDashboardData() {
            try {
                // Get user profile
                const userResponse = await axios.get('/api/user');
                this.user = userResponse.data;

                // Get dashboard summary data
                const summaryResponse = await axios.get('/api/dashboard');
                const summaryData = summaryResponse.data.data;
                this.totalBalance = summaryData.total_balance;
                this.monthlySpending = summaryData.monthly_spending;
                this.upcomingBillsCount = summaryData.upcoming_bills_count;
                this.netSavings = summaryData.net_savings;

                // Get recent transactions
                const transactionsResponse = await axios.get('/api/dashboard/recent-transactions');
                this.recentTransactions = transactionsResponse.data.data;
                this.loadingTransactions = false;

                // Get upcoming bills
                const billsResponse = await axios.get('/api/dashboard/upcoming-bills');
                this.upcomingBills = billsResponse.data.data;
                this.loadingBills = false;

                // Get spending by category data
                // const spendingResponse = await axios.get('/api/reports/spending-by-category', {
                //     params: { timeframe: this.spendingTimeframe }
                // });
                // this.spendingData = spendingResponse.data.data;

                // Get monthly summary data
                //const monthlyResponse = await axios.get('/api/reports/monthly-summary');
                //this.monthlyData = monthlyResponse.data.data;

                // Get trend data
                const trendsResponse = await axios.get('/api/reports/trends');
                const trendsData = trendsResponse.data.data;
                this.prevMonthSpending = trendsData.prev_month_spending;
                this.prevMonthSavings = trendsData.prev_month_savings;

            } catch (error) {
                console.error('Error fetching dashboard data:', error);
                // If you have a toast notification system
                if (this.$toast) {
                    this.$toast.error('Failed to load dashboard data');
                }
            }
        },

        viewTransaction(transaction) {
            this.$router.push({ name: 'transaction-details', params: { id: transaction.id } });
        },

        payBill(bill) {
            this.$router.push({ name: 'pay-bill', params: { id: bill.id } });
        },

        async fetchSpendingByTimeframe() {
            try {
                const response = await axios.get('/api/reports/spending-by-category', {
                    params: { timeframe: this.spendingTimeframe }
                });
                this.spendingData = response.data.data;
            } catch (error) {
                console.error('Error fetching spending data:', error);
            }
        }
    },

    watch: {
        spendingTimeframe(newTimeframe) {
            this.fetchSpendingByTimeframe();
        }
    },

    filters: {
        currency(value) {
            return '৳ ' + Number(value).toLocaleString('en-BD');
        }
    }
};
</script>

<style scoped>
.dashboard {
    max-width: 1400px;
    margin: 0 auto;
    padding: 1.5rem;
}

.dashboard-header {
    margin-bottom: 1.5rem;
}

.dashboard-header h1 {
    font-size: 1.5rem;
    font-weight: 700;
    color: #1f2937;
}

.dashboard-header p {
    color: #6b7280;
}

.dashboard-stats {
    margin-bottom: 2rem;
}

.grid {
    display: grid;
}

.grid-cols-1 {
    grid-template-columns: repeat(1, minmax(0, 1fr));
}

.gap-4 {
    gap: 1rem;
}

.stat-card {
    background-color: white;
    padding: 1rem;
    border-radius: 0.5rem;
    box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
    transition: transform 0.2s ease;
}

.stat-card:hover {
    transform: translateY(-3px);
}

.stat-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 2.5rem;
    height: 2.5rem;
    border-radius: 9999px;
    margin-right: 0.75rem;
}

.bg-blue-100 {
    background-color: #dbeafe;
}

.bg-red-100 {
    background-color: #fee2e2;
}

.bg-yellow-100 {
    background-color: #fef3c7;
}

.bg-green-100 {
    background-color: #d1fae5;
}

.text-blue-500 {
    color: #3b82f6;
}

.text-red-500 {
    color: #ef4444;
}

.text-yellow-500 {
    color: #f59e0b;
}

.text-green-500 {
    color: #10b981;
}

.text-green-600 {
    color: #059669;
}

.text-red-600 {
    color: #dc2626;
}

.stat-value {
    font-size: 1.5rem;
    font-weight: 700;
    color: #1f2937;
}

.flex {
    display: flex;
}

.flex-col {
    flex-direction: column;
}

.items-center {
    align-items: center;
}

.justify-between {
    justify-content: space-between;
}

.flex-1 {
    flex: 1 1 0%;
}

.mb-2 {
    margin-bottom: 0.5rem;
}

.mb-6 {
    margin-bottom: 1.5rem;
}

.mb-8 {
    margin-bottom: 2rem;
}

.mr-2 {
    margin-right: 0.5rem;
}

.mr-3 {
    margin-right: 0.75rem;
}

.mt-1 {
    margin-top: 0.25rem;
}

.mt-2 {
    margin-top: 0.5rem;
}

.text-sm {
    font-size: 0.875rem;
}

.text-xs {
    font-size: 0.75rem;
}

.text-lg {
    font-size: 1.125rem;
}

.text-2xl {
    font-size: 1.5rem;
}

.font-medium {
    font-weight: 500;
}

.font-semibold {
    font-weight: 600;
}

.font-bold {
    font-weight: 700;
}

.text-gray-500 {
    color: #6b7280;
}

.text-gray-600 {
    color: #4b5563;
}

.text-gray-800 {
    color: #1f2937;
}

.text-gray-900 {
    color: #111827;
}

.text-blue-500 {
    color: #3b82f6;
}

.text-blue-700 {
    color: #1d4ed8;
}

.btn-refresh {
    display: flex;
    align-items: center;
    padding: 0.5rem 1rem;
    border-radius: 0.375rem;
    background-color: #f3f4f6;
    color: #374151;
    font-size: 0.875rem;
    font-weight: 500;
    transition: all 0.2s ease;
}

.btn-refresh:hover {
    background-color: #e5e7eb;
}

.bg-white {
    background-color: white;
}

.rounded-lg {
    border-radius: 0.5rem;
}

.rounded-full {
    border-radius: 9999px;
}

.rounded {
    border-radius: 0.25rem;
}

.shadow {
    box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
}

.border {
    border-width: 1px;
}

.border-b {
    border-bottom-width: 1px;
}

.p-3 {
    padding: 0.75rem;
}

.p-4 {
    padding: 1rem;
}

.px-2 {
    padding-left: 0.5rem;
    padding-right: 0.5rem;
}

.px-4 {
    padding-left: 1rem;
    padding-right: 1rem;
}

.py-1 {
    padding-top: 0.25rem;
    padding-bottom: 0.25rem;
}

.py-3 {
    padding-top: 0.75rem;
    padding-bottom: 0.75rem;
}

.py-8 {
    padding-top: 2rem;
    padding-bottom: 2rem;
}

.section-header {
    padding: 0.75rem 1rem;
    border-bottom-width: 1px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.section-content {
    padding: 1rem;
}

.transactions-section,
.bills-section,
.chart-section {
    background-color: white;
    border-radius: 0.5rem;
    box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
}

.bg-blue-500 {
    background-color: #3b82f6;
}

.bg-gray-200 {
    background-color: #e5e7eb;
}

.text-white {
    color: white;
}

.text-center {
    text-align: center;
}

.space-x-2 > * + * {
    margin-left: 0.5rem;
}

.gap-6 {
    gap: 1.5rem;
}

.hover\:text-blue-700:hover {
    color: #1d4ed8;
}

.ml-1 {
    margin-left: 0.25rem;
}

@media (min-width: 768px) {
    .md\:grid-cols-2 {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (min-width: 1024px) {
    .lg\:grid-cols-4 {
        grid-template-columns: repeat(4, minmax(0, 1fr));
    }

    .lg\:flex-row {
        flex-direction: row;
    }
}

@media (max-width: 768px) {
    .dashboard-stats {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 640px) {
    .dashboard-stats {
        grid-template-columns: 1fr;
    }
}
</style>
