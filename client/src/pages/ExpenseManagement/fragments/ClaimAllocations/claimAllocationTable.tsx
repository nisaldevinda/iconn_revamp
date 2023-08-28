import React, { useEffect, useRef, useState, useContext } from 'react';
import { ProFormText, ProFormDatePicker, ProFormSwitch, ProFormDigit } from '@ant-design/pro-form';
import { InputRef, Select } from 'antd';
import {
  Button,
  Tag,
  Space,
  Image,
  Row,
  Col,
  Tooltip,
  Spin,
  Modal,
  Popover,
  DatePicker,
  TimePicker,
  Form,
  message,
  Popconfirm,
  ConfigProvider,
  InputNumber,
  Card,
  Result,
  Table,
  Input,
} from 'antd';
import type { ProColumns, ActionType } from '@ant-design/pro-table';
import moment from 'moment';
import { Access, FormattedMessage, Link, useAccess, useIntl } from 'umi';
import ProTable from '@ant-design/pro-table';
import _, { cond } from 'lodash';

import {
  updateInvalidAttendences,
  getInvalidAttendanceSheetAdminData,
  getRelatedBreakes,
} from '@/services/attendance';
import {
  getClaimAllocationData,
  updateEmployeeClaimAllocations,
  removeEmployeeClaimAllocation,
} from '@/services/expenseModule';
import { APIResponse } from '@/utils/request';
import { getEmployeeList, getOtPayTypeList } from '@/services/dropdown';
import { getAllWorkShifts } from '@/services/workShift';
import en_US from 'antd/lib/locale-provider/en_US';
import styles from './style.less';
// import './index.css';
import ProCard from '@ant-design/pro-card';
import type { FormInstance } from 'antd/es/form';
import { DeleteOutlined } from '@ant-design/icons';

moment.locale('en');

const EditableContext = React.createContext<FormInstance<any> | null>(null);

interface Item {
  id: number;
  date: string;
  employeeIdNo: number;
  name: string;
  shiftId: number;
  summaryId: number;
  timeZone: string;
  requestedTimeChangeId: number | null;
  shift: string;
  day: {
    isWorked: number;
    dayType: string;
  };
  leave: {
    name: string;
    color: string;
  }[];
  in: {
    time: string;
    late: boolean;
    date?: string;
  };
  out: {
    time: string;
    early: boolean;
    date?: string;
    isDifferentOutDate: boolean;
  };
  duration: {
    worked: string;
    breaks: string;
  };
}

interface EditableRowProps {
  index: number;
}

const EditableRow: React.FC<EditableRowProps> = ({ index, ...props }) => {
  const [form] = Form.useForm();
  return (
    <Form form={form} component={false}>
      <EditableContext.Provider value={form}>
        <tr {...props} />
      </EditableContext.Provider>
    </Form>
  );
};

interface EditableCellProps {
  title: React.ReactNode;
  editable: boolean;
  children: React.ReactNode;
  dataIndex: keyof Item;
  record: Item;
  handleSave: (record: Item) => void;
  handleIntialData: (record: Item) => void;
  setCurrentEditingRow: any;
  setChangedRecordIds: any;
  changedRecordIds: any;
  currentEditingRow: any;
  checkHasChanges: any;
  setLoading: any;
}

