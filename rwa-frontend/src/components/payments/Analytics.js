import React, { useState, useEffect } from 'react';
import { Container, Row, Col, Card, Button, Form, Alert, Spinner } from 'react-bootstrap';
import { FaChartLine, FaDownload, FaUsers, FaCreditCard, FaPercent, FaArrowUp } from 'react-icons/fa';
import { Chart as ChartJS, CategoryScale, LinearScale, BarElement, LineElement, PointElement, Title, Tooltip, Legend, ArcElement } from 'chart.js';
import { Bar, Line, Doughnut } from 'react-chartjs-2';
import { paymentAPI } from '../../services/api';
import { formatCurrency, getCurrentMonth, getMonthOptions } from '../../utils/helpers';

// Register Chart.js components
ChartJS.register(
  CategoryScale,
  LinearScale,
  BarElement,
  LineElement,
  PointElement,
  Title,
  Tooltip,
  Legend,
  ArcElement
);

const Analytics = () => {
  const [analyticsData, setAnalyticsData] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [filters, setFilters] = useState({
    start_month: getCurrentMonth(),
    end_month: getCurrentMonth(),
    year: new Date().getFullYear()
  });

  // Load analytics when filters change
  useEffect(() => {
    loadAnalytics();
  }, [filters]); // eslint-disable-line react-hooks/exhaustive-deps

  const loadAnalytics = async () => {
    try {
      setLoading(true);
      setError(null);

      // Get payment summary and detailed analytics
      const [summaryResponse, paymentsResponse] = await Promise.all([
        paymentAPI.getPaymentSummary(),
        paymentAPI.getPayments({
          per_page: 1000, // Get all payments for analytics
          payment_month_start: filters.start_month,
          payment_month_end: filters.end_month
        })
      ]);

      const summary = summaryResponse.data || {};
      const payments = paymentsResponse.data || [];

      // Process data for charts
      const processedData = processAnalyticsData(payments, summary);
      setAnalyticsData(processedData);

    } catch (err) {
      setError('Failed to load analytics: ' + err.message);
    } finally {
      setLoading(false);
    }
  };

  const processAnalyticsData = (payments, summary) => {
    // Monthly trends
    const monthlyData = {};
    payments.forEach(payment => {
      const month = payment.payment_month;
      if (!monthlyData[month]) {
        monthlyData[month] = {
          total_due: 0,
          total_paid: 0,
          paid_count: 0,
          pending_count: 0,
          partial_count: 0,
          overdue_count: 0
        };
      }
      
      monthlyData[month].total_due += parseFloat(payment.amount_due || 0);
      monthlyData[month].total_paid += parseFloat(payment.amount_paid || 0);
      
      switch (payment.status?.toLowerCase()) {
        case 'paid':
          monthlyData[month].paid_count++;
          break;
        case 'pending':
          monthlyData[month].pending_count++;
          break;
        case 'partial':
          monthlyData[month].partial_count++;
          break;
        case 'overdue':
          monthlyData[month].overdue_count++;
          break;
        default:
          break;
      }
    });

    // Sort months
    const sortedMonths = Object.keys(monthlyData).sort();
    
    // Payment status distribution
    const statusCounts = {
      paid: payments.filter(p => p.status?.toLowerCase() === 'paid').length,
      pending: payments.filter(p => p.status?.toLowerCase() === 'pending').length,
      partial: payments.filter(p => p.status?.toLowerCase() === 'partial').length,
      overdue: payments.filter(p => p.status?.toLowerCase() === 'overdue').length,
    };

    // Collection trends (monthly collection rates)
    const collectionTrends = sortedMonths.map(month => {
      const data = monthlyData[month];
      const collectionRate = data.total_due > 0 ? (data.total_paid / data.total_due) * 100 : 0;
      return {
        month,
        collection_rate: Math.round(collectionRate * 100) / 100,
        total_due: data.total_due,
        total_paid: data.total_paid
      };
    });

    // Floor-wise analysis
    const floorData = {};
    payments.forEach(payment => {
      const floor = payment.resident?.floor || 'Unknown';
      if (!floorData[floor]) {
        floorData[floor] = {
          total_due: 0,
          total_paid: 0,
          resident_count: new Set()
        };
      }
      
      floorData[floor].total_due += parseFloat(payment.amount_due || 0);
      floorData[floor].total_paid += parseFloat(payment.amount_paid || 0);
      floorData[floor].resident_count.add(payment.resident_id);
    });

    const floorAnalysis = Object.entries(floorData).map(([floor, data]) => ({
      floor: `Floor ${floor}`,
      total_due: data.total_due,
      total_paid: data.total_paid,
      collection_rate: data.total_due > 0 ? (data.total_paid / data.total_due) * 100 : 0,
      resident_count: data.resident_count.size
    })).sort((a, b) => parseInt(a.floor.replace('Floor ', '')) - parseInt(b.floor.replace('Floor ', '')));

    return {
      summary,
      monthlyTrends: {
        labels: sortedMonths,
        datasets: [
          {
            label: 'Amount Due',
            data: sortedMonths.map(month => monthlyData[month].total_due),
            backgroundColor: 'rgba(255, 193, 7, 0.5)',
            borderColor: 'rgba(255, 193, 7, 1)',
            borderWidth: 2
          },
          {
            label: 'Amount Paid',
            data: sortedMonths.map(month => monthlyData[month].total_paid),
            backgroundColor: 'rgba(40, 167, 69, 0.5)',
            borderColor: 'rgba(40, 167, 69, 1)',
            borderWidth: 2
          }
        ]
      },
      paymentStatus: {
        labels: ['Paid', 'Pending', 'Partial', 'Overdue'],
        datasets: [{
          data: [statusCounts.paid, statusCounts.pending, statusCounts.partial, statusCounts.overdue],
          backgroundColor: [
            'rgba(40, 167, 69, 0.8)',
            'rgba(255, 193, 7, 0.8)',
            'rgba(23, 162, 184, 0.8)',
            'rgba(220, 53, 69, 0.8)'
          ],
          borderColor: [
            'rgba(40, 167, 69, 1)',
            'rgba(255, 193, 7, 1)',
            'rgba(23, 162, 184, 1)',
            'rgba(220, 53, 69, 1)'
          ],
          borderWidth: 2
        }]
      },
      collectionTrends: {
        labels: collectionTrends.map(t => t.month),
        datasets: [{
          label: 'Collection Rate (%)',
          data: collectionTrends.map(t => t.collection_rate),
          borderColor: 'rgba(23, 162, 184, 1)',
          backgroundColor: 'rgba(23, 162, 184, 0.1)',
          tension: 0.4,
          fill: true
        }]
      },
      floorWise: {
        labels: floorAnalysis.map(f => f.floor),
        datasets: [
          {
            label: 'Amount Due',
            data: floorAnalysis.map(f => f.total_due),
            backgroundColor: 'rgba(255, 193, 7, 0.6)'
          },
          {
            label: 'Amount Paid',
            data: floorAnalysis.map(f => f.total_paid),
            backgroundColor: 'rgba(40, 167, 69, 0.6)'
          }
        ]
      },
      collectionTrendsData: collectionTrends,
      floorAnalysisData: floorAnalysis,
      totalResidents: [...new Set(payments.map(p => p.resident_id))].length
    };
  };

  const handleFilterChange = (field, value) => {
    setFilters(prev => ({ ...prev, [field]: value }));
  };

  const exportAnalytics = () => {
    // In a real application, you would generate and download a report
    alert('Analytics export functionality would be implemented here');
  };

  const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: {
        position: 'top',
      },
    },
    scales: {
      y: {
        beginAtZero: true,
        ticks: {
          callback: function(value) {
            return '₹' + value.toLocaleString();
          }
        }
      }
    }
  };

  const doughnutOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: {
        position: 'bottom',
      },
    },
  };

  const lineOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: {
        position: 'top',
      },
    },
    scales: {
      y: {
        beginAtZero: true,
        max: 100,
        ticks: {
          callback: function(value) {
            return value + '%';
          }
        }
      }
    }
  };

  return (
    <Container fluid className="analytics-page">
      <Row className="mb-4">
        <Col>
          <div className="d-flex justify-content-between align-items-center">
            <h1 className="mb-0">
              <FaChartLine className="me-2" />
              Payment Analytics
            </h1>
            <div>
              <Button 
                variant="outline-success"
                className="me-2"
                onClick={exportAnalytics}
              >
                <FaDownload className="me-1" />
                Export Report
              </Button>
              <Button 
                variant="outline-primary" 
                onClick={loadAnalytics}
                disabled={loading}
              >
                {loading ? (
                  <>
                    <Spinner animation="border" size="sm" className="me-1" />
                    Loading...
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

      {/* Filters */}
      <Card className="mb-4">
        <Card.Header>
          <h5 className="mb-0">Analytics Filters</h5>
        </Card.Header>
        <Card.Body>
          <Row className="g-3">
            <Col md={3}>
              <Form.Group>
                <Form.Label>Start Month</Form.Label>
                <Form.Select
                  value={filters.start_month}
                  onChange={(e) => handleFilterChange('start_month', e.target.value)}
                >
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
                <Form.Label>End Month</Form.Label>
                <Form.Select
                  value={filters.end_month}
                  onChange={(e) => handleFilterChange('end_month', e.target.value)}
                >
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
                <Form.Label>Year</Form.Label>
                <Form.Select
                  value={filters.year}
                  onChange={(e) => handleFilterChange('year', parseInt(e.target.value))}
                >
                  <option value={2024}>2024</option>
                  <option value={2025}>2025</option>
                  <option value={2026}>2026</option>
                </Form.Select>
              </Form.Group>
            </Col>
          </Row>
        </Card.Body>
      </Card>

      {loading ? (
        <div className="text-center p-4">
          <Spinner animation="border" role="status">
            <span className="visually-hidden">Loading...</span>
          </Spinner>
          <p className="mt-2 mb-0">Loading analytics...</p>
        </div>
      ) : analyticsData ? (
        <>
          {/* Summary Statistics */}
          <Row className="mb-4">
            <Col lg={3} md={6} className="mb-3">
              <Card className="stats-card">
                <Card.Body>
                  <div className="d-flex justify-content-between align-items-center">
                    <div>
                      <h6 className="text-muted mb-1">Total Residents</h6>
                      <h3 className="mb-0">{analyticsData.totalResidents}</h3>
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
                        {formatCurrency(analyticsData.summary.total_amount_due || 0)}
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
                      <h6 className="text-muted mb-1">Total Collected</h6>
                      <h3 className="mb-0 text-success">
                        {formatCurrency(analyticsData.summary.total_amount_paid || 0)}
                      </h3>
                    </div>
                    <div className="stats-icon bg-success">
                      <FaArrowUp />
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
                        {analyticsData.summary.collection_percentage || 0}%
                      </h3>
                    </div>
                    <div className="stats-icon bg-info">
                      <FaPercent />
                    </div>
                  </div>
                </Card.Body>
              </Card>
            </Col>
          </Row>

          {/* Charts Row 1 */}
          <Row className="mb-4">
            <Col lg={8} className="mb-3">
              <Card>
                <Card.Header>
                  <h5 className="mb-0">Monthly Payment Trends</h5>
                </Card.Header>
                <Card.Body>
                  <div style={{ height: '400px' }}>
                    <Bar data={analyticsData.monthlyTrends} options={chartOptions} />
                  </div>
                </Card.Body>
              </Card>
            </Col>

            <Col lg={4} className="mb-3">
              <Card>
                <Card.Header>
                  <h5 className="mb-0">Payment Status Distribution</h5>
                </Card.Header>
                <Card.Body>
                  <div style={{ height: '400px' }}>
                    <Doughnut data={analyticsData.paymentStatus} options={doughnutOptions} />
                  </div>
                </Card.Body>
              </Card>
            </Col>
          </Row>

          {/* Charts Row 2 */}
          <Row className="mb-4">
            <Col lg={6} className="mb-3">
              <Card>
                <Card.Header>
                  <h5 className="mb-0">Collection Rate Trends</h5>
                </Card.Header>
                <Card.Body>
                  <div style={{ height: '350px' }}>
                    <Line data={analyticsData.collectionTrends} options={lineOptions} />
                  </div>
                </Card.Body>
              </Card>
            </Col>

            <Col lg={6} className="mb-3">
              <Card>
                <Card.Header>
                  <h5 className="mb-0">Floor-wise Analysis</h5>
                </Card.Header>
                <Card.Body>
                  <div style={{ height: '350px' }}>
                    <Bar data={analyticsData.floorWise} options={chartOptions} />
                  </div>
                </Card.Body>
              </Card>
            </Col>
          </Row>

          {/* Data Tables */}
          <Row>
            <Col lg={6} className="mb-3">
              <Card>
                <Card.Header>
                  <h5 className="mb-0">Collection Trends Data</h5>
                </Card.Header>
                <Card.Body>
                  <div className="table-responsive">
                    <table className="table table-sm table-hover">
                      <thead>
                        <tr>
                          <th>Month</th>
                          <th>Collection Rate</th>
                          <th>Amount Due</th>
                          <th>Amount Paid</th>
                        </tr>
                      </thead>
                      <tbody>
                        {analyticsData.collectionTrendsData?.map((trend, index) => (
                          <tr key={index}>
                            <td>{trend.month}</td>
                            <td>
                              <span className={`badge ${trend.collection_rate >= 80 ? 'bg-success' : trend.collection_rate >= 60 ? 'bg-warning' : 'bg-danger'}`}>
                                {trend.collection_rate}%
                              </span>
                            </td>
                            <td>{formatCurrency(trend.total_due)}</td>
                            <td>{formatCurrency(trend.total_paid)}</td>
                          </tr>
                        ))}
                      </tbody>
                    </table>
                  </div>
                </Card.Body>
              </Card>
            </Col>

            <Col lg={6} className="mb-3">
              <Card>
                <Card.Header>
                  <h5 className="mb-0">Floor-wise Performance</h5>
                </Card.Header>
                <Card.Body>
                  <div className="table-responsive">
                    <table className="table table-sm table-hover">
                      <thead>
                        <tr>
                          <th>Floor</th>
                          <th>Residents</th>
                          <th>Collection Rate</th>
                          <th>Total Due</th>
                          <th>Total Paid</th>
                        </tr>
                      </thead>
                      <tbody>
                        {analyticsData.floorAnalysisData?.map((floor, index) => (
                          <tr key={index}>
                            <td>{floor.floor}</td>
                            <td>{floor.resident_count}</td>
                            <td>
                              <span className={`badge ${floor.collection_rate >= 80 ? 'bg-success' : floor.collection_rate >= 60 ? 'bg-warning' : 'bg-danger'}`}>
                                {Math.round(floor.collection_rate)}%
                              </span>
                            </td>
                            <td>{formatCurrency(floor.total_due)}</td>
                            <td>{formatCurrency(floor.total_paid)}</td>
                          </tr>
                        ))}
                      </tbody>
                    </table>
                  </div>
                </Card.Body>
              </Card>
            </Col>
          </Row>
        </>
      ) : null}
    </Container>
  );
};

export default Analytics;