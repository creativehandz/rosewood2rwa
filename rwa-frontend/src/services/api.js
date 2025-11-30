import axios from 'axios';

// Base API configuration
const API_BASE_URL = process.env.REACT_APP_API_URL || 'https://rosewoodestate2rwa.creativehandz.in/api/v1/public';

// Create axios instance with default config
const api = axios.create({
  baseURL: API_BASE_URL,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
});

// Simple response interceptor for error handling (no auth required)
api.interceptors.response.use(
  (response) => response,
  (error) => {
    // Just return the error without auth handling for demo purposes
    return Promise.reject(error);
  }
);

// No authentication required for this demo

// Payment API functions
export const paymentAPI = {
  // Get all payments with filters
  getPayments: async (filters = {}) => {
    const params = new URLSearchParams();
    if (filters.status) params.append('status', filters.status);
    if (filters.payment_month) params.append('payment_month', filters.payment_month);
    if (filters.payment_month_start) params.append('payment_month_start', filters.payment_month_start);
    if (filters.payment_month_end) params.append('payment_month_end', filters.payment_month_end);
    if (filters.resident_id) params.append('resident_id', filters.resident_id);
    if (filters.search) params.append('search', filters.search);
    if (filters.floor) params.append('floor', filters.floor);
    if (filters.min_due_amount) params.append('min_due_amount', filters.min_due_amount);
    if (filters.min_months_overdue) params.append('min_months_overdue', filters.min_months_overdue);
    if (filters.risk_level) params.append('risk_level', filters.risk_level);
    if (filters.per_page) params.append('per_page', filters.per_page);
    if (filters.page) params.append('page', filters.page);

    const response = await api.get(`/payments?${params}`);
    return response.data;
  },

  // Get single payment
  getPayment: async (id) => {
    const response = await api.get(`/payments/${id}`);
    return response.data;
  },

  // Create new payment
  createPayment: async (paymentData) => {
    const response = await api.post('/payments', paymentData);
    return response.data;
  },

  // Update payment
  updatePayment: async (id, paymentData) => {
    const response = await api.put(`/payments/${id}`, paymentData);
    return response.data;
  },

  // Delete payment
  deletePayment: async (id) => {
    const response = await api.delete(`/payments/${id}`);
    return response.data;
  },

  // Get payments by status
  getPaymentsByStatus: async (status) => {
    const response = await api.get(`/payments/filter/by-status/${status}`);
    return response.data;
  },

  // Get payments by month
  getPaymentsByMonth: async (month) => {
    const response = await api.get(`/payments/filter/by-month/${month}`);
    return response.data;
  },

  // Get overdue payments
  getOverduePayments: async () => {
    const response = await api.get('/payments/filter/overdue');
    return response.data;
  },

  // Get payment summary
  getPaymentSummary: async () => {
    const response = await api.get('/payments/summary');
    return response.data;
  },

  // Bulk sync payments
  bulkSync: async (payments) => {
    const response = await api.post('/payments/bulk-sync', { payments });
    return response.data;
  },

  // Get unpaid residents (using non-payers endpoint)
  getUnpaidResidents: async (filters = {}) => {
    const params = new URLSearchParams();
    if (filters.search) params.append('search', filters.search);
    if (filters.payment_month) params.append('payment_month', filters.payment_month);
    if (filters.floor) params.append('floor', filters.floor);
    if (filters.min_due_amount) params.append('min_due_amount', filters.min_due_amount);

    const response = await api.get(`/residents/filter/non-payers?${params}`);
    return response.data;
  },

  // Get defaulters
  getDefaulters: async (filters = {}) => {
    const params = new URLSearchParams();
    if (filters.search) params.append('search', filters.search);
    if (filters.risk_level) params.append('risk_level', filters.risk_level);
    if (filters.floor) params.append('floor', filters.floor);
    if (filters.min_months_overdue) params.append('min_months_overdue', filters.min_months_overdue);

    const response = await api.get(`/payments/filter/defaulters?${params}`);
    return response.data;
  },

  // Get analytics
  getAnalytics: async (filters = {}) => {
    const params = new URLSearchParams();
    if (filters.start_month) params.append('start_month', filters.start_month);
    if (filters.end_month) params.append('end_month', filters.end_month);
    if (filters.year) params.append('year', filters.year);

    const response = await api.get(`/payments/analytics?${params}`);
    return response.data;
  },
};

// Resident API functions
export const residentAPI = {
  // Get all residents
  getResidents: async (filters = {}) => {
    const params = new URLSearchParams();
    if (filters.search) params.append('search', filters.search);
    if (filters.floor) params.append('floor', filters.floor);
    if (filters.per_page) params.append('per_page', filters.per_page);
    if (filters.page) params.append('page', filters.page);

    const response = await api.get(`/residents?${params}`);
    return response.data;
  },

  // Get single resident
  getResident: async (id) => {
    const response = await api.get(`/residents/${id}`);
    return response.data;
  },

  // Get resident payments
  getResidentPayments: async (residentId) => {
    const response = await api.get(`/residents/${residentId}/payments`);
    return response.data;
  },

  // Get dashboard stats
  getDashboardStats: async () => {
    const response = await api.get('/dashboard/stats');
    return response.data;
  },
};

// Google Sheets sync API functions
export const googleSheetsAPI = {
  // Test connection
  testConnection: async () => {
    const response = await api.get('/payments/google-sheets/test-connection');
    return response.data;
  },

  // Sync to sheets
  syncToSheets: async () => {
    const response = await api.post('/payments/google-sheets/sync-to-sheets');
    return response.data;
  },

  // Sync from sheets
  syncFromSheets: async () => {
    const response = await api.post('/payments/google-sheets/sync-from-sheets');
    return response.data;
  },

  // Bidirectional sync
  bidirectionalSync: async () => {
    const response = await api.post('/payments/google-sheets/bidirectional-sync');
    return response.data;
  },

  // Get sync status
  getSyncStatus: async () => {
    const response = await api.get('/payments/google-sheets/sync-status');
    return response.data;
  },
};

// Export the axios instance for custom requests
export default api;