const EditableCell: React.FC<EditableCellProps> = ({
  title,
  editable,
  children,
  dataIndex,
  record,
  handleSave,
  handleIntialData,
  setCurrentEditingRow,
  setChangedRecordIds,
  changedRecordIds,
  currentEditingRow,
  checkHasChanges,
  setLoading,
  ...restProps
}) => {
  const [editing, setEditing] = useState(false);
  const [isSaving, setIsSaving] = useState(false);
  const inputRef = useRef<InputRef>(null);
  const form = useContext(EditableContext)!;
  const [workShiftList, setWorkShiftList] = useState([]);

  const [recordInDateModel, setRecordInDateModel] = useState('');

  useEffect(() => {
    if (editing) {
      if (dataIndex == 'shift') {
        callGetAllWorkShifts();
      }
      inputRef.current!.focus();
    }
  }, [editing]);

  async function callGetAllWorkShifts() {
    try {
      const response = await getAllWorkShifts();
      const { data } = response.data;
      const workShiftsArray = data.map((workshift: any) => {
        return {
          label: workshift.name,
          value: workshift.id,
        };
      });
      setWorkShiftList(workShiftsArray);
    } catch (err) {
      console.log(err);
    }
  }

  const toggleEdit = () => {
    if (!editing) {
      if (currentEditingRow != null && currentEditingRow != record.id) {
        return;
      }
    }

    let fieldErrors = 0;

    if (form.getFieldsError().length > 0) {
      form.getFieldsError().forEach((element: any) => {
        if (element.errors.length > 0) {
          fieldErrors += 1;
        }
      });
    }

    if (fieldErrors > 0) {
      return;
    }

    setEditing(!editing);

    setRecordInDateModel(record.date);
    setCurrentEditingRow(record.id);
    switch (dataIndex) {
      case 'allocatedAmount':
        form.setFieldsValue({ [dataIndex]: record[dataIndex] });
        break;
      default:
        form.setFieldsValue({ [dataIndex]: record[dataIndex] });
        break;
    }
  };

  const validatedRow = (record: any): boolean => {
    let invalidParam = false;
    if (!record.allocatedAmount) {
      invalidParam = true;
    } else {
      if (record.allocatedAmount < record.usedAmount) {
        invalidParam = true;
      } else {
        invalidParam = false;
      }
    }

    return invalidParam;
  };

  const save = async () => {
    try {
      setIsSaving(true);
      const values = await form.validateFields();
      let tempArr = {
        id: record.id,
        allocatedAmount: record.allocatedAmount,
        usedAmount: record.usedAmount,
        incompleUpdate: record.incompleUpdate,
        isChanged: record.isChanged,
        hasErrors: record.hasErrors,
      };

      if (values.hasOwnProperty('allocatedAmount')) {
        let allocatedAmount =
          values.allocatedAmount != null && values.allocatedAmount != ''
            ? values.allocatedAmount
            : 0;
        tempArr.allocatedAmount = allocatedAmount;
      }

      let hasRecordChange = await checkHasChanges(tempArr);

      console.log(tempArr);

      if (hasRecordChange) {
        const invalidParams = validatedRow(tempArr);
        // const invalidParams = null;
        console.log(invalidParams);

        tempArr.hasErrors = invalidParams ? true : false;
        tempArr.incompleUpdate = true;
        tempArr.isChanged = true;
        toggleEdit();
        setIsSaving(false);
        await handleSave({ ...record, ...tempArr });
      } else {
        tempArr.incompleUpdate = false;
        tempArr.isChanged = false;
        toggleEdit();
        setIsSaving(false);
        await handleSave({ ...record, ...tempArr });
      }
    } catch (errInfo) {
      console.log('Save failed:', errInfo);
    }
  };

  const getRules = (dataIndex) => {
    if (dataIndex == 'allocatedAmount') {
      return [{ required: true, message: 'Required' }];
    }
    return [];
  };

  let childNode = children;

  if (editable) {
    childNode = editing ? (
      <>
        {
          <Form.Item
            style={{
              margin: 0,
              width: dataIndex == 'allocatedAmount' ? '100%' : 100,
            }}
            name={dataIndex}
            rules={getRules(dataIndex)}
          >
            {dataIndex == 'allocatedAmount' ? (
              <InputNumber
                ref={inputRef}
                precision={2}
                style={{ borderRadius: 6, fontSize: 10, width: '100%' }}
                onKeyDown={(value) => {
                  if (value.target.value === '' || value.target.value == null) {
                    form.setFields([
                      {
                        name: 'allocatedAmount',
                        errors: ['Required'],
                      },
                    ]);
                    form.setFieldsValue({ allocatedAmount: 0 });

                    inputRef.current!.focus();
                    return;
                  }

                  if (value.target.value) {
                    if (value.target.value.match(/[a-zA-Z]/)) {
                      let key = 'Error';
                      form.setFieldsValue({ allocatedAmount: value.target.value });
                      form.setFields([
                        {
                          name: 'allocatedAmount',
                          errors: ['Invalid Amount'],
                        },
                      ]);

                      inputRef.current!.focus();
                      return;
                    }
                  }
                }}
                onPressEnter={(value) => {
                  if (value.target.value === '' || value.target.value == null) {
                    form.setFields([
                      {
                        name: 'allocatedAmount',
                        errors: ['Required'],
                      },
                    ]);
                    form.setFieldsValue({ allocatedAmount: 0 });

                    inputRef.current!.focus();
                    return;
                  }

                  if (value.target.value) {
                    if (value.target.value.match(/[a-zA-Z]/)) {
                      let key = 'Error';
                      form.setFieldsValue({ allocatedAmount: value.target.value });
                      form.setFields([
                        {
                          name: 'allocatedAmount',
                          errors: ['Invalid Amount'],
                        },
                      ]);

                      inputRef.current!.focus();
                      return;
                    }

                    if (value.target.value < record.usedAmount) {
                      let key = 'Error';
                      message.error({
                        content: 'Allocated amount should be greater than used amount',
                        key,
                      });

                      form.setFieldsValue({ allocatedAmount: record.allocatedAmount });
                      inputRef.current!.focus();
                      return;
                    }
                  }
                  save();
                }}
                onBlur={(value) => {
                  if (value.target.value === '' || value.target.value == null) {
                    form.setFields([
                      {
                        name: 'allocatedAmount',
                        errors: ['Required'],
                      },
                    ]);
                    form.setFieldsValue({ allocatedAmount: 0 });

                    inputRef.current!.focus();
                    return;
                  }

                  if (value.target.value) {
                    if (value.target.value.match(/[a-zA-Z]/)) {
                      let key = 'Error';
                      form.setFieldsValue({ allocatedAmount: value.target.value });
                      form.setFields([
                        {
                          name: 'allocatedAmount',
                          errors: ['Invalid Amount'],
                        },
                      ]);

                      inputRef.current!.focus();
                      return;
                    }

                    if (value.target.value < record.usedAmount) {
                      let key = 'Error';
                      message.error({
                        content: 'Allocated amount should be greater than used amount',
                        key,
                      });

                      form.setFieldsValue({ allocatedAmount: record.allocatedAmount });
                      inputRef.current!.focus();
                      return;
                    }
                  }
                  save();
                }}
              />
            ) : (
              <Input
                ref={inputRef}
                style={{ borderRadius: 6, fontSize: 10 }}
                onPressEnter={save}
                onBlur={save}
              />
            )}
          </Form.Item>
        }
      </>
    ) : (
      <div
        className={styles.editableCellValueWrap}
        style={{ paddingRight: 24 }}
        onClick={toggleEdit}
      >
        {children}
      </div>
    );
  }

  return <td {...restProps}>{childNode}</td>;
};

