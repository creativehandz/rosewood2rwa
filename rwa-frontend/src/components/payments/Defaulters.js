import React, { useState, useEffect } from 'react';
import { Container, Row, Col, Card, Table, Badge, Button, Form, Alert, Spinner, Modal, ProgressBar } from 'react-bootstrap';
import { FaExclamationTriangle, FaSearch, FaPhoneAlt, FaEnvelope, FaHome, FaCalendarAlt, FaFileExcel, FaCreditCard } from 'react-icons/fa';
import { paymentAPI } from '../../services/api';
import { formatCurrency, formatDate, debounce } from '../../utils/helpers';
import PaymentForm from './PaymentForm';

const Defaulters = () => {
  const [defaulters, setDefaulters] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [successMessage, setSuccessMessage] = useState('');
  const [filters, setFilters] = useState({
    search: '',
    risk_level: '',
    floor: '',
    min_months_overdue: ''
  });
  const [selectedResident, setSelectedResident] = useState(null);
  const [showPaymentModal, setShowPaymentModal] = useState(false);
  const [showDetailsModal, setShowDetailsModal] = useState(false);
  const [stats, setStats] = useState({
    total_defaulters: 0,
    total_overdue_amount: 0,
    high_risk_count: 0,
    critical_risk_count: 0,
    average_months_overdue: 0
  });

  // Debounced search function
  const debouncedSearch = debounce((searchTerm) => {
    setFilters(prev => ({ ...prev, search: searchTerm }));
  }, 500);

  // Load defaulters when filters change
  useEffect(() => {
    loadDefaulters();
  }, [filters]); // eslint-disable-line react-hooks/exhaustive-deps

  const loadDefaulters = async () => {
    try {
      setLoading(true);
      setError(null);

      // Get overdue payments (payments with pending status beyond due date)
      const response = await paymentAPI.getPayments({
        status: 'Pending,Overdue',
        ...filters
      });

      // Handle paginated response structure
      const payments = response.data?.data || response.data || [];
      
      // Group payments by resident and calculate defaulter metrics
      const residentMap = {};
      
      payments.forEach(payment => {
        const residentId = payment.resident_id;
        if (!residentMap[residentId]) {
          residentMap[residentId] = {
            resident: payment.resident,
            payments: [],
            total_overdue_amount: 0,
            months_overdue: 0,
            oldest_overdue_month: null,
            latest_overdue_month: null,
            risk_level: 'Low'
          };
        }
        
        residentMap[residentId].payments.push(payment);
        residentMap[residentId].total_overdue_amount += parseFloat(payment.amount_due || 0);
        
        const paymentDate = new Date(payment.payment_month + '-01');
        const currentDate = new Date();
        const monthsOverdue = Math.floor(
          (currentDate - paymentDate) / (1000 * 60 * 60 * 24 * 30)
        );
        
        if (monthsOverdue > residentMap[residentId].months_overdue) {
          residentMap[residentId].months_overdue = monthsOverdue;
          residentMap[residentId].oldest_overdue_month = payment.payment_month;
        }
        
        if (!residentMap[residentId].latest_overdue_month || 
            payment.payment_month > residentMap[residentId].latest_overdue_month) {
          residentMap[residentId].latest_overdue_month = payment.payment_month;
        }
      });

      // Calculate risk levels and filter based on criteria
      const defaultersList = Object.values(residentMap).map(defaulter => {
        // Calculate risk level based on months overdue and amount
        if (defaulter.months_overdue >= 6 || defaulter.total_overdue_amount >= 50000) {
          defaulter.risk_level = 'Critical';
        } else if (defaulter.months_overdue >= 3 || defaulter.total_overdue_amount >= 20000) {
          defaulter.risk_level = 'High';
        } else if (defaulter.months_overdue >= 2 || defaulter.total_overdue_amount >= 10000) {
          defaulter.risk_level = 'Medium';
        } else {
          defaulter.risk_level = 'Low';
        }

        return defaulter;
      }).filter(defaulter => {
        // Apply filters
        if (filters.risk_level && defaulter.risk_level !== filters.risk_level) {
          return false;
        }
        if (filters.floor && defaulter.resident?.floor !== parseInt(filters.floor)) {
          return false;
        }
        if (filters.min_months_overdue && defaulter.months_overdue < parseInt(filters.min_months_overdue)) {
          return false;
        }
        if (filters.search) {
          const searchTerm = filters.search.toLowerCase();
          return (
            defaulter.resident?.name?.toLowerCase().includes(searchTerm) ||
            defaulter.resident?.house_number?.toString().includes(searchTerm) ||
            defaulter.resident?.phone?.includes(searchTerm)
          );
        }
        return true;
      }).sort((a, b) => {
        // Sort by risk level (Critical > High > Medium > Low) then by months overdue
        const riskOrder = { 'Critical': 4, 'High': 3, 'Medium': 2, 'Low': 1 };
        const riskDiff = riskOrder[b.risk_level] - riskOrder[a.risk_level];
        return riskDiff !== 0 ? riskDiff : b.months_overdue - a.months_overdue;
      });

      setDefaulters(defaultersList);

      // Calculate statistics
      const totalOverdueAmount = defaultersList.reduce((sum, defaulter) => 
        sum + defaulter.total_overdue_amount, 0
      );

      const highRiskCount = defaultersList.filter(d => d.risk_level === 'High').length;
      const criticalRiskCount = defaultersList.filter(d => d.risk_level === 'Critical').length;
      const avgMonthsOverdue = defaultersList.length > 0 ? 
        defaultersList.reduce((sum, d) => sum + d.months_overdue, 0) / defaultersList.length : 0;

      setStats({
        total_defaulters: defaultersList.length,
        total_overdue_amount: totalOverdueAmount,
        high_risk_count: highRiskCount,
        critical_risk_count: criticalRiskCount,
        average_months_overdue: Math.round(avgMonthsOverdue * 10) / 10
      });

    } catch (err) {
      setError('Failed to load defaulters: ' + err.message);
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

  const handleAddPayment = (defaulter) => {
    // Use the most recent unpaid payment for the form
    const latestPayment = defaulter.payments.sort((a, b) => 
      new Date(b.payment_month) - new Date(a.payment_month)
    )[0];
    
    setSelectedResident(latestPayment);
    setShowPaymentModal(true);
  };

  const handleViewDetails = (defaulter) => {
    setSelectedResident(defaulter);
    setShowDetailsModal(true);
  };

  const handlePaymentSuccess = (message) => {
    setSuccessMessage(message);
    setShowPaymentModal(false);
    setSelectedResident(null);
    loadDefaulters();
    
    setTimeout(() => setSuccessMessage(''), 5000);
  };

  const handlePaymentError = (message) => {
    setError(message);
    setTimeout(() => setError(''), 5000);
  };

  const getRiskBadge = (riskLevel) => {
    const badges = {
      'Critical': 'danger',
      'High': 'warning',
      'Medium': 'info',
      'Low': 'success'
    };
    return <Badge bg={badges[riskLevel] || 'secondary'}>{riskLevel} Risk</Badge>;
  };

  const getRiskProgressColor = (riskLevel) => {
    const colors = {
      'Critical': 'danger',
      'High': 'warning', 
      'Medium': 'info',
      'Low': 'success'
    };
    return colors[riskLevel] || 'secondary';
  };

  const getFloorOptions = () => {
    if (!Array.isArray(defaulters)) return [];
    const floors = [...new Set(defaulters.map(d => d.resident?.floor).filter(Boolean))];
    return floors.sort((a, b) => a - b);
  };

  const exportToExcel = () => {
    // In a real application, you would generate and download an Excel file
    alert('Excel export functionality would be implemented here');
  };

  return (
    <Container fluid className="defaulters-page">
      <Row className="mb-4">
        <Col>
          <div className="d-flex justify-content-between align-items-center">
            <h1 className="mb-0">
              <FaExclamationTriangle className="me-2 text-danger" />
              Defaulter Management
            </h1>
            <div>
              <Button 
                variant="outline-success"
                className="me-2"
                onClick={exportToExcel}
              >
                <FaFileExcel className="me-1" />
                Export to Excel
              </Button>
              <Button 
                variant="outline-primary" 
                onClick={loadDefaulters}
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
                  <h6 className="text-muted mb-1">Total Defaulters</h6>
                  <h3 className="mb-0 text-danger">{stats.total_defaulters}</h3>
                </div>
                <div className="stats-icon bg-danger">
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
                  <h6 className="text-muted mb-1">Total Overdue</h6>
                  <h3 className="mb-0 text-warning">
                    {formatCurrency(stats.total_overdue_amount)}
                  </h3>
                </div>
                <div className="stats-icon bg-warning">
                  <FaCreditCard />
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
                  <h6 className="text-muted mb-1">High Risk</h6>
                  <h3 className="mb-0" style={{ color: '#fd7e14' }}>{stats.high_risk_count}</h3>
                </div>
                <div className="stats-icon" style={{ backgroundColor: '#fd7e14' }}>
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
                  <h6 className="text-muted mb-1">Critical Risk</h6>
                  <h3 className="mb-0 text-danger">{stats.critical_risk_count}</h3>
                </div>
                <div className="stats-icon bg-danger">
                  <FaExclamationTriangle />
                </div>
              </div>
            </Card.Body>
          </Card>
        </Col>
      </Row>

      {/* Filters */}
      <Card className="mb-4">
        <Card.Header>
          <h5 className="mb-0">Filter Defaulters</h5>
        </Card.Header>
        <Card.Body>
          <Row className="g-3">
            <Col md={3}>
              <Form.Group>
                <Form.Label>Search Defaulters</Form.Label>
                <div className="position-relative">
                  <Form.Control
                    type="text"
                    placeholder="Search by name, house number, phone..."
                    onChange={handleSearch}
                  />
                  <FaSearch className="position-absolute top-50 end-0 translate-middle-y me-3 text-muted" />
                </div>
              </Form.Group>
            </Col>
            
            <Col md={3}>
              <Form.Group>
                <Form.Label>Risk Level</Form.Label>
                <Form.Select
                  value={filters.risk_level}
                  onChange={(e) => handleFilterChange('risk_level', e.target.value)}
                >
                  <option value="">All Risk Levels</option>
                  <option value="Critical">Critical Risk</option>
                  <option value="High">High Risk</option>
                  <option value="Medium">Medium Risk</option>
                  <option value="Low">Low Risk</option>
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
                <Form.Label>Min Months Overdue</Form.Label>
                <Form.Select
                  value={filters.min_months_overdue}
                  onChange={(e) => handleFilterChange('min_months_overdue', e.target.value)}
                >
                  <option value="">Any Duration</option>
                  <option value="1">1+ Months</option>
                  <option value="2">2+ Months</option>
                  <option value="3">3+ Months</option>
                  <option value="6">6+ Months</option>
                </Form.Select>
              </Form.Group>
            </Col>
          </Row>
        </Card.Body>
      </Card>

      {/* Defaulters Table */}
      <Card>
        <Card.Header>
          <h5 className="mb-0">Defaulters List</h5>
        </Card.Header>
        <Card.Body>
          {loading ? (
            <div className="text-center p-4">
              <Spinner animation="border" role="status">
                <span className="visually-hidden">Loading...</span>
              </Spinner>
              <p className="mt-2 mb-0">Loading defaulters...</p>
            </div>
          ) : (
            <div className="table-responsive">
              <Table hover className="mb-0">
                <thead>
                  <tr>
                    <th>Resident Details</th>
                    <th>Risk Assessment</th>
                    <th>Overdue Details</th>
                    <th>Total Overdue</th>
                    <th>Contact Info</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  {defaulters.length === 0 ? (
                    <tr>
                      <td colSpan={6} className="text-center py-4">
                        <div className="text-muted">
                          <FaExclamationTriangle className="mb-2" style={{ fontSize: '2rem' }} />
                          <p>No defaulters found</p>
                        </div>
                      </td>
                    </tr>
                  ) : (
                    Array.isArray(defaulters) ? defaulters.map((defaulter, index) => (
                      <tr key={index} className="defaulter-row">
                        <td>
                          <div>
                            <strong>{defaulter.resident?.owner_name || 'N/A'}</strong>
                            <div className="text-muted small">
                              <FaHome className="me-1" />
                              House: {defaulter.resident?.house_number}, Floor: {defaulter.resident?.floor}
                            </div>
                          </div>
                        </td>
                        <td>
                          <div>
                            {getRiskBadge(defaulter.risk_level)}
                            <ProgressBar 
                              now={Math.min(defaulter.months_overdue * 10, 100)} 
                              variant={getRiskProgressColor(defaulter.risk_level)}
                              size="sm"
                              className="mt-1"
                            />
                            <div className="text-muted small mt-1">
                              Risk Score: {Math.min(defaulter.months_overdue * 10, 100)}%
                            </div>
                          </div>
                        </td>
                        <td>
                          <div>
                            <div className="fw-bold text-danger">
                              {defaulter.months_overdue} months overdue
                            </div>
                            <div className="text-muted small">
                              <FaCalendarAlt className="me-1" />
                              Since: {defaulter.oldest_overdue_month}
                            </div>
                            <div className="text-muted small">
                              {defaulter.payments.length} unpaid bills
                            </div>
                          </div>
                        </td>
                        <td>
                          <span className="payment-amount text-danger fw-bold fs-5">
                            {formatCurrency(defaulter.total_overdue_amount)}
                          </span>
                        </td>
                        <td>
                          <div className="contact-info">
                            {defaulter.resident?.phone && (
                              <div className="small">
                                <FaPhoneAlt className="me-1" />
                                <a href={`tel:${defaulter.resident.phone}`} className="text-decoration-none">
                                  {defaulter.resident.phone}
                                </a>
                              </div>
                            )}
                            {defaulter.resident?.email && (
                              <div className="small">
                                <FaEnvelope className="me-1" />
                                <a href={`mailto:${defaulter.resident.email}`} className="text-decoration-none">
                                  {defaulter.resident.email}
                                </a>
                              </div>
                            )}
                          </div>
                        </td>
                        <td>
                          <div className="action-buttons">
                            <Button
                              variant="outline-info"
                              size="sm"
                              className="me-1 mb-1"
                              onClick={() => handleViewDetails(defaulter)}
                            >
                              View Details
                            </Button>
                            <Button
                              variant="success"
                              size="sm"
                              onClick={() => handleAddPayment(defaulter)}
                            >
                              Add Payment
                            </Button>
                          </div>
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
          <Modal.Title>Add Payment for Defaulter</Modal.Title>
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

      {/* Details Modal */}
      <Modal show={showDetailsModal} onHide={() => setShowDetailsModal(false)} size="lg">
        <Modal.Header closeButton>
          <Modal.Title>Defaulter Details</Modal.Title>
        </Modal.Header>
        <Modal.Body>
          {selectedResident && (
            <div>
              <Row className="mb-3">
                <Col md={6}>
                  <Card>
                    <Card.Header>
                      <strong>Resident Information</strong>
                    </Card.Header>
                    <Card.Body>
                      <p><strong>Name:</strong> {selectedResident.resident?.name}</p>
                      <p><strong>House Number:</strong> {selectedResident.resident?.house_number}</p>
                      <p><strong>Floor:</strong> {selectedResident.resident?.floor}</p>
                      <p><strong>Phone:</strong> {selectedResident.resident?.phone || 'N/A'}</p>
                      <p><strong>Email:</strong> {selectedResident.resident?.email || 'N/A'}</p>
                    </Card.Body>
                  </Card>
                </Col>
                <Col md={6}>
                  <Card>
                    <Card.Header>
                      <strong>Risk Assessment</strong>
                    </Card.Header>
                    <Card.Body>
                      <p><strong>Risk Level:</strong> {getRiskBadge(selectedResident.risk_level)}</p>
                      <p><strong>Months Overdue:</strong> {selectedResident.months_overdue}</p>
                      <p><strong>Total Overdue:</strong> {formatCurrency(selectedResident.total_overdue_amount)}</p>
                      <p><strong>Unpaid Bills:</strong> {selectedResident.payments?.length || 0}</p>
                      <p><strong>Oldest Overdue:</strong> {selectedResident.oldest_overdue_month}</p>
                    </Card.Body>
                  </Card>
                </Col>
              </Row>
              
              <Card>
                <Card.Header>
                  <strong>Unpaid Payments History</strong>
                </Card.Header>
                <Card.Body>
                  <Table size="sm" hover>
                    <thead>
                      <tr>
                        <th>Month</th>
                        <th>Amount Due</th>
                        <th>Status</th>
                        <th>Due Date</th>
                      </tr>
                    </thead>
                    <tbody>
                      {selectedResident.payments?.map((payment, index) => (
                        <tr key={index}>
                          <td>{payment.payment_month}</td>
                          <td>{formatCurrency(payment.amount_due)}</td>
                          <td>
                            <Badge bg="danger">{payment.status}</Badge>
                          </td>
                          <td>{formatDate(payment.due_date)}</td>
                        </tr>
                      ))}
                    </tbody>
                  </Table>
                </Card.Body>
              </Card>
            </div>
          )}
        </Modal.Body>
        <Modal.Footer>
          <Button variant="secondary" onClick={() => setShowDetailsModal(false)}>
            Close
          </Button>
          <Button variant="success" onClick={() => {
            setShowDetailsModal(false);
            handleAddPayment(selectedResident);
          }}>
            Add Payment
          </Button>
        </Modal.Footer>
      </Modal>
    </Container>
  );
};

export default Defaulters;