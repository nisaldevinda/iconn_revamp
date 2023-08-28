import React, { useState, useEffect } from 'react';
import { getModel, Models } from '@/services/model';
import BasicContainer from '@/components/BasicContainer';
import { DrawerForm, ModalForm, ProFormSelect, ProFormDigit } from '@ant-design/pro-form';
import {
  message,
  Popconfirm,
  Tooltip,
  Form,
  Row,
  Col,
  Space,
  Spin,
  Tag,
  Empty,
  Typography,
  Button,
  Modal,
  Select,
  Input,
  Radio,
  Transfer,
  InputNumber,
} from 'antd';
import request, { APIResponse } from '@/utils/request';
import { useIntl } from 'react-intl';
import { PageContainer } from '@ant-design/pro-layout';
import { useAccess, Access, useParams, FormattedMessage } from 'umi';
import PermissionDeniedPage from '../../../403';
import { financialYears } from '@/services/financialYear';
import { getAllocationEnableClaimTypes } from '@/services/expenseModule';
import { getEmployeeListForClaimAllocation } from '@/services/dropdown';
import { genarateEmptyValuesObject } from '@/utils/utils';
import CreateForm from './create';
import moment from 'moment';
import { claimCategories } from '@/services/expenseModule';
import { number } from 'currency-codes';
import AllocationTable from './claimAllocationTable';
import {
  addEmployeeClaimAllocation,
  addBulkEmployeeClaimAllocation,
} from '@/services/expenseModule';
import { getAllEmploymentStatus } from '@/services/employmentStatus';
import { getAllJobCategories } from '@/services/jobCategory';
import { getAllJobTitles } from '@/services/jobTitle';

export type ClaimAllocationProps = {
  refresh?: number;
};

