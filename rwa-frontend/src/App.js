import React, { useState } from 'react';
import { BrowserRouter as Router, Routes, Route, Navigate } from 'react-router-dom';
import { Navbar, Nav, Container, Alert } from 'react-bootstrap';
import { FaHome, FaMoneyBillWave, FaUsers, FaExclamationTriangle, FaChartLine, FaSync } from 'react-icons/fa';
import PaymentDashboard from './components/payments/PaymentDashboard';
import UnpaidResidents from './components/payments/UnpaidResidents';
import Defaulters from './components/payments/Defaulters';
import Analytics from './components/payments/Analytics';
import 'bootstrap/dist/css/bootstrap.min.css';
import './components/payments/PaymentDashboard.css';
import './components/payments/EnhancedPaymentStyles.css';

function App() {
  const [alert, setAlert] = useState({ show: false, type: '', message: '' });

  const showAlert = (type, message) => {
    setAlert({ show: true, type, message });
    setTimeout(() => {
      setAlert({ show: false, type: '', message: '' });
    }, 5000);
  };

  // Main Navigation Component
  const Navigation = () => (
    <Navbar bg="dark" variant="dark" expand="lg" className="mb-4">
      <Container>
        <Navbar.Brand href="/">
          <FaHome className="me-2" />
          RWA Payment System
        </Navbar.Brand>
        <Navbar.Toggle aria-controls="basic-navbar-nav" />
        <Navbar.Collapse id="basic-navbar-nav">
          <Nav className="me-auto">
            <Nav.Link href="/payments">
              <FaMoneyBillWave className="me-1" />
              Payments
            </Nav.Link>
            <Nav.Link href="/unpaid-residents">
              <FaUsers className="me-1" />
              Unpaid Residents
            </Nav.Link>
            <Nav.Link href="/defaulters">
              <FaExclamationTriangle className="me-1" />
              Defaulters
            </Nav.Link>
            <Nav.Link href="/analytics">
              <FaChartLine className="me-1" />
              Analytics
            </Nav.Link>
            <Nav.Link href="/sync" className="text-info">
              <FaSync className="me-1" />
              Sync
            </Nav.Link>
          </Nav>
        </Navbar.Collapse>
      </Container>
    </Navbar>
  );

  return (
    <Router>
      <div className="App">
        <Navigation />
        
        {alert.show && (
          <Container>
            <Alert variant={alert.type} className="mb-3">
              {alert.message}
            </Alert>
          </Container>
        )}

        <Routes>
          <Route path="/" element={<Navigate to="/payments" replace />} />
          <Route 
            path="/payments" 
            element={<PaymentDashboard showAlert={showAlert} />} 
          />
          <Route 
            path="/unpaid-residents" 
            element={<UnpaidResidents />} 
          />
          <Route 
            path="/defaulters" 
            element={<Defaulters />} 
          />
          <Route 
            path="/analytics" 
            element={<Analytics />} 
          />
          <Route path="*" element={<Navigate to="/payments" replace />} />
        </Routes>
      </div>
    </Router>
  );
}

export default App;
