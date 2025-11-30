import React, { useState, useEffect } from 'react';
import { Table, Row, Col, Form, Button, Modal, Spinner, Badge, Pagination } from 'react-bootstrap';
import { FaEdit, FaTrash, FaEye, FaSearch } from 'react-icons/fa';
import { paymentAPI } from '../../services/api';
import { formatDate, formatCurrency, getStatusBadgeClass, getCurrentMonth, getMonthOptions, debounce, calculatePagination } from '../../utils/helpers';
import PaymentForm from './PaymentForm';

const PaymentList = ({ onRefresh, onSuccess, onError }) => {
  const [payments, setPayments] = useState([]);
  const [loading, setLoading] = useState(true);
  const [filters, setFilters] = useState({
    search: '',
    status: '',
    payment_month: getCurrentMonth(),
    page: 1,
    per_page: 10
  });
  const [pagination, setPagination] = useState(null);
  const [selectedPayment, setSelectedPayment] = useState(null);
  const [showEditModal, setShowEditModal] = useState(false);
  const [showDeleteModal, setShowDeleteModal] = useState(false);
  const [showViewModal, setShowViewModal] = useState(false);
  const [deleting, setDeleting] = useState(false);

  // Debounced search function
  const debouncedSearch = debounce((searchTerm) => {
    setFilters(prev => ({ ...prev, search: searchTerm, page: 1 }));
  }, 500);

  // Load payments when filters change
  useEffect(() => {
    const loadPayments = async () => {
      try {
        setLoading(true);
        const response = await paymentAPI.getPayments(filters);
        
        // Handle both old and new response structures
        if (response.data && Array.isArray(response.data.data)) {
          // New Laravel pagination structure
          setPayments(response.data.data || []);
          setPagination({
            current_page: response.data.current_page,
            last_page: response.data.last_page,
            total: response.data.total,
            from: response.data.from,
            to: response.data.to,
            per_page: response.data.per_page
          });
        } else if (Array.isArray(response.data)) {
          // Old structure (direct array)
          setPayments(response.data);
          setPagination(response.meta || null);
        } else {
          setPayments([]);
          setPagination(null);
        }
      } catch (err) {
        onError('Failed to load payments: ' + err.message);
      } finally {
        setLoading(false);
      }
    };

    loadPayments();
  }, [filters, onError]);

  const refreshPayments = async () => {
    try {
      setLoading(true);
      const response = await paymentAPI.getPayments(filters);
      
      // Handle both old and new response structures
      if (response.data && Array.isArray(response.data.data)) {
        // New Laravel pagination structure
        setPayments(response.data.data || []);
        setPagination({
          current_page: response.data.current_page,
          last_page: response.data.last_page,
          total: response.data.total,
          from: response.data.from,
          to: response.data.to,
          per_page: response.data.per_page
        });
      } else if (Array.isArray(response.data)) {
        // Old structure (direct array)
        setPayments(response.data);
        setPagination(response.meta || null);
      } else {
        setPayments([]);
        setPagination(null);
      }
    } catch (err) {
      onError('Failed to load payments: ' + err.message);
    } finally {
      setLoading(false);
    }
  };

  const handleFilterChange = (field, value) => {
    setFilters(prev => ({ 
      ...prev, 
      [field]: value, 
      page: 1 // Reset to first page when filters change
    }));
  };

  const handleSearch = (e) => {
    debouncedSearch(e.target.value);
  };

  const handlePageChange = (page) => {
    setFilters(prev => ({ ...prev, page }));
  };

  const handleEdit = (payment) => {
    setSelectedPayment(payment);
    setShowEditModal(true);
  };

  const handleView = (payment) => {
    setSelectedPayment(payment);
    setShowViewModal(true);
  };

  const handleDelete = (payment) => {
    setSelectedPayment(payment);
    setShowDeleteModal(true);
  };

  const confirmDelete = async () => {
    if (!selectedPayment) return;

    try {
      setDeleting(true);
      await paymentAPI.deletePayment(selectedPayment.id);
      
      onSuccess('Payment deleted successfully');
      setShowDeleteModal(false);
      setSelectedPayment(null);
      refreshPayments();
      onRefresh();
    } catch (err) {
      onError('Failed to delete payment: ' + err.message);
    } finally {
      setDeleting(false);
    }
  };

  const handleEditSuccess = (message) => {
    onSuccess(message);
    setShowEditModal(false);
    setSelectedPayment(null);
    refreshPayments();
    onRefresh();
  };

  const renderPagination = () => {
    if (!pagination || pagination.last_page <= 1) return null;

    const paginationData = calculatePagination(pagination.current_page, pagination.last_page);

    return (
      <div className="d-flex justify-content-between align-items-center">
        <div className="text-muted">
          Showing {pagination.from} to {pagination.to} of {pagination.total} payments
        </div>
        <Pagination className="mb-0">
          <Pagination.First 
            disabled={!paginationData.showFirst} 
            onClick={() => handlePageChange(1)}
          />
          <Pagination.Prev 
            disabled={!paginationData.showPrev} 
            onClick={() => handlePageChange(pagination.current_page - 1)}
          />
          
          {paginationData.pages.map(page => (
            <Pagination.Item
              key={page}
              active={page === pagination.current_page}
              onClick={() => handlePageChange(page)}
            >
              {page}
            </Pagination.Item>
          ))}
          
          <Pagination.Next 
            disabled={!paginationData.showNext} 
            onClick={() => handlePageChange(pagination.current_page + 1)}
          />
          <Pagination.Last 
            disabled={!paginationData.showLast} 
            onClick={() => handlePageChange(pagination.last_page)}
          />
        </Pagination>
      </div>
    );
  };

  return (
    <div>
      {/* Filters */}
      <div className="filter-section">
        <Row className="g-3">
          <Col md={4}>
            <Form.Group>
              <Form.Label>Search Residents</Form.Label>
              <div className="position-relative">
                <Form.Control
                  type="text"
                  placeholder="Search by name, house number..."
                  onChange={handleSearch}
                />
                <FaSearch className="position-absolute top-50 end-0 translate-middle-y me-3 text-muted" />
              </div>
            </Form.Group>
          </Col>
          
          <Col md={3}>
            <Form.Group>
              <Form.Label>Status</Form.Label>
              <Form.Select
                value={filters.status}
                onChange={(e) => handleFilterChange('status', e.target.value)}
              >
                <option value="">All Statuses</option>
                <option value="Pending">Pending</option>
                <option value="Paid">Paid</option>
                <option value="Partial">Partial</option>
                <option value="Overdue">Overdue</option>
              </Form.Select>
            </Form.Group>
          </Col>
          
          <Col md={3}>
            <Form.Group>
              <Form.Label>Payment Month</Form.Label>
              <Form.Select
                value={filters.payment_month}
                onChange={(e) => handleFilterChange('payment_month', e.target.value)}
              >
                <option value="">All Months</option>
                {getMonthOptions().map(month => (
                  <option key={month.value} value={month.value}>
                    {month.label}
                  </option>
                ))}
              </Form.Select>
            </Form.Group>
          </Col>
          
          <Col md={2}>
            <Form.Group>
              <Form.Label>Per Page</Form.Label>
              <Form.Select
                value={filters.per_page}
                onChange={(e) => handleFilterChange('per_page', parseInt(e.target.value))}
              >
                <option value={10}>10</option>
                <option value={25}>25</option>
                <option value={50}>50</option>
                <option value={100}>100</option>
              </Form.Select>
            </Form.Group>
          </Col>
        </Row>
      </div>

      {/* Payment Table */}
      <div className="payment-table">
        {loading ? (
          <div className="text-center p-4">
            <Spinner animation="border" role="status">
              <span className="visually-hidden">Loading...</span>
            </Spinner>
            <p className="mt-2 mb-0">Loading payments...</p>
          </div>
        ) : (
          <>
            <div className="table-responsive">
              <Table hover className="mb-0">
                <thead>
                  <tr>
                    <th>Resident</th>
                    <th>Month</th>
                    <th>Amount Due</th>
                    <th>Amount Paid</th>
                    <th>Status</th>
                    <th>Payment Date</th>
                    <th>Method</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  {payments.length === 0 ? (
                    <tr>
                      <td colSpan={8} className="text-center py-4">
                        <div className="text-muted">
                          <FaSearch className="mb-2" style={{ fontSize: '2rem' }} />
                          <p>No payments found</p>
                        </div>
                      </td>
                    </tr>
                  ) : (
                    payments.map(payment => (
                      <tr key={payment.id} className="payment-row">
                        <td>
                          <div>
                            <strong>{payment.resident?.owner_name || payment.resident?.name || 'N/A'}</strong>
                            <div className="resident-info">
                              House: {payment.resident?.house_number}, Floor: {payment.resident?.floor || 'N/A'}
                            </div>
                          </div>
                        </td>
                        <td>{payment.payment_month}</td>
                        <td>
                          <span className="payment-amount">
                            {formatCurrency(payment.amount_due)}
                          </span>
                        </td>
                        <td>
                          <span className={`payment-amount ${payment.amount_paid > 0 ? 'positive' : ''}`}>
                            {formatCurrency(payment.amount_paid)}
                          </span>
                        </td>
                        <td>
                          <Badge className={getStatusBadgeClass(payment.status)}>
                            {payment.status}
                          </Badge>
                        </td>
                        <td>{formatDate(payment.payment_date)}</td>
                        <td>{payment.payment_method}</td>
                        <td>
                          <div className="action-buttons">
                            <Button
                              variant="outline-info"
                              size="sm"
                              onClick={() => handleView(payment)}
                              title="View Details"
                            >
                              <FaEye />
                            </Button>
                            <Button
                              variant="outline-primary"
                              size="sm"
                              onClick={() => handleEdit(payment)}
                              title="Edit Payment"
                            >
                              <FaEdit />
                            </Button>
                            <Button
                              variant="outline-danger"
                              size="sm"
                              onClick={() => handleDelete(payment)}
                              title="Delete Payment"
                            >
                              <FaTrash />
                            </Button>
                          </div>
                        </td>
                      </tr>
                    ))
                  )}
                </tbody>
              </Table>
            </div>
            
            {/* Pagination */}
            <div className="pagination-wrapper">
              {renderPagination()}
            </div>
          </>
        )}
      </div>

      {/* Edit Payment Modal */}
      <Modal show={showEditModal} onHide={() => setShowEditModal(false)} size="lg">
        <Modal.Header closeButton>
          <Modal.Title>Edit Payment</Modal.Title>
        </Modal.Header>
        <Modal.Body>
          {selectedPayment && (
            <PaymentForm
              payment={selectedPayment}
              onSuccess={handleEditSuccess}
              onError={onError}
              isModal={true}
            />
          )}
        </Modal.Body>
      </Modal>

      {/* View Payment Modal */}
      <Modal show={showViewModal} onHide={() => setShowViewModal(false)}>
        <Modal.Header closeButton>
          <Modal.Title>Payment Details</Modal.Title>
        </Modal.Header>
        <Modal.Body>
          {selectedPayment && (
            <div>
              <Row>
                <Col sm={6}>
                  <strong>Resident:</strong>
                  <p>{selectedPayment.resident?.owner_name || selectedPayment.resident?.name}</p>
                </Col>
                <Col sm={6}>
                  <strong>House & Floor:</strong>
                  <p>House {selectedPayment.resident?.house_number}, Floor {selectedPayment.resident?.floor || 'N/A'}</p>
                </Col>
              </Row>
              <Row>
                <Col sm={6}>
                  <strong>Payment Month:</strong>
                  <p>{selectedPayment.payment_month}</p>
                </Col>
                <Col sm={6}>
                  <strong>Status:</strong>
                  <p>
                    <Badge className={getStatusBadgeClass(selectedPayment.status)}>
                      {selectedPayment.status}
                    </Badge>
                  </p>
                </Col>
              </Row>
              <Row>
                <Col sm={6}>
                  <strong>Amount Due:</strong>
                  <p className="payment-amount">{formatCurrency(selectedPayment.amount_due)}</p>
                </Col>
                <Col sm={6}>
                  <strong>Amount Paid:</strong>
                  <p className="payment-amount positive">{formatCurrency(selectedPayment.amount_paid)}</p>
                </Col>
              </Row>
              <Row>
                <Col sm={6}>
                  <strong>Payment Date:</strong>
                  <p>{formatDate(selectedPayment.payment_date) || 'Not specified'}</p>
                </Col>
                <Col sm={6}>
                  <strong>Payment Method:</strong>
                  <p>{selectedPayment.payment_method}</p>
                </Col>
              </Row>
              {selectedPayment.payment_description && (
                <Row>
                  <Col>
                    <strong>Description:</strong>
                    <p>{selectedPayment.payment_description}</p>
                  </Col>
                </Row>
              )}
              {selectedPayment.notes && (
                <Row>
                  <Col>
                    <strong>Notes:</strong>
                    <p>{selectedPayment.notes}</p>
                  </Col>
                </Row>
              )}
            </div>
          )}
        </Modal.Body>
        <Modal.Footer>
          <Button variant="secondary" onClick={() => setShowViewModal(false)}>
            Close
          </Button>
          <Button variant="primary" onClick={() => {
            setShowViewModal(false);
            handleEdit(selectedPayment);
          }}>
            Edit Payment
          </Button>
        </Modal.Footer>
      </Modal>

      {/* Delete Confirmation Modal */}
      <Modal show={showDeleteModal} onHide={() => setShowDeleteModal(false)}>
        <Modal.Header closeButton>
          <Modal.Title>Confirm Delete</Modal.Title>
        </Modal.Header>
        <Modal.Body>
          {selectedPayment && (
            <p>
              Are you sure you want to delete the payment for{' '}
              <strong>{selectedPayment.resident?.owner_name || selectedPayment.resident?.name}</strong> for{' '}
              <strong>{selectedPayment.payment_month}</strong>?
            </p>
          )}
        </Modal.Body>
        <Modal.Footer>
          <Button variant="secondary" onClick={() => setShowDeleteModal(false)}>
            Cancel
          </Button>
          <Button 
            variant="danger" 
            onClick={confirmDelete}
            disabled={deleting}
          >
            {deleting ? (
              <>
                <Spinner animation="border" size="sm" className="me-1" />
                Deleting...
              </>
            ) : (
              'Delete Payment'
            )}
          </Button>
        </Modal.Footer>
      </Modal>
    </div>
  );
};

export default PaymentList;