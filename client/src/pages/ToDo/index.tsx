import React, { useState, useEffect } from 'react';

import { Button, Col, Row, Typography, Card, Divider, Space, } from 'antd';

import { Access, useAccess, useIntl, history } from 'umi';
import PermissionDeniedPage from '@/pages/403';
import styles from './styles.less';
import _ from 'lodash'
import { PageContainer } from '@ant-design/pro-layout';
import { getPendingRequestCount } from '@/services/workflowServices';
import { getDocumentAcknowledgeCount } from '@/services/documentManager';
const ToDo: React.FC = () => {
    const { Text } = Typography;
    const access = useAccess();
    const { hasPermitted } = access;
    const intl = useIntl();
    const [leaveRequestCount, setLeaveRequestCount] = useState(0);
    const [shortLeaveRequestCount, setShortLeaveRequestCount] = useState(0);
    const [timeChangeRequestCount, setTimeChangeRequestCount] = useState(0);
    const [cancelShortLeaveRequestCount, setCancelShortLeaveRequestCount] = useState(0);
    const [claimRequestCount, setClaimRequestCount] = useState(0);
    const [postOtRequestCount, setPostOtRequestCount] = useState(0);
    const [shiftChangeRequestCount, setShiftChangeRequestCount] = useState(0);
    const [profileRequestCount, setProfileRequestCount] = useState(0);
    const [documentRequestCount, setDocumentRequestCount] = useState(0);
    const [leaveCoveringRequestCount, setLeaveCoveringRequestCount] = useState(0);
    const [cancelLeaveRequestCount, setCancelLeaveRequestCount] = useState(0);
    const [resignationRequestCount, setResignationRequestCount] = useState(0);
    const [resignationTemplateInstances, setResignationTemplateInstances] = useState([]);
    const [confirmationTemplateInstances, setConfirmationTemplateInstances] = useState([]);

    useEffect(() => {
        if (hasPermitted('todo-request-access')) {
            fetchRequestCount();
        }
        if (hasPermitted('document-manager-employee-access')) {
            fetchDocumentAcknowledgeCount();
        }
    }, []);

    const fetchRequestCount = async () => {
        const data = await getPendingRequestCount();
        setLeaveRequestCount(data.data.leaveCount);
        setTimeChangeRequestCount(data.data.timeChangeCount);
        setClaimRequestCount(data.data.claimRequestCount);
        setPostOtRequestCount(data.data.postOtRequestCount);
        setProfileRequestCount(data.data.profileCount);
        setLeaveCoveringRequestCount(data.data.leaveCoveringPersonRequestsCount);
        setCancelShortLeaveRequestCount(data.data.cancelShortLeaveRequestCount);
        setCancelLeaveRequestCount(data.data.cancelLeaveRequestCount);
        setResignationRequestCount(data.data.resignationRequestCount);
        setShiftChangeRequestCount(data.data.shiftChangeRequestCount);
        setShortLeaveRequestCount(data.data.shortLeaveCount);
        setResignationTemplateInstances(data.data.resignationTemplateInstances);
        setConfirmationTemplateInstances(data.data.confirmationTemplateInstances);
    }
    const fetchDocumentAcknowledgeCount = async () => {
        const data = await getDocumentAcknowledgeCount();
        setDocumentRequestCount(data.data);
    }
    return (
      <Access
        accessible={
          hasPermitted('admin-widgets') ||
          hasPermitted('employee-widgets') ||
          hasPermitted('manager-widgets')
        }
        fallback={<PermissionDeniedPage />}
      >
        <PageContainer>
          <Card>
            <Col span={12}>
              <Text className={styles.heading}>
                {intl.formatMessage({
                  id: 'pages.todo.title',
                  defaultMessage: 'All To Do',
                })}
              </Text>
            </Col>
            <Divider />
            {hasPermitted('todo-request-access') && (
              <>
                {(leaveRequestCount != 0 ||
                  shortLeaveRequestCount != 0 ||
                  leaveCoveringRequestCount != 0 ||
                  cancelLeaveRequestCount != 0 || cancelShortLeaveRequestCount != 0) && (
                  <>
                    <Row>
                      <Col>
                        <Text className={styles.subheading}>
                          {intl.formatMessage({
                            id: 'pages.todo.Leave',
                            defaultMessage: 'Leave Manager',
                          })}
                        </Text>
                      </Col>
                    </Row>
                    <br />
                    {leaveRequestCount != 0 && (
                      <>
                        <Row>
                          <Col span={2} className={styles.col}>
                            <span className={styles.leaveOuter}>
                              <span className={styles.leave}>
                                <p className={styles.count}>{leaveRequestCount}</p>
                              </span>
                            </span>
                          </Col>
                          <Col span={18}>
                            <Text className={styles.primary}>
                              {intl.formatMessage({
                                id: 'pages.todo.Leave.primary',
                                defaultMessage: 'Pending  Leave Requests ',
                              })}
                            </Text>
                            <Text className={styles.secondary}>
                              {intl.formatMessage({
                                id: 'pages.todo.secondary',
                                defaultMessage: ' to be approved',
                              })}
                            </Text>
                          </Col>
                          <Col span={4}>
                            <Button
                              className={styles.leaveViewButton}
                              onClick={() => {
                                // history.push('/manager-self-service/user-requests');
                                history.push({
                                  pathname: '/manager-self-service/user-requests',
                                  state: {tabKey: '2'}
                                });
                              }}
                            >
                              <p className={styles.buttonText}>
                                {intl.formatMessage({
                                  id: 'pages.todo.buttonText',
                                  defaultMessage: ' View',
                                })}
                              </p>
                            </Button>
                          </Col>
                        </Row>

                        <Divider />
                      </>
                    )}
                    {shortLeaveRequestCount != 0 && (
                      <>
                        <Row>
                          <Col span={2} className={styles.col}>
                            <span className={styles.leaveOuter}>
                              <span className={styles.leave}>
                                <p className={styles.count}>{shortLeaveRequestCount}</p>
                              </span>
                            </span>
                          </Col>
                          <Col span={18}>
                            <Text className={styles.primary}>
                              {intl.formatMessage({
                                id: 'pages.todo.shortLeave.primary',
                                defaultMessage: 'Pending Short Leave Requests ',
                              })}
                            </Text>
                            <Text className={styles.secondary}>
                              {intl.formatMessage({
                                id: 'pages.todo.secondary',
                                defaultMessage: ' to be approved',
                              })}
                            </Text>
                          </Col>
                          <Col span={4}>
                            <Button
                              className={styles.leaveViewButton}
                              onClick={() => {
                                // history.push('/manager-self-service/user-requests');
                                history.push({
                                  pathname: '/manager-self-service/user-requests',
                                  state: {tabKey: '4'}
                                });
                              }}
                            >
                              <p className={styles.buttonText}>
                                {intl.formatMessage({
                                  id: 'pages.todo.buttonText',
                                  defaultMessage: ' View',
                                })}
                              </p>
                            </Button>
                          </Col>
                        </Row>

                        <Divider />
                      </>
                    )}
                    {leaveCoveringRequestCount != 0 && (
                      <>
                        <Row>
                          <Col span={2} className={styles.col}>
                            <span className={styles.leaveOuter}>
                              <span className={styles.leave}>
                                <p className={styles.count}>{leaveCoveringRequestCount}</p>
                              </span>
                            </span>
                          </Col>
                          <Col span={18}>
                            <Text className={styles.primary}>
                              {intl.formatMessage({
                                id: 'pages.todo.shortLeave.primary',
                                defaultMessage: 'Pending Leave Covering Requests ',
                              })}
                            </Text>
                            <Text className={styles.secondary}>
                              {intl.formatMessage({
                                id: 'pages.todo.secondary',
                                defaultMessage: ' to be approved',
                              })}
                            </Text>
                          </Col>
                          <Col span={4}>
                            <Button
                              className={styles.leaveViewButton}
                              onClick={() => {
                                history.push('/leave/leave-covering-request');
                              }}
                            >
                              <p className={styles.buttonText}>
                                {intl.formatMessage({
                                  id: 'pages.todo.buttonText',
                                  defaultMessage: 'View',
                                })}
                              </p>
                            </Button>
                          </Col>
                        </Row>

                        <Divider />
                      </>
                    )}
                    {cancelLeaveRequestCount != 0 && (
                      <>
                        <Row>
                          <Col span={2} className={styles.col}>
                            <span className={styles.leaveOuter}>
                              <span className={styles.leave}>
                                <p className={styles.count}>{cancelLeaveRequestCount}</p>
                              </span>
                            </span>
                          </Col>
                          <Col span={18}>
                            <Text className={styles.primary}>
                              {intl.formatMessage({
                                id: 'pages.todo.shortLeave.primary',
                                defaultMessage: 'Pending Leave Cancel Requests ',
                              })}
                            </Text>
                            <Text className={styles.secondary}>
                              {intl.formatMessage({
                                id: 'pages.todo.secondary',
                                defaultMessage: ' to be approved',
                              })}
                            </Text>
                          </Col>
                          <Col span={4}>
                            <Button
                              className={styles.leaveViewButton}
                              onClick={() => {
                                // history.push('/manager-self-service/user-requests');
                                history.push({
                                  pathname: '/manager-self-service/user-requests',
                                  state: {tabKey: '6'}
                                });
                              }}
                            >
                              <p className={styles.buttonText}>
                                {intl.formatMessage({
                                  id: 'pages.todo.buttonText',
                                  defaultMessage: ' View',
                                })}
                              </p>
                            </Button>
                          </Col>
                        </Row>
                        <Divider />
                      </>
                    )}
                     {cancelShortLeaveRequestCount != 0 &&
                        <>
                        <Row>
                            <Col span={2} className={styles.col}>
                                <span className={styles.leaveOuter}>
                                    <span className={styles.leave}>
                                        <p className={styles.count}>{cancelShortLeaveRequestCount}</p>
                                    </span>
                                </span>
                            </Col>
                            <Col span={18}>
                                <Text className={styles.primary}>
                                    {intl.formatMessage({
                                        id: 'pages.todo.shortLeave.primary',
                                        defaultMessage: 'Pending Short Leave Cancel Requests '
                                    })}
                                </Text>
                                <Text className={styles.secondary}>
                                    {intl.formatMessage({
                                        id: 'pages.todo.secondary',
                                        defaultMessage: ' to be approved'
                                    })}
                                </Text>
                            </Col>
                            <Col span={4}>
                                <Button className={styles.leaveViewButton}
                                    onClick={() => { 
                                      // history.push('/manager-self-service/user-requests'); 
                                      history.push({
                                        pathname: '/manager-self-service/user-requests',
                                        state: {tabKey: '8'}
                                      });
                                    }}
                                >
                                    <p className={styles.buttonText}>
                                        {intl.formatMessage({
                                            id: 'pages.todo.buttonText',
                                            defaultMessage: ' View'
                                        })}
                                    </p>
                                </Button>
                            </Col>
                        </Row>

                        <Divider />
                      </>}
                  </>
                )}
                {(timeChangeRequestCount != 0 || postOtRequestCount != 0 || shiftChangeRequestCount != 0) && (
                  <>
                    <Row>
                      <Col>
                        <Text className={styles.subheading}>
                          {intl.formatMessage({
                            id: 'pages.todo.attendance',
                            defaultMessage: 'Attendance Manager',
                          })}
                        </Text>
                      </Col>
                    </Row>
                    <br />
                    {timeChangeRequestCount != 0 && (
                      <>
                        <Row>
                          <Col span={2} className={styles.col}>
                            <span className={styles.attendanceOuter}>
                              <span className={styles.attendance}>
                                <p className={styles.count}>{timeChangeRequestCount}</p>
                              </span>
                            </span>
                          </Col>
                          <Col span={18}>
                            <Text className={styles.primary}>
                              {intl.formatMessage({
                                id: 'pages.todo.timeChange.primary',
                                defaultMessage: 'Pending Time Change Request',
                              })}
                            </Text>
                            <Text className={styles.secondary}>
                              {intl.formatMessage({
                                id: 'pages.todo.secondary',
                                defaultMessage: ' to be approved',
                              })}
                            </Text>
                          </Col>
                          <Col span={4}>
                            <Button
                              className={styles.attendanceViewButton}
                              onClick={() => {
                                // history.push('/manager-self-service/user-requests');
                                history.push({
                                  pathname: '/manager-self-service/user-requests',
                                  state: {tabKey: '3'}
                                });
                              }}
                            >
                              <p className={styles.buttonText}>
                                {intl.formatMessage({
                                  id: 'pages.todo.buttonText',
                                  defaultMessage: ' View',
                                })}
                              </p>
                            </Button>
                          </Col>
                        </Row>
                        <Divider />
                      </>
                    )}
                    {shiftChangeRequestCount != 0 && (
                      <>
                        <Row>
                          <Col span={2} className={styles.col}>
                            <span className={styles.attendanceOuter}>
                              <span className={styles.attendance}>
                                <p className={styles.count}>{shiftChangeRequestCount}</p>
                              </span>
                            </span>
                          </Col>
                          <Col span={18}>
                            <Text className={styles.primary}>
                              {intl.formatMessage({
                                id: 'pages.todo.shift.primary',
                                defaultMessage: 'Pending Shift Change Requests',
                              })}
                            </Text>
                            <Text className={styles.secondary}>
                              {intl.formatMessage({
                                id: 'pages.todo.secondary',
                                defaultMessage: ' to be approved',
                              })}
                            </Text>
                          </Col>
                          <Col span={4}>
                            <Button
                              className={styles.attendanceViewButton}
                              onClick={() => {
                                // history.push('/manager-self-service/user-requests');
                                history.push({
                                  pathname: '/manager-self-service/user-requests',
                                  state: {tabKey: '5'}
                                });
                              }}
                            >
                              <p className={styles.buttonText}>
                                {intl.formatMessage({
                                  id: 'pages.todo.buttonText',
                                  defaultMessage: ' View',
                                })}
                              </p>
                            </Button>
                          </Col>
                        </Row>
                        <Divider />
                      </>
                    )}
                    {postOtRequestCount != 0 && (
                      <>
                        <Row>
                          <Col span={2} className={styles.col}>
                            <span className={styles.attendanceOuter}>
                              <span className={styles.attendance}>
                                <p className={styles.count}>{postOtRequestCount}</p>
                              </span>
                            </span>
                          </Col>
                          <Col span={18}>
                            <Text className={styles.primary}>
                              {intl.formatMessage({
                                id: 'pages.todo.post_ot.primary',
                                defaultMessage: 'Pending Post OT Requests',
                              })}
                            </Text>
                            <Text className={styles.secondary}>
                              {intl.formatMessage({
                                id: 'pages.todo.secondary',
                                defaultMessage: ' to be approved',
                              })}
                            </Text>
                          </Col>
                          <Col span={4}>
                            <Button
                              className={styles.attendanceViewButton}
                              onClick={() => {
                                // history.push('/manager-self-service/user-requests');
                                history.push({
                                  pathname: '/manager-self-service/user-requests',
                                  state: {tabKey: '10'}
                                });
                              }}
                            >
                              <p className={styles.buttonText}>
                                {intl.formatMessage({
                                  id: 'pages.todo.buttonText',
                                  defaultMessage: ' View',
                                })}
                              </p>
                            </Button>
                          </Col>
                        </Row>
                        <Divider />
                      </>
                    )}
                  </>
                )}
                {(profileRequestCount != 0 || resignationRequestCount != 0) && (
                    <>
                      <Row>
                        <Col>
                          <Text className={styles.subheading}>
                            {intl.formatMessage({
                              id: 'pages.todo.attendance',
                              defaultMessage: 'Profile Manager',
                            })}
                          </Text>
                        </Col>
                      </Row>
                      <br />

                      {profileRequestCount != 0 && (
                        <>
                          <Row>
                            <Col span={2} className={styles.col}>
                              <span className={styles.profileOuter}>
                                <span className={styles.profile}>
                                  <p className={styles.count}>{profileRequestCount}</p>
                                </span>
                              </span>
                            </Col>
                            <Col span={18}>
                              <Text className={styles.primary}>
                                {intl.formatMessage({
                                  id: 'pages.todo.profile.primary',
                                  defaultMessage: 'Pending Profile Change Requests',
                                })}
                              </Text>
                              <Text className={styles.secondary}>
                                {intl.formatMessage({
                                  id: 'pages.todo.secondary',
                                  defaultMessage: ' to be approved',
                                })}
                              </Text>
                            </Col>
                            <Col span={4}>
                              <Button
                                className={styles.profileViewButton}
                                onClick={() => {
                                  // history.push('/manager-self-service/user-requests');
                                  history.push({
                                    pathname: '/manager-self-service/user-requests',
                                    state: {tabKey: '1'}
                                  });
                                }}
                              >
                                <p className={styles.buttonText}>
                                  {intl.formatMessage({
                                    id: 'pages.todo.buttonText',
                                    defaultMessage: ' View',
                                  })}
                                </p>
                              </Button>
                            </Col>
                          </Row>
                          <Divider />
                        </>
                      )}
                      {resignationRequestCount != 0 && (
                        <>
                          <Row>
                            <Col span={2} className={styles.col}>
                              <span className={styles.profileOuter}>
                                <span className={styles.profile}>
                                  <p className={styles.count}>{resignationRequestCount}</p>
                                </span>
                              </span>
                            </Col>
                            <Col span={18}>
                              <Text className={styles.primary}>
                                {intl.formatMessage({
                                  id: 'pages.todo.profile.primary',
                                  defaultMessage: 'Pending Resignation Requests',
                                })}
                              </Text>
                              <Text className={styles.secondary}>
                                {intl.formatMessage({
                                  id: 'pages.todo.secondary',
                                  defaultMessage: ' to be approved',
                                })}
                              </Text>
                            </Col>
                            <Col span={4}>
                              <Button
                                className={styles.profileViewButton}
                                onClick={() => {
                                  // history.push('/manager-self-service/user-requests');
                                  history.push({
                                    pathname: '/manager-self-service/user-requests',
                                    state: {tabKey: '7'}
                                  });
                                }}
                              >
                                <p className={styles.buttonText}>
                                  {intl.formatMessage({
                                    id: 'pages.todo.buttonText',
                                    defaultMessage: ' View',
                                  })}
                                </p>
                              </Button>
                            </Col>
                          </Row>
                          <Divider />
                        </>
                      )}
                    </>
                  )}
              </>
            )}
            {hasPermitted('document-manager-employee-access') && documentRequestCount != 0 && (
              <>
                <Row>
                  <Col>
                    <Text className={styles.subheading}>
                      {intl.formatMessage({
                        id: 'pages.todo.attendance',
                        defaultMessage: 'Document Manager',
                      })}
                    </Text>
                  </Col>
                </Row>
                <br />
                <Row>
                  <Col span={2} className={styles.col}>
                    <span className={styles.documentsOuter}>
                      <span className={styles.documents}>
                        <p className={styles.count}>{documentRequestCount}</p>
                      </span>
                    </span>
                  </Col>
                  <Col span={18}>
                    <Text className={styles.primary}>
                      {intl.formatMessage({
                        id: 'pages.todo.documents.primary',
                        defaultMessage: 'Pending Documents ',
                      })}
                    </Text>
                    <Text className={styles.secondary}>
                      {intl.formatMessage({
                        id: 'pages.todo.secondary',
                        defaultMessage: 'to be acknowledge',
                      })}
                    </Text>
                  </Col>
                  <Col span={4}>
                    <Button
                      className={styles.documentViewButton}
                      onClick={() => {
                        history.push({
                          pathname: '/ess/my-info-view',
                          search: 'documents',
                        });
                      }}
                    >
                      <p className={styles.buttonText}>
                        {intl.formatMessage({
                          id: 'pages.todo.buttonText',
                          defaultMessage: ' View',
                        })}
                      </p>
                    </Button>
                  </Col>
                </Row>
              </>
            )}
            {resignationTemplateInstances.length > 0 && (
              <>
                <Row>
                  <Col>
                    <Text className={styles.subheading}>
                      {intl.formatMessage({
                        id: 'pages.todo.exitInterviewForm',
                        defaultMessage: 'Resignation Process',
                      })}
                    </Text>
                  </Col>
                </Row>
                <br />
                <Row>
                  <Col span={2} className={styles.col}>
                    <span className={styles.documentsOuter}>
                      <span className={styles.documents}>
                        <p className={styles.count}>{resignationTemplateInstances.length}</p>
                      </span>
                    </span>
                  </Col>
                  <Col span={18}>
                    <Text className={styles.primary}>
                      {intl.formatMessage({
                        id: 'pages.todo.documents.primary',
                        defaultMessage: 'Exit Interview Form',
                      })}
                    </Text>
                    <Text className={styles.secondary}>
                      {intl.formatMessage({
                        id: 'pages.todo.secondary',
                        defaultMessage: ' to be filled',
                      })}
                    </Text>
                  </Col>
                  <Col span={4}>
                    <Button
                      className={styles.documentViewButton}
                      onClick={() => {
                        history.push({
                          pathname: `/template-builder/${resignationTemplateInstances[0].hash}/interactive-viewer`,
                          search: '',
                        });
                      }}
                    >
                      <p className={styles.buttonText}>
                        {intl.formatMessage({
                          id: 'pages.todo.buttonText',
                          defaultMessage: ' View',
                        })}
                      </p>
                    </Button>
                  </Col>
                </Row>
              </>
            )}
            {confirmationTemplateInstances.length > 0 && (
              <>
                <Row>
                  <Col>
                    <Text className={styles.subheading}>
                      {intl.formatMessage({
                        id: 'pages.todo.confirmationReviewForm',
                        defaultMessage: 'Confirmation Process',
                      })}
                    </Text>
                  </Col>
                </Row>
                <br />
                <Row>
                  <Col span={2} className={styles.col}>
                    <span className={styles.documentsOuter}>
                      <span className={styles.documents}>
                        <p className={styles.count}>{confirmationTemplateInstances.length}</p>
                      </span>
                    </span>
                  </Col>
                  <Col span={18}>
                    <Text className={styles.primary}>
                      {intl.formatMessage({
                        id: 'pages.todo.documents.primary',
                        defaultMessage: 'Confirmation Review Form',
                      })}
                    </Text>
                    <Text className={styles.secondary}>
                      {intl.formatMessage({
                        id: 'pages.todo.secondary',
                        defaultMessage: ' to be filled',
                      })}
                    </Text>
                  </Col>
                  <Col span={4}>
                    <Button
                      className={styles.documentViewButton}
                      onClick={() => {
                        history.push({
                          pathname: `/template-builder/${confirmationTemplateInstances[0].hash}/interactive-viewer`,
                          search: '',
                        });
                      }}
                    >
                      <p className={styles.buttonText}>
                        {intl.formatMessage({
                          id: 'pages.todo.buttonText',
                          defaultMessage: ' View',
                        })}
                      </p>
                    </Button>
                  </Col>
                </Row>
              </>
            )}
            <Divider />
            {claimRequestCount != 0 && (
            <>
              <Row>
                <Col>
                  <Text className={styles.subheading}>
                    {intl.formatMessage({
                      id: 'pages.todo.expense',
                      defaultMessage: 'Expense Manager',
                    })}
                  </Text>
                </Col>
              </Row>
              <br />
              {claimRequestCount != 0 && (
                <>
                  <Row>
                    <Col span={2} className={styles.col}>
                      <span className={styles.attendanceOuter}>
                        <span className={styles.attendance}>
                          <p className={styles.count}>{claimRequestCount}</p>
                        </span>
                      </span>
                    </Col>
                    <Col span={18}>
                      <Text className={styles.primary}>
                        {intl.formatMessage({
                          id: 'pages.todo.claim.primary',
                          defaultMessage: 'Pending Claim Request',
                        })}
                      </Text>
                      <Text className={styles.secondary}>
                        {intl.formatMessage({
                          id: 'pages.todo.secondary',
                          defaultMessage: ' to be approved',
                        })}
                      </Text>
                    </Col>
                    <Col span={4}>
                      <Button
                        className={styles.attendanceViewButton}
                        onClick={() => {
                          // history.push('/manager-self-service/user-requests');
                          history.push({
                            pathname: '/manager-self-service/user-requests',
                            state: {tabKey: '9'}
                          });
                        }}
                      >
                        <p className={styles.buttonText}>
                          {intl.formatMessage({
                            id: 'pages.todo.buttonText',
                            defaultMessage: ' View',
                          })}
                        </p>
                      </Button>
                    </Col>
                  </Row>
                  <Divider />
                </>
              )}
            </>
          )}
          </Card>
        </PageContainer>
      </Access>
    );
};

export default ToDo;
