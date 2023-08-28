import {
  Form,
  Input,
  InputNumber,
  Popconfirm,
  Table,
  Typography,
  Tag,
  Tooltip,
  Row,
  Col,
  Card,
  Empty,
  List,
  Avatar,
} from 'antd';
import {
  EditOutlined,
  EyeOutlined,
  StopOutlined,
  DeleteOutlined,
  CloseCircleOutlined,
} from '@ant-design/icons';
import { color } from 'html2canvas/dist/types/css/types/color';
import React, { useEffect, useState, useRef } from 'react';
import ErrorIcon from '@/assets/bulkUpload/error-icon.svg';
import { ModalForm } from '@ant-design/pro-form';
import { FormattedMessage } from 'react-intl';
import { Access, useAccess, useIntl, history } from 'umi';
import request, { APIResponse } from '@/utils/request';
import ProTable from '@ant-design/pro-table';
import type { ProColumns, ActionType } from '@ant-design/pro-table';

interface Item {
  key: string;
  empNumber: string;
  entitlementCount: number;
  usedCount: number;
  leaveType: string;
}

const originData: Item[] = [];
for (let i = 0; i < 100; i++) {
  originData.push({
    key: i.toString(),
    empNumber: `E000${i}`,
    entitlementCount: 32,
    usedCount: 10,
    leaveType: `Leave Type . ${i}`,
  });
}
interface EditableCellProps extends React.HTMLAttributes<HTMLElement> {
  editing: boolean;
  dataIndex: string;
  title: any;
  inputType: 'number' | 'text';
  record: Item;
  index: number;
  children: React.ReactNode;
}

const EditableCell: React.FC<EditableCellProps> = ({
  editing,
  dataIndex,
  title,
  inputType,
  record,
  index,
  children,
  ...restProps
}) => {
  const inputNode = inputType === 'number' ? <InputNumber precision={1} step={0.5} /> : <Input />;

  return (
    <td {...restProps}>
      {editing ? (
        <Form.Item
          name={dataIndex}
          style={{ margin: 0 }}
          rules={[
            {
              required: true,
              message: `Please Input ${title}!`,
            },
          ]}
        >
          {inputNode}
        </Form.Item>
      ) : (
        children
      )}
    </td>
  );
};

interface ValidatorProps {
  tableData: any;
  setTableData: any;
  refresh?: number;
  setErrorCount: any;
}

