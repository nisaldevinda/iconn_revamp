import React, { useEffect, useRef, useState } from 'react';
import { getAllReports, printToPdf, removeReport } from '@/services/reportService';
import ProTable, { ProColumns, ActionType } from '@ant-design/pro-table';
import {
  Space,
  Select,
  Divider,
  Button,
  Tooltip,
  Popconfirm,
  Message,
  Row,
  Col,
  Form,
  Tag,
  Typography,
  Switch,
} from 'antd';
import { history, useAccess, Access } from 'umi';
import PermissionDeniedPage from './../403';
import { FormattedMessage, useIntl } from 'react-intl';

import _, { forEach, reduce } from 'lodash';
import { PageContainer } from '@ant-design/pro-layout';
import {
  DeleteOutlined,
  EditOutlined,
  EyeOutlined,
  PlusOutlined,
  SortAscendingOutlined,
} from '@ant-design/icons';
import request, { APIResponse } from '@/utils/request';
import { getPrivileges } from '@/utils/permission';
import {
  addLeaveEntitlement,
  deleteLeaveEntitlement,
  getAllLeaveEntitlement,
  getExistingEmployees,
  getExistingLeavePeriods,
  getExistingLeaveTypes,
  getLeaveType,
  getLeaveTypes,
  getMyLeaveEntitlement,
  updateLeaveEntitlement,
} from '@/services/leaveEntitlment';
import { getModel } from '@/services/model';
import ProForm, {
  DrawerForm,
  ProFormDatePicker,
  ProFormDigit,
  ProFormRadio,
  ProFormSelect,
  ProFormTextArea,
} from '@ant-design/pro-form';
import form from 'antd/lib/form';
import moment from 'moment';
import { getCompany } from '@/services/company';
import { getEmployee } from '@/services/employee';
import styles from './index.less';
import { getEmployeeList, getLeaveTypesList, getAllEmployeeList } from '@/services/dropdown';

