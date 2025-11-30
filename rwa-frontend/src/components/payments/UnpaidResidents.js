import React, { useState, useEffect } from 'react';
import { Container, Row, Col, Card, Table, Badge, Button, Form, Alert, Spinner, Modal } from 'react-bootstrap';
import { FaUsers, FaSearch, FaExclamationTriangle, FaPhoneAlt, FaEnvelope, FaHome, FaCalendarAlt } from 'react-icons/fa';
import { paymentAPI } from '../../services/api';
import { formatCurrency, formatDate, getCurrentMonth, getMonthOptions, debounce } from '../../utils/helpers';
import PaymentForm from './PaymentForm';

const UnpaidResidents = () => {
  const [unpaidResidents, setUnpaidResidents] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [successMessage, setSuccessMessage] = useState('');
  const [filters, setFilters] = useState({
    search: '',
    payment_month: getCurrentMonth(),
    floor: '',
    min_due_amount: ''
  });
  const [selectedResident, setSelectedResident] = useState(null);
  const [showPaymentModal, setShowPaymentModal] = useState(false);
  const [stats, setStats] = useState({
    total_unpaid_residents: 0,
    total_unpaid_amount: 0,
    oldest_unpaid_month: null,
    average_unpaid_amount: 0
  });

  // Debounced search function
  const debouncedSearch = debounce((searchTerm) => {
    setFilters(prev => ({ ...prev, search: searchTerm }));
  }, 500);

  // Load unpaid residents when filters change
  useEffect(() => {
    loadUnpaidResidents();
  }, [filters]); // eslint-disable-line react-hooks/exhaustive-deps

  const loadUnpaidResidents = async () => {
    try {
      setLoading(true);
      setError(null);

      // Create query parameters
      const params = new URLSearchParams();
      if (filters.search) params.append('search', filters.search);
      if (filters.payment_month) params.append('payment_month', filters.payment_month);
      if (filters.floor) params.append('floor', filters.floor);
      if (filters.min_due_amount) params.append('min_due_amount', filters.min_due_amount);

      const response = await paymentAPI.getPayments({
        status: 'Pending',
        ...filters
      });

      // Process the data to group by residents and calculate stats
      // Handle paginated response structure
      const residents = response.data?.data || response.data || [];
      setUnpaidResidents(residents);

      // Calculate statistics
      const totalUnpaidAmount = residents.reduce((sum, payment) => 
        sum + parseFloat(payment.amount_due || 0), 0
      );

      const uniqueResidents = [...new Set(residents.map(p => p.resident_id))];
      
      setStats({
        total_unpaid_residents: uniqueResidents.length,
        total_unpaid_amount: totalUnpaidAmount,
        oldest_unpaid_month: residents.length > 0 ? 
          Math.min(...residents.map(p => new Date(p.payment_month + '-01').getTime())) : null,
        average_unpaid_amount: uniqueResidents.length > 0 ? 
          totalUnpaidAmount / uniqueResidents.length : 0
      });

    } catch (err) {
      setError('Failed to load unpaid residents: ' + err.message);
    } finally {
      setLoading(false);
    }
  };

  const handleFilterChange = (field, value) => {
    setFilters(prev => ({ ...prev, [field]: value }));
  };

  const handleSearch = (e) => {
    debouncedSearch(e.target.value);
  };

  const handleAddPayment = (resident) => {
    setSelectedResident(resident);
    setShowPaymentModal(true);
  };

  const handlePaymentSuccess = (message) => {
    setSuccessMessage(message);
    setShowPaymentModal(false);
    setSelectedResident(null);
    loadUnpaidResidents();
    
    // Clear success message after 5 seconds
    setTimeout(() => setSuccessMessage(''), 5000);
  };

  const handlePaymentError = (message) => {
    setError(message);
    
    // Clear error message after 5 seconds
    setTimeout(() => setError(''), 5000);
  };

  const getFloorOptions = () => {
    if (!Array.isArray(unpaidResidents)) return [];
    const floors = [...new Set(unpaidResidents.map(p => p.resident?.floor).filter(Boolean))];
    return floors.sort((a, b) => a - b);
  };

  const getUrgencyBadge = (paymentMonth) => {
    const monthsOverdue = Math.floor(
      (new Date() - new Date(paymentMonth + '-01')) / (1000 * 60 * 60 * 24 * 30)
    );

    if (monthsOverdue >= 3) {
      return <Badge bg="danger">Critical ({monthsOverdue} months)</Badge>;
    } else if (monthsOverdue >= 2) {
      return <Badge bg="warning">High ({monthsOverdue} months)</Badge>;
    } else if (monthsOverdue >= 1) {
      return <Badge bg="info">Medium ({monthsOverdue} months)</Badge>;
    }
    return <Badge bg="secondary">Current</Badge>;
  };

  return (
    <Container fluid className="unpaid-residents-page">
      <Row className="mb-4">
        <Col>
          <div className="d-flex justify-content-between align-items-center">
            <h1 className="mb-0">
              <FaUsers className="me-2" />
              Unpaid Residents
            </h1>
            <Button 
              variant="outline-primary" 
              onClick={loadUnpaidResidents}
              disabled={loading}
            >
              {loading ? (
                <>
                  <Spinner animation="border" size="sm" className="me-1" />
                  Refreshing...
                </>
              ) : (
                'Refresh Data'
              )}
            </Button>
          </div>
        </Col>
      </Row>

      {/* Alert Messages */}
      {error && (
        <Row className="mb-3">
          <Col>
            <Alert variant="danger" dismissible onClose={() => setError('')}>
              {error}
            </Alert>
          </Col>
        </Row>
      )}

      {successMessage && (
        <Row className="mb-3">
          <Col>
            <Alert variant="success" dismissible onClose={() => setSuccessMessage('')}>
              {successMessage}
            </Alert>
          </Col>
        </Row>
      )}

      {/* Statistics Cards */}
      <Row className="mb-4">
        <Col lg={3} md={6} className="mb-3">
          <Card className="stats-card">
            <Card.Body>
              <div className="d-flex justify-content-between align-items-center">
                <div>
                  <h6 className="text-muted mb-1">Total Unpaid Residents</h6>
                  <h3 className="mb-0 text-danger">{stats.total_unpaid_residents}</h3>
                </div>
                <div className="stats-icon bg-danger">
                  <FaUsers />
                </div>
              </div>
            </Card.Body>
          </Card>
        </Col>

        <Col lg={3} md={6} className="mb-3">
          <Card className="stats-card">
            <Card.Body>
              <div className="d-flex justify-content-between align-items-center">
                <div>
                  <h6 className="text-muted mb-1">Total Unpaid Amount</h6>
                  <h3 className="mb-0 text-warning">
                    {formatCurrency(stats.total_unpaid_amount)}
                  </h3>
                </div>
                <div className="stats-icon bg-warning">
                  <FaExclamationTriangle />
                </div>
              </div>
            </Card.Body>
          </Card>
        </Col>

        <Col lg={3} md={6} className="mb-3">
          <Card className="stats-card">
            <Card.Body>
              <div className="d-flex justify-content-between align-items-center">
                <div>
                  <h6 className="text-muted mb-1">Average Unpaid</h6>
                  <h3 className="mb-0 text-info">
                    {formatCurrency(stats.average_unpaid_amount)}
                  </h3>
                </div>
                <div className="stats-icon bg-info">
                  <FaCalendarAlt />
                </div>
              </div>
            </Card.Body>
          </Card>
        </Col>

        <Col lg={3} md={6} className="mb-3">
          <Card className="stats-card">
            <Card.Body>
              <div className="d-flex justify-content-between align-items-center">
                <div>
                  <h6 className="text-muted mb-1">Oldest Unpaid</h6>
                  <h3 className="mb-0 text-dark">
                    {stats.oldest_unpaid_month ? 
                      formatDate(stats.oldest_unpaid_month) : 'N/A'
                    }
                  </h3>
                </div>
                <div className="stats-icon bg-dark">
                  <FaHome />
                </div>
              </div>
            </Card.Body>
          </Card>
        </Col>
      </Row>

      {/* Filters */}
      <Card className="mb-4">
        <Card.Header>
          <h5 className="mb-0">Filter Unpaid Residents</h5>
        </Card.Header>
        <Card.Body>
          <Row className="g-3">
            <Col md={3}>
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
            
            <Col md={3}>
              <Form.Group>
                <Form.Label>Floor</Form.Label>
                <Form.Select
                  value={filters.floor}
                  onChange={(e) => handleFilterChange('floor', e.target.value)}
                >
                  <option value="">All Floors</option>
                  {getFloorOptions().map(floor => (
                    <option key={floor} value={floor}>
                      Floor {floor}
                    </option>
                  ))}
                </Form.Select>
              </Form.Group>
            </Col>
            
            <Col md={3}>
              <Form.Group>
                <Form.Label>Minimum Due Amount</Form.Label>
                <Form.Control
                  type="number"
                  placeholder="Enter amount..."
                  value={filters.min_due_amount}
                  onChange={(e) => handleFilterChange('min_due_amount', e.target.value)}
                />
              </Form.Group>
            </Col>
          </Row>
        </Card.Body>
      </Card>

      {/* Unpaid Residents Table */}
      <Card>
        <Card.Header>
          <h5 className="mb-0">Unpaid Residents List</h5>
        </Card.Header>
        <Card.Body>
          {loading ? (
            <div className="text-center p-4">
              <Spinner animation="border" role="status">
                <span className="visually-hidden">Loading...</span>
              </Spinner>
              <p className="mt-2 mb-0">Loading unpaid residents...</p>
            </div>
          ) : (
            <div className="table-responsive">
              <Table hover className="mb-0">
                <thead>
                  <tr>
                    <th>Resident Details</th>
                    <th>Payment Month</th>
                    <th>Amount Due</th>
                    <th>Urgency</th>
                    <th>Contact Info</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  {unpaidResidents.length === 0 ? (
                    <tr>
                      <td colSpan={6} className="text-center py-4">
                        <div className="text-muted">
                          <FaUsers className="mb-2" style={{ fontSize: '2rem' }} />
                          <p>No unpaid residents found</p>
                        </div>
                      </td>
                    </tr>
                  ) : (
                    Array.isArray(unpaidResidents) ? unpaidResidents.map(payment => (
                      <tr key={payment.id} className="unpaid-resident-row">
                        <td>
                          <div>
                            <strong>{payment.resident?.owner_name || 'N/A'}</strong>
                            <div className="text-muted small">
                              <FaHome className="me-1" />
                              House: {payment.resident?.house_number}, Floor: {payment.resident?.floor}
                            </div>
                          </div>
                        </td>
                        <td>
                          <div className="payment-month">
                            {payment.payment_month}
                            <div className="text-muted small">
                              Due: {formatDate(payment.due_date)}
                            </div>
                          </div>
                        </td>
                        <td>
                          <span className="payment-amount text-danger fw-bold">
                            {formatCurrency(payment.amount_due)}
                          </span>
                        </td>
                        <td>
                          {getUrgencyBadge(payment.payment_month)}
                        </td>
                        <td>
                          <div className="contact-info">
                            {payment.resident?.phone && (
                              <div className="small">
                                <FaPhoneAlt className="me-1" />
                                {payment.resident.phone}
                              </div>
                            )}
                            {payment.resident?.email && (
                              <div className="small">
                                <FaEnvelope className="me-1" />
                                {payment.resident.email}
                              </div>
                            )}
                          </div>
                        </td>
                        <td>
                          <Button
                            variant="success"
                            size="sm"
                            onClick={() => handleAddPayment(payment)}
                          >
                            Add Payment
                          </Button>
                        </td>
                      </tr>
                    )) : []
                  )}
                </tbody>
              </Table>
            </div>
          )}
        </Card.Body>
      </Card>

      {/* Add Payment Modal */}
      <Modal show={showPaymentModal} onHide={() => setShowPaymentModal(false)} size="lg">
        <Modal.Header closeButton>
          <Modal.Title>Add Payment</Modal.Title>
        </Modal.Header>
        <Modal.Body>
          {selectedResident && (
            <PaymentForm
              payment={{
                ...selectedResident,
                amount_paid: selectedResident.amount_due
              }}
              onSuccess={handlePaymentSuccess}
              onError={handlePaymentError}
              isModal={true}
            />
          )}
        </Modal.Body>
      </Modal>
    </Container>
  );
};

export default UnpaidResidents;