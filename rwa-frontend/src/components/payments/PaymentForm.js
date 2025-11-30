import React, { useState, useEffect } from 'react';
import { Row, Col, Form, Button, Spinner, Alert } from 'react-bootstrap';
import { FaSearch, FaUser, FaHome } from 'react-icons/fa';
import { paymentAPI, residentAPI } from '../../services/api';
import { validatePayment, getCurrentMonth, getMonthOptions, PAYMENT_METHODS, PAYMENT_STATUSES, debounce } from '../../utils/helpers';

const PaymentForm = ({ payment = null, onSuccess, onError, isModal = false }) => {
  const [formData, setFormData] = useState({
    resident_id: '',
    payment_month: getCurrentMonth(),
    amount_due: '',
    amount_paid: '',
    payment_method: 'Cash',
    status: 'Pending',
    payment_date: '',
    payment_description: '',
    late_fee: '',
    notes: ''
  });
  
  const [residents, setResidents] = useState([]);
  const [selectedResident, setSelectedResident] = useState(null);
  const [residentSearch, setResidentSearch] = useState('');
  const [showResidentResults, setShowResidentResults] = useState(false);
  const [loadingResidents, setLoadingResidents] = useState(false);
  const [submitting, setSubmitting] = useState(false);
  const [errors, setErrors] = useState({});
  const [localError, setLocalError] = useState('');

  // Initialize form data for editing
  useEffect(() => {
    if (payment) {
      setFormData({
        resident_id: payment.resident_id || '',
        payment_month: payment.payment_month || getCurrentMonth(),
        amount_due: payment.amount_due || '',
        amount_paid: payment.amount_paid || '',
        payment_method: payment.payment_method || 'Cash',
        status: payment.status || 'Pending',
        payment_date: payment.payment_date ? payment.payment_date.split('T')[0] : '',
        payment_description: payment.payment_description || '',
        late_fee: payment.late_fee || '',
        notes: payment.notes || ''
      });
      
      if (payment.resident) {
        setSelectedResident(payment.resident);
        setResidentSearch(payment.resident.name);
      }
    }
  }, [payment]);

  // Debounced resident search
  const debouncedResidentSearch = debounce(async (searchTerm) => {
    if (searchTerm.length < 2) {
      setResidents([]);
      setShowResidentResults(false);
      return;
    }

    try {
      setLoadingResidents(true);
      const response = await residentAPI.getResidents({ search: searchTerm, per_page: 10 });
      setResidents(response.data || []);
      setShowResidentResults(true);
    } catch (err) {
      console.error('Error searching residents:', err);
    } finally {
      setLoadingResidents(false);
    }
  }, 300);

  const handleResidentSearch = (e) => {
    const value = e.target.value;
    setResidentSearch(value);
    
    if (!value) {
      setSelectedResident(null);
      setFormData(prev => ({ ...prev, resident_id: '' }));
      setResidents([]);
      setShowResidentResults(false);
      return;
    }
    
    debouncedResidentSearch(value);
  };

  const handleResidentSelect = (resident) => {
    setSelectedResident(resident);
    setResidentSearch(resident.name);
    setFormData(prev => ({ ...prev, resident_id: resident.id }));
    setShowResidentResults(false);
  };

  const handleInputChange = (field, value) => {
    setFormData(prev => ({ ...prev, [field]: value }));
    
    // Clear field error when user starts typing
    if (errors[field]) {
      setErrors(prev => ({ ...prev, [field]: '' }));
    }

    // Auto-calculate status based on amounts
    if (field === 'amount_paid' || field === 'amount_due') {
      const amountDue = field === 'amount_due' ? parseFloat(value) || 0 : parseFloat(formData.amount_due) || 0;
      const amountPaid = field === 'amount_paid' ? parseFloat(value) || 0 : parseFloat(formData.amount_paid) || 0;
      
      let newStatus = 'Pending';
      if (amountPaid >= amountDue && amountDue > 0) {
        newStatus = 'Paid';
      } else if (amountPaid > 0 && amountPaid < amountDue) {
        newStatus = 'Partial';
      }
      
      setFormData(prev => ({ ...prev, status: newStatus }));
    }
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setLocalError('');
    
    // Validate form
    const validation = validatePayment(formData);
    if (!validation.isValid) {
      setErrors(validation.errors);
      return;
    }

    try {
      setSubmitting(true);
      
      const submitData = {
        ...formData,
        amount_due: parseFloat(formData.amount_due) || 0,
        amount_paid: parseFloat(formData.amount_paid) || 0,
        late_fee: parseFloat(formData.late_fee) || 0,
        payment_date: formData.payment_date || null
      };

      if (payment) {
        // Update existing payment
        await paymentAPI.updatePayment(payment.id, submitData);
        onSuccess('Payment updated successfully');
      } else {
        // Create new payment
        await paymentAPI.createPayment(submitData);
        onSuccess('Payment created successfully');
      }

      // Reset form if not in modal
      if (!isModal) {
        resetForm();
      }
    } catch (err) {
      const errorMessage = err.response?.data?.message || err.message || 'Failed to save payment';
      setLocalError(errorMessage);
      if (onError) onError(errorMessage);
    } finally {
      setSubmitting(false);
    }
  };

  const resetForm = () => {
    setFormData({
      resident_id: '',
      payment_month: getCurrentMonth(),
      amount_due: '',
      amount_paid: '',
      payment_method: 'Cash',
      status: 'Pending',
      payment_date: '',
      payment_description: '',
      late_fee: '',
      notes: ''
    });
    setSelectedResident(null);
    setResidentSearch('');
    setErrors({});
    setLocalError('');
  };

  return (
    <div className="payment-form">
      {localError && (
        <Alert variant="danger" className="mb-3">
          {localError}
        </Alert>
      )}

      <Form onSubmit={handleSubmit}>
        {/* Resident Selection */}
        <div className="form-section">
          <h5>
            <FaUser className="me-2" />
            Resident Information
          </h5>
          
          {selectedResident ? (
            <div className="selected-resident">
              <Row>
                <Col md={8}>
                  <h6 className="mb-1">{selectedResident.name}</h6>
                  <p className="mb-1 text-muted">
                    <FaHome className="me-1" />
                    House {selectedResident.house_number}, Floor {selectedResident.floor}
                  </p>
                  <p className="mb-0 text-muted">Phone: {selectedResident.phone}</p>
                </Col>
                <Col md={4} className="text-end">
                  <Button 
                    variant="outline-secondary" 
                    size="sm"
                    onClick={() => {
                      setSelectedResident(null);
                      setResidentSearch('');
                      setFormData(prev => ({ ...prev, resident_id: '' }));
                    }}
                  >
                    Change Resident
                  </Button>
                </Col>
              </Row>
            </div>
          ) : (
            <div className="resident-search">
              <Form.Group className="mb-3">
                <Form.Label>Search Resident</Form.Label>
                <div className="position-relative">
                  <Form.Control
                    type="text"
                    placeholder="Type resident name, house number, or phone..."
                    value={residentSearch}
                    onChange={handleResidentSearch}
                    isInvalid={!!errors.resident_id}
                  />
                  <FaSearch className="position-absolute top-50 end-0 translate-middle-y me-3 text-muted" />
                  {loadingResidents && (
                    <Spinner 
                      animation="border" 
                      size="sm" 
                      className="position-absolute top-50 end-0 translate-middle-y me-5"
                    />
                  )}
                </div>
                <Form.Control.Feedback type="invalid">
                  {errors.resident_id}
                </Form.Control.Feedback>
              </Form.Group>
              
              {showResidentResults && residents.length > 0 && (
                <div className="resident-search-results">
                  {residents.map(resident => (
                    <div 
                      key={resident.id}
                      className="resident-search-item"
                      onClick={() => handleResidentSelect(resident)}
                    >
                      <div className="fw-bold">{resident.name}</div>
                      <div className="text-muted small">
                        House {resident.house_number}, Floor {resident.floor} • {resident.phone}
                      </div>
                    </div>
                  ))}
                </div>
              )}
            </div>
          )}
        </div>

        {/* Payment Details */}
        <div className="form-section">
          <h5>Payment Details</h5>
          
          <Row className="g-3">
            <Col md={6}>
              <Form.Floating>
                <Form.Select
                  id="payment_month"
                  value={formData.payment_month}
                  onChange={(e) => handleInputChange('payment_month', e.target.value)}
                  isInvalid={!!errors.payment_month}
                >
                  {getMonthOptions().map(month => (
                    <option key={month.value} value={month.value}>
                      {month.label}
                    </option>
                  ))}
                </Form.Select>
                <label htmlFor="payment_month">Payment Month</label>
                <Form.Control.Feedback type="invalid">
                  {errors.payment_month}
                </Form.Control.Feedback>
              </Form.Floating>
            </Col>
            
            <Col md={6}>
              <Form.Floating>
                <Form.Control
                  id="payment_date"
                  type="date"
                  value={formData.payment_date}
                  onChange={(e) => handleInputChange('payment_date', e.target.value)}
                />
                <label htmlFor="payment_date">Payment Date (Optional)</label>
              </Form.Floating>
            </Col>
          </Row>

          <Row className="g-3 mt-1">
            <Col md={4}>
              <Form.Floating>
                <Form.Control
                  id="amount_due"
                  type="number"
                  step="0.01"
                  min="0"
                  placeholder="Amount Due"
                  value={formData.amount_due}
                  onChange={(e) => handleInputChange('amount_due', e.target.value)}
                  isInvalid={!!errors.amount_due}
                />
                <label htmlFor="amount_due">Amount Due (₹)</label>
                <Form.Control.Feedback type="invalid">
                  {errors.amount_due}
                </Form.Control.Feedback>
              </Form.Floating>
            </Col>
            
            <Col md={4}>
              <Form.Floating>
                <Form.Control
                  id="amount_paid"
                  type="number"
                  step="0.01"
                  min="0"
                  placeholder="Amount Paid"
                  value={formData.amount_paid}
                  onChange={(e) => handleInputChange('amount_paid', e.target.value)}
                  isInvalid={!!errors.amount_paid}
                />
                <label htmlFor="amount_paid">Amount Paid (₹)</label>
                <Form.Control.Feedback type="invalid">
                  {errors.amount_paid}
                </Form.Control.Feedback>
              </Form.Floating>
            </Col>
            
            <Col md={4}>
              <Form.Floating>
                <Form.Control
                  id="late_fee"
                  type="number"
                  step="0.01"
                  min="0"
                  placeholder="Late Fee"
                  value={formData.late_fee}
                  onChange={(e) => handleInputChange('late_fee', e.target.value)}
                />
                <label htmlFor="late_fee">Late Fee (₹)</label>
              </Form.Floating>
            </Col>
          </Row>

          <Row className="g-3 mt-1">
            <Col md={6}>
              <Form.Floating>
                <Form.Select
                  id="payment_method"
                  value={formData.payment_method}
                  onChange={(e) => handleInputChange('payment_method', e.target.value)}
                >
                  {PAYMENT_METHODS.map(method => (
                    <option key={method.value} value={method.value}>
                      {method.label}
                    </option>
                  ))}
                </Form.Select>
                <label htmlFor="payment_method">Payment Method</label>
              </Form.Floating>
            </Col>
            
            <Col md={6}>
              <Form.Floating>
                <Form.Select
                  id="status"
                  value={formData.status}
                  onChange={(e) => handleInputChange('status', e.target.value)}
                  isInvalid={!!errors.status}
                >
                  {PAYMENT_STATUSES.map(status => (
                    <option key={status.value} value={status.value}>
                      {status.label}
                    </option>
                  ))}
                </Form.Select>
                <label htmlFor="status">Status</label>
                <Form.Control.Feedback type="invalid">
                  {errors.status}
                </Form.Control.Feedback>
              </Form.Floating>
            </Col>
          </Row>
        </div>

        {/* Additional Information */}
        <div className="form-section">
          <h5>Additional Information</h5>
          
          <Row className="g-3">
            <Col md={6}>
              <Form.Floating>
                <Form.Control
                  id="payment_description"
                  type="text"
                  placeholder="Payment Description"
                  value={formData.payment_description}
                  onChange={(e) => handleInputChange('payment_description', e.target.value)}
                />
                <label htmlFor="payment_description">Description (Optional)</label>
              </Form.Floating>
            </Col>
            
            <Col md={6}>
              <Form.Floating>
                <Form.Control
                  id="notes"
                  as="textarea"
                  rows={1}
                  placeholder="Notes"
                  value={formData.notes}
                  onChange={(e) => handleInputChange('notes', e.target.value)}
                />
                <label htmlFor="notes">Notes (Optional)</label>
              </Form.Floating>
            </Col>
          </Row>
        </div>

        {/* Submit Buttons */}
        <div className="d-flex gap-2 justify-content-end">
          {!isModal && (
            <Button 
              variant="outline-secondary" 
              type="button"
              onClick={resetForm}
              disabled={submitting}
            >
              Reset Form
            </Button>
          )}
          <Button 
            variant="primary" 
            type="submit"
            disabled={submitting || !selectedResident}
          >
            {submitting ? (
              <>
                <Spinner animation="border" size="sm" className="me-2" />
                {payment ? 'Updating...' : 'Creating...'}
              </>
            ) : (
              payment ? 'Update Payment' : 'Create Payment'
            )}
          </Button>
        </div>
      </Form>
    </div>
  );
};

export default PaymentForm;