type EditableTableProps = Parameters<typeof Table>[0];

interface DataType {
  key: React.Key;
  name: string;
  age: string;
  address: string;
}

type ColumnTypes = Exclude<EditableTableProps['columns'], undefined>;

export type TableViewProps = {
  employeeId?: number;
  others?: boolean;
  refresh: any;
  nonEditModel?: boolean;
  adminView?: boolean;
  getRelatedEmployeeList: any;
  selectedFinacialYear: any;
  selectedClaimType: any;
  accessLevel?: string;
};

type AttendanceItem = {
  id: number;
  date: string;
  employeeIdNo: number;
  name: string;
  shiftId: number;
  summaryId: number;
  timeZone: string;
  requestedTimeChangeId: number | null;
  shift: string;
  day: {
    isWorked: number;
    dayType: string;
  };
  leave: {
    name: string;
    color: string;
  }[];
  in: {
    time: string;
    late: boolean;
    date?: string;
  };
  out: {
    time: string;
    early: boolean;
    date?: string;
    isDifferentOutDate: boolean;
  };
  duration: {
    worked: string;
    breaks: string;
  };
};

const EmployeeClaimAllocationTableView: React.FC<TableViewProps> = (props) => {
  const intl = useIntl();
  const access = useAccess();
  const { hasPermitted } = access;

  const actionRef = useRef<ActionType>();
  const [selectorEmployees, setSelectorEmployees] = useState([]);
  const [payTypes, setPayTypes] = useState([]);
  const [claimAllocationSheetData, setClaimAllocationSheetData] = useState([]);
  const [intialData, setIntialData] = useState<any>([]);
  const [dataCount, setDataCount] = useState(0);
  const [tableState, setTableState] = useState<any>({});
  const [othersView, setOthersView] = useState(props.others ?? false);
  const [adminView, setAdminView] = useState(props.adminView ?? false);
  const [loading, setLoading] = useState(false);

  const [isModalVisible, setIsModalVisible] = useState(false);
  const [disableModelButtons, setDisableModelButtons] = useState(false);
  const [loadingModelRequest, setLoadingModelRequest] = useState(false);
  const [currentEditingRow, setCurrentEditingRow] = useState(null);
  const [changedRecordIds, setChangedRecordIds] = useState(null);
  const [summaryIdModel, setSummaryIdModel] = useState<number>();
  const [inDateModel, setInDateModel] = useState<moment.Moment>();
  const [outDateModel, setOutDateModel] = useState<moment.Moment>();
  const [filteredShiftDay, setFilteredShiftDay] = useState<moment.Moment>(
    moment().subtract(1, 'day'),
  );

  const [inTimeModel, setInTimeModel] = useState<moment.Moment>();

  const [outTimeModel, setOutTimeModel] = useState<moment.Moment>();
  const key = 'saving';
  const summaryUrl = '/attendance-manager/summary';
  const [relateScope, setRelateScope] = useState<string | null>(null);
  const { RangePicker } = DatePicker;
  const [form] = Form.useForm();

  const [count, setCount] = useState(2);

  const handleSave = (row: DataType) => {
    const newData = [...claimAllocationSheetData];
    const index = newData.findIndex((item) => row.id === item.id);
    const item = newData[index];
    newData.splice(index, 1, {
      ...item,
      ...row,
    });

    setClaimAllocationSheetData([...newData]);
    setCurrentEditingRow(null);
  };

  const handleIntialData = (row: DataType) => {
    const newData = [...intialData];
    const index = newData.findIndex((item) => row.id === item.id);
    console.log(index);
    console.log(row);
    const item = newData[index];
    newData.splice(index, 1, {
      ...item,
      ...row,
    });

    setIntialData([...newData]);
  };

  useEffect(() => {
    actionRef.current?.reload();
  }, [props.refresh]);

  const checkHasChanges = (editedRow) => {
    const index = intialData.findIndex((item) => editedRow.id === item.id);
    if (index == -1) {
      return false;
    }

    let orginalRecord = intialData[index];

    //check whether in time is changed
    if (orginalRecord.allocatedAmount !== editedRow.allocatedAmount) {
      return true;
    }

    return false;
  };

  const components = {
    body: {
      row: EditableRow,
      cell: EditableCell,
    },
  };

  var columns: ProColumns<AttendanceItem>[] = [
    {
      title: (
        <FormattedMessage
          id="employeeName"
          defaultMessage={intl.formatMessage({
            id: 'employeeName',
            defaultMessage: 'Employee Name',
          })}
        />
      ),
      dataIndex: 'empName',
      width: 250,
      hideInSearch: true,
      render: (_, record) => {
        return {
          props: {
            style: record?.incompleUpdate ? { background: '#FCFEF1' } : {},
          },
          children: <Space>{record.firstName + ' ' + record.lastName}</Space>,
        };
      },
    },
    {
      title: (
        <FormattedMessage
          id="amountAllocated"
          defaultMessage={intl.formatMessage({
            id: 'amountAllocated',
            defaultMessage: 'Amount Allocated',
          })}
        />
      ),
      dataIndex: 'allocatedAmount',
      search: false,
      width: 200,
      editable: true,
      render: (_, record) => {
        return {
          props: {
            style: record?.incompleUpdate ? { background: '#FCFEF1' } : {},
          },
          children: <Space>{record.allocatedAmount}</Space>,
        };
      },
    },
    {
      title: (
        <FormattedMessage
          id="usedToDate"
          defaultMessage={intl.formatMessage({
            id: 'usedToDate',
            defaultMessage: 'Used To Date',
          })}
        />
      ),
      dataIndex: 'usedAmount',
      width: 200,
      hideInSearch: true,
      render: (_, record) => {
        return {
          props: {
            style: record?.incompleUpdate ? { background: '#FCFEF1' } : {},
          },
          children: <Space>{record.usedAmount}</Space>,
        };
      },
    },
    {
      title: (
        <FormattedMessage
          id="balanceAmount"
          defaultMessage={intl.formatMessage({
            id: 'inTime',
            defaultMessage: 'Balance Amount',
          })}
        />
      ),
      dataIndex: 'balanceAmount',
      search: false,
      width: 200,
      render: (_, record) => {
        return {
          props: {
            style: record?.incompleUpdate ? { background: '#FCFEF1' } : {},
          },
          children: <Space>{record.allocatedAmount - record.usedAmount}</Space>,
        };
      },
    },
    {
      title: '',
      width: 50,
      render: (_, record) => {
        return {
          props: {
            style: record?.incompleUpdate ? { background: '#FCFEF1' } : {},
          },
          children: (
            <Space>
              <Popconfirm
                title={intl.formatMessage({
                  id: 'are_you_sure',
                  defaultMessage: 'Are you sure?',
                })}
                onConfirm={() => removeRecord(record)}
                okText="Yes"
                cancelText="No"
              >
                <Tooltip
                  title={intl.formatMessage({
                    id: 'delete',
                    defaultMessage: 'Delete',
                  })}
                >
                  <a data-key={`${'salaries'}.${record.id}.delete`}>
                    {' '}
                    <DeleteOutlined />{' '}
                  </a>
                </Tooltip>
              </Popconfirm>
            </Space>
          ),
        };
      },
    },
  ];

  useEffect(() => {
    if (othersView) {
      callGetEmployeeData();
      callGetOTPayTypesData();
    }
  }, []);

  useEffect(() => {
    if (isModalVisible) {
      getRelatedBreakDetails();
    }
  }, [isModalVisible]);

  useEffect(() => {
    if (props.selectedClaimType && props.selectedFinacialYear) {
      callGetEmployeeCalimAllocationData(1, 10).then(() => {});
    }
  }, [props.selectedClaimType, props.selectedFinacialYear]);

  useEffect(() => {
    if (outDateModel == undefined || outTimeModel || undefined || inTimeModel == undefined) {
      form.setFieldsValue({ relatedBreaksDetails: null });
    }
  }, [inDateModel, inTimeModel, outDateModel, outTimeModel]);

  const getRelatedBreakDetails = async () => {
    let realtedBreaks = await getRelatedBreakes({ summeryId: summaryIdModel });
    const breakeData = realtedBreaks.data.map((col) => {
      return {
        id: col.id,
        breakInTime: moment(col.breakInTime, 'hh:mm A'),
        breakInDate: moment(col.breakInDate, 'YYYY-MM-DD'),
        breakOutTime: moment(col.breakOutTime, 'hh:mm A'),
        breakOutDate: moment(col.breakOutDate, 'YYYY-MM-DD'),
      };
    });

    await form.setFieldsValue({ relatedBreaksDetails: breakeData });
    setLoading(false);
  };

  const cols = columns.map((col) => {
    if (!col.editable) {
      return col;
    }
    return {
      ...col,
      onCell: (record: DataType) => ({
        record,
        editable: col.editable,
        dataIndex: col.dataIndex,
        title: col.title,
        handleSave,
        handleIntialData,
        currentEditingRow: currentEditingRow,
        setCurrentEditingRow: setCurrentEditingRow,
        setChangedRecordIds: setChangedRecordIds,
        changedRecordIds: changedRecordIds,
        checkHasChanges,
        setLoading,
      }),
    };
  });

  async function callGetEmployeeData() {
    let scope = 'EMPLOYEE';
    if (adminView) {
      scope = 'ADMIN';
    } else if (othersView) {
      scope = 'MANAGER';
    }
    try {
      const response = await getEmployeeList(scope);
      const { data } = response;
      const selectorEmployees = data.map((employee: any) => {
        return {
          label: employee.employeeName,
          value: employee.id,
        };
      });
      setSelectorEmployees(selectorEmployees);
    } catch (err) {
      console.log(err);
    }
  }

  async function callGetOTPayTypesData() {
    let scope = 'EMPLOYEE';
    if (adminView) {
      scope = 'ADMIN';
    } else if (othersView) {
      scope = 'MANAGER';
    }
    try {
      const response = await getOtPayTypeList(scope);
      setPayTypes(response.data);
    } catch (err) {
      console.log(err);
    }
  }

  async function callGetEmployeeCalimAllocationData(
    pageNo?: number,
    pageCount?: number,
    sort = { name: 'date', order: 'DESC' },
  ) {
    setLoading(true);
    let shiftDate = filteredShiftDay ? filteredShiftDay.format('YYYY-MM-DD') : null;

    let scope = 'EMPLOYEE';
    if (props.accessLevel == 'admin') {
      scope = 'ADMIN';
    } else if (props.accessLevel == 'manager') {
      scope = 'MANAGER';
    }

    const params = {
      selectedClaimType: props.selectedClaimType,
      selectedFinacialYear: props.selectedFinacialYear,
      sort: sort,
      scope: scope,
      pageNo: pageNo,
      pageCount: pageCount,
    };

    setClaimAllocationSheetData([]);
    setIntialData([]);
    setDataCount(0);
    await getClaimAllocationData(params)
      .then((response: any) => {
        if (response) {
          setClaimAllocationSheetData(response.data.sheets);
          let orgData = [];
          response.data.sheets.map((sheet) => {
            let tempObj = {
              id: sheet.id,
              allocatedAmount: sheet.allocatedAmount,
            };
            orgData.push(tempObj);
          });

          setIntialData([...orgData]);
          setDataCount(response.data.count);
        }
        setLoading(false);
      })
      .catch(() => {
        setLoading(false);
      });
  }

  const handleCancel = () => {
    setIsModalVisible(false);
  };

  const updateEmployeeClaimAllocation = (breakList: any) => {
    if (currentEditingRow != null) {
      return;
    }

    setLoading(true);
    let changedRecords = [];
    let changedRecordIds = [];
    claimAllocationSheetData.map((el, index) => {
      if (el.isChanged && !el.hasErrors) {
        changedRecords.push(el);
        changedRecordIds.push(el.id);
      }
    });

    let params = {
      updatedClaimAllocations: JSON.stringify(changedRecords),
    };

    updateEmployeeClaimAllocations(params)
      .then((response: any) => {
        setLoading(false);

        if (response.data) {
          actionRef.current?.reload();

          message.success({
            content: intl.formatMessage({
              id: 'invalidAttendanceUpdated',
              defaultMessage: 'Employee claim allocations updated successfully.',
            }),
            key,
          });
        }

        if (response.error) {
          message.error({
            content: intl.formatMessage({
              id: 'rejectedInvalidAttendance',
              defaultMessage: 'Failed to save the invalid attendance.',
            }),
            key,
          });
        }
      })
      .catch((error: APIResponse) => {
        setLoadingModelRequest(false);
        setDisableModelButtons(false);

        message.error({
          content:
            error.message ??
            intl.formatMessage({
              id: 'rejectedInvalidAttendance',
              defaultMessage: 'Failed to save the invalid attendance.',
            }),
          key,
        });
      });
  };

  const removeRecord = async (record: any) => {
    let res = await removeEmployeeClaimAllocation({ id: record.id });
    actionRef.current?.reload();
    props.getRelatedEmployeeList();
  };

  const disableDatesForFilter = (current: any) => {
    let compareDate = moment(current, 'YYYY-MM-DD').format('YYYY-MM-DD');
    const isNextDay = moment(compareDate, 'YYYY-MM-DD') >= moment();
    const isEqualToCurrentDate = moment(compareDate, 'YYYY-MM-DD').isSame(
      moment().format('YYYY-MM-DD'),
    );

    const isDisable = isNextDay || isEqualToCurrentDate;
    return isDisable;
  };

  const isSaveBtnDisable = () => {
    let changedRecords = [];
    claimAllocationSheetData.map((el, index) => {
      if (el.isChanged && !el.hasErrors) {
        changedRecords.push(el);
      }
    });
    return !(changedRecords.length > 0);
  };

  return (
    <ProCard direction="column" ghost gutter={[0, 16]} style={{ padding: 0, margin: 0 }}>
      <Row style={{ width: '100%' }}>
        <Col style={{ width: '100%' }}>
          <ConfigProvider locale={en_US}>
            <Space direction="vertical" size={25} style={{ width: '100%' }}>
              <>
                {
                  <Row>
                    <Col span={24}>
                      <div style={{ paddingLeft: 5 }}>
                        <Spin size="large" spinning={loading}>
                          <Row>
                            <ProTable<AttendanceItem>
                              columns={cols as ColumnTypes}
                              scroll={claimAllocationSheetData.length > 0 ? { y: 400 } : undefined}
                              components={components}
                              rowClassName={() => 'editableRow'}
                              actionRef={actionRef}
                              dataSource={claimAllocationSheetData}
                              request={async (
                                params = { current: 1, pageSize: 10 },
                                sort,
                                filter,
                              ) => {
                                const sortValue = sort?.name
                                  ? {
                                      name: 'name',
                                      order: sort?.name === 'ascend' ? 'ASC' : 'DESC',
                                    }
                                  : {
                                      name: 'date',
                                      order: sort?.date === 'ascend' ? 'ASC' : 'DESC',
                                    };

                                const tableParams = {
                                  current: params?.current,
                                  pageSize: params?.pageSize,
                                  sortValue: sortValue,
                                };
                                setTableState(tableParams);

                                await callGetEmployeeCalimAllocationData(
                                  params?.current,
                                  params?.pageSize,
                                  sortValue,
                                );
                                return claimAllocationSheetData;
                              }}
                              toolBarRender={false}
                              pagination={{ pageSize: 10, total: dataCount }}
                              search={false}
                              style={{ width: '100%', fontSize: 10 }}
                            />
                          </Row>
                        </Spin>
                      </div>
                    </Col>
                    <Col span={24}>
                      <Row
                        className={'invalid-attendance-btn-section'}
                        style={{ marginTop: 20, float: 'right' }}
                      >
                        <Button key="back" style={{ marginRight: 10 }} onClick={handleCancel}>
                          Cancel
                        </Button>
                        <Button
                          key="submit"
                          type="primary"
                          loading={loadingModelRequest}
                          disabled={isSaveBtnDisable()}
                          onClick={updateEmployeeClaimAllocation}
                        >
                          <FormattedMessage id="save" defaultMessage="Save" />
                        </Button>
                      </Row>
                    </Col>
                  </Row>
                }
              </>
            </Space>
          </ConfigProvider>
        </Col>
      </Row>
    </ProCard>
  );
};

export default EmployeeClaimAllocationTableView;
