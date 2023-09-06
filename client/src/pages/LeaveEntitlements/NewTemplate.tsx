import React, { useState, useEffect } from 'react';
import _ from 'lodash';
import { useAccess, Access, useParams, useIntl, history } from 'umi';
import PermissionDeniedPage from '@/pages/403';
import { PageContainer } from '@ant-design/pro-layout';
import ProForm, {
  ProFormDatePicker,
  ProFormDigit,
  ProFormField,
  ProFormRadio,
  ProFormSelect,
  ProFormText,
  ProFormTextArea,
} from '@ant-design/pro-form';
import { Button, Card, Col, Form, Row, Transfer, message as Message, Tag, Spin } from 'antd';
import request from '@/utils/request';
import { getModel } from '@/services/model';
import { getAllEmployee, getEmployee, getFilteredEmployee } from '@/services/employee';
import {
  addLeaveEntitlement,
  addLeaveEntitlementForMultiple,
  getLeaveEntitlement,
  getLeaveType,
  getLeaveTypes,
} from '@/services/leaveEntitlment';
import { getCompany } from '@/services/company';
import moment from 'moment';
import styles from './index.less';
import { getEmployeeList, getLeaveTypesList } from '@/services/dropdown';

const LeaveEntitlement: React.FC = (props) => {
  const access = useAccess();
  const { hasPermitted } = access;
  const { id } = useParams();
  const intl = useIntl();
  const [form] = Form.useForm();
  const [targetKeys, setTargetKeys] = useState(initialTargetKeys);
  const [selectedKeys, setSelectedKeys] = useState([]);
  const [leaveEntitlementForValue, setLeaveEntitlementFor] = useState('individual');
  const [locationValue, setLocationValue] = useState('');
  const [departmentValue, setDepartmentValue] = useState('');
  const [TreeData, setTreeData] = useState([]);
  const [selectedLeavePeriodType, setSelectedLeavePeriodType] = useState('');
  const [selectedLeavePeriod, setSelectedLeavePeriod] = useState('');
  const [leavePeriodEnum, setLeavePeriodEnum] = useState({});
  const [isLeavePeriodDisabled, setIsLeavePeriodDisabled] = useState(true);
  const [selectedEmployee, setSelectedEmployee] = useState('');
  const initialTargetKeys = TreeData;
  const [leavePeriodStartDate, setLeavePeriodStartDate] = useState('');
  const [leavePeriodEndate, setLeavePeriodEndDate] = useState('');
  const [leavePeriodArray, setLeavePeriodArray] = useState([]);
  const [relatedLeaveTypes, setRelatedLeaveTypes] = useState<any>([]);
  const [relatedDepartments, setRelatedDepartments] = useState<any>([]);
  const [relatedEmployees, setRelatedEmployees] = useState<any>([]);
  const [relatedLocations, setRelatedLocations] = useState<any>([]);
  const [allEmployees, setAllEmployees] = useState([]);
  const [loading, setLoading] = useState(false);

  const onFormChange = async (val) => {
    setLeaveEntitlementFor(form.getFieldValue('leaveEntitlementFor'));
    setLocationValue(form.getFieldValue('location'));
    setDepartmentValue(form.getFieldValue('department'));
    setSelectedEmployee(form.getFieldValue('employeeId'));
    const leavePeriod = form.getFieldValue('leavePeriod');
    const leaveTypeValue = form.getFieldValue('leaveTypeId');

    if (leaveTypeValue) {
      const leaveTypeResponse = getLeaveType(leaveTypeValue);
      await setSelectedLeavePeriodType((await leaveTypeResponse).data.leavePeriod);
    }
  };

  useEffect(() => {
    calculateLeavePeriod();
  }, [selectedLeavePeriodType]);
  useEffect(() => {
    calculateLeavePeriod();
  }, [selectedEmployee]);

  useEffect(() => {
    fetchExistingEntitlements;
  }, [id]);

  useEffect(() => {
    generateTreeData();
  }, [departmentValue, locationValue, leaveEntitlementForValue]);

  useEffect(() => {
    fetchDropDownData();
    getDepartments('department');
    getLocations('location');
    generateTreeData();
  }, []);

  useEffect(() => {
    if (selectedLeavePeriod) {
      setLeavePeriodStartDate(
        moment(leavePeriodArray[selectedLeavePeriod].leavePeriodStartDate, 'DD-MM-YYYY').format(
          'YYYY-MM-DD',
        ),
      );
      setLeavePeriodEndDate(
        moment(leavePeriodArray[selectedLeavePeriod].leavePeriodEndate, 'DD-MM-YYYY').format(
          'YYYY-MM-DD',
        ),
      );
      form.setFieldsValue({
        effectiveDate: moment(
          leavePeriodArray[selectedLeavePeriod].leavePeriodStartDate,
          'DD-MM-YYYY',
        ),
        expiryDate: moment(leavePeriodArray[selectedLeavePeriod].leavePeriodEndate, 'DD-MM-YYYY'),
      });
    }
  }, [selectedLeavePeriod]);

  const fetchDropDownData = async () => {
    try {
      setLoading(true);
      const allEmployeeData = await getAllEmployee({
        sorter: {
          employeeName: 'ascend',
        },
      });
      setAllEmployees(allEmployeeData.data);

      const { data } = await getEmployeeList('ADMIN');
      const employees = data.map((employee: any) => {
        return {
          label: employee.employeeNumber + ' | ' + employee.employeeName,
          value: employee.id,
        };
      });
      setRelatedEmployees(employees);
      const leaveTypesArr = await getLeaveTypesList('ADMIN');
      const leaveTypes = leaveTypesArr.data.map((el: any) => {
        if (el.adminCanAdjustEntitlements) {
          return {
            label: el.name,
            value: el.id,
          };
        }
      });
      setRelatedLeaveTypes(leaveTypes);
      setLoading(false);
    } catch (err) {
      console.log(err);
    }
  };

  const onChange = (nextTargetKeys, direction, moveKeys) => {
    setTargetKeys(nextTargetKeys);
  };

  const onSelectChange = (sourceSelectedKeys, targetSelectedKeys) => {
    setSelectedKeys([...sourceSelectedKeys, ...targetSelectedKeys]);
  };
  const fetchExistingEntitlements = async () => {
    const leaveEntitlementRes = getLeaveEntitlement(id);
    console.log(leaveEntitlementRes.data);
  };

  const calculateLeavePeriod = async () => {
    let currentYearStartDate;
    let currentYearEndDate;
    let nextYearStartDate;
    let nextYearEndDate;
    let lastYearStartDate;
    let lastYearEndDate;
    if (selectedLeavePeriodType === 'STANDARD') {
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
      setIsLeavePeriodDisabled(false);
    } else {
      if (!selectedEmployee) {
        setIsLeavePeriodDisabled(true);
      } else {
        // reset hire date based fields while changing selected employee
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
            setLeavePeriodEnum([]);
            return;
          }
        }
        setIsLeavePeriodDisabled(false);
      }
    }
    setLeavePeriodArray([
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

  const generateTreeData = async () => {
    const mockData = [];

    if (allEmployees) {
      let filteredEmployees;
      if (departmentValue && locationValue) {
        filteredEmployees = _.filter(
          allEmployees,
          (o) =>
            o.currentJobs.departmentId === departmentValue &&
            o.currentJobs.locationId === locationValue,
        );
      } else if (departmentValue && !locationValue) {
        filteredEmployees = _.filter(
          allEmployees,
          (o) => o.currentJobs.departmentId === departmentValue,
        );
      } else if (!departmentValue && locationValue) {
        filteredEmployees = _.filter(
          allEmployees,
          (o) => o.currentJobs.locationId === locationValue,
        );
      } else {
        filteredEmployees = [...allEmployees];
      }

      filteredEmployees.forEach(async (e, i) => {
        await mockData.push({
          key: e.id,
          title: e.employeeName,
        });
      });
    }

    setTreeData(mockData);
  };

  const getDepartments = async (name) => {
    // setLoading(true);
    const actions: any = [];
    const response = await getModel(name);
    let path: string;
    if (!_.isEmpty(response.data)) {
      path = `/api${response.data.modelDataDefinition.path}`;
    }
    const res = await request(path);
    await res.data.forEach(async (element: any, i: number) => {
      await actions.push({ value: element['id'], label: element['name'] });
    });
    setRelatedDepartments(actions);
    // setLoading(false);
    return actions;
  };

  const getLocations = async (name) => {
    // setLoading(true);
    const actions: any = [];
    const response = await getModel(name);
    let path: string;
    if (!_.isEmpty(response.data)) {
      path = `/api${response.data.modelDataDefinition.path}`;
    }
    const res = await request(path);
    await res.data.forEach(async (element: any, i: number) => {
      await actions.push({ value: element['id'], label: element['name'] });
    });
    setRelatedLocations(actions);
    // setLoading(false);
    return actions;
  };

  // const getLeaveTypes = async (name) => {
  //     const LeaveTypesArr: any = [];

  //     const res = await getLeaveTypesList("ADMIN");
  //     await res.data.forEach(async (element: any, i: number) => {
  //         await LeaveTypesArr.push({ value: element['id'], label: element['name'] });

  //     });
  //     setRelatedLeaveTypes(LeaveTypesArr);
  // }

  const formComponents = () => {
    if (leaveEntitlementForValue === 'multiple' && selectedLeavePeriodType == 'STANDARD') {
      return (
        <>
          {' '}
          <Row gutter={[32, 16]}>
            <Col span={12}>
              <ProFormSelect
                name="location"
                label="Location"
                placeholder="Select Location"
                // request={async () => getOptions("location")}
                options={relatedLocations}
                showSearch
              />
            </Col>
            <Col span={12}>
              <ProFormSelect
                name="department"
                label="Department"
                placeholder="Select Department"
                // request={async () => getOptions("department")}
                options={relatedDepartments}
                showSearch
              />
            </Col>
          </Row>
          <Form.Item name="employeeIds" label="Select Employees">
            <Transfer
              listStyle={{
                width: '100%',
                height: 400,
              }}
              showSearch
              filterOption={(search, item) => {
                return item.title.toLowerCase().indexOf(search.toLowerCase()) >= 0;
              }}
              dataSource={TreeData}
              // titles={['Source', 'Target']}
              targetKeys={targetKeys}
              selectedKeys={selectedKeys}
              onChange={onChange}
              onSelectChange={onSelectChange}
              render={(item) => item.title}
            />
          </Form.Item>
        </>
      );
    }

    return (
      <ProFormSelect
        width={330}
        name="employeeId"
        label="Employee Name"
        placeholder="Select Employee"
        showSearch
        rules={[
          {
            required: true,
            message: intl.formatMessage({
              id: 'employeeId',
              defaultMessage: 'Required',
            }),
          },
        ]}
        // request={async () => getOptions("employee")}
        options={relatedEmployees}
      />
    );
  };

  const formOnFinish = async (data) => {
    const requestData = {
      leaveTypeId: data.leaveTypeId,
      employeeId: data.employeeId,
      leavePeriodFrom: leavePeriodStartDate,
      leavePeriodTo: leavePeriodEndate,
      comment: data.comment,
      validFrom: data.effectiveDate,
      validTo: data.expiryDate,
      entilementCount: data.numberOfDays,
    };

    try {
      if (data.leaveEntitlementFor === 'individual') {
        const { message, data } = await addLeaveEntitlement(requestData);
        history.push(`/leave/leave-entitlements`);
        Message.success(message);
      } else {
        requestData['employeeIds'] = targetKeys;
        const { message, data } = await addLeaveEntitlementForMultiple(requestData);
        history.push(`/leave/leave-entitlements`);
        Message.success(message);
      }
    } catch (err) {
      console.log(err);
      Message.error({
        content: err.message,
      });
      // const errorArray = []
      // for (const i in err.data) {
      //   errorArray.push({ name: i, errors: err.data[i] })
      // }
      // form.setFields([...errorArray]);
    }
  };
  return (
    <Access
      accessible={hasPermitted('leave-entitlement-write')}
      fallback={<PermissionDeniedPage />}
    >
      <div
        style={{
          backgroundColor: 'white',
          borderTopLeftRadius: '30px',
          paddingLeft: '50px',
          paddingTop: '50px',
          paddingBottom: '50px',
          width: '100%',
          paddingRight: '0px',
        }}
      >
        <PageContainer>
          <Card>
            <Spin size="large" spinning={loading}>
              <Col offset={1} span={12}>
                <ProForm
                  id={'NewTemplate'}
                  form={form}
                  initialValues={{
                    leaveEntitlementFor: 'individual',
                  }}
                  onFinish={formOnFinish}
                  onValuesChange={onFormChange}
                  submitter={{
                    searchConfig: {
                      resetText: 'reset',
                      submitText: 'submit',
                    },
                    render: (props, doms) => {
                      return [
                        <Row justify="end" gutter={[16, 16]}>
                          <Col span={4}>
                            <Button
                              block
                              type="default"
                              key="rest"
                              size="middle"
                              onClick={() => props.form?.resetFields()}
                              className={styles.resetBtn}
                            >
                              Reset
                            </Button>
                          </Col>
                          <Col span={4}>
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
                    // request={async () => getOptions("leaveType")}
                    options={relatedLeaveTypes}
                    showSearch
                    placeholder="Select Leave Type"
                    rules={[
                      {
                        required: true,
                        message: intl.formatMessage({
                          id: 'leaveEntitlemen.add.leaveType',
                          defaultMessage: 'Required',
                        }),
                      },
                    ]}
                  />

                  <ProFormRadio.Group
                    name="leaveEntitlementFor"
                    label="Leave Entitlement For"
                    radioType="button"
                    className={styles.btnGroupLeaveEntitlement}
                    options={[
                      {
                        label: 'Individual',
                        value: 'individual',
                        style: {
                          borderTopLeftRadius: 6,
                          borderBottomLeftRadius: 6,
                        },
                      },
                      {
                        label: 'Multiple Employees',
                        value: 'multiple',
                        disabled: selectedLeavePeriodType !== 'STANDARD',
                        style: {
                          borderTopRightRadius: 6,
                          borderBottomRightRadius: 6,
                        },
                      },
                    ]}
                  />
                  {formComponents()}
                  <ProFormSelect
                    width={330}
                    name="leavePeriod"
                    label="Leave Period"
                    placeholder="Select Leave Period"
                    rules={[
                      {
                        required: true,
                        message: intl.formatMessage({
                          id: 'leaveEntitlemen.add.leavePeriod',
                          defaultMessage: 'Required',
                        }),
                      },
                    ]}
                    showSearch
                    valueEnum={leavePeriodEnum}
                    disabled={isLeavePeriodDisabled}
                    onChange={(val: any) => {
                      setSelectedLeavePeriod(val);
                    }}
                  />
                  <Row gutter={[32, 16]}>
                    <Col span={8}>
                      <ProFormDatePicker
                        width={'100%'}
                        name="effectiveDate"
                        label="Effective Date"
                        placeholder="Select Date"
                        format={'DD-MM-YYYY'}
                        rules={[
                          {
                            required: true,
                            message: intl.formatMessage({
                              id: 'leaveEntitlemen.add.effectiveDate',
                              defaultMessage: 'Required',
                            }),
                          },
                        ]}
                        onChange={(value) => {
                          if (selectedLeavePeriod && value) {
                            var isSameOrAfterStartDate = value.isSameOrAfter(
                              moment(
                                leavePeriodArray[selectedLeavePeriod].leavePeriodStartDate,
                                'DD-MM-YYYY',
                              ).format('YYYY-MM-DD'),
                              'day',
                            );
                            var isSameOrAfterEndDate = value.isSameOrBefore(
                              moment(
                                leavePeriodArray[selectedLeavePeriod].leavePeriodEndate,
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
                                  leavePeriodArray[selectedLeavePeriod].leavePeriodStartDate,
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
                        label="Expiry Date"
                        placeholder="Select Date"
                        format={'DD-MM-YYYY'}
                        rules={[
                          {
                            required: true,
                            message: intl.formatMessage({
                              id: 'leaveEntitlemen.add.expiryDate',
                              defaultMessage: 'Required',
                            }),
                          },
                        ]}
                        onChange={(value) => {
                          if (selectedLeavePeriod && value) {
                            var isSameOrAfterStartDate = value.isSameOrAfter(
                              moment(
                                leavePeriodArray[selectedLeavePeriod].leavePeriodStartDate,
                                'DD-MM-YYYY',
                              ).format('YYYY-MM-DD'),
                              'day',
                            );
                            var isSameOrAfterEndDate = value.isSameOrBefore(
                              moment(
                                leavePeriodArray[selectedLeavePeriod].leavePeriodEndate,
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
                                  leavePeriodArray[selectedLeavePeriod].leavePeriodEndate,
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
                        fieldProps={{
                          type: 'number',
                          precision: 2,
                        }}
                        width={'100%'}
                        name="numberOfDays"
                        label="Number of Days"
                        // max={365}
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
                                        id: 'leaveEntitlemen.add.max.numberOfDays',
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
                              return Promise.resolve();
                            },
                          }),
                        ]}
                      />
                    </Col>
                  </Row>

                  <ProFormTextArea
                    name="comment"
                    label="Comment "
                    placeholder=""
                    rules={[
                      {
                        max: 200,
                        message: intl.formatMessage({
                          id: 'leaveEntitlemen.add.comment',
                          defaultMessage: 'Maximum length is 200 characters.',
                        }),
                      },
                    ]}
                  />
                  <Row>
                    <Col>
                      <Form.Item
                        name="type"
                        label="Entitlement Type"
                        initialValue="MANUAL"
                      ></Form.Item>
                    </Col>
                    <Col offset={1}>
                      <Tag
                        style={{
                          borderRadius: '18px',
                          background: '#C0FFC7',
                          color: '#3E8D47',
                          fontSize: '12px',
                        }}
                      >
                        Manually
                      </Tag>
                    </Col>
                  </Row>
                </ProForm>
              </Col>
            </Spin>
          </Card>
        </PageContainer>
      </div>
    </Access>
  );
};

export default LeaveEntitlement;