const ClaimAllocation: React.FC<ClaimAllocationProps> = (props) => {
  const { Text } = Typography;
  const access = useAccess();
  const { hasPermitted } = access;
  //   const { id } = useParams();
  const intl = useIntl();
  const [form] = Form.useForm();
  const [bulkCreateform] = Form.useForm();
  const [financeYearList, setFinanceYearList] = useState([]);
  const [employeeList, setEmployeeList] = useState([]);
  const [claimTypeList, setClaimTypeList] = useState([]);
  const [selectedFinacialYear, setSelectedFinacialYear] = useState(null);
  const [selectedFinacialYearForBulkAllocation, setSelectedFinacialYearForBulkAllocation] =
    useState(null);
  const [selectedClaimType, setSelectedClaimType] = useState(null);
  const [selectedClaimTypeForBulkAllocation, setSelectedClaimTypeForBulkAllocation] =
    useState(null);
  const [selectedEmployee, setSelectedEmployee] = useState(null);
  const [selectedAllocationType, setSelectedAllocationType] = useState('designation');
  const [canAddEmployee, setCanAddEmployee] = useState(false);
  const [refresh, setRefresh] = useState(0);
  const [isModalVisible, setIsModalVisible] = useState(false);
  const [isLoading, setIsLoading] = useState(false);
  const [employmentStatusList, setEmploymentStatusList] = useState([]);
  const [jobCategoryList, setJobCategoryList] = useState([]);
  const [jobTitleList, setJobTitleList] = useState([]);
  const [empStatusTargetKeys, setEmpStatusTargetKeys] = useState<string[]>([]);
  const [jobCatTargetKeys, setJobCatTargetKeys] = useState<string[]>([]);
  const [jobTitleTargetKeys, setJobTitleTargetKeys] = useState<string[]>([]);

  useEffect(() => {
    getAllOptions();
  }, [props.refresh]);

  useEffect(() => {
    if (selectedClaimType && selectedFinacialYear) {
      setCanAddEmployee(true);
      getRelatedEmployeeList();
    } else {
      setCanAddEmployee(false);
    }
  }, [selectedClaimType, selectedFinacialYear]);

  const getRelatedEmployeeList = async () => {
    try {
      const employeeData = await getEmployeeListForClaimAllocation({
        selectedClaimType,
        selectedFinacialYear,
      });

      const employeeArray = employeeData.data.map((emp: any) => {
        return {
          label: emp.employeeNumber+' | '+emp.firstName + ' ' + emp.lastName,
          value: emp.id,
        };
      });
      setEmployeeList(employeeArray);
    } catch (err) {
      console.error(err);
    }
  };

  const createBulkAllocation = async () => {
    const key = 'Saving';
    await bulkCreateform.validateFields();
    setIsLoading(true);
    let params = {
      financialYearId: selectedFinacialYearForBulkAllocation,
      claimTypeId: selectedClaimTypeForBulkAllocation,
      allocatedAmount: bulkCreateform.getFieldValue('allocationAmount')
        ? bulkCreateform.getFieldValue('allocationAmount')
        : 0,
      allocationType: selectedAllocationType,
      selectedJobCategories: jobCatTargetKeys,
      selectedEmployStatuses: empStatusTargetKeys,
      selectedJobTitles: jobTitleTargetKeys,
    };

    await addBulkEmployeeClaimAllocation(params)
      .then(async (response: APIResponse) => {
        if (response.error) {
          setIsLoading(false);
          message.error({
            content:
              response.message ??
              intl.formatMessage({
                id: 'failedToSave',
                defaultMessage: 'Cannot Save',
              }),
            key,
          });
          if (response.data && Object.keys(response.data).length !== 0) {
          }
          return;
        }

        message.success({
          content:
            response.message ??
            intl.formatMessage({
              id: 'successfullySaved',
              defaultMessage: 'Successfully Allocated',
            }),
          key,
        });

        bulkCreateform.setFieldsValue({
          bulkFinancialYear: null,
          bulkClaimType: null,
          allocationType: 'designation',
          allowEmploymentDesignations: [],
          allowEmploymentStatuses: [],
          allowJobCategories: [],
          allocationAmount: null,
        });
        setEmpStatusTargetKeys([]);
        setJobCatTargetKeys([]);
        setJobTitleTargetKeys([]);
        setSelectedClaimTypeForBulkAllocation(null);
        setSelectedFinacialYearForBulkAllocation(null);

        await getRelatedEmployeeList();
        setSelectedEmployee(null);
        setRefresh((prev) => prev + 1);
        setIsLoading(false);
        // setAddApproverPoolFormVisible(false);
      })

      .catch((error: APIResponse) => {
        let errorMessage;
        let errorMessageInfo;
        if (error.message.includes('.')) {
          let errorMessageData = error.message.split('.');
          errorMessage = errorMessageData.slice(0, 1);
          errorMessageInfo = errorMessageData.slice(1).join('.');
        }
        message.error({
          content: error.message ? (
            <>{error.message}</>
          ) : (
            intl.formatMessage({
              id: 'failedToSave',
              defaultMessage: 'Cannot Save',
            })
          ),
          key,
        });
        if (error && Object.keys(error.data).length !== 0) {
          for (const feildName in error.data) {
            const errors = error.data[feildName];
            form.setFields([
              {
                name: feildName,
                errors: errors,
              },
            ]);
          }
        }
      });
  };

  const handleAddEmployeeAllocation = async () => {
    await form.validateFields();
    let params = {
      financialYearId: selectedFinacialYear,
      employeeId: selectedEmployee,
      claimTypeId: selectedClaimType,
      allocatedAmount: 0,
    };
    const key = 'saving';

    await addEmployeeClaimAllocation(params)
      .then(async (response: APIResponse) => {
        if (response.error) {
          message.error({
            content:
              response.message ??
              intl.formatMessage({
                id: 'failedToSave',
                defaultMessage: 'Cannot Save',
              }),
            key,
          });
          if (response.data && Object.keys(response.data).length !== 0) {
          }
          return;
        }

        message.success({
          content:
            response.message ??
            intl.formatMessage({
              id: 'successfullySaved',
              defaultMessage: 'Successfully Allocated',
            }),
          key,
        });

        form.setFieldsValue({
          employeeId: null,
        });

        await getRelatedEmployeeList();
        setSelectedEmployee(null);
        setRefresh((prev) => prev + 1);
        // setAddApproverPoolFormVisible(false);
      })

      .catch((error: APIResponse) => {
        let errorMessage;
        let errorMessageInfo;
        if (error.message.includes('.')) {
          let errorMessageData = error.message.split('.');
          errorMessage = errorMessageData.slice(0, 1);
          errorMessageInfo = errorMessageData.slice(1).join('.');
        }
        message.error({
          content: error.message ? (
            <>{error.message}</>
          ) : (
            intl.formatMessage({
              id: 'failedToSave',
              defaultMessage: 'Cannot Save',
            })
          ),
          key,
        });
        if (error && Object.keys(error.data).length !== 0) {
          for (const feildName in error.data) {
            const errors = error.data[feildName];
            form.setFields([
              {
                name: feildName,
                errors: errors,
              },
            ]);
          }
        }
      });
  };

  const getAllOptions = async () => {
    try {
      const financialYearData = await financialYears({});

      const financialYearArray = financialYearData.data.map((finYear: any) => {
        return {
          label: finYear.financialDateRangeString,
          value: finYear.id,
        };
      });
      setFinanceYearList(financialYearArray);

      const claimTypesData = await getAllocationEnableClaimTypes({});

      const claimTypeArray = claimTypesData.data.map((claimType: any) => {
        return {
          label: claimType.typeName,
          value: claimType.id,
        };
      });

      const empStatusData = await getAllEmploymentStatus();
      const empStatusArray = empStatusData.data.map((empStatus) => {
        return {
          title: empStatus.name,
          key: empStatus.id,
        };
      });
      setEmploymentStatusList(empStatusArray);

      //get job Categories
      const jobCategoryData = await getAllJobCategories();

      const jobCatArray = jobCategoryData.data.map((jobCat) => {
        return {
          title: jobCat.name,
          key: jobCat.id,
        };
      });
      setJobCategoryList(jobCatArray);

      //get Designations
      const jobTitilesData = await getAllJobTitles();

      const jobTitleArray = jobTitilesData.data.map((jobTitle) => {
        return {
          title: jobTitle.name,
          key: jobTitle.id,
        };
      });
      setJobTitleList(jobTitleArray);

      setClaimTypeList(claimTypeArray);
      setSelectedEmployee(null);
      setSelectedFinacialYear(null);
      setSelectedClaimType(null);
      setRefresh((prev) => prev + 1);
      form.setFieldsValue({
        financialYear: null,
        employeeId: null,
        claimType: null,
      });

      console.log(financialYearData.data);
    } catch (err) {
      console.error(err);
    }
  };
  const handleCancel = () => {
    setIsModalVisible(false);
  };

  return (
    <>
      <Row>
        <Col span={12}>
          <Row
            style={{
              marginTop: 8,
              float: 'left',
              display: 'flex',
              marginRight: '2vh',
            }}
          >
            <Text style={{ fontSize: 22, color: '#394241' }}>{'Claim Allocations'}</Text>
          </Row>
        </Col>
        <Col span={12}>
          <Row
            style={{
              marginTop: 8,
              float: 'right',
              display: 'flex',
              marginRight: '2vh',
            }}
          >
            <Col span={15}>
              <Button
                type="primary"
                onClick={() => {
                  bulkCreateform.setFieldsValue({
                    bulkFinancialYear: null,
                    bulkClaimType: null,
                    allocationType: 'designation',
                    allowEmploymentDesignations: [],
                    allowEmploymentStatuses: [],
                    allowJobCategories: [],
                    allocationAmount: null,
                  });
                  setEmpStatusTargetKeys([]);
                  setJobCatTargetKeys([]);
                  setJobTitleTargetKeys([]);
                  setSelectedClaimTypeForBulkAllocation(null);
                  setSelectedFinacialYearForBulkAllocation(null);
                  setIsModalVisible(true);
                }}
                key="console"
              >
                Claim Bulk Allocation
              </Button>
            </Col>
          </Row>
        </Col>
      </Row>
      <Row style={{ marginTop: 20 }}>
        <Space direction="vertical" size={25} style={{ width: '100%' }}>
          <div
            style={{
              borderRadius: '5px',
              background: '#FFFFFF',
              width: '100%',
              marginBottom: '20px',
            }}
          >
            <Form form={form} onFinish={() => {}} autoComplete="off" layout="vertical">
              <Row>
                <Col
                  style={{
                    height: 35,
                    width: 250,
                  }}
                  span={6}
                >
                  <ProFormSelect
                    width={'100%'}
                    name="financialYear"
                    placeholder={intl.formatMessage({
                      id: 'financialYearDropdownLabel',
                      defaultMessage: 'Select Financial Year',
                    })}
                    label={'Financial Year'}
                    options={financeYearList}
                    showSearch
                    onChange={(val: any) => {
                      setSelectedFinacialYear(val);
                    }}
                  />
                </Col>

                <Col
                  style={{
                    height: 35,
                    width: 250,
                    paddingLeft: 20,
                  }}
                  span={6}
                >
                  <ProFormSelect
                    width={'100%'}
                    name="claimType"
                    placeholder={intl.formatMessage({
                      id: 'claimTypeDropdownLabel',
                      defaultMessage: 'Select Claim Type',
                    })}
                    label={'Claim Type'}
                    options={claimTypeList}
                    showSearch
                    onChange={(val: any) => {
                      setSelectedClaimType(val);
                    }}
                  />
                </Col>
              </Row>
              <Row style={{ marginTop: 50 }}>
                <Col
                  style={{
                    height: 35,
                    width: 250,
                  }}
                  span={6}
                >
                  <ProFormSelect
                    width={'100%'}
                    disabled={!canAddEmployee}
                    name="employeeId"
                    placeholder={intl.formatMessage({
                      id: 'leaveEntitlement.employee',
                      defaultMessage: 'Select Employee',
                    })}
                    label={'Employee'}
                    options={employeeList}
                    onChange={(val: any) => {
                      setSelectedEmployee(val);
                    }}
                    rules={[{ required: true, message: 'Required' }]}
                    showSearch
                  />
                </Col>

                <Col
                  style={{
                    height: 35,
                    width: 250,
                    paddingLeft: 20,
                    paddingTop: 30,
                  }}
                  span={6}
                >
                  <Button
                    disabled={!canAddEmployee}
                    onClick={() => {
                      handleAddEmployeeAllocation();
                    }}
                    type="primary"
                    key="console"
                  >
                    + Add Employee
                  </Button>
                </Col>
              </Row>
            </Form>
          </div>
        </Space>
      </Row>
      <Row style={{ marginTop: 40 }}>
        <Space direction="vertical" size={25} style={{ width: '100%' }}>
          <AllocationTable
            selectedFinacialYear={selectedFinacialYear}
            selectedClaimType={selectedClaimType}
            adminView={true}
            refresh={refresh}
            getRelatedEmployeeList={getRelatedEmployeeList}
            others={true}
            accessLevel={'admin'}
          />
        </Space>
      </Row>
      <Modal
        title={
          <FormattedMessage id="claim_bulk_allocation" defaultMessage="Claim Bulk Allocation" />
        }
        visible={isModalVisible}
        className={'claim_bulk_allocation'}
        width={800}
        onCancel={handleCancel}
        footer={[
          <>
            <Button key="back" onClick={handleCancel}>
              Cancel
            </Button>
            <Button
              key="submit"
              type="primary"
              // loading={loadingModelRequest}
              // disabled={disableModelButtons}
              onClick={createBulkAllocation}
            >
              <FormattedMessage id="save" defaultMessage="Save" />
            </Button>
          </>,
        ]}
      >
        <Spin spinning={isLoading}>
          <Row>
            <Form
              style={{ width: '100%' }}
              form={bulkCreateform}
              onFinish={() => {}}
              autoComplete="off"
              layout="vertical"
            >
              <Row
                className={'claimBulkAllocationModal'}
                gutter={{ xs: 8, sm: 16, md: 24, lg: 32 }}
              >
                <Col span={12}>
                  <ProFormSelect
                    width={'100%'}
                    name="bulkFinancialYear"
                    placeholder={intl.formatMessage({
                      id: 'financialYearDropdownLabel',
                      defaultMessage: 'Select Financial Year',
                    })}
                    label={'Financial Year'}
                    rules={[{ required: true, message: 'Required' }]}
                    options={financeYearList}
                    showSearch
                    onChange={(val: any) => {
                      // setSelectedFinacialYear(val);
                      setSelectedFinacialYearForBulkAllocation(val);
                    }}
                  />
                </Col>
                <Col span={12}>
                  <ProFormSelect
                    width={'100%'}
                    name="bulkClaimType"
                    placeholder={intl.formatMessage({
                      id: 'claimTypeDropdownLabel',
                      defaultMessage: 'Select Claim Type',
                    })}
                    label={'Claim Type'}
                    options={claimTypeList}
                    rules={[{ required: true, message: 'Required' }]}
                    showSearch
                    onChange={(val: any) => {
                      setSelectedClaimTypeForBulkAllocation(val);
                      // setSelectedClaimType(val);
                    }}
                  />
                </Col>
                <Col span={24}>
                  <Form.Item name={'allocationType'} label={'Allocation Type'}>
                    <Radio.Group
                      buttonStyle="solid"
                      onChange={(value) => {
                        console.log(value.target.value);
                        setSelectedAllocationType(value.target.value);
                        switch (value.target.value) {
                          case 'designation':
                            setEmpStatusTargetKeys([]);
                            setJobCatTargetKeys([]);
                            break;
                          case 'employmentStatus':
                            setJobCatTargetKeys([]);
                            setJobTitleTargetKeys([]);
                            break;
                          case 'jobCategory':
                            setEmpStatusTargetKeys([]);
                            setJobTitleTargetKeys([]);
                            break;

                          default:
                            break;
                        }
                      }}
                      defaultValue={'designation'}
                    >
                      {/* <Space direction="vertical"> */}
                      <Radio value={'designation'}>{'Designation'}</Radio>
                      <Radio value={'employmentStatus'}>{'Employment Status'}</Radio>
                      <Radio value={'jobCategory'}>{'Job Category'}</Radio>
                      {/* </Space> */}
                    </Radio.Group>
                  </Form.Item>
                </Col>
                {selectedAllocationType == 'designation' ? (
                  <Col span={24}>
                    <Form.Item
                      label={intl.formatMessage({
                        id: 'claimAllocation.selectDesignations',
                        defaultMessage: 'Select Employment Designation',
                      })}
                      name={'allowEmploymentDesignations'}
                      rules={[{ required: true, message: 'Required' }]}
                    >
                      <Transfer
                        dataSource={jobTitleList}
                        showSearch
                        filterOption={(search, item) => {
                          return item.title.toLowerCase().indexOf(search.toLowerCase()) >= 0;
                        }}
                        targetKeys={jobTitleTargetKeys}
                        onChange={(newTargetKeys: string[]) => {
                          setJobTitleTargetKeys(newTargetKeys);
                          // const currentValues = { ...props.values };
                          // currentValues['allowEmploymentStatuses'] = newTargetKeys;
                          // props.setValues(currentValues);
                        }}
                        render={(item) => item.title}
                        listStyle={{
                          width: 600,
                          height: 300,
                          marginBottom: 20,
                        }}
                        locale={{
                          itemUnit: 'Designation',
                          itemsUnit: 'Designations',
                        }}
                      />
                    </Form.Item>
                  </Col>
                ) : (
                  <></>
                )}

                {selectedAllocationType == 'employmentStatus' ? (
                  <Col span={24}>
                    <Form.Item
                      label={intl.formatMessage({
                        id: 'shiftAssign.selectJobCategories',
                        defaultMessage: 'Select Employment Status',
                      })}
                      name={'allowEmploymentStatuses'}
                      rules={[{ required: true, message: 'Required' }]}
                    >
                      <Transfer
                        dataSource={employmentStatusList}
                        showSearch
                        filterOption={(search, item) => {
                          return item.title.toLowerCase().indexOf(search.toLowerCase()) >= 0;
                        }}
                        targetKeys={empStatusTargetKeys}
                        onChange={(newTargetKeys: string[]) => {
                          setEmpStatusTargetKeys(newTargetKeys);
                          // const currentValues = { ...props.values };
                          // currentValues['allowEmploymentStatuses'] = newTargetKeys;
                          // props.setValues(currentValues);
                        }}
                        render={(item) => item.title}
                        listStyle={{
                          width: 600,
                          height: 300,
                          marginBottom: 20,
                        }}
                        locale={{
                          itemUnit: 'Employment Status',
                          itemsUnit: 'Employment Statuses',
                        }}
                      />
                    </Form.Item>
                  </Col>
                ) : (
                  <></>
                )}

                {selectedAllocationType == 'jobCategory' ? (
                  <Col span={24}>
                    <Form.Item
                      label={intl.formatMessage({
                        id: 'shiftAssign.selectJobCategories',
                        defaultMessage: 'Select Job Categories',
                      })}
                      name={'allowJobCategories'}
                      rules={[{ required: true, message: 'Required' }]}
                    >
                      <Transfer
                        dataSource={jobCategoryList}
                        showSearch
                        filterOption={(search, item) => {
                          return item.title.toLowerCase().indexOf(search.toLowerCase()) >= 0;
                        }}
                        targetKeys={jobCatTargetKeys}
                        onChange={(newTargetKeys: string[]) => {
                          setJobCatTargetKeys(newTargetKeys);
                          // const currentValues = { ...props.values };
                          // currentValues['allowJobCategories'] = newTargetKeys;
                          // props.setValues(currentValues);
                        }}
                        render={(item) => item.title}
                        listStyle={{
                          width: 600,
                          height: 300,
                          marginBottom: 20,
                        }}
                        locale={{
                          itemUnit: 'Job Category',
                          itemsUnit: 'Job Categories',
                        }}
                      />
                    </Form.Item>
                  </Col>
                ) : (
                  <></>
                )}
                <Col span={12}>
                  <ProFormDigit
                    label={intl.formatMessage({
                      id: 'shiftAssign.selectJobCategories',
                      defaultMessage: 'Amount',
                    })}
                    name={'allocationAmount'}
                    rules={[{ required: true, message: 'Required' }]}
                    min={0}
                    fieldProps={{ precision: 2 }}
                  />
                </Col>
              </Row>
            </Form>
          </Row>
        </Spin>
      </Modal>
    </>
  );
};

export default ClaimAllocation;
