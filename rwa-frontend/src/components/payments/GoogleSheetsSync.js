import React, { useState, useEffect, useCallback } from 'react';
import { Container, Card, Button, Alert, Form, Spinner, Badge, Table, Modal } from 'react-bootstrap';
import { FaSync, FaCheckCircle, FaExclamationTriangle, FaUpload, FaDownload, FaCog, FaInfoCircle } from 'react-icons/fa';
import { googleSheetsAPI } from '../../services/api';
import { formatDate } from '../../utils/helpers';

const GoogleSheetsSync = () => {
  const [connectionStatus, setConnectionStatus] = useState('unknown'); // unknown, connected, error
  const [syncStatus, setSyncStatus] = useState('idle'); // idle, syncing, success, error
  const [autoSync, setAutoSync] = useState(false);
  const [syncInterval, setSyncInterval] = useState(30); // minutes
  const [lastSyncTime, setLastSyncTime] = useState(null);
  const [syncLogs, setSyncLogs] = useState([]);
  const [connectionInfo, setConnectionInfo] = useState(null);
  const [showLogsModal, setShowLogsModal] = useState(false);
  const [syncDirection, setSyncDirection] = useState('bidirectional'); // upload, download, bidirectional
  const [loading, setLoading] = useState(false);
  const [alert, setAlert] = useState({ show: false, type: '', message: '' });

  const showAlert = (type, message) => {
    setAlert({ show: true, type, message });
    setTimeout(() => {
      setAlert({ show: false, type: '', message: '' });
    }, 5000);
  };

  const testConnection = useCallback(async () => {
    try {
      setLoading(true);
      const response = await googleSheetsAPI.testConnection();
      
      if (response.status === 'success') {
        setConnectionStatus('connected');
        setConnectionInfo(response.data);
        showAlert('success', 'Connection to Google Sheets successful!');
      } else {
        setConnectionStatus('error');
        showAlert('danger', response.message || 'Failed to connect to Google Sheets');
      }
    } catch (error) {
      console.error('Connection test failed:', error);
      setConnectionStatus('error');
      showAlert('danger', 'Connection test failed. Please check your configuration.');
    } finally {
      setLoading(false);
    }
  }, []);

  const handleSync = useCallback(async (direction = syncDirection) => {
    try {
      setSyncStatus('syncing');
      setLoading(true);
      
      let response;
      switch (direction) {
        case 'upload':
          response = await googleSheetsAPI.uploadToSheets();
          break;
        case 'download':
          response = await googleSheetsAPI.downloadFromSheets();
          break;
        case 'bidirectional':
        default:
          response = await googleSheetsAPI.syncBidirectional();
          break;
      }

      if (response.status === 'success') {
        setSyncStatus('success');
        setLastSyncTime(new Date());
        
        // Add to sync logs
        const logEntry = {
          id: Date.now(),
          timestamp: new Date(),
          direction,
          status: 'success',
          message: response.message || 'Sync completed successfully',
          details: response.data
        };
        setSyncLogs(prev => [logEntry, ...prev.slice(0, 9)]); // Keep last 10 logs
        
        showAlert('success', `${direction.charAt(0).toUpperCase() + direction.slice(1)} sync completed successfully!`);
      } else {
        setSyncStatus('error');
        
        const logEntry = {
          id: Date.now(),
          timestamp: new Date(),
          direction,
          status: 'error',
          message: response.message || 'Sync failed',
          details: response.error
        };
        setSyncLogs(prev => [logEntry, ...prev.slice(0, 9)]);
        
        showAlert('danger', response.message || 'Sync failed');
      }
    } catch (error) {
      console.error('Sync failed:', error);
      setSyncStatus('error');
      
      const logEntry = {
        id: Date.now(),
        timestamp: new Date(),
        direction,
        status: 'error',
        message: 'Sync failed with error',
        details: error.message
      };
      setSyncLogs(prev => [logEntry, ...prev.slice(0, 9)]);
      
      showAlert('danger', 'Sync failed. Please try again.');
    } finally {
      setLoading(false);
      
      // Reset sync status after 3 seconds
      setTimeout(() => {
        setSyncStatus('idle');
      }, 3000);
    }
  }, [syncDirection]);

  const loadSyncSettings = () => {
    // Load settings from localStorage
    const savedAutoSync = localStorage.getItem('googleSheets_autoSync');
    const savedInterval = localStorage.getItem('googleSheets_syncInterval');
    const savedDirection = localStorage.getItem('googleSheets_syncDirection');
    
    if (savedAutoSync !== null) {
      setAutoSync(JSON.parse(savedAutoSync));
    }
    if (savedInterval) {
      setSyncInterval(parseInt(savedInterval));
    }
    if (savedDirection) {
      setSyncDirection(savedDirection);
    }
  };

  const saveSyncSettings = () => {
    localStorage.setItem('googleSheets_autoSync', JSON.stringify(autoSync));
    localStorage.setItem('googleSheets_syncInterval', syncInterval.toString());
    localStorage.setItem('googleSheets_syncDirection', syncDirection);
    showAlert('success', 'Settings saved successfully!');
  };

  const loadSyncLogs = () => {
    // Load logs from localStorage
    const savedLogs = localStorage.getItem('googleSheets_syncLogs');
    if (savedLogs) {
      try {
        const logs = JSON.parse(savedLogs).map(log => ({
          ...log,
          timestamp: new Date(log.timestamp)
        }));
        setSyncLogs(logs);
      } catch (error) {
        console.error('Failed to load sync logs:', error);
      }
    }
  };

  const saveSyncLogs = (logs) => {
    localStorage.setItem('googleSheets_syncLogs', JSON.stringify(logs));
  };

  useEffect(() => {
    const initializeComponent = async () => {
      await testConnection();
      loadSyncSettings();
      loadSyncLogs();
    };
    
    initializeComponent();
  }, [testConnection]);

  // Auto-sync effect
  useEffect(() => {
    let interval;
    if (autoSync && syncInterval > 0) {
      interval = setInterval(() => {
        handleSync();
      }, syncInterval * 60 * 1000);
    }
    return () => {
      if (interval) clearInterval(interval);
    };
  }, [autoSync, syncInterval, handleSync]);

  // Save logs whenever they change
  useEffect(() => {
    if (syncLogs.length > 0) {
      saveSyncLogs(syncLogs);
    }
  }, [syncLogs]);

  const getConnectionBadge = () => {
    switch (connectionStatus) {
      case 'connected':
        return <Badge bg="success"><FaCheckCircle className="me-1" />Connected</Badge>;
      case 'error':
        return <Badge bg="danger"><FaExclamationTriangle className="me-1" />Error</Badge>;
      default:
        return <Badge bg="secondary">Unknown</Badge>;
    }
  };

  const getSyncStatusBadge = () => {
    switch (syncStatus) {
      case 'syncing':
        return <Badge bg="primary"><Spinner size="sm" className="me-1" />Syncing...</Badge>;
      case 'success':
        return <Badge bg="success"><FaCheckCircle className="me-1" />Success</Badge>;
      case 'error':
        return <Badge bg="danger"><FaExclamationTriangle className="me-1" />Error</Badge>;
      default:
        return <Badge bg="secondary">Idle</Badge>;
    }
  };

  const getDirectionIcon = (direction) => {
    switch (direction) {
      case 'upload':
        return <FaUpload className="text-primary" />;
      case 'download':
        return <FaDownload className="text-info" />;
      case 'bidirectional':
        return <FaSync className="text-success" />;
      default:
        return <FaSync />;
    }
  };

  return (
    <Container>
      {alert.show && (
        <Alert variant={alert.type} className="mb-3">
          {alert.message}
        </Alert>
      )}

      {/* Connection Status Card */}
      <Card className="mb-4">
        <Card.Header className="d-flex justify-content-between align-items-center">
          <h5 className="mb-0">
            <FaInfoCircle className="me-2 text-info" />
            Google Sheets Connection
          </h5>
          {getConnectionBadge()}
        </Card.Header>
        <Card.Body>
          <div className="row">
            <div className="col-md-8">
              {connectionInfo && (
                <div>
                  <p className="mb-1"><strong>Spreadsheet:</strong> {connectionInfo.title}</p>
                  <p className="mb-1"><strong>URL:</strong> 
                    <a href={connectionInfo.url} target="_blank" rel="noopener noreferrer" className="ms-2">
                      Open in Google Sheets
                    </a>
                  </p>
                  <p className="mb-0"><strong>Last Updated:</strong> {connectionInfo.lastModified ? formatDate(connectionInfo.lastModified) : 'Unknown'}</p>
                </div>
              )}
              {connectionStatus === 'error' && (
                <p className="text-danger mb-0">
                  Unable to connect to Google Sheets. Please check your configuration and try again.
                </p>
              )}
            </div>
            <div className="col-md-4 text-end">
              <Button 
                variant="outline-primary" 
                onClick={testConnection}
                disabled={loading}
              >
                {loading ? <Spinner size="sm" className="me-1" /> : <FaCheckCircle className="me-1" />}
                Test Connection
              </Button>
            </div>
          </div>
        </Card.Body>
      </Card>

      {/* Sync Controls Card */}
      <Card className="mb-4">
        <Card.Header className="d-flex justify-content-between align-items-center">
          <h5 className="mb-0">
            <FaSync className="me-2 text-success" />
            Sync Controls
          </h5>
          {getSyncStatusBadge()}
        </Card.Header>
        <Card.Body>
          <div className="row">
            <div className="col-md-6">
              <Form.Group className="mb-3">
                <Form.Label>Sync Direction</Form.Label>
                <Form.Select 
                  value={syncDirection} 
                  onChange={(e) => setSyncDirection(e.target.value)}
                >
                  <option value="bidirectional">Bidirectional (Upload & Download)</option>
                  <option value="upload">Upload to Sheets</option>
                  <option value="download">Download from Sheets</option>
                </Form.Select>
              </Form.Group>
              
              <div className="d-flex gap-2 mb-3">
                <Button 
                  variant="success" 
                  onClick={() => handleSync('upload')}
                  disabled={loading || connectionStatus !== 'connected'}
                >
                  <FaUpload className="me-1" />
                  Upload to Sheets
                </Button>
                <Button 
                  variant="info" 
                  onClick={() => handleSync('download')}
                  disabled={loading || connectionStatus !== 'connected'}
                >
                  <FaDownload className="me-1" />
                  Download from Sheets
                </Button>
                <Button 
                  variant="primary" 
                  onClick={() => handleSync('bidirectional')}
                  disabled={loading || connectionStatus !== 'connected'}
                >
                  <FaSync className="me-1" />
                  Bidirectional Sync
                </Button>
              </div>
            </div>
            
            <div className="col-md-6">
              <div className="border-start ps-3">
                <h6>Last Sync Information</h6>
                {lastSyncTime ? (
                  <div>
                    <p className="mb-1"><strong>Time:</strong> {formatDate(lastSyncTime)}</p>
                    <p className="mb-0"><strong>Status:</strong> {getSyncStatusBadge()}</p>
                  </div>
                ) : (
                  <p className="text-muted mb-0">No sync performed yet</p>
                )}
              </div>
            </div>
          </div>
        </Card.Body>
      </Card>

      {/* Auto-Sync Settings Card */}
      <Card className="mb-4">
        <Card.Header>
          <h5 className="mb-0">
            <FaCog className="me-2 text-secondary" />
            Auto-Sync Settings
          </h5>
        </Card.Header>
        <Card.Body>
          <div className="row">
            <div className="col-md-6">
              <Form.Check 
                type="switch"
                id="auto-sync-switch"
                label="Enable Auto-Sync"
                checked={autoSync}
                onChange={(e) => setAutoSync(e.target.checked)}
                className="mb-3"
              />
              
              {autoSync && (
                <Form.Group className="mb-3">
                  <Form.Label>Sync Interval (minutes)</Form.Label>
                  <Form.Select 
                    value={syncInterval} 
                    onChange={(e) => setSyncInterval(parseInt(e.target.value))}
                  >
                    <option value={5}>Every 5 minutes</option>
                    <option value={15}>Every 15 minutes</option>
                    <option value={30}>Every 30 minutes</option>
                    <option value={60}>Every hour</option>
                    <option value={180}>Every 3 hours</option>
                    <option value={360}>Every 6 hours</option>
                  </Form.Select>
                </Form.Group>
              )}
            </div>
            
            <div className="col-md-6">
              <div className="d-flex gap-2">
                <Button variant="outline-primary" onClick={saveSyncSettings}>
                  Save Settings
                </Button>
                <Button variant="outline-secondary" onClick={() => setShowLogsModal(true)}>
                  View Sync Logs
                </Button>
              </div>
            </div>
          </div>
        </Card.Body>
      </Card>

      {/* Recent Sync Logs */}
      {syncLogs.length > 0 && (
        <Card>
          <Card.Header>
            <h5 className="mb-0">Recent Sync Activity</h5>
          </Card.Header>
          <Card.Body>
            <Table responsive size="sm">
              <thead>
                <tr>
                  <th>Time</th>
                  <th>Direction</th>
                  <th>Status</th>
                  <th>Message</th>
                </tr>
              </thead>
              <tbody>
                {syncLogs.slice(0, 5).map((log) => (
                  <tr key={log.id}>
                    <td>{formatDate(log.timestamp)}</td>
                    <td>
                      {getDirectionIcon(log.direction)}
                      <span className="ms-1">{log.direction}</span>
                    </td>
                    <td>
                      <Badge bg={log.status === 'success' ? 'success' : 'danger'}>
                        {log.status}
                      </Badge>
                    </td>
                    <td>{log.message}</td>
                  </tr>
                ))}
              </tbody>
            </Table>
          </Card.Body>
        </Card>
      )}

      {/* Sync Logs Modal */}
      <Modal show={showLogsModal} onHide={() => setShowLogsModal(false)} size="lg">
        <Modal.Header closeButton>
          <Modal.Title>Sync Logs</Modal.Title>
        </Modal.Header>
        <Modal.Body>
          {syncLogs.length > 0 ? (
            <Table responsive>
              <thead>
                <tr>
                  <th>Time</th>
                  <th>Direction</th>
                  <th>Status</th>
                  <th>Message</th>
                  <th>Details</th>
                </tr>
              </thead>
              <tbody>
                {syncLogs.map((log) => (
                  <tr key={log.id}>
                    <td>{formatDate(log.timestamp)}</td>
                    <td>
                      {getDirectionIcon(log.direction)}
                      <span className="ms-1">{log.direction}</span>
                    </td>
                    <td>
                      <Badge bg={log.status === 'success' ? 'success' : 'danger'}>
                        {log.status}
                      </Badge>
                    </td>
                    <td>{log.message}</td>
                    <td>
                      {log.details && (
                        <small className="text-muted">
                          {typeof log.details === 'object' 
                            ? JSON.stringify(log.details, null, 2) 
                            : log.details
                          }
                        </small>
                      )}
                    </td>
                  </tr>
                ))}
              </tbody>
            </Table>
          ) : (
            <p className="text-muted">No sync logs available.</p>
          )}
        </Modal.Body>
        <Modal.Footer>
          <Button variant="secondary" onClick={() => setShowLogsModal(false)}>
            Close
          </Button>
        </Modal.Footer>
      </Modal>
    </Container>
  );
};

export default GoogleSheetsSync;