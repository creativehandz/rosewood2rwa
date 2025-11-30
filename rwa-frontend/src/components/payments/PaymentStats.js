import React, { useState, useEffect } from 'react';
import { Row, Col, Card, Table, Spinner } from 'react-bootstrap';
import { FaChartBar, FaCalendarAlt, FaArrowUp, FaArrowDown } from 'react-icons/fa';
import { paymentAPI } from '../../services/api';
import { formatCurrency, getStatusBadgeClass, getMonthOptions } from '../../utils/helpers';

const PaymentStats = ({ summary, onRefresh }) => {
  const [monthlyStats, setMonthlyStats] = useState([]);
  const [statusStats, setStatusStats] = useState([]);
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    const loadDetailedStats = async () => {
      try {
        setLoading(true);
        
        // Get monthly breakdown for the last 6 months
        const monthOptions = getMonthOptions(6, 0);
        const monthlyPromises = monthOptions.slice(-6).map(async (month) => {
          try {
            const response = await paymentAPI.getPaymentsByMonth(month.value);
            const payments = response.data || [];
            
            const totalDue = payments.reduce((sum, p) => sum + (parseFloat(p.amount_due) || 0), 0);
            const totalPaid = payments.reduce((sum, p) => sum + (parseFloat(p.amount_paid) || 0), 0);
            const totalPayments = payments.length;
            const paidPayments = payments.filter(p => p.status === 'Paid').length;
            
            return {
              month: month.label,
              monthValue: month.value,
              totalDue,
              totalPaid,
              totalPayments,
              paidPayments,
              collectionRate: totalDue > 0 ? ((totalPaid / totalDue) * 100).toFixed(1) : 0
            };
          } catch (error) {
            return {
              month: month.label,
              monthValue: month.value,
              totalDue: 0,
              totalPaid: 0,
              totalPayments: 0,
              paidPayments: 0,
              collectionRate: 0
            };
          }
        });

        const monthlyResults = await Promise.all(monthlyPromises);
        setMonthlyStats(monthlyResults);

        // Get status breakdown
        const statusPromises = ['Pending', 'Paid', 'Partial', 'Overdue'].map(async (status) => {
          try {
            const response = await paymentAPI.getPaymentsByStatus(status);
            const payments = response.data || [];
            
            const totalAmount = payments.reduce((sum, p) => sum + (parseFloat(p.amount_due) || 0), 0);
            const paidAmount = payments.reduce((sum, p) => sum + (parseFloat(p.amount_paid) || 0), 0);
            
            return {
              status,
              count: payments.length,
              totalAmount,
              paidAmount,
              percentage: summary?.total_payments > 0 ? ((payments.length / summary.total_payments) * 100).toFixed(1) : 0
            };
          } catch (error) {
            return {
              status,
              count: 0,
              totalAmount: 0,
              paidAmount: 0,
              percentage: 0
            };
          }
        });

        const statusResults = await Promise.all(statusPromises);
        setStatusStats(statusResults);

      } catch (err) {
        console.error('Error loading detailed stats:', err);
      } finally {
        setLoading(false);
      }
    };

    loadDetailedStats();
  }, [summary]);



  const calculateTrend = (data, field) => {
    if (data.length < 2) return { trend: 'neutral', percentage: 0 };
    
    const current = data[data.length - 1][field];
    const previous = data[data.length - 2][field];
    
    if (previous === 0) return { trend: 'neutral', percentage: 0 };
    
    const change = ((current - previous) / previous) * 100;
    return {
      trend: change > 0 ? 'up' : change < 0 ? 'down' : 'neutral',
      percentage: Math.abs(change).toFixed(1)
    };
  };

  const collectionTrend = calculateTrend(monthlyStats, 'totalPaid');
  const paymentsTrend = calculateTrend(monthlyStats, 'totalPayments');

  if (loading) {
    return (
      <div className="text-center py-4">
        <Spinner animation="border" role="status">
          <span className="visually-hidden">Loading statistics...</span>
        </Spinner>
        <p className="mt-2 mb-0">Loading detailed statistics...</p>
      </div>
    );
  }

  return (
    <div>
      {/* Summary Cards */}
      <Row className="mb-4">
        <Col lg={3} md={6} className="mb-3">
          <Card className="stats-card h-100">
            <Card.Body>
              <div className="d-flex justify-content-between align-items-center">
                <div>
                  <h6 className="text-muted mb-1">Total Payments</h6>
                  <h3 className="mb-0">{summary?.total_payments || 0}</h3>
                  <small className="text-muted">
                    {paymentsTrend.trend === 'up' && <FaArrowUp className="text-success me-1" />}
                    {paymentsTrend.trend === 'down' && <FaArrowDown className="text-danger me-1" />}
                    {paymentsTrend.percentage}% from last month
                  </small>
                </div>
                <div className="stats-icon bg-primary">
                  <FaChartBar />
                </div>
              </div>
            </Card.Body>
          </Card>
        </Col>

        <Col lg={3} md={6} className="mb-3">
          <Card className="stats-card h-100">
            <Card.Body>
              <div className="d-flex justify-content-between align-items-center">
                <div>
                  <h6 className="text-muted mb-1">Collection Rate</h6>
                  <h3 className="mb-0">{summary?.collection_percentage || 0}%</h3>
                  <small className="text-muted">
                    {collectionTrend.trend === 'up' && <FaArrowUp className="text-success me-1" />}
                    {collectionTrend.trend === 'down' && <FaArrowDown className="text-danger me-1" />}
                    {collectionTrend.percentage}% from last month
                  </small>
                </div>
                <div className="stats-icon bg-success">
                  <FaArrowUp />
                </div>
              </div>
            </Card.Body>
          </Card>
        </Col>

        <Col lg={3} md={6} className="mb-3">
          <Card className="stats-card h-100">
            <Card.Body>
              <div className="d-flex justify-content-between align-items-center">
                <div>
                  <h6 className="text-muted mb-1">Outstanding</h6>
                  <h3 className="mb-0 text-warning">
                    {formatCurrency((summary?.total_amount_due || 0) - (summary?.total_amount_paid || 0))}
                  </h3>
                  <small className="text-muted">Amount pending</small>
                </div>
                <div className="stats-icon bg-warning">
                  <FaCalendarAlt />
                </div>
              </div>
            </Card.Body>
          </Card>
        </Col>

        <Col lg={3} md={6} className="mb-3">
          <Card className="stats-card h-100">
            <Card.Body>
              <div className="d-flex justify-content-between align-items-center">
                <div>
                  <h6 className="text-muted mb-1">This Month</h6>
                  <h3 className="mb-0">
                    {monthlyStats.length > 0 ? monthlyStats[monthlyStats.length - 1].totalPayments : 0}
                  </h3>
                  <small className="text-muted">Payments recorded</small>
                </div>
                <div className="stats-icon bg-info">
                  <FaChartBar />
                </div>
              </div>
            </Card.Body>
          </Card>
        </Col>
      </Row>

      <Row>
        {/* Monthly Statistics */}
        <Col lg={8} className="mb-4">
          <Card>
            <Card.Header>
              <h5 className="mb-0">
                <FaCalendarAlt className="me-2" />
                Monthly Collection Trend
              </h5>
            </Card.Header>
            <Card.Body>
              <div className="table-responsive">
                <Table hover>
                  <thead>
                    <tr>
                      <th>Month</th>
                      <th>Payments</th>
                      <th>Amount Due</th>
                      <th>Amount Paid</th>
                      <th>Collection Rate</th>
                    </tr>
                  </thead>
                  <tbody>
                    {monthlyStats.map((stat, index) => (
                      <tr key={stat.monthValue}>
                        <td>{stat.month}</td>
                        <td>
                          <span className="fw-bold">{stat.totalPayments}</span>
                          <small className="text-muted ms-1">
                            ({stat.paidPayments} paid)
                          </small>
                        </td>
                        <td className="payment-amount">
                          {formatCurrency(stat.totalDue)}
                        </td>
                        <td className="payment-amount positive">
                          {formatCurrency(stat.totalPaid)}
                        </td>
                        <td>
                          <div className="d-flex align-items-center">
                            <div className="progress me-2" style={{ width: '60px', height: '8px' }}>
                              <div 
                                className="progress-bar bg-success" 
                                style={{ width: `${Math.min(stat.collectionRate, 100)}%` }}
                              ></div>
                            </div>
                            <span className="fw-bold">{stat.collectionRate}%</span>
                          </div>
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </Table>
              </div>
            </Card.Body>
          </Card>
        </Col>

        {/* Status Breakdown */}
        <Col lg={4} className="mb-4">
          <Card>
            <Card.Header>
              <h5 className="mb-0">
                <FaChartBar className="me-2" />
                Payment Status Breakdown
              </h5>
            </Card.Header>
            <Card.Body>
              {statusStats.map(stat => (
                <div key={stat.status} className="d-flex justify-content-between align-items-center mb-3">
                  <div>
                    <span className={getStatusBadgeClass(stat.status)}>
                      {stat.status}
                    </span>
                    <div className="small text-muted mt-1">
                      {formatCurrency(stat.totalAmount)} total
                    </div>
                  </div>
                  <div className="text-end">
                    <div className="fw-bold">{stat.count}</div>
                    <div className="small text-muted">{stat.percentage}%</div>
                  </div>
                </div>
              ))}
            </Card.Body>
          </Card>
        </Col>
      </Row>

      {/* Additional Insights */}
      <Row>
        <Col>
          <Card>
            <Card.Header>
              <h5 className="mb-0">Key Insights</h5>
            </Card.Header>
            <Card.Body>
              <Row>
                <Col md={6}>
                  <h6>Collection Performance</h6>
                  <ul className="list-unstyled">
                    <li className="mb-2">
                      <strong>Average Monthly Collection:</strong> {' '}
                      {monthlyStats.length > 0 ? 
                        formatCurrency(monthlyStats.reduce((sum, m) => sum + m.totalPaid, 0) / monthlyStats.length) : 
                        '₹0'
                      }
                    </li>
                    <li className="mb-2">
                      <strong>Best Month:</strong> {' '}
                      {monthlyStats.length > 0 ? 
                        monthlyStats.reduce((best, current) => 
                          current.collectionRate > best.collectionRate ? current : best
                        ).month : 'N/A'
                      }
                    </li>
                    <li>
                      <strong>Collection Trend:</strong> {' '}
                      <span className={`text-${collectionTrend.trend === 'up' ? 'success' : collectionTrend.trend === 'down' ? 'danger' : 'muted'}`}>
                        {collectionTrend.trend === 'up' ? 'Improving' : collectionTrend.trend === 'down' ? 'Declining' : 'Stable'}
                        {' '}({collectionTrend.percentage}%)
                      </span>
                    </li>
                  </ul>
                </Col>
                <Col md={6}>
                  <h6>Payment Status Summary</h6>
                  <ul className="list-unstyled">
                    <li className="mb-2">
                      <strong>Paid Payments:</strong> {' '}
                      {statusStats.find(s => s.status === 'Paid')?.count || 0} payments
                    </li>
                    <li className="mb-2">
                      <strong>Pending Payments:</strong> {' '}
                      {statusStats.find(s => s.status === 'Pending')?.count || 0} payments
                    </li>
                    <li>
                      <strong>Overdue Payments:</strong> {' '}
                      <span className="text-danger">
                        {statusStats.find(s => s.status === 'Overdue')?.count || 0} payments
                      </span>
                    </li>
                  </ul>
                </Col>
              </Row>
            </Card.Body>
          </Card>
        </Col>
      </Row>
    </div>
  );
};

export default PaymentStats;