const ValidateDataView: React.FC<ValidatorProps> = (props) => {
  const [form] = Form.useForm();
  const [data, setData] = useState([]);
  const [editingKey, setEditingKey] = useState('');
  const [selectedRowErrors, setSelectedRowErrors] = useState({});
  const [errorModalVisible, handleErrorModalVisible] = useState<boolean>(false);
  const intl = useIntl();
  const actionRef = useRef<ActionType>();

  const isEditing = (record: Item) => record.key === editingKey;

  const edit = (record: Partial<Item> & { key: React.Key }) => {
    form.setFieldsValue({ name: '', age: '', address: '', ...record });
    setEditingKey(record.key);
  };

  const deleteRecord = (key) => {
    const newData = [...props.tableData];
    let updatedRecordSet = [];
    newData.forEach((data) => {
      if (key !== data.key) {
        updatedRecordSet.push(data);
      }
    });

    props.setTableData(updatedRecordSet);
  };

  const view = async (record: Partial<Item> & { key: React.Key }) => {
    await setSelectedRowErrors(record.errorData);
    handleErrorModalVisible(true);
  };

  const cancel = () => {
    setEditingKey('');
  };

  useEffect(() => {
    actionRef.current?.reload();
  }, [props.refresh]);

  useEffect(() => {
    calculateErrorCount(props.tableData);
  }, [props.tableData]);

  useEffect(() => {
    setData(props.tableData);
  }, []);

  const save = async (key: React.Key) => {
    try {
      let row = (await form.validateFields()) as Item;

      const newData = [...props.tableData];
      const index = newData.findIndex((item) => key === item.key);
      if (index > -1) {
        const item = newData[index];
        row['employeeId'] = item['employeeId'];
        row['leaveTypeId'] = item['leaveTypeId'];
        row['leavePeriodFrom'] = item['leavePeriodFrom'];
        row['leavePeriodTo'] = item['leavePeriodTo'];

        validateNewData(row).then(function (result) {
          newData.splice(index, 1, {
            ...item,
            ...result,
          });
          setEditingKey('');
          props.setTableData(newData);
        });
      } else {
        newData.push(row);
        props.setTableData(newData);
        setEditingKey('');
      }
    } catch (errInfo) {
      console.log('Validate Failed:', errInfo);
    }
  };

  const calculateErrorCount = (tableData) => {
    let errCount = 0;
    tableData.map((col) => {
      errCount += col.errorData.employeeNumber ? col.errorData.employeeNumber.length : 0;
      errCount += col.errorData.usedCount ? col.errorData.usedCount.length : 0;
      errCount += col.errorData.entilementCount ? col.errorData.entilementCount.length : 0;
      errCount += col.errorData.other ? col.errorData.other.length : 0;
    });

    props.setErrorCount(errCount);
  };

  const validateNewData = async (dataSet: any) => {
    let errorData = {
      employeeNumber: [],
      entilementCount: [],
      usedCount: [],
      other: [],
    };
    let numOfErrorCount = 0;

    //validate Employee Number
    let params = { employeeNumber: dataSet['employeeNumber'] };
    let path = `/api/get-employee-employee-number`;
    const res = await request(path, { params });
    if (res.data.length == 0) {
      errorData.employeeNumber.push('Invalid Employee Number');
      numOfErrorCount++;
    } else {
      //check availability of entitlement
      params = {
        employeeId: dataSet['employeeId'],
        leaveTypeId: dataSet['leaveTypeId'],
        leavePeriodFrom: dataSet['leavePeriodFrom'],
        leavePeriodTo: dataSet['leavePeriodTo'],
      };
      path = `api/check-entitlement-availability`;
      const resl = await request(path, { params });
      if (resl.data) {
        errorData.other.push(
          'Already have entitilement for this leave type for this perticular employee for requested leave period',
        );
        numOfErrorCount++;
      }
    }

    //validate entitlement allocated count
    if (dataSet['entilementCount'] !== '' && dataSet['entilementCount'] !== null) {
      if (dataSet['entilementCount'] < 0) {
        errorData.entilementCount.push('Not allowed minus values');
        numOfErrorCount++;
      }

      if (isNaN(dataSet['entilementCount'])) {
        errorData.entilementCount.push('Only allowed numeric values');
        numOfErrorCount++;
      }

      if (dataSet['usedCount'] !== '' && dataSet['usedCount'] !== null) {
        if (dataSet['entilementCount'] < dataSet['usedCount']) {
          errorData.entilementCount.push('Should be greater than used count');
          numOfErrorCount++;
        }
      }
    }

    //validate entitlement allocated count
    if (dataSet['usedCount'] !== '' && dataSet['usedCount'] !== null) {
      if (dataSet['usedCount'] < 0) {
        errorData.usedCount.push('Not allowed minus values');
        numOfErrorCount++;
      }

      if (isNaN(dataSet['usedCount'])) {
        errorData.usedCount.push('Only allowed numeric values');
        numOfErrorCount++;
      }

      if (dataSet['entilementCount'] !== '' && dataSet['entilementCount'] !== null) {
        if (dataSet['entilementCount'] < dataSet['usedCount']) {
          errorData.usedCount.push('Should be less than entitlement count');
          numOfErrorCount++;
        }
      }
    }

    dataSet['hasErrors'] = numOfErrorCount > 0 ? true : false;
    dataSet['isfrontEndFix'] = numOfErrorCount > 0 ? false : true;
    dataSet['errorData'] = errorData;

    return dataSet;
  };

  const columns: ProColumns[] = [
    {
      title: 'Employee Number',
      dataIndex: 'employeeNumber',
      width: '20%',
      editable: true,
      render(text, record) {
        return {
          props: {
            style: record.hasErrors
              ? { background: 'rgb(255, 204, 204, 0.25)' }
              : { background: 'transparent' },
          },
          children: <div>{text}</div>,
        };
      },
    },
    {
      title: 'Leave Type',
      dataIndex: 'leaveType',
      width: '20%',
      editable: false,
      render(text, record) {
        return {
          props: {
            style: record.hasErrors
              ? { background: 'rgb(255, 204, 204, 0.25)' }
              : { background: 'transparent' },
          },
          children: <div>{text}</div>,
        };
      },
    },
    {
      title: 'Allocated',
      dataIndex: 'entilementCount',
      width: '15%',
      editable: true,
      render(text, record) {
        return {
          props: {
            style: record.hasErrors
              ? { background: 'rgb(255, 204, 204, 0.25)' }
              : { background: 'transparent' },
          },
          children: <div>{text}</div>,
        };
      },
    },
    {
      title: 'Used',
      dataIndex: 'usedCount',
      width: '15%',
      editable: true,
      render(text, record) {
        return {
          props: {
            style: record.hasErrors
              ? { background: 'rgb(255, 204, 204, 0.25)' }
              : { background: 'transparent' },
          },
          children: <div>{text}</div>,
        };
      },
    },
    {
      title: 'Status',
      width: '15%',
      dataIndex: 'status',
      render: (_: any, record: Item) => {
        const editable = isEditing(record);
        return {
          props: {
            style: record.hasErrors
              ? { background: 'rgb(255, 204, 204, 0.25)', textAlign: 'center' }
              : { background: 'transparent', textAlign: 'center' },
          },
          children: record.hasErrors ? (
            <Tag
              style={{
                borderRadius: 20,
                paddingRight: 20,
                paddingLeft: 20,
                paddingTop: 2,
                paddingBottom: 2,
                // border: 0,
              }}
              color="red"
            >
              {'Data Error'}
            </Tag>
          ) : (
            <Tag
              style={{
                borderRadius: 20,
                paddingRight: 20,
                paddingLeft: 20,
                paddingTop: 2,
                paddingBottom: 2,
                // border: 0,
              }}
              color="green"
            >
              {'Ready For Upload'}
            </Tag>
          ),
        };
      },
    },
    {
      title: 'Actions',
      width: '10%',
      dataIndex: 'actions',
      render: (_: any, record: Item) => {
        const editable = isEditing(record);
        return {
          props: {
            style: record.hasErrors
              ? { background: 'rgb(255, 204, 204, 0.25)' }
              : { background: 'transparent' },
          },
          children:
            record.hasErrors || record.isfrontEndFix ? (
              editable ? (
                <span>
                  <Typography.Link onClick={() => save(record.key)} style={{ marginRight: 8 }}>
                    Save
                  </Typography.Link>
                  <Popconfirm title="Sure to cancel?" onConfirm={cancel}>
                    <a>Cancel</a>
                  </Popconfirm>
                </span>
              ) : (
                <div style={{ display: 'flex' }}>
                  <Typography.Link disabled={editingKey !== ''} onClick={() => edit(record)}>
                    <Tooltip title="Edit Row">
                      <EditOutlined style={{ fontSize: 16 }} />
                    </Tooltip>
                  </Typography.Link>
                  <Typography.Link
                    disabled={editingKey !== ''}
                    style={{ marginLeft: 15 }}
                    onClick={() => view(record)}
                  >
                    {!record.isfrontEndFix ? (
                      <Tooltip title="View Errors">
                        <EyeOutlined style={{ fontSize: 16 }} />
                      </Tooltip>
                    ) : (
                      <></>
                    )}
                  </Typography.Link>
                  <Typography.Link disabled={editingKey !== ''} style={{ marginLeft: 15 }}>
                    {!record.isfrontEndFix ? (
                      <Tooltip title="Delete Record">
                        <Popconfirm
                          title="Sure to delete?"
                          onConfirm={() => deleteRecord(record.key)}
                        >
                          <DeleteOutlined style={{ fontSize: 16 }} />
                        </Popconfirm>
                      </Tooltip>
                    ) : (
                      <></>
                    )}
                  </Typography.Link>
                </div>
              )
            ) : (
              <></>
            ),
        };
      },
    },
  ];

  const mergedColumns = columns.map((col) => {
    if (!col.editable) {
      return col;
    }
    return {
      ...col,
      onCell: (record: Item) => ({
        record,
        inputType:
          col.dataIndex === 'usedCount' || col.dataIndex === 'entilementCount' ? 'number' : 'text',
        dataIndex: col.dataIndex,
        title: col.title,
        editing: isEditing(record),
      }),
    };
  });

  return (
    <>
      <Form form={form} component={false}>
        <ProTable
          actionRef={actionRef}
          search={false}
          components={{
            body: {
              cell: EditableCell,
            },
          }}
          bordered
          scroll={{ y: 440 }}
          dataSource={props.tableData}
          columns={mergedColumns}
          rowClassName="editable-row"
          pagination={false}
        />
      </Form>
      <ModalForm
        width={'70%'}
        title={intl.formatMessage({
          id: 'errorModal',
          defaultMessage: 'Error Details',
        })}
        modalProps={{
          destroyOnClose: true,
        }}
        onFinish={async (values: any) => {}}
        visible={errorModalVisible}
        onVisibleChange={handleErrorModalVisible}
        initialValues={{
          useMode: 'chapter',
        }}
        submitter={{
          render: () => {
            return [<>{[]}</>];
          },
        }}
      >
        <div className="site-card-wrapper">
          <Row gutter={16}>
            <Col span={6}>
              <Card
                style={{ backgroundColor: 'rgb(255, 204, 204, 0.18)', height: 270 }}
                title="Employee Number Field Errors"
                bordered={false}
              >
                {selectedRowErrors.employeeNumber ? (
                  <>
                    <List
                      itemLayout="horizontal"
                      dataSource={selectedRowErrors.employeeNumber}
                      renderItem={(item) => (
                        <List.Item>
                          <List.Item.Meta
                            avatar={
                              <Avatar
                                style={{ background: 'none', marginRight: -15, marginTop: -4 }}
                                icon={
                                  <CloseCircleOutlined style={{ color: 'red', fontSize: 20 }} />
                                }
                              />
                            }
                            title={<a>{item}</a>}
                          />
                        </List.Item>
                      )}
                    />
                  </>
                ) : (
                  <Empty image={Empty.PRESENTED_IMAGE_SIMPLE} description={'No Errors'}></Empty>
                )}
              </Card>
            </Col>
            <Col span={6}>
              <Card
                style={{ backgroundColor: 'rgb(255, 204, 204, 0.18)', height: 270 }}
                title="Entitlement Count Field Errors"
                bordered={false}
              >
                {selectedRowErrors.entilementCount ? (
                  <>
                    <List
                      itemLayout="horizontal"
                      dataSource={selectedRowErrors.entilementCount}
                      renderItem={(item) => (
                        <List.Item>
                          <List.Item.Meta
                            avatar={
                              <Avatar
                                style={{ background: 'none', marginRight: -15, marginTop: -4 }}
                                icon={
                                  <CloseCircleOutlined style={{ color: 'red', fontSize: 20 }} />
                                }
                              />
                            }
                            title={<a>{item}</a>}
                          />
                        </List.Item>
                      )}
                    />
                  </>
                ) : (
                  <Empty image={Empty.PRESENTED_IMAGE_SIMPLE} description={'No Errors'}></Empty>
                )}
              </Card>
            </Col>
            <Col span={6}>
              <Card
                style={{ backgroundColor: 'rgb(255, 204, 204, 0.18)', height: 270 }}
                title="Used Count Field Errors"
                bordered={false}
              >
                {selectedRowErrors.usedCount ? (
                  <>
                    <List
                      itemLayout="horizontal"
                      dataSource={selectedRowErrors.usedCount}
                      renderItem={(item) => (
                        <List.Item>
                          <List.Item.Meta
                            avatar={
                              <Avatar
                                style={{ background: 'none', marginRight: -15, marginTop: -4 }}
                                icon={
                                  <CloseCircleOutlined style={{ color: 'red', fontSize: 20 }} />
                                }
                              />
                            }
                            title={<a>{item}</a>}
                          />
                        </List.Item>
                      )}
                    />
                  </>
                ) : (
                  <Empty image={Empty.PRESENTED_IMAGE_SIMPLE} description={'No Errors'}></Empty>
                )}
              </Card>
            </Col>
            <Col span={6}>
              <Card
                style={{ backgroundColor: 'rgb(255, 204, 204, 0.18)', height: 270 }}
                title="Other Errors"
                bordered={false}
              >
                {selectedRowErrors.other ? (
                  <>
                    <List
                      itemLayout="horizontal"
                      dataSource={selectedRowErrors.other}
                      renderItem={(item) => (
                        <List.Item>
                          <List.Item.Meta
                            avatar={
                              <Avatar
                                style={{ background: 'none', marginRight: -15, marginTop: -4 }}
                                icon={
                                  <CloseCircleOutlined style={{ color: 'red', fontSize: 20 }} />
                                }
                              />
                            }
                            title={<a>{item}</a>}
                          />
                        </List.Item>
                      )}
                    />
                  </>
                ) : (
                  <Empty image={Empty.PRESENTED_IMAGE_SIMPLE} description={'No Errors'}></Empty>
                )}
              </Card>
            </Col>
          </Row>
        </div>
      </ModalForm>
    </>
  );
};

export default ValidateDataView;
