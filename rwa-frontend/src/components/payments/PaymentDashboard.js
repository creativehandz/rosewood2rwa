import React, { useState, useEffect } from 'react';
import { Container, Row, Col, Card, Alert, Spinner } from 'react-bootstrap';
import { FaCreditCard, FaUsers, FaChartLine, FaSync } from 'react-icons/fa';
import { paymentAPI, residentAPI } from '../../services/api';
import { formatCurrency } from '../../utils/helpers';
import PaymentList from './PaymentList';
import PaymentForm from './PaymentForm';
import PaymentStats from './PaymentStats';
import GoogleSheetsSync from './GoogleSheetsSync';
import './PaymentDashboard.css';

const PaymentDashboard = () => {
  const [activeTab, setActiveTab] = useState('payments');
  const [stats, setStats] = useState(null);
  const [paymentSummary, setPaymentSummary] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [successMessage, setSuccessMessage] = useState('');
  const [refreshTrigger, setRefreshTrigger] = useState(0);

  // Load dashboard data
  useEffect(() => {
    loadDashboardData();
  }, [refreshTrigger]);

  const loadDashboardData = async () => {
    try {
      setLoading(true);
      setError(null);

      // Load payment summary (this we know works)
      const summaryResponse = await paymentAPI.getPaymentSummary();
      setPaymentSummary(summaryResponse.data);

      // Try to load dashboard stats, but handle errors gracefully
      try {
        const statsResponse = await residentAPI.getDashboardStats();
        setStats(statsResponse.data);
      } catch (statsError) {
        console.warn('Dashboard stats failed, using default values:', statsError.message);
        // Set default stats if the endpoint fails
        setStats({
          total_residents: 0,
          active_residents: 0,
          total_payers: 0,
          total_non_payers: 0,
          current_month_collection: 0,
          pending_payments: 0,
          overdue_payments: 0,
          collection_percentage: 0
        });
      }
    } catch (err) {
      setError('Failed to load payment summary: ' + err.message);
    } finally {
      setLoading(false);
    }
  };

  const handleRefresh = () => {
    setRefreshTrigger(prev => prev + 1);
  };

  const showSuccess = (message) => {
    setSuccessMessage(message);
    setTimeout(() => setSuccessMessage(''), 5000);
  };

  const showError = (message) => {
    setError(message);
    setTimeout(() => setError(''), 5000);
  };

  if (loading) {
    return (
      <Container className="mt-4 text-center">
        <Spinner animation="border" role="status">
          <span className="visually-hidden">Loading...</span>
        </Spinner>
        <p className="mt-2">Loading payment dashboard...</p>
      </Container>
    );
  }

  return (
    <Container fluid className="payment-dashboard">
      <Row className="mb-4">
        <Col>
          <div className="d-flex justify-content-between align-items-center">
            <h1 className="mb-0">
              <FaCreditCard className="me-2" />
              Payment Management
            </h1>
            <button 
              className="btn btn-outline-primary"
              onClick={handleRefresh}
              disabled={loading}
            >
              <FaSync className="me-1" />
              Refresh
            </button>
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

      {/* Dashboard Stats Cards */}
      <Row className="mb-4">
        <Col lg={3} md={6} className="mb-3">
          <Card className="stats-card">
            <Card.Body>
              <div className="d-flex justify-content-between align-items-center">
                <div>
                  <h6 className="text-muted mb-1">Total Residents</h6>
                  <h3 className="mb-0">{stats?.total_residents || 0}</h3>
                </div>
                <div className="stats-icon bg-primary">
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
                  <h6 className="text-muted mb-1">Total Due</h6>
                  <h3 className="mb-0 text-warning">
                    {formatCurrency(paymentSummary?.total_amount_due || 0)}
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
                  <h6 className="text-muted mb-1">Total Paid</h6>
                  <h3 className="mb-0 text-success">
                    {formatCurrency(paymentSummary?.total_amount_paid || 0)}
                  </h3>
                </div>
                <div className="stats-icon bg-success">
                  <FaChartLine />
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
                  <h6 className="text-muted mb-1">Collection Rate</h6>
                  <h3 className="mb-0 text-info">
                    {paymentSummary?.collection_percentage || 0}%
                  </h3>
                </div>
                <div className="stats-icon bg-info">
                  <FaChartLine />
                </div>
              </div>
            </Card.Body>
          </Card>
        </Col>
      </Row>

      {/* Navigation Tabs */}
      <Row className="mb-4">
        <Col>
          <Card>
            <Card.Header>
              <ul className="nav nav-tabs card-header-tabs">
                <li className="nav-item">
                  <button
                    className={`nav-link ${activeTab === 'payments' ? 'active' : ''}`}
                    onClick={() => setActiveTab('payments')}
                  >
                    <FaCreditCard className="me-1" />
                    Payments
                  </button>
                </li>
                <li className="nav-item">
                  <button
                    className={`nav-link ${activeTab === 'add-payment' ? 'active' : ''}`}
                    onClick={() => setActiveTab('add-payment')}
                  >
                    Add Payment
                  </button>
                </li>
                <li className="nav-item">
                  <button
                    className={`nav-link ${activeTab === 'stats' ? 'active' : ''}`}
                    onClick={() => setActiveTab('stats')}
                  >
                    <FaChartLine className="me-1" />
                    Statistics
                  </button>
                </li>
                <li className="nav-item">
                  <button
                    className={`nav-link ${activeTab === 'sync' ? 'active' : ''}`}
                    onClick={() => setActiveTab('sync')}
                  >
                    <FaSync className="me-1" />
                    Google Sheets
                  </button>
                </li>
              </ul>
            </Card.Header>

            <Card.Body>
              {/* Tab Content */}
              {activeTab === 'payments' && (
                <PaymentList 
                  onRefresh={handleRefresh}
                  onSuccess={showSuccess}
                  onError={showError}
                />
              )}

              {activeTab === 'add-payment' && (
                <PaymentForm 
                  onSuccess={(message) => {
                    showSuccess(message);
                    handleRefresh();
                    setActiveTab('payments');
                  }}
                  onError={showError}
                />
              )}

              {activeTab === 'stats' && (
                <PaymentStats 
                  summary={paymentSummary}
                  onRefresh={handleRefresh}
                />
              )}

              {activeTab === 'sync' && (
                <GoogleSheetsSync 
                  onSuccess={showSuccess}
                  onError={showError}
                  onRefresh={handleRefresh}
                />
              )}
            </Card.Body>
          </Card>
        </Col>
      </Row>
    </Container>
  );
};

export default PaymentDashboard;