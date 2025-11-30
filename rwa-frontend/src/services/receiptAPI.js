import api from './api';

const RECEIPT_BASE_URL = '/receipts';

export const receiptAPI = {
  // Get all receipts with filters
  getReceipts: (params = {}) => {
    return api.get(RECEIPT_BASE_URL, { params });
  },

  // Get receipt by payment ID
  getReceiptByPayment: (paymentId) => {
    return api.get(`${RECEIPT_BASE_URL}/payment/${paymentId}`);
  },

  // Generate receipt for payment
  generateReceipt: (paymentId) => {
    return api.post(`${RECEIPT_BASE_URL}/generate/${paymentId}`);
  },

  // Get receipt view URL
  getReceiptViewUrl: (receiptId) => {
    return `${api.defaults.baseURL}${RECEIPT_BASE_URL}/${receiptId}/view`;
  },

  // Get receipt download URL
  getReceiptDownloadUrl: (receiptId) => {
    return `${api.defaults.baseURL}${RECEIPT_BASE_URL}/${receiptId}/download`;
  },

  // Generate missing receipts
  generateMissingReceipts: () => {
    return api.post(`${RECEIPT_BASE_URL}/generate-missing`);
  }
};

export default receiptAPI;