const LeaveEntitlements: React.FC = () => {
  const access = useAccess();
  const { hasPermitted } = access;
  const intl = useIntl();
  const actionRef = useRef<ActionType>();
  const privilege = getPrivileges();
  const [leaveTypes, setLeaveTypes] = useState([]);
  const [model, setModel] = useState([]);
  const [drawerVisit, setDrawerVisit] = useState(false);
  const [form] = Form.useForm();
  const { Text } = Typography;
  const [searchForm] = Form.useForm();
  const [allEntitlements, setAllEntitlements] = useState([]);
  const [selectedLeaveType, setSelectedLeaveType] = useState('');
  const [selectedEmployee, setSelectedEmployee] = useState('');
  const [leavePeriodArray, setLeavePeriodArray] = useState([]);
  const [isLeavePeriodDisabled, setIsLeavePeriodDisabled] = useState(true);
  const [leavePeriodEnum, setLeavePeriodEnum] = useState({});
  const [selectedLeavePeriodType, setSelectedLeavePeriodType] = useState('');
  const [selectedEntitlementType, setSelectedEntitlementType] = useState('');
  const [selectedLeavePeriod, setSelectedLeavePeriod] = useState<any>(null);
  const [leavePeriodStartDate, setLeavePeriodStartDate] = useState('');
  const [leavePeriodEndate, setLeavePeriodEndDate] = useState('');
  const [leavePeriodArrayForm, setLeavePeriodArrayFrom] = useState([]);
  const [selectedRecordId, setSelectedRecordId] = useState('');
  const [availableEmployeesWithInactive, setAvailableEmployeesWithInactive] = useState([]);
  const [availableEmployeesWithoutInactive, setAvailableEmployeesWithoutInactive] = useState([]);
  const [existingLeaveTypesArr, setExistingLeaveTypes] = useState([]);
  const [utilizedCount, setUtilizedCount] = useState(0);
  const [relatedLeaveTypes, setRelatedLeaveTypes] = useState<any>([]);
  const [adminsCanAdjust, setAdminsCanAdjust] = useState(false);
  const [filteredData, setFilteredData] = useState([]);
  const [isEnableInactiveEmployees, setIsEnableInactiveEmployees] = useState<any>(false);

  useEffect(() => {
    fetchDropDownData();
    fetchEmployees();
    fetchLeaveTypes();
    fetchExistingLeaveTypes();
  }, []);

  useEffect(() => {
    getExistingLeavePeriods({
      selectedLeaveType: selectedLeaveType,
      selectedEmployee: selectedEmployee,
    }).then(async (res) => {
      await setLeavePeriodArray(res.data);
    });
    searchForm.resetFields(['id']);
  }, [selectedLeaveType, selectedEmployee]);

  useEffect(() => {
    calculateLeavePeriod();
  }, [selectedLeavePeriodType]);

  useEffect(() => {
    if (Object.keys(leavePeriodEnum).length > 0) {
      Object.entries(leavePeriodEnum).forEach(([key, value]) => {
        var label =
          moment(leavePeriodStartDate, 'YYYY-MM-DD').format('DD-MM-YYYY') +
          ' to ' +
          moment(leavePeriodEndate, 'YYYY-MM-DD').format('DD-MM-YYYY');
        if (value == label) {
          setSelectedLeavePeriod(key);
          form.setFieldsValue({
            leavePeriod: key,
          });
        }
      });
    }
  }, [leavePeriodEnum]);

  const fetchExistingEntitlements = async (filterData) => {
    const response = await getAllLeaveEntitlement({ filter: { ...filterData } });
    setFilteredData(filterData);
    setAllEntitlements(response.data);
  };
  const fetchMyEntitlements = async () => {
    const response = await getMyLeaveEntitlement();
    setAllEntitlements(response.data);
  };
  useEffect(() => {
    if (!hasPermitted('leave-entitlement-write')) {
      fetchMyEntitlements();
    }
  }, []);

  const fetchEmployees = async () => {
    try {
      const { data } = await getAllEmployeeList('ADMIN');
      const employeesWithInactive = data.map((employee: any) => {
        return {
          label: employee.employeeNumber + ' | ' + employee.employeeName,
          value: employee.id,
        };
      });
      const employeesWithoutInactive = data.map((employee: any) => {
        if (employee.isActive) {
          return {
            label: employee.employeeNumber + ' | ' + employee.employeeName,
            value: employee.id,
          };
        }
      });

      setAvailableEmployeesWithInactive(employeesWithInactive);
      setAvailableEmployeesWithoutInactive(employeesWithoutInactive);
    } catch (err) {
      console.log(err);
    }
  };

  const fetchDropDownData = async () => {
    try {
      const leaveTypesArr = await getLeaveTypesList('ADMIN');
      const leaveTypes = leaveTypesArr.data.map((el: any) => {
        return {
          label: el.name,
          value: el.id,
        };
      });
      setRelatedLeaveTypes(leaveTypes);
    } catch (err) {
      console.log(err);
    }
  };

  const onFormChange = async (val, oth) => {
    for (const [key, value] of Object.entries(val)) {
      if (key === 'leaveTypeId') {
        const leaveTypeResponse = getLeaveType(value);
        await setSelectedLeavePeriodType((await leaveTypeResponse).data.leavePeriod);
      }
      if (key === 'leavePeriod') {
        setLeavePeriodStartDate(
          moment(leavePeriodArrayForm[value].leavePeriodStartDate, 'DD-MM-YYYY').format(
            'YYYY-MM-DD',
          ),
        );
        setLeavePeriodEndDate(
          moment(leavePeriodArrayForm[value].leavePeriodEndate, 'DD-MM-YYYY').format('YYYY-MM-DD'),
        );

        form.setFieldsValue({
          effectiveDate: moment(leavePeriodArrayForm[value].leavePeriodStartDate, 'DD-MM-YYYY'),
          expiryDate: moment(leavePeriodArrayForm[value].leavePeriodEndate, 'DD-MM-YYYY'),
        });
      }
    }
  };
  const generateLeaveTypeEnum = () => {
    const enumV = {};
    if (leaveTypes) {
      leaveTypes.forEach((element) => {
        enumV[element.value] = element.label;
      });
    }
    return enumV;
  };
  const fetchLeaveTypes = async () => {
    const actions = [];
    const response = await getModel('leaveType');
    const modelResponse = await getModel('leaveEntitlement');
    setModel(modelResponse.data.modelDataDefinition.fields.type.values);
    let path: string;
    if (!_.isEmpty(response.data)) {
      path = `/api${response.data.modelDataDefinition.path}`;
    }
    const res = await request(path, {
      sorter: {
        name: 'ascend',
      },
    });
    await res.data.forEach(async (element: any, i: number) => {
      await actions.push({ value: element['id'], label: element['name'] });
    });
    setLeaveTypes(actions);
  };

  const fetchExistingLeaveTypes = async () => {
    const existingLeaveArr = await getLeaveTypes();
    setExistingLeaveTypes(existingLeaveArr.data);
  };
  const searchFormOnFinish = (e) => {
    fetchExistingEntitlements(e);
  };

  const generateEnum = () => {
    const valueEnum = {};
    //const enumV=model
    model.forEach((element) => {
      valueEnum[element.value] = {
        text: element.defaultLabel,
      };
    });
    return valueEnum;
  };

  const getOptions = async (name) => {
    const actions: any = [];
    const response = await getModel(name);
    let path: string;
    if (!_.isEmpty(response.data)) {
      path = `/api${response.data.modelDataDefinition.path}`;
    }
    const res = await request(path);

    await res.data.forEach(async (element: any, i: number) => {
      if (name === 'employee') {
        await actions.push({
          value: element['id'],
          label: `${element['firstName']} ${element['lastName']}`,
        });
      } else {
        await actions.push({ value: element['id'], label: element['name'] });
      }
    });
    return actions;
  };

  const calculateLeavePeriod = async () => {
    let currentYearStartDate;
    let currentYearEndDate;
    let nextYearStartDate;
    let nextYearEndDate;
    let lastYearStartDate;
    let lastYearEndDate;

    if (selectedLeavePeriodType != '' && selectedLeavePeriodType === 'STANDARD') {
      const companyResponse = await getCompany();
      let leavePeriodStartingMonth;
      let leavePeriodEndingMonth;
      if (companyResponse) {
        leavePeriodStartingMonth = companyResponse.data.leavePeriodStartingMonth;
        leavePeriodEndingMonth = companyResponse.data.leavePeriodEndingMonth;
      }
      // moment(dateFrom).subtract(1,'years').endOf('month').format('DD-MM-YYYY')
      currentYearStartDate = moment([moment().year(), parseInt(leavePeriodStartingMonth) - 1])
        .startOf('month')
        .format('DD-MM-YYYY');
      currentYearEndDate = moment([moment().year(), parseInt(leavePeriodEndingMonth) - 1])
        .endOf('month')
        .format('DD-MM-YYYY');
      nextYearStartDate = moment(currentYearStartDate, 'DD-MM-YYYY')
        .add(1, 'year')
        .format('DD-MM-YYYY');
      nextYearEndDate = moment(currentYearEndDate, 'DD-MM-YYYY')
        .add(1, 'year')
        .format('DD-MM-YYYY');
      lastYearStartDate = moment(currentYearStartDate, 'DD-MM-YYYY')
        .subtract(1, 'year')
        .format('DD-MM-YYYY');
      lastYearEndDate = moment(currentYearEndDate, 'DD-MM-YYYY')
        .subtract(1, 'year')
        .format('DD-MM-YYYY');
      // setIsLeavePeriodDisabled(false)
    } else if (selectedLeavePeriodType != '' && selectedLeavePeriodType == 'HIRE_DATE_BASED') {
      form.setFieldsValue({
        leavePeriod: undefined,
      });
      const employeeResponce = await getEmployee(selectedEmployee);
      if (employeeResponce.data) {
        const hireDate = employeeResponce.data.hireDate;
        const hireDateObject = moment(hireDate, 'YYYY-MM-DD');
        if (hireDateObject.isValid()) {
          currentYearStartDate = moment([
            moment().year(),
            hireDateObject.month(),
            hireDateObject.date(),
          ]).format('DD-MM-YYYY');
          currentYearEndDate = moment([
            moment().year(),
            hireDateObject.month(),
            hireDateObject.date(),
          ])
            .subtract(1, 'day')
            .format('DD-MM-YYYY');
          currentYearEndDate = moment(currentYearEndDate, 'DD-MM-YYYY')
            .add(1, 'year')
            .format('DD-MM-YYYY');
          nextYearStartDate = moment(currentYearStartDate, 'DD-MM-YYYY')
            .add(1, 'year')
            .format('DD-MM-YYYY');
          nextYearEndDate = moment(currentYearEndDate, 'DD-MM-YYYY')
            .add(1, 'year')
            .format('DD-MM-YYYY');
          lastYearStartDate = moment(currentYearStartDate, 'DD-MM-YYYY')
            .subtract(1, 'year')
            .format('DD-MM-YYYY');
          lastYearEndDate = moment(currentYearEndDate, 'DD-MM-YYYY')
            .subtract(1, 'year')
            .format('DD-MM-YYYY');
          form.setFields([
            {
              name: 'leavePeriod',
              errors: [],
            },
          ]);
        } else {
          form.setFields([
            {
              name: 'leavePeriod',
              errors: ['Employee does not have a Hire Date'],
            },
          ]);
          setLeavePeriodArray([]);
          setLeavePeriodEnum({});
          return;
        }
      }
      // setIsLeavePeriodDisabled(false)
    }
    setLeavePeriodArrayFrom([
      {
        leavePeriodStartDate: lastYearStartDate,
        leavePeriodEndate: lastYearEndDate,
      },
      {
        leavePeriodStartDate: currentYearStartDate,
        leavePeriodEndate: currentYearEndDate,
      },
      {
        leavePeriodStartDate: nextYearStartDate,
        leavePeriodEndate: nextYearEndDate,
      },
    ]);
    setLeavePeriodEnum({
      0: `${lastYearStartDate} to ${lastYearEndDate}`,
      1: `${currentYearStartDate} to ${currentYearEndDate}`,
      2: `${nextYearStartDate} to ${nextYearEndDate}`,
    });
  };

  const deleteEntitlements = async (id) => {
    try {
      const { message } = await deleteLeaveEntitlement(id);
      Message.success(message);
      actionRef.current?.reload();

      fetchExistingEntitlements(filteredData);
    } catch (error) {
      if (!_.isEmpty(error.message)) {
        let errorMessage;
        let errorMessageInfo;
        if (error.message.includes('.')) {
          let errorMessageData = error.message.split('.');
          errorMessage = errorMessageData.slice(0, 1);
          errorMessageInfo = errorMessageData.slice(1).join('.');
        }
        Message.error({
          content: error.message ? (
            <>
              {errorMessage ?? error.message}
              <br />
              <span style={{ fontWeight: 150, color: '#A9A9A9', fontSize: '14px' }}>
                {errorMessageInfo ?? ''}
              </span>
            </>
          ) : (
            intl.formatMessage({
              id: 'failedToDelete',
              defaultMessage: 'Cannot Delete.Leave entitlement is used by employee',
            })
          ),
        });
      }
    }
  };
  const searchFormOnChange = (val, oth) => {
    const employeeVal = searchForm.getFieldValue('employeeId');
    const leaveTypeId = searchForm.getFieldValue('leaveTypeId');
    setSelectedLeaveType(leaveTypeId);
    setSelectedEmployee(employeeVal);
  };

  const columns: ProColumns<any>[] = [
    {
      title: 'LeaveType',
      dataIndex: 'leaveTypeId',

      key: 'leaveType',
      valueType: 'select',
      defaultSortOrder: 'ascend',
      sorter: (a, b) => a.leaveTypeId - b.leaveTypeId,
      valueEnum: generateLeaveTypeEnum(),
    },
    {
      title: 'Entitlement type',
      dataIndex: 'type',
      key: 'type',
      filters: true,
      onFilter: true,
      valueType: 'select',
      valueEnum: generateEnum(),
    },
    {
      title: 'Leave Period',
      key: 'period',
      defaultSortOrder: 'descend',
      sorter: (a, b) => moment(a.leavePeriodFrom).unix() - moment(b.leavePeriodFrom).unix(),
      render: (e) => {
        return (
          <div
            style={{
              textOverflow: 'ellipsis',
              whiteSpace: 'nowrap',
            }}
          >{`${
            moment(e.leavePeriodFrom, 'YYYY-MM-DD').isValid()
              ? moment(e.leavePeriodFrom).format('DD-MM-YYYY')
              : null
          } to ${
            moment(e.leavePeriodTo, 'YYYY-MM-DD').isValid()
              ? moment(e.leavePeriodTo).format('DD-MM-YYYY')
              : null
          }`}</div>
        );
      },
    },
    {
      title: 'Effective Date',

      key: 'validFrom',
      defaultSortOrder: 'ascend',
      sorter: (a, b) => moment(a.validFrom).unix() - moment(b.validFrom).unix(),
      render: (e) => {
        return (
          <div
            style={{
              textOverflow: 'ellipsis',
              whiteSpace: 'nowrap',
            }}
          >
            {moment(e.validFrom, 'YYYY-MM-DD').isValid()
              ? moment(e.validFrom).format('DD-MM-YYYY')
              : null}
          </div>
        );
      },
    },
    {
      title: 'Expiry Date ',
      key: 'validTo',
      render: (e) => {
        return (
          <div
            style={{
              textOverflow: 'ellipsis',
              whiteSpace: 'nowrap',
            }}
          >
            {moment(e.validTo, 'YYYY-MM-DD').isValid()
              ? moment(e.validTo).format('DD-MM-YYYY')
              : null}
          </div>
        );
      },
    },
    {
      title: ' Number Of Days',
      key: 'entilementCount',
      render: (e) => {
        return (
          <> {e.entilementCount > 1 ? `${e.entilementCount} days` : `${e.entilementCount} day`}</>
        );
      },
    },
    {
      title: 'Actions',
      key: 'actions',
      hideInTable: !hasPermitted('leave-entitlement-write'),
      width: 200,
      render: (text, record, index) => {
        const currentLeaveType = _.find(existingLeaveTypesArr, (e) => e.id === record.leaveTypeId);

        return (
          <Space direction="horizontal" style={{ float: 'left' }}>
            <Tooltip
              placement={'bottom'}
              key="editRecordTooltip"
              title={intl.formatMessage({
                id: 'edit',
                defaultMessage: 'Edit',
              })}
            >
              <a
                key="editRecordButton"
                onClick={async () => {
                  const leaveTypeResponse = getLeaveType(record.leaveTypeId);
                  await setSelectedLeavePeriodType((await leaveTypeResponse).data.leavePeriod);
                  // await calculateLeavePeriod()
                  setSelectedRecordId(record.id);
                  setSelectedEntitlementType(record.type);
                  setLeavePeriodStartDate(
                    moment(record.leavePeriodFrom, 'YYYY-MM-DD').format('YYYY-MM-DD'),
                  );
                  setLeavePeriodEndDate(
                    moment(record.leavePeriodTo, 'YYYY-MM-DD').format('YYYY-MM-DD'),
                  );
                  if (record.usedCount == 0 && record.pendingCount == 0) {
                    setIsLeavePeriodDisabled(false);
                  } else {
                    setIsLeavePeriodDisabled(true);
                  }
                  setUtilizedCount(Number(record.usedCount) + Number(record.pendingCount));
                  form.setFieldsValue({
                    leaveTypeId: record.leaveTypeId,
                    // leavePeriod: `${moment(record.leavePeriodFrom).format("DD-MM-YYYY")} to ${moment(record.leavePeriodTo).format("DD-MM-YYYY")}`,
                    effectiveDate: record.validFrom,
                    expiryDate: record.validTo,
                    entilementCount: record.entilementCount,
                    comment: record.comment,
                  });
                  const selectedLeaveTy = _.find(
                    existingLeaveTypesArr,
                    (o) => o.id == record.leaveTypeId,
                  );
                  if (selectedLeaveTy) {
                    setAdminsCanAdjust(selectedLeaveTy.adminCanAdjustEntitlements);
                  }

                  setDrawerVisit(true);
                }}
              >
                {currentLeaveType.adminCanAdjustEntitlements ? <EditOutlined /> : <EyeOutlined />}
              </a>
            </Tooltip>
            <Popconfirm
              key="delete-pop-confirm"
              placement="topRight"
              title="Are you sure?"
              okText="Yes"
              cancelText="No"
              onConfirm={() => {
                const { id } = record;
                deleteEntitlements(id);
              }}
              style={{ marginLeft: 10, marginRight: 10 }}
            >
              <Tooltip key="delete-tool-tip" title="Delete">
                <a key="delete-btn">
                  <DeleteOutlined />
                </a>
              </Tooltip>
            </Popconfirm>
          </Space>
        );
      },
    },
  ];

  const formOnFinish = async (data) => {
    const requestData = {
      id: selectedRecordId,
      employeeId: selectedEmployee,
      leaveTypeId: data.leaveTypeId,
      leavePeriodFrom: leavePeriodStartDate,
      leavePeriodTo: leavePeriodEndate,
      comment: data.comment,
      validFrom: data.effectiveDate,
      validTo: data.expiryDate,
      entilementCount: data.entilementCount,
    };
    try {
      const { message, data } = await updateLeaveEntitlement(selectedRecordId, requestData);
      history.push(`/leave/leave-entitlements`);
      Message.success(message);
      setDrawerVisit(false);
      searchForm.submit();
    } catch (err) {
      console.log(err);
      message.error({
        content: err.message,
      });
      //   const errorArray = []
      //   for (const i in err.data) {
      //     errorArray.push({ name: i, errors: err.data[i] })
      //   }
      //   form.setFields([...errorArray]);
    }
  };
  return (
    <Access
      accessible={hasPermitted('leave-entitlement-write')}
      fallback={<PermissionDeniedPage />}
    >
      <PageContainer
        extra={[
          <Button
            key="3"
            onClick={(e) => {
              history.push('/leave/leave-entitlements/new');
            }}
            style={{
              background: '#FFFFFF',
              border: '1px solid #7DC014',
              color: '#7DC014',
            }}
          >
            {' '}
            <PlusOutlined /> Add Entitlement
          </Button>,
        ]}
      >
        <Row style={{ marginBottom: '32px' }}>
          <div style={{ background: '#FFFFFF', padding: '32px', width: '100%', borderRadius: 10 }}>
            <ProForm
              id={'searchForm'}
              layout="inline"
              form={searchForm}
              initialValues={{
                leaveEntitlementFor: 'individual',
              }}
              onFinish={searchFormOnFinish}
              onValuesChange={searchFormOnChange}
              submitter={{
                resetButtonProps: {
                  style: {
                    display: 'none',
                  },
                },
                render: (props, doms) => {
                  return [
                    <Col span={8}>
                      <Row align="bottom" style={{ paddingTop: '36px' }}>
                        <Button
                          type="primary"
                          key="submit"
                          size="middle"
                          onClick={() => props.form?.submit()}
                        >
                          Search
                        </Button>
                      </Row>
                    </Col>,
                  ];
                },
              }}
            >
              <Row>
                <Col>
                  <Row style={{ marginBottom: '16px' }}>
                    <label>
                      {intl.formatMessage({
                        id: 'leaveEntitlement.label.employee',
                        defaultMessage: 'Employee',
                      })}{' '}
                    </label>{' '}
                    <div style={{ color: 'red', marginLeft: '6px' }}> *</div>
                  </Row>
                  <Row>
                    {isEnableInactiveEmployees ? (
                      <ProFormSelect
                        width={230}
                        name="employeeId"
                        placeholder={intl.formatMessage({
                          id: 'leaveEntitlement.employee',
                          defaultMessage: 'Select Employee',
                        })}
                        rules={[
                          {
                            required: true,
                            message: intl.formatMessage({
                              id: 'leaveEntitlement.edit.employeeName',
                              defaultMessage: 'Required',
                            }),
                          },
                        ]}
                        options={availableEmployeesWithInactive}
                        showSearch
                      />
                    ) : (
                      <ProFormSelect
                        width={230}
                        name="employeeId"
                        placeholder={intl.formatMessage({
                          id: 'leaveEntitlement.employee',
                          defaultMessage: 'Select Employee',
                        })}
                        rules={[
                          {
                            required: true,
                            message: intl.formatMessage({
                              id: 'leaveEntitlement.edit.employeeName',
                              defaultMessage: 'Required',
                            }),
                          },
                        ]}
                        options={availableEmployeesWithoutInactive}
                        showSearch
                      />
                    )}
                  </Row>
                </Col>
                <Col>
                  <Row style={{ marginBottom: '16px' }}>
                    <label>Leave Type</label>
                  </Row>
                  <Row>
                    <ProFormSelect
                      width={230}
                      name="leaveTypeId"
                      disabled={!selectedEmployee}
                      placeholder="Select Leave Type"
                      options={leaveTypes}
                      showSearch
                      // request={async () => {
                      //   const response = await getExistingLeaveTypes();
                      //   return response.data;
                      // }}
                    />
                  </Row>
                </Col>
                {selectedLeaveType && selectedEmployee ? (
                  <>
                    <Col>
                      <Row style={{ marginBottom: '16px' }}>
                        <label>Leave Period</label>
                      </Row>
                      <Row>
                        <ProFormSelect
                          width={230}
                          name="id"
                          placeholder="Select Leave Period"
                          options={leavePeriodArray}
                        />
                      </Row>
                    </Col>
                  </>
                ) : (
                  <></>
                )}

                <Col
                  style={{
                    width: 350,
                    height: 35,
                    paddingLeft: 20,
                    textAlign: 'left',
                    marginTop: 45,
                  }}
                >
                  <Text
                    style={{
                      marginRight: 30,
                      verticalAlign: 'bottom',
                    }}
                  >
                    {intl.formatMessage({
                      id: 'includeInactive',
                      defaultMessage: 'Enable Inactive Employees',
                    })}
                  </Text>
                  <Switch
                    onChange={(checked: boolean, event: Event) => {
                      setIsEnableInactiveEmployees(checked);
                      searchForm.setFieldsValue({
                        employeeId: null,
                      });
                    }}
                    checkedChildren="Yes"
                    unCheckedChildren="No"
                  />
                </Col>
              </Row>
            </ProForm>
          </div>
        </Row>
        {allEntitlements.length !== 0 ? (
          <Row>
            <ProTable
              actionRef={actionRef}
              rowKey="id"
              search={false}
              columns={columns}
              style={{ width: '100%' }}
              scroll={{ x: '200px' }}
              pagination={{ pageSize: 10, defaultPageSize: 10 }}
              dataSource={allEntitlements}
            />
          </Row>
        ) : (
          <></>
        )}
        <DrawerForm
          onVisibleChange={setDrawerVisit}
          visible={drawerVisit}
          id={'NewTemplate'}
          form={form}
          onFinish={formOnFinish}
          onValuesChange={onFormChange}
          submitter={{
            searchConfig: {
              resetText: 'reset',
              submitText: 'submit',
            },
            render: (props, doms) => {
              if (!adminsCanAdjust) {
                return [<></>];
              }
              return [
                <Row justify="end" gutter={[16, 16]}>
                  <Col span={12}>
                    <Button
                      block
                      type="default"
                      key="rest"
                      size="middle"
                      onClick={() => props.form?.resetFields()}
                      className={'reset-btn'}
                    >
                      Reset
                    </Button>
                  </Col>
                  <Col span={12}>
                    <Button
                      block
                      type="primary"
                      key="submit"
                      size="middle"
                      onClick={() => props.form?.submit()}
                    >
                      Save
                    </Button>
                  </Col>
                </Row>,
              ];
            },
          }}
        >
          <ProFormSelect
            width={330}
            name="leaveTypeId"
            label={intl.formatMessage({
              id: ' leaveType',
              defaultMessage: 'Leave Type',
            })}
            showSearch
            // request={async () => getOptions('leaveType')}
            options={relatedLeaveTypes}
            placeholder={intl.formatMessage({
              id: 'leaveEntitlement. leaveType',
              defaultMessage: 'Select Leave Type',
            })}
            disabled={!adminsCanAdjust}
            rules={[
              {
                required: true,
                message: intl.formatMessage({
                  id: 'leaveEntitlement.edit.leaveType',
                  defaultMessage: 'Required',
                }),
              },
            ]}
          />
          <ProFormSelect
            width={330}
            name="leavePeriod"
            label={intl.formatMessage({
              id: 'leavePeriod',
              defaultMessage: 'Leave Period',
            })}
            placeholder={intl.formatMessage({
              id: 'leaveEntitlement.leavePeriod',
              defaultMessage: 'Select Leave Period',
            })}
            rules={[
              {
                required: true,
                message: intl.formatMessage({
                  id: 'leaveEntitlement.edit.leavePeriod',
                  defaultMessage: 'Required',
                }),
              },
            ]}
            valueEnum={leavePeriodEnum}
            value={selectedLeavePeriod}
            disabled={isLeavePeriodDisabled || !adminsCanAdjust}
            onChange={(val: any) => {
              setSelectedLeavePeriod(val);
              form.setFieldsValue({
                leavePeriod: val,
              });
            }}
            // request={async () => getOptions("leavePeriod")}
          />
          <Row gutter={[32, 16]}>
            <Col span={8}>
              <ProFormDatePicker
                disabled={!adminsCanAdjust}
                width={'100%'}
                name="effectiveDate"
                label={intl.formatMessage({
                  id: 'effectiveDate',
                  defaultMessage: 'Effective Date',
                })}
                placeholder={intl.formatMessage({
                  id: 'leaveEntitlement.effectiveDate',
                  defaultMessage: 'Select Date',
                })}
                format={'DD-MM-YYYY'}
                rules={[
                  {
                    required: true,
                    message: intl.formatMessage({
                      id: 'leaveEntitlement.edit.effectiveDate',
                      defaultMessage: 'Required',
                    }),
                  },
                ]}
                onChange={(value) => {
                  if (selectedLeavePeriod && value) {
                    var isSameOrAfterStartDate = value.isSameOrAfter(
                      moment(
                        leavePeriodArrayForm[selectedLeavePeriod].leavePeriodStartDate,
                        'DD-MM-YYYY',
                      ).format('YYYY-MM-DD'),
                      'day',
                    );
                    var isSameOrAfterEndDate = value.isSameOrBefore(
                      moment(
                        leavePeriodArrayForm[selectedLeavePeriod].leavePeriodEndate,
                        'DD-MM-YYYY',
                      ).format('YYYY-MM-DD'),
                      'day',
                    );

                    if (isSameOrAfterStartDate && isSameOrAfterEndDate) {
                      form.setFieldsValue({
                        effectiveDate: value.format('YYYY-MM-DD'),
                      });
                    } else {
                      form.setFieldsValue({
                        effectiveDate: moment(
                          leavePeriodArrayForm[selectedLeavePeriod].leavePeriodStartDate,
                          'DD-MM-YYYY',
                        ),
                      });
                    }
                  }
                }}
              />
            </Col>
            <Col span={8}>
              <ProFormDatePicker
                width={'100%'}
                name="expiryDate"
                label={intl.formatMessage({
                  id: 'expiryDate',
                  defaultMessage: 'Expiry Date',
                })}
                placeholder={intl.formatMessage({
                  id: 'leaveEntitlement.expiryDate',
                  defaultMessage: 'Select Date',
                })}
                format={'DD-MM-YYYY'}
                disabled={!adminsCanAdjust}
                rules={[
                  {
                    required: true,
                    message: intl.formatMessage({
                      id: 'leaveEntitlement.edit.expiryDate',
                      defaultMessage: 'Required',
                    }),
                  },
                ]}
                onChange={(value) => {
                  if (selectedLeavePeriod && value) {
                    var isSameOrAfterStartDate = value.isSameOrAfter(
                      moment(
                        leavePeriodArrayForm[selectedLeavePeriod].leavePeriodStartDate,
                        'DD-MM-YYYY',
                      ).format('YYYY-MM-DD'),
                      'day',
                    );
                    var isSameOrAfterEndDate = value.isSameOrBefore(
                      moment(
                        leavePeriodArrayForm[selectedLeavePeriod].leavePeriodEndate,
                        'DD-MM-YYYY',
                      ).format('YYYY-MM-DD'),
                      'day',
                    );

                    if (isSameOrAfterStartDate && isSameOrAfterEndDate) {
                      form.setFieldsValue({
                        expiryDate: value.format('YYYY-MM-DD'),
                      });
                    } else {
                      form.setFieldsValue({
                        expiryDate: moment(
                          leavePeriodArrayForm[selectedLeavePeriod].leavePeriodEndate,
                          'DD-MM-YYYY',
                        ),
                      });
                    }
                  }
                }}
              />
            </Col>
            <Col span={8}>
              <ProFormDigit
                disabled={!adminsCanAdjust}
                fieldProps={{
                  precision: 2,
                }}
                width={'100%'}
                name="entilementCount"
                label={intl.formatMessage({
                  id: 'numberofDays',
                  defaultMessage: 'Number of Days',
                })}
                // formatter={value => `${value}%`}
                //min={utilizedCount}
                rules={[
                  {
                    required: true,
                    message: intl.formatMessage({
                      id: 'leaveEntitlemen.add.numberOfDays',
                      defaultMessage: 'Required',
                    }),
                  },
                  () => ({
                    validator(type, value) {
                      const numStr = String(value);
                      // String Contains Decimal
                      if (numStr.includes('.')) {
                        if (numStr.split('.')[1].length > 2) {
                          return Promise.reject(
                            new Error(
                              intl.formatMessage({
                                id: 'leaveEntitlemen.add.max.decimals',
                                defaultMessage: 'Only up to two decimal places',
                              }),
                            ),
                          );
                        }
                      }
                      if (value > 365) {
                        return Promise.reject(
                          new Error(
                            intl.formatMessage({
                              id: 'leaveEntitlemen.add.max.numberOfDays',
                              defaultMessage: 'Should be less than 365',
                            }),
                          ),
                        );
                      }
                      if (value < utilizedCount) {
                        return Promise.reject(
                          new Error(
                            intl.formatMessage({
                              id: 'leaveEntitlemen.add.error.utilizedAmount',
                              defaultMessage: `Cannot be less than ${utilizedCount} (utilized amount) `,
                            }),
                          ),
                        );
                      }
                      return Promise.resolve();
                    },
                  }),
                ]}
              />
            </Col>
          </Row>
          <Col span={24}>
            <ProFormTextArea
              disabled={!adminsCanAdjust}
              name="comment"
              label={intl.formatMessage({
                id: 'leaveEntitlement.Comment',
                defaultMessage: 'Comment',
              })}
              placeholder=""
              rules={[
                {
                  max: 200,
                  message: intl.formatMessage({
                    id: 'leaveEntitlement.edit.comment',
                    defaultMessage: 'Maximum length is 200 characters.',
                  }),
                },
              ]}
            />
          </Col>
          <Row>
            <Col span={4}>
              <Form.Item name="type" label="Entitlement Type" initialValue="MANUAL"></Form.Item>
            </Col>
            <Col span={8}>
              <Tag
                style={{
                  borderRadius: '18px',
                  background: '#CDE7FF',
                  color: '#7EBC1C',
                  fontSize: '12px',
                }}
              >
                {selectedEntitlementType == 'ACCRUAL' ? 'Accrual' : 'Manual'}
              </Tag>
            </Col>
          </Row>
        </DrawerForm>
      </PageContainer>
    </Access>
  );
};

export default LeaveEntitlements;
