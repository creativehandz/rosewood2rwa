// Date utility functions
export const formatDate = (date) => {
  if (!date) return '';
  const d = new Date(date);
  return d.toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  });
};

export const formatDateTime = (date) => {
  if (!date) return '';
  const d = new Date(date);
  return d.toLocaleString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
};

export const formatCurrency = (amount) => {
  if (!amount) return '₹0';
  return `₹${parseFloat(amount).toLocaleString('en-IN')}`;
};

export const getCurrentMonth = () => {
  const now = new Date();
  const year = now.getFullYear();
  const month = String(now.getMonth() + 1).padStart(2, '0');
  return `${year}-${month}`;
};

export const getMonthOptions = (monthsBack = 12, monthsForward = 3) => {
  const options = [];
  const currentDate = new Date();
  
  // Add previous months
  for (let i = monthsBack; i > 0; i--) {
    const date = new Date(currentDate.getFullYear(), currentDate.getMonth() - i, 1);
    options.push({
      value: `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}`,
      label: date.toLocaleDateString('en-US', { year: 'numeric', month: 'long' })
    });
  }
  
  // Add current month
  options.push({
    value: getCurrentMonth(),
    label: currentDate.toLocaleDateString('en-US', { year: 'numeric', month: 'long' })
  });
  
  // Add future months
  for (let i = 1; i <= monthsForward; i++) {
    const date = new Date(currentDate.getFullYear(), currentDate.getMonth() + i, 1);
    options.push({
      value: `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}`,
      label: date.toLocaleDateString('en-US', { year: 'numeric', month: 'long' })
    });
  }
  
  return options;
};

// Form validation utilities
export const validatePayment = (payment) => {
  const errors = {};

  if (!payment.resident_id) {
    errors.resident_id = 'Please select a resident';
  }

  if (!payment.payment_month) {
    errors.payment_month = 'Payment month is required';
  }

  if (!payment.amount_due || payment.amount_due <= 0) {
    errors.amount_due = 'Amount due must be greater than 0';
  }

  if (payment.amount_paid < 0) {
    errors.amount_paid = 'Amount paid cannot be negative';
  }

  if (payment.amount_paid > payment.amount_due) {
    errors.amount_paid = 'Amount paid cannot be greater than amount due';
  }

  if (!payment.status) {
    errors.status = 'Status is required';
  }

  return {
    isValid: Object.keys(errors).length === 0,
    errors
  };
};

// Status badge utilities
export const getStatusBadgeClass = (status) => {
  switch (status?.toLowerCase()) {
    case 'paid':
      return 'badge bg-success';
    case 'pending':
      return 'badge bg-warning text-dark';
    case 'partial':
      return 'badge bg-info';
    case 'overdue':
      return 'badge bg-danger';
    default:
      return 'badge bg-secondary';
  }
};

// Payment method options
export const PAYMENT_METHODS = [
  { value: 'Cash', label: 'Cash' },
  { value: 'UPI', label: 'UPI' },
  { value: 'Bank Transfer', label: 'Bank Transfer' },
  { value: 'Cheque', label: 'Cheque' },
  { value: 'Online', label: 'Online' }
];

// Payment status options
export const PAYMENT_STATUSES = [
  { value: 'Pending', label: 'Pending' },
  { value: 'Paid', label: 'Paid' },
  { value: 'Partial', label: 'Partial' },
  { value: 'Overdue', label: 'Overdue' }
];

// Debounce utility for search
export const debounce = (func, wait) => {
  let timeout;
  return function executedFunction(...args) {
    const later = () => {
      clearTimeout(timeout);
      func(...args);
    };
    clearTimeout(timeout);
    timeout = setTimeout(later, wait);
  };
};

// Local storage utilities
export const storage = {
  get: (key, defaultValue = null) => {
    try {
      const item = localStorage.getItem(key);
      return item ? JSON.parse(item) : defaultValue;
    } catch (error) {
      console.error('Error reading from localStorage:', error);
      return defaultValue;
    }
  },
  
  set: (key, value) => {
    try {
      localStorage.setItem(key, JSON.stringify(value));
    } catch (error) {
      console.error('Error writing to localStorage:', error);
    }
  },
  
  remove: (key) => {
    try {
      localStorage.removeItem(key);
    } catch (error) {
      console.error('Error removing from localStorage:', error);
    }
  }
};

// Table pagination utility
export const calculatePagination = (currentPage, totalPages, maxVisible = 5) => {
  const pages = [];
  const half = Math.floor(maxVisible / 2);
  
  let start = Math.max(1, currentPage - half);
  let end = Math.min(totalPages, start + maxVisible - 1);
  
  if (end - start + 1 < maxVisible) {
    start = Math.max(1, end - maxVisible + 1);
  }
  
  for (let i = start; i <= end; i++) {
    pages.push(i);
  }
  
  return {
    pages,
    showFirst: start > 1,
    showLast: end < totalPages,
    showPrev: currentPage > 1,
    showNext: currentPage < totalPages
  };
};