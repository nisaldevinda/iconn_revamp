import React, { useState, useEffect } from 'react';
import { Avatar, Card, Row, Skeleton, Select, Col, Typography, Button, Divider, Spin } from 'antd';
import { Access, history, useAccess, useIntl } from 'umi';

import anniversaries from '../../assets/anniversaries.svg';
import birthdays from '../../assets/birthdays.svg';
import myinfo from '../../assets/myinfo.svg';
import myteam from '../../assets/myteam.svg';
import organization from '../../assets/organization.svg';
import reports from '../../assets/reports.svg';
import settings from '../../assets/settings.svg';

import TinyModelLayout from '@/components/Dashboard/TinyModel';
import { getLastPublishedNotices } from '@/services/notice';
import { getUpcomingBirthDays, getUpcomingHiredDays } from '@/services/employee';
import BarChart from './BarChart';
import PieChart from './PieChart';
import PieChartLiquid from './PieChartLiquid';
import GaugeChart from './GaugeChart';
import AreaChart from './AreaChart';
import GroupBarChart from './GroupBarChart';
import AgeChart from './AgeChart';
import './cardLayout.css';
import { queryChartById, queryReportById } from '@/services/reportService';
import { getPrivileges } from '@/utils/permission';
import _ from 'lodash';
import ReportWidget from './ReportWidget';
import { getAllNoticeCategory } from '@/services/noticeCategory';
import styles from './Dashboard.less';
import { getPendingRequestCount } from '@/services/workflowServices';
import { getDocumentAcknowledgeCount } from '@/services/documentManager';

const { Meta } = Card;

export type CardProps = {
  title: string;
  cardWidth?: number;
  cardHeight: number;
  loading?: boolean;
  data?: any;
  pendingCountData: any;
  acknoledgementDataCount: any;
  viewMoreText?: string;
  viewMoreLink?: string;
  fieldData?: string;
  fields?: string[];
  filter?: boolean;
};

const CardLayout: React.FC<CardProps> = (props) => {
  const [loading, setLoading] = useState(false);
  const [data, setData] = useState([]);
  const [extraData, setExtraData] = useState({});
  const [headCountData, setHeadCountData] = useState([]);
  const [ageDistribution, setAgeDistribution] = useState();
  const [title, setTitle] = useState<string>();
  const [tags, setTag] = useState<any>();
  const [error, setError] = useState<boolean>(false);
  const [genderByDeptData, setGenderByDept] = useState([]);
  let viewMore;
  let imgSVG;
  let nextToTitle;
  const viewMoreLink = props.viewMoreLink ?? '';
  const access = useAccess();
  const { canShowShortcuts } = access;
  const privilege = getPrivileges();
  const { hasPermitted } = access;
  const { Option } = Select;
  const intl = useIntl();

  const [filterType, setfilterType] = useState('All');
  const { Text } = Typography;
  const [leaveRequestCount, setLeaveRequestCount] = useState(0);
  const [shortLeaveRequestCount, setShortLeaveRequestCount] = useState(0);
  const [timeChangeRequestCount, setTimeChangeRequestCount] = useState(0);
  const [claimRequestCount, setClaimRequestCount] = useState(0);
  const [postOtRequestCount, setPostOtRequestCount] = useState(0);
  const [leaveCoveringRequestCount, setLeaveCoveringRequestCount] = useState(0);
  const [cancelLeaveRequestCount, setCancelLeaveRequestCount] = useState(0);
  const [resignationRequestCount, setResignationRequestCount] = useState(0);
  const [cancelShortLeaveRequestCount, setCancelShortLeaveRequestCount] = useState(0);
  const [shiftChangeRequestCount, setShiftChangeRequestCount] = useState(0);
  const [profileRequestCount, setProfileRequestCount] = useState(0);
  const [documentRequestCount, setDocumentRequestCount] = useState(0);
  const [resignationTemplateInstances, setResignationTemplateInstances] = useState([]);
  const [confirmationTemplateInstances, setConfirmationTemplateInstances] = useState([]);

  useEffect(() => {
    if (privilege) {
      switch (props.title) {
        case 'Upcoming Birthdayss':
          fetchBirthDaysData();
          break;
        case 'Upcoming Anniversaries':
          fetchAnniversaryData();
          break;
        case 'Notices':
          fetchNoticeData();
          break;
        case 'Head count':
          fetchHeadcount();
          break;
        case 'Gender Composition Chart':
          fetchGenderByDepartment();
          break;
      }
    }

    // if (props.pendingCountData && Object.keys(props.pendingCountData).length > 0) {
    //   fetchRequestCount();
    // }
    // if (hasPermitted('document-manager-employee-access')) {
    //   fetchDocumentAcknowledgeCount();
    // }
  }, []);

  useEffect(() => {
    if (Object.keys(props.pendingCountData).length > 0) {
      fetchRequestCount();
    }
  }, [props.pendingCountData]);

  useEffect(() => {
    if (props.acknoledgementDataCount > 0) {
      fetchDocumentAcknowledgeCount();
    }
  }, [props.acknoledgementDataCount]);

  const fetchRequestCount = async () => {
    try {
      const data = props.pendingCountData;

      setLeaveRequestCount(data.leaveCount);
      setTimeChangeRequestCount(data.timeChangeCount);
      setClaimRequestCount(data.claimRequestCount);
      setPostOtRequestCount(data.postOtRequestCount);
      setLeaveCoveringRequestCount(data.leaveCoveringPersonRequestsCount);
      setCancelLeaveRequestCount(data.cancelLeaveRequestCount);
      setResignationRequestCount(data.resignationRequestCount);
      setShiftChangeRequestCount(data.shiftChangeRequestCount);
      setProfileRequestCount(data.profileCount);
      setShortLeaveRequestCount(data.shortLeaveCount);
      setCancelShortLeaveRequestCount(data.cancelShortLeaveRequestCount);
      setResignationTemplateInstances(data.resignationTemplateInstances);
      setConfirmationTemplateInstances(data.confirmationTemplateInstances);
    } catch (error) {
      console.log(error);
    }
  };

  const fetchDocumentAcknowledgeCount = async () => {
    try {
      setDocumentRequestCount(props.acknoledgementDataCount);
    } catch (error) {
      console.log(error);
    }
  };
  const fetchNoticeData = async () => {
    setLoading(true);

    let noticeCategories = {};
    let _noticeCategories = [];

    const responseNoticeCategory = await getAllNoticeCategory();
    if (responseNoticeCategory && responseNoticeCategory.data) {
      _noticeCategories = responseNoticeCategory.data.map((category) => {
        noticeCategories[category.id] = category.name;
        return {
          label: category.name,
          value: category.id,
        };
      });

      _noticeCategories.unshift({ label: 'All', value: 0 });
    }

    const responseLastPublishedNotices = await getLastPublishedNotices(privilege);
    if (responseLastPublishedNotices && responseLastPublishedNotices.data) {
      const groupedNotices = Object.values(responseLastPublishedNotices.data).reduce(function (
        accumulator,
        currentValue,
      ) {
        (accumulator[currentValue['noticeCategoryId']] =
          accumulator[currentValue['noticeCategoryId']] || []).push(currentValue);
        return accumulator;
      },
      {});

      const notices = Object.keys(groupedNotices).map((noticeCategoryId) => {
        let noticeCategory = noticeCategories[noticeCategoryId] ?? 'Default';
        return {
          noticeCategoryId,
          noticeCategory,
          notices: groupedNotices[noticeCategoryId],
        };
      });

      setExtraData({ ...extraData, noticeCategories: _noticeCategories, notices: notices });
      setData(notices);
    }

    setLoading(false);
  };
  const fetchBirthDaysData = async () => {
    setLoading(true);
    await getUpcomingBirthDays(privilege).then((response) => {
      if (response && response.data) {
        setData(response.data);
      }
    });
    setLoading(false);
  };
  const fetchAnniversaryData = async () => {
    setLoading(true);
    await getUpcomingHiredDays(privilege).then((response) => {
      if (response && response.data) {
        setData(response.data);
      }
    });
    setLoading(false);
  };
  const fetchHeadcount = async () => {
    const response = await queryReportById(privilege, 'head-count');
    setHeadCountData(response.data);
  };
  const fetchGenderByDepartment = async () => {
    const genderData = await queryChartById(privilege, '1');
    setGenderByDept(genderData.data.data);
  };

  switch (props.title) {
    case 'Upcoming Birthdays':
      imgSVG = birthdays;
      break;
    case 'Upcoming Anniversaries':
      imgSVG = anniversaries;
      break;
    case 'To Do':
      viewMore = (
        <Access
          accessible={
            hasPermitted('admin-widgets') ||
            hasPermitted('employee-widgets') ||
            hasPermitted('manager-widgets')
          }
        >
          <a
            onClick={() => {
              history.push(viewMoreLink);
            }}
          >
            {props.viewMoreText}
          </a>
        </Access>
      );
      break;
    case 'Notices':
      viewMore = (
        <Access
          accessible={
            hasPermitted('company-notice-read-write') || hasPermitted('team-notice-read-write')
          }
        >
          <a
            onClick={() => {
              history.push(viewMoreLink);
            }}
          >
            {props.viewMoreText}
          </a>
        </Access>
      );
      nextToTitle = (
        <Select
          loading={loading}
          style={{ width: 150, marginLeft: 10 }}
          options={extraData?.noticeCategories}
          defaultValue={
            extraData?.noticeCategories ? extraData?.selectedNoticeCategories ?? 0 : undefined
          }
          value={extraData?.selectedNoticeCategories}
          onChange={(selectedOption) => {
            setExtraData({ ...extraData, selectedNoticeCategories: selectedOption });
            if (selectedOption == 0) {
              setData(extraData?.notices);
            } else {
              setData(
                extraData?.notices.filter((group) => group.noticeCategoryId == selectedOption),
              );
            }
          }}
        />
      );
      break;
  }

  const setUpTag = async () => {
    let _tags;
    if (props.fieldData === 'tinyView') {
      _tags = (
        <tr>
          <td>
            <div
              className={'testCss'}
              style={{
                height: 220,
                width: '100%',
                overflowY: 'scroll',
                // background: '#f2f2f2',
              }}
            >
              <table
                style={{
                  width: '96%',
                }}
              >
                <tbody>
                  {data.map((item: any, index: number) => {
                    return (
                      <tr key={'tinyViewRow' + Math.random()}>
                        <td
                          colSpan={2}
                          style={{
                            width: '100%',
                            height: 100,
                          }}
                        >
                          <TinyModelLayout data={item} />
                        </td>
                      </tr>
                    );
                  })}
                </tbody>
              </table>
            </div>
          </td>
        </tr>
      );
    } else if (props.fieldData === 'tableView' && props.fields) {
      _tags = props.fields.map((row: any, rowIndex: number) => {
        return (
          <tr key={'tableViewRow' + Math.random()}>
            {row.rowData.map((col: any, index: number) => {
              let iconSVG;
              let route;
              switch (col.title) {
                case 'My Team':
                  iconSVG = myteam;
                  break;
                case 'My Info':
                  iconSVG = myinfo;
                  break;
                case 'Organization':
                  iconSVG = organization;
                  break;
                case 'Reports':
                  iconSVG = reports;
                  break;
                case 'Settings':
                  iconSVG = settings;
                  break;
              }
              return (
                <Access accessible={canShowShortcuts(col.link)}>
                  <td
                    style={{ width: '33%', height: 110, textAlign: 'center' }}
                    onClick={() => {
                      history.push(col.link);
                    }}
                  >
                    {/* <div style={{ width: '100%', alignItems: 'center' }}>
                      <table style={{ width: '100%' }}>
                        <tbody>
                          <tr
                            style={{
                              textAlign: 'center',
                              verticalAlign: 'middle',
                            }}
                          >
                            <td
                              style={{
                                textAlign: 'center',
                                verticalAlign: 'middle',
                              }}
                            >
                              <Avatar src={iconSVG} size={64} />
                            </td>
                          </tr>
                          <tr
                            style={{
                              textAlign: 'center',
                              verticalAlign: 'middle',
                            }}
                          >
                            <td
                              style={{
                                textAlign: 'center',
                                verticalAlign: 'middle',
                              }}
                            >
                              <span>{col.title}</span>
                            </td>
                          </tr>
                        </tbody>
                      </table>
                    </div> */}
                  </td>
                </Access>
              );
            })}
          </tr>
        );
      });
    } else if (props.fieldData === 'rowView') {
      _tags = (
        <tr>
          <td>
            <div
              className={'testCss'}
              style={{
                height: 200,
                width: '100%',
                overflowY: 'scroll',
                // background: '#f2f2f2',
              }}
            >
              <table
                style={{
                  width: '96%',
                }}
              >
                <tbody>
                  {data.map((row: any, index: number) => {
                    const bDay = new Date(row.dateOfBirth);
                    const today = new Date();
                    const year =
                      today.getMonth() > bDay.getMonth()
                        ? today.getFullYear() + 1
                        : today.getMonth() === bDay.getMonth() && today.getDay() > bDay.getDay()
                        ? today.getFullYear() + 1
                        : today.getFullYear();
                    const month = bDay.toLocaleString('default', { month: 'long' });
                    const border = index + 1 === data.length ? '0pt' : '1pt solid black';

                    return (
                      <tr
                        style={{
                          borderBottom: border,
                          height: 60,
                        }}
                        key={'rowViewRow' + Math.random()}
                      >
                        <td style={{ width: '20%' }}>
                          <Avatar src={row.profilePic} size={32} />
                        </td>
                        <td
                          style={{
                            width: '80%',
                          }}
                        >
                          <table>
                            <tbody>
                              <tr>
                                <td>
                                  <span style={{ fontSize: '13px' }}>{row.name}</span>
                                </td>
                              </tr>
                              <tr>
                                <td>
                                  <span style={{ fontSize: '13px' }}>
                                    {month} {row.day}, {year}
                                  </span>
                                </td>
                              </tr>
                            </tbody>
                          </table>
                        </td>
                      </tr>
                    );
                  })}
                </tbody>
              </table>
            </div>
          </td>
        </tr>
      );
    } else if (props.fieldData === 'barChartView') {
      _tags = (
        <tr>
          <td style={{ paddingTop: 10 }}>
            <GroupBarChart cardHeight={props.cardHeight} data={genderByDeptData} />
          </td>
        </tr>
      );
    } else if (props.fieldData === 'pieChartLiquidView') {
      _tags = (
        <tr>
          <td>
            <PieChartLiquid />
          </td>
        </tr>
      );
    } else if (props.fieldData === 'barLineView') {
      _tags = (
        <tr>
          <td style={{ paddingTop: 10 }}>
            <BarChart data={headCountData} />
          </td>
        </tr>
      );
    } else if (props.fieldData === 'gaugeChartView') {
      _tags = (
        <tr>
          <td>
            <GaugeChart />
          </td>
        </tr>
      );
    } else if (props.fieldData === 'areaChartView') {
      _tags = (
        <tr>
          <td>
            <AreaChart />
          </td>
        </tr>
      );
    } else if (props.fieldData === 'pieChartView') {
      _tags = (
        <tr>
          <td>
            <PieChart />
          </td>
        </tr>
      );
    } else if (props.fieldData === 'ageChartView') {
      _tags = (
        <tr>
          <td>
            <AgeChart />
          </td>
        </tr>
      );
    } else if (props.fieldData === 'report') {
      _tags = (
        <tr>
          <td>
            <ReportWidget
              reportId={props?.data?.reportId}
              setTitle={setTitle}
              width={props.cardWidth}
              height={props.cardHeight}
            />
          </td>
        </tr>
      );
    } else if (props.fieldData === 'toDoView') {
      _tags = (
        <tr>
          <td>
            <div
              className={'testCss'}
              style={{
                height: 200,
                width: '100%',
                overflowY: 'scroll',
              }}
            >
              <table className={styles.table}>
                <tbody>
                  {hasPermitted('todo-request-access') && (
                    <>
                      {(filterType === 'leaveManager' || filterType === 'All') &&
                        (leaveRequestCount != 0 ||
                          shortLeaveRequestCount != 0 ||
                          leaveCoveringRequestCount != 0 ||
                          cancelLeaveRequestCount != 0 ||
                          cancelShortLeaveRequestCount != 0) && (
                          <>
                            <Row>
                              <Col className={styles.categoriesField}>
                                <Text className={styles.subheading}>
                                  {intl.formatMessage({
                                    id: 'pages.todo.Leave',
                                    defaultMessage: 'Leave Manager',
                                  })}
                                </Text>
                              </Col>
                            </Row>

                            {leaveRequestCount != 0 && (
                              <>
                                <Row>
                                  {/* <Col span={2} className={styles.col}>
                                    <span className={styles.leaveOuter}>
                                      <span className={styles.leave}>
                                        <p className={styles.count}>{leaveRequestCount}</p>
                                      </span>
                                    </span>
                                  </Col> */}
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
                                        history.push('/manager-self-service/user-requests');
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

                                <Divider className={styles.dividerField} />
                              </>
                            )}
                            {shortLeaveRequestCount != 0 && (
                              <>
                                <Row>
                                  {/* <Col span={2} className={styles.col}>
                                    <span className={styles.leaveOuter}>
                                      <span className={styles.leave}>
                                        <p className={styles.count}>{shortLeaveRequestCount}</p>
                                      </span>
                                    </span>
                                  </Col> */}
                                  <Col span={18} style={{ textAlign: 'left' }}>
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
                                        history.push('/manager-self-service/user-requests');
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

                                <Divider className={styles.dividerField} />
                              </>
                            )}
                            {leaveCoveringRequestCount != 0 && (
                              <>
                                <Row>
                                  {/* <Col span={2} className={styles.col}>
                                    <span className={styles.leaveOuter}>
                                      <span className={styles.leave}>
                                        <p className={styles.count}>{leaveCoveringRequestCount}</p>
                                      </span>
                                    </span>
                                  </Col> */}
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
                                          defaultMessage: ' View',
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
                                  {/* <Col span={2} className={styles.col}>
                                    <span className={styles.leaveOuter}>
                                      <span className={styles.leave}>
                                        <p className={styles.count}>{cancelLeaveRequestCount}</p>
                                      </span>
                                    </span>
                                  </Col> */}
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
                                        history.push('/manager-self-service/user-requests');
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
                            {cancelShortLeaveRequestCount != 0 && (
                              <>
                                <Row>
                                  {/* <Col span={2} className={styles.col}>
                                    <span className={styles.leaveOuter}>
                                      <span className={styles.leave}>
                                        <p className={styles.count}>
                                          {cancelShortLeaveRequestCount}
                                        </p>
                                      </span>
                                    </span>
                                  </Col> */}
                                  <Col span={18}>
                                    <Text className={styles.primary}>
                                      {intl.formatMessage({
                                        id: 'pages.todo.shortLeave.primary',
                                        defaultMessage: 'Pending Short Leave Cancel Requests ',
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
                                        history.push('/manager-self-service/user-requests');
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
                      {(filterType === 'attendanceManager' || filterType === 'All') &&
                        (timeChangeRequestCount != 0 ||
                          shiftChangeRequestCount != 0 ||
                          postOtRequestCount != 0) && (
                          <>
                            <Row>
                              <Col className={styles.categoriesField}>
                                <Text className={styles.subheading}>
                                  {intl.formatMessage({
                                    id: 'pages.todo.attendance',
                                    defaultMessage: 'Attendance Manager',
                                  })}
                                </Text>
                              </Col>
                            </Row>

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
                                        history.push('/manager-self-service/user-requests');
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
                                <Divider className={styles.dividerField} />
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
                                        history.push('/manager-self-service/user-requests');
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
                                <Divider className={styles.dividerField} />
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
                                        history.push('/manager-self-service/user-requests');
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
                                <Divider className={styles.dividerField} />
                              </>
                            )}
                          </>
                        )}
                      {(filterType === 'profileManager' || filterType === 'All') &&
                        profileRequestCount != 0 && (
                          <>
                            <Row>
                              <Col className={styles.categoriesField}>
                                <Text className={styles.subheading}>
                                  {intl.formatMessage({
                                    id: 'pages.todo.attendance',
                                    defaultMessage: 'Profile Manager',
                                  })}
                                </Text>
                              </Col>
                            </Row>

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
                                    history.push('/manager-self-service/user-requests');
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
                            <Divider className={styles.dividerField} />
                          </>
                        )}
                    </>
                  )}
                  {hasPermitted('document-manager-employee-access') && (
                    <>
                      {(filterType === 'documentManager' || filterType === 'All') &&
                        documentRequestCount != 0 && (
                          <>
                            <Row>
                              <Col className={styles.categoriesField}>
                                <Text className={styles.subheading}>
                                  {intl.formatMessage({
                                    id: 'pages.todo.attendance',
                                    defaultMessage: 'Document Manager',
                                  })}
                                </Text>
                              </Col>
                            </Row>

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
                                    defaultMessage: 'Pending Documents',
                                  })}
                                </Text>
                                <Text className={styles.secondary}>
                                  {intl.formatMessage({
                                    id: 'pages.todo.secondary',
                                    defaultMessage: ' to be acknowledge',
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
                    </>
                  )}
                  {(filterType === 'expenseManager' || filterType === 'All') &&
                    claimRequestCount != 0 && (
                      <>
                        <Row>
                          <Col className={styles.categoriesField}>
                            <Text className={styles.subheading}>
                              {intl.formatMessage({
                                id: 'pages.todo.expence',
                                defaultMessage: 'Expense Manager',
                              })}
                            </Text>
                          </Col>
                        </Row>

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
                                    history.push('/manager-self-service/user-requests');
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
                            <Divider className={styles.dividerField} />
                          </>
                        )}
                      </>
                    )}
                </tbody>
              </table>
            </div>
          </td>
        </tr>
      );
    } else {
      _tags = <p>Nothing</p>;
    }

    setTag(_tags);
  };

  return (
    <Card
      hoverable
      // loading= {loading}
      style={{
        height: props.cardHeight,
        borderRadius: '10px',
        paddingLeft: '8px',
      }}
    >
      <Row style={{ marginBottom: '12px' }}>
        <table style={{ width: '100%' }}>
          <thead style={{}}>
            <tr key={'dashboardWidgetHeader' + Math.random()}>
              {imgSVG ? (
                <td style={{ width: 40 }}>
                  <Avatar src={imgSVG} size={30} />
                </td>
              ) : null}
              <td style={{ verticalAlign: 'middle' }}>
                <Meta title={title ?? props.title} />
              </td>
              {nextToTitle}
              <td className={styles.selectCol}>
                {props.data.filter ? (
                  <>
                    <Select
                      onChange={(value) => {
                        setfilterType(value);
                        console.log(value, 'filter');
                      }}
                      className={styles.select}
                      allowClear={true}
                      defaultValue={filterType}
                    >
                      <Option value="All">
                        {intl.formatMessage({ id: 'ALL', defaultMessage: 'All' })}
                      </Option>
                      <Option value="leaveManager">
                        {intl.formatMessage({
                          id: 'LEAVE_MANAGER',
                          defaultMessage: 'Leave Manager',
                        })}
                      </Option>
                      <Option value="attendanceManager">
                        {intl.formatMessage({
                          id: 'ATTENDANCE_MANAGER',
                          defaultMessage: 'Attendance Manager',
                        })}
                      </Option>
                      <Option value="profileManager">
                        {intl.formatMessage({
                          id: 'PROFILE_MANAGER',
                          defaultMessage: 'Profile Manager',
                        })}
                      </Option>
                      <Option value="documentManager">
                        {intl.formatMessage({
                          id: 'DOCUMENT_MANAGER',
                          defaultMessage: 'Document Manager',
                        })}
                      </Option>
                      <Option value="expenseManager">
                        {intl.formatMessage({
                          id: 'EXPENSE_MANAGER',
                          defaultMessage: 'Expense Manager',
                        })}
                      </Option>
                    </Select>
                  </>
                ) : (
                  <></>
                )}
              </td>
              <td style={{ textAlign: 'right' }}>{viewMore}</td>
            </tr>
          </thead>
        </table>
      </Row>
      <Divider />
      <Row>
        <table style={{ width: '100%', borderCollapse: 'collapse' }}>
          <tbody>
            {!loading && props.fieldData === 'tinyView' ? (
              <tr>
                <td>
                  <div
                    className={'testCss'}
                    style={{
                      height: 165,
                      width: '100%',
                      overflowY: 'scroll',
                      // background: '#f2f2f2',
                      padding: 20,
                    }}
                  >
                    <table
                      style={{
                        width: '96%',
                      }}
                    >
                      <tbody>
                        {data.map((item: any, index: number) => {
                          return (
                            <tr key={'tinyViewRow' + Math.random()}>
                              <td
                                colSpan={2}
                                style={{
                                  width: '100%',
                                }}
                              >
                                <h5>{item.noticeCategory}</h5>
                                {item.notices.map((subitem) => (
                                  <TinyModelLayout data={subitem} />
                                ))}
                              </td>
                            </tr>
                          );
                        })}
                      </tbody>
                    </table>
                  </div>
                </td>
              </tr>
            ) : props.fieldData === 'tableView' && props.fields ? (
              props.fields.map((row: any, rowIndex: number) => {
                return (
                  <tr key={'tableViewRow' + Math.random()}>
                    {row.rowData.map((col: any, index: number) => {
                      let iconSVG;
                      let route;
                      switch (col.title) {
                        case 'My Team':
                          iconSVG = myteam;
                          break;
                        case 'My Info':
                          iconSVG = myinfo;
                          break;
                        case 'Organization':
                          iconSVG = organization;
                          break;
                        case 'Reports':
                          iconSVG = reports;
                          break;
                        case 'Settings':
                          iconSVG = settings;
                          break;
                      }
                      return (
                        <Access accessible={canShowShortcuts(col.link)}>
                          <td
                            style={{ width: '33%', height: 110, textAlign: 'center' }}
                            onClick={() => {
                              history.push(col.link);
                            }}
                          >
                            <div style={{ width: '100%', alignItems: 'center' }}>
                              <table style={{ width: '100%' }}>
                                <tbody>
                                  <tr
                                    style={{
                                      textAlign: 'center',
                                      verticalAlign: 'middle',
                                    }}
                                  >
                                    <td
                                      style={{
                                        textAlign: 'center',
                                        verticalAlign: 'middle',
                                      }}
                                    >
                                      <Avatar src={iconSVG} size={64} />
                                    </td>
                                  </tr>
                                  <tr
                                    style={{
                                      textAlign: 'center',
                                      verticalAlign: 'middle',
                                    }}
                                  >
                                    <td
                                      style={{
                                        textAlign: 'center',
                                        verticalAlign: 'middle',
                                      }}
                                    >
                                      <span>{col.title}</span>
                                    </td>
                                  </tr>
                                </tbody>
                              </table>
                            </div>
                          </td>
                        </Access>
                      );
                    })}
                  </tr>
                );
              })
            ) : props.fieldData === 'rowView' ? (
              <tr>
                <td>
                  <div
                    className={'testCss'}
                    style={{
                      height: 165,
                      width: '100%',
                      overflowY: 'scroll',
                      padding: 20,
                      // background: '#f2f2f2',
                    }}
                  >
                    <table
                      style={{
                        width: '96%',
                      }}
                    >
                      <tbody>
                        {data.map((row: any, index: number) => {
                          const bDay = new Date(row.dateOfBirth);
                          const today = new Date();
                          const year =
                            today.getMonth() > bDay.getMonth()
                              ? today.getFullYear() + 1
                              : today.getMonth() === bDay.getMonth() &&
                                today.getDay() > bDay.getDay()
                              ? today.getFullYear() + 1
                              : today.getFullYear();
                          const month = bDay.toLocaleString('default', { month: 'long' });
                          const border = index + 1 === data.length ? '0pt' : '1pt solid black';

                          return (
                            <tr
                              style={{
                                borderBottom: border,
                                height: 20,
                              }}
                              key={'rowViewRow' + Math.random()}
                            >
                              <td style={{ width: '20%' }}>
                                <Avatar src={row.profilePic} size={32} />
                              </td>
                              <td
                                style={{
                                  width: '80%',
                                }}
                              >
                                <table>
                                  <tbody>
                                    <tr>
                                      <td>
                                        <span style={{ fontSize: '13px' }}>{row.name}</span>
                                      </td>
                                    </tr>
                                    <tr>
                                      <td>
                                        <span style={{ fontSize: '13px' }}>
                                          {month} {row.day}
                                        </span>
                                      </td>
                                    </tr>
                                  </tbody>
                                </table>
                              </td>
                            </tr>
                          );
                        })}
                      </tbody>
                    </table>
                  </div>
                </td>
              </tr>
            ) : props.fieldData === 'barChartView' ? (
              <tr>
                <td style={{ paddingTop: 10 }}>
                  <GroupBarChart cardHeight={props.cardHeight} data={genderByDeptData} />
                </td>
              </tr>
            ) : props.fieldData === 'pieChartLiquidView' ? (
              <tr>
                <td>
                  <PieChartLiquid />
                </td>
              </tr>
            ) : props.fieldData === 'barLineView' ? (
              <tr>
                <td style={{ paddingTop: 10 }}>
                  <BarChart data={headCountData} />
                </td>
              </tr>
            ) : props.fieldData === 'gaugeChartView' ? (
              <tr>
                <td>
                  <GaugeChart />
                </td>
              </tr>
            ) : props.fieldData === 'areaChartView' ? (
              <tr>
                <td>
                  <AreaChart />
                </td>
              </tr>
            ) : props.fieldData === 'pieChartView' ? (
              <tr>
                <td>
                  <PieChart />
                </td>
              </tr>
            ) : props.fieldData === 'ageChartView' ? (
              <tr>
                <td>
                  <AgeChart />
                </td>
              </tr>
            ) : props.fieldData === 'report' ? (
              <tr>
                <td>
                  <ReportWidget
                    reportId={props?.data?.reportId}
                    setTitle={setTitle}
                    width={props.cardWidth}
                    height={props.cardHeight}
                  />
                </td>
              </tr>
            ) : props.fieldData === 'toDoView' ? (
              <tr>
                <td>
                  <div
                    className={'testCss'}
                    style={{
                      height: 165,
                      width: '100%',
                      overflowY: 'scroll',
                      padding: 20,
                    }}
                  >
                    <table className={styles.table}>
                      <tbody>
                        {hasPermitted('todo-request-access') && (
                          <>
                            {(filterType === 'leaveManager' || filterType === 'All') &&
                              (leaveRequestCount != 0 ||
                                shortLeaveRequestCount != 0 ||
                                leaveCoveringRequestCount != 0 ||
                                cancelLeaveRequestCount != 0 ||
                                cancelShortLeaveRequestCount != 0) && (
                                <>
                                  <Row>
                                    <Col className={styles.categoriesField}>
                                      <Text className={styles.subheading}>
                                        {intl.formatMessage({
                                          id: 'pages.todo.Leave',
                                          defaultMessage: 'Leave Manager',
                                        })}
                                      </Text>
                                    </Col>
                                  </Row>

                                  {leaveRequestCount != 0 && (
                                    <>
                                      <Row style={{ marginTop: 0, marginBottom: 0 }}>
                                        {/* <Col span={2} className={styles.col}>
                                          <span className={styles.leaveOuter}>
                                            <span className={styles.leave}>
                                              <p className={styles.count}>{leaveRequestCount}</p>
                                            </span>
                                          </span>
                                        </Col> */}
                                        <Col span={20}>
                                          <Text className={styles.primary}>
                                            {intl.formatMessage({
                                              id: 'pages.todo.Leave.primary',
                                              defaultMessage: 'Pending  Leave Requests ',
                                            })}
                                          </Text>
                                          <br />
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
                                                state: { tabKey: '2' },
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

                                      <Divider className={styles.dividerField} />
                                    </>
                                  )}
                                  {shortLeaveRequestCount != 0 && (
                                    <>
                                      <Row>
                                        <Col span={2} className={styles.col}>
                                          <span className={styles.leaveOuter}>
                                            <span className={styles.leave}>
                                              <p className={styles.count}>
                                                {shortLeaveRequestCount}
                                              </p>
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
                                                state: { tabKey: '4' },
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

                                      <Divider className={styles.dividerField} />
                                    </>
                                  )}
                                  {leaveCoveringRequestCount != 0 && (
                                    <>
                                      <Row>
                                        <Col span={2} className={styles.col}>
                                          <span className={styles.leaveOuter}>
                                            <span className={styles.leave}>
                                              <p className={styles.count}>
                                                {leaveCoveringRequestCount}
                                              </p>
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
                                                defaultMessage: ' View',
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
                                              <p className={styles.count}>
                                                {cancelLeaveRequestCount}
                                              </p>
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
                                                state: { tabKey: '6' },
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
                                  {cancelShortLeaveRequestCount != 0 && (
                                    <>
                                      <Row>
                                        <Col span={2} className={styles.col}>
                                          <span className={styles.leaveOuter}>
                                            <span className={styles.leave}>
                                              <p className={styles.count}>
                                                {cancelShortLeaveRequestCount}
                                              </p>
                                            </span>
                                          </span>
                                        </Col>
                                        <Col span={18}>
                                          <Text className={styles.primary}>
                                            {intl.formatMessage({
                                              id: 'pages.todo.shortLeave.primary',
                                              defaultMessage:
                                                'Pending Short Leave Cancel Requests ',
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
                                                state: { tabKey: '8' },
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
                            {(filterType === 'attendanceManager' || filterType === 'All') &&
                              (timeChangeRequestCount != 0 ||
                                shiftChangeRequestCount != 0 ||
                                postOtRequestCount != 0) && (
                                <>
                                  <Row>
                                    <Col className={styles.categoriesField}>
                                      <Text className={styles.subheading}>
                                        {intl.formatMessage({
                                          id: 'pages.todo.attendance',
                                          defaultMessage: 'Attendance Manager',
                                        })}
                                      </Text>
                                    </Col>
                                  </Row>

                                  {timeChangeRequestCount != 0 && (
                                    <>
                                      <Row>
                                        <Col span={2} className={styles.col}>
                                          <span className={styles.attendanceOuter}>
                                            <span className={styles.attendance}>
                                              <p className={styles.count}>
                                                {timeChangeRequestCount}
                                              </p>
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
                                                state: { tabKey: '3' },
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
                                      <Divider className={styles.dividerField} />
                                    </>
                                  )}
                                  {shiftChangeRequestCount != 0 && (
                                    <>
                                      <Row>
                                        <Col span={2} className={styles.col}>
                                          <span className={styles.attendanceOuter}>
                                            <span className={styles.attendance}>
                                              <p className={styles.count}>
                                                {shiftChangeRequestCount}
                                              </p>
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
                                                state: { tabKey: '5' },
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
                                      <Divider className={styles.dividerField} />
                                    </>
                                  )}
                                  {postOtRequestCount != 0 && (
                                    <>
                                      <Row>
                                        {/* <Col span={2} className={styles.col}>
                                          <span className={styles.attendanceOuter}>
                                            <span className={styles.attendance}>
                                              <p className={styles.count}>{postOtRequestCount}</p>
                                            </span>
                                          </span>
                                        </Col> */}
                                        <Col span={20}>
                                          <Text className={styles.primary}>
                                            {intl.formatMessage({
                                              id: 'pages.todo.post_ot.primary',
                                              defaultMessage: 'Pending Post OT Requests',
                                            })}
                                          </Text>
                                          <br />
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
                                                state: { tabKey: '10' },
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
                                      <Divider className={styles.dividerField} />
                                    </>
                                  )}
                                </>
                              )}
                            {(((filterType === 'profileManager' || filterType === 'All') &&
                              profileRequestCount != 0) ||
                              resignationRequestCount != 0) && (
                              <>
                                <Row>
                                  <Col className={styles.categoriesField}>
                                    <Text className={styles.subheading}>
                                      {intl.formatMessage({
                                        id: 'pages.todo.attendance',
                                        defaultMessage: 'Profile Manager',
                                      })}
                                    </Text>
                                  </Col>
                                </Row>
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
                                              state: { tabKey: '1' },
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
                                    <Divider className={styles.dividerField} />
                                  </>
                                )}
                                {resignationRequestCount != 0 && (
                                  <>
                                    <Row>
                                      <Col span={2} className={styles.col}>
                                        <span className={styles.profileOuter}>
                                          <span className={styles.profile}>
                                            <p className={styles.count}>
                                              {resignationRequestCount}
                                            </p>
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
                                              state: { tabKey: '7' },
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
                                    <Divider className={styles.dividerField} />
                                  </>
                                )}
                              </>
                            )}
                          </>
                        )}
                        {hasPermitted('document-manager-employee-access') && (
                          <>
                            {(filterType === 'documentManager' || filterType === 'All') &&
                              documentRequestCount != 0 && (
                                <>
                                  <Row>
                                    <Col className={styles.categoriesField}>
                                      <Text className={styles.subheading}>
                                        {intl.formatMessage({
                                          id: 'pages.todo.attendance',
                                          defaultMessage: 'Document Manager',
                                        })}
                                      </Text>
                                    </Col>
                                  </Row>

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
                                    <p className={styles.count}>
                                      {resignationTemplateInstances.length}
                                    </p>
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
                                    <p className={styles.count}>
                                      {confirmationTemplateInstances.length}
                                    </p>
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
                        <Divider className={styles.dividerField} />
                        {(filterType === 'expenseManager' || filterType === 'All') &&
                          claimRequestCount != 0 && (
                            <>
                              <Row>
                                <Col className={styles.categoriesField}>
                                  <Text className={styles.subheading}>
                                    {intl.formatMessage({
                                      id: 'pages.todo.expense',
                                      defaultMessage: 'Expense Manager',
                                    })}
                                  </Text>
                                </Col>
                              </Row>

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
                                            state: { tabKey: '9' },
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
                                  <Divider className={styles.dividerField} />
                                </>
                              )}
                            </>
                          )}
                      </tbody>
                    </table>
                  </div>
                </td>
              </tr>
            ) : (
              <Spin style={{ height: '100%', width: '100%' }} />
            )}
            {/* </Skeleton> */}
          </tbody>
        </table>
      </Row>
    </Card>
  );
};

export default CardLayout;
