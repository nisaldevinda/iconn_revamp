import React, { useState, useEffect } from 'react';
import { APIResponse } from '@/utils/request';
import { ResultStatusType } from 'antd/lib/result';
import { Models } from '@/services/model';
import { getBase64 } from '@/utils/fileStore';
import { UploadProps } from 'antd/es/upload/interface';
import { FormattedMessage, IntlShape } from 'react-intl';
import ErrorIcon from '@/assets/bulkUpload/error-icon.svg';
import { UploadOutlined, ReloadOutlined } from '@ant-design/icons';
import _ from 'lodash';
import {
  Upload,
  Button,
  Col,
  Result,
  Row,
  message,
  Card,
  Image,
  Spin,
  Divider,
  Space,
  Typography
} from 'antd';
import styles from './index.less'
import { EditableProTable } from '@ant-design/pro-table';

interface ResultTagProps {
  status: ResultStatusType;
  title: string;
  extraRender: React.ReactNode;
  subTitle: string;
}

interface ValidatorProps {
  intl: IntlShape;
  onFileUpload: (formData: any) => Promise<APIResponse | void>;
  onFinish: (formData: any) => Promise<APIResponse | void>;
  cardTitleRender: React.ReactNode;
  supportData: any;
}

interface ValidateButtonProps {
  validateType: ValidatorState;
  setValidatorState: any;
  uploadProps: UploadProps;
}

type ValidatorState = 'initial' | 'validation' | 'success'; // main validator states

// sub component which renders the Intial and Success Notfication in the validator card
const ResultTag: React.FC<ResultTagProps> = (props) => {
  return (
    <Result
      status={props.status}
      title={props.title}
      extra={props.extraRender}
      subTitle={props.subTitle}
      icon={<></>}
      className={styles.result}
    />
  );
};

// sub component which renders the upload button according to the validator state
const ValidateButton: React.FC<ValidateButtonProps> = (props) => {
  switch (props.validateType) {
    case 'initial':
      return (
        <Upload {...props.uploadProps}>
          <Button type="primary" icon={<UploadOutlined />}>
            <FormattedMessage id="bulk-upload-dataset-upload" defaultMessage=" Upload Datasheet" />
          </Button>
        </Upload>
      );
    case 'validation':
      return (
        <>
          <Upload {...props.uploadProps}>
            <Button type="primary" icon={<UploadOutlined />}>
              <FormattedMessage
                id="bulk-upload-dataset-reUpload"
                defaultMessage=" Reupload Datasheet"
              />
            </Button>
          </Upload>
          <Button style={{ marginLeft: 10 }} onClick={() => {
            props.setValidatorState('initial');
          }} key="back">
            Cancel
          </Button>
        </>

      );
    case 'success':
      return <></>;
  }
};

const Validator: React.FC<ValidatorProps> = (props) => {
  const [validatorState, setValidatorState] = useState<ValidatorState>('initial');
  const [successCount, setSuccessCount] = useState<number>(0);
  const [errorCount, setErrorCount] = useState<number>(0);
  const [uploadingState, setUploadingState] = useState<boolean>(false);
  const [data, setData] = useState([]);

  const [columns, setColumns] = useState<Array<any>>();

  useEffect(() => {
    const _columns = [
      {
        title: 'Employee',
        dataIndex: 'employeeId',
        valueType: 'select',
        request: async () => props.supportData.employees.map((option: any) => {
          return {
            value: option.id,
            label: option.name,
          }
        }),
        render: (dom: any, record: any) => <Space direction='vertical' style={{textAlign: 'left', width: '100%'}}>
          <Typography.Text>{dom}</Typography.Text>
          <Typography.Text type="danger">{record.error.employeeId}</Typography.Text>
        </Space>,
        width: 200,
        formItemProps: {
          rules: [
            {
              required: true,
              message: 'Required',
            },
          ],
        }
      },
      {
        title: 'Effective Date',
        dataIndex: 'effectiveDate',
        valueType: 'date',
        render: (dom: any, record: any) => <Space direction='vertical' style={{textAlign: 'left', width: '100%'}}>
          <Typography.Text>{dom}</Typography.Text>
          <Typography.Text type="danger">{record.error.effectiveDate}</Typography.Text>
        </Space>,
        width: 150,
        formItemProps: {
          rules: [
            {
              required: true,
              message: 'Required',
            },
          ],
        }
      },
      {
        title: 'Organization',
        dataIndex: 'orgStructureEntityId',
        valueType: 'select',
        request: async () => props.supportData.orgEntities.map((option: any) => {
          return {
            value: option.id,
            label: option.name,
          }
        }),
        render: (dom: any, record: any) => <Space direction='vertical' style={{textAlign: 'left', width: '100%'}}>
          <Typography.Text>{dom}</Typography.Text>
          <Typography.Text type="danger">{record.error.orgStructureEntityId}</Typography.Text>
        </Space>,
        width: 400
      },
      {
        title: 'Location',
        dataIndex: 'locationId',
        valueType: 'select',
        request: async () => props.supportData.locations.map((option: any) => {
          return {
            value: option.id,
            label: option.name,
          }
        }),
        render: (dom: any, record: any) => <Space direction='vertical' style={{textAlign: 'left', width: '100%'}}>
          <Typography.Text>{dom}</Typography.Text>
          <Typography.Text type="danger">{record.error.locationId}</Typography.Text>
        </Space>,
        width: 200
      },
      {
        title: 'New Job Title',
        dataIndex: 'jobTitleId',
        valueType: 'select',
        request: async () => props.supportData.jobTitles.map((option: any) => {
          return {
            value: option.id,
            label: option.name,
          }
        }),
        render: (dom: any, record: any) => <Space direction='vertical' style={{textAlign: 'left', width: '100%'}}>
          <Typography.Text>{dom}</Typography.Text>
          <Typography.Text type="danger">{record.error.jobTitleId}</Typography.Text>
        </Space>,
        width: 200
      },
      {
        title: 'New Pay Grade',
        dataIndex: 'payGradeId',
        valueType: 'select',
        request: async () => props.supportData.payGrades.map((option: any) => {
          return {
            value: option.id,
            label: option.name,
          }
        }),
        render: (dom: any, record: any) => <Space direction='vertical' style={{textAlign: 'left', width: '100%'}}>
          <Typography.Text>{dom}</Typography.Text>
          <Typography.Text type="danger">{record.error.payGradeId}</Typography.Text>
        </Space>,
        width: 200
      },
      {
        title: 'Calendar Type',
        dataIndex: 'calendarId',
        valueType: 'select',
        request: async () => props.supportData.workCalendars.map((option: any) => {
          return {
            value: option.id,
            label: option.name,
          }
        }),
        render: (dom: any, record: any) => <Space direction='vertical' style={{textAlign: 'left', width: '100%'}}>
          <Typography.Text>{dom}</Typography.Text>
          <Typography.Text type="danger">{record.error.calendarId}</Typography.Text>
        </Space>,
        width: 200
      },
      {
        title: 'Transfer Type',
        dataIndex: 'transferTypeId',
        valueType: 'select',
        request: async () => props.supportData.transferTypes.map((option: any) => {
          return {
            value: option.id,
            label: option.name,
          }
        }),
        render: (dom: any, record: any) => <Space direction='vertical' style={{textAlign: 'left', width: '100%'}}>
          <Typography.Text>{dom}</Typography.Text>
          <Typography.Text type="danger">{record.error.transferTypeId}</Typography.Text>
        </Space>,
        width: 200
      },
      {
        title: 'Transfer Reason',
        dataIndex: 'transferReason',
        valueType: 'text',
        render: (dom: any, record: any) => <Space direction='vertical' style={{textAlign: 'left', width: '100%'}}>
          <Typography.Text>{dom}</Typography.Text>
          <Typography.Text type="danger">{record.error.transferReason}</Typography.Text>
        </Space>,
        width: 200
      },
      {
        title: 'Option',
        valueType: 'option',
        fixed: 'right',
        render: (_, record, __, action) => [
          <a
            key="editable"
            onClick={() => {
              action?.startEditable?.(record.id);
            }}
          >
            Edit
          </a>,
          <a
            key="delete"
            onClick={() => {
              setData(data => data.filter((item: any) => item.id !== record.id));
            }}
          >
            Delete
          </a>
        ],
      }
    ];

    setColumns(_columns);
  }, [props.supportData])

  useEffect(() => {
    setErrorCount(data.filter((record: any) => record.hasError)?.length);
  }, [data])

  const uploadExcelFile = async (options: any) => {
    const { file } = options;

    if (!_.isUndefined(file) || !_.isEmpty(file)) {
      const fileURL = await getBase64(file);
      const queryParams = {
        modelName: Models.Employee,
        fileName: file.name,
        fileSize: file.size,
        fileType: file.type,
        file: fileURL.split(',')[1]
      };
      const key = 'uploading';
      message.loading({
        content: props.intl.formatMessage({
          id: 'uploading',
          defaultMessage: 'Uploading...',
        }),
        key,
      });
      setUploadingState(true);
      await props
        .onFileUpload(queryParams)
        .then((response: APIResponse) => {
          if (response.error) {
            message.error({
              content:
                response.message ??
                props.intl.formatMessage({
                  id: 'failedToUpload',
                  defaultMessage: 'Failed to upload file',
                }),
              key,
            });
            return;
          }

          let _data = response.data ?? [];
          let index = 0;

          _data = _data.map((record: any) => {
            const hasError = !_.isEmpty(Object.values(record.error).filter((value: any) => {
              return value != null
            }));
            return { ...record, hasError, id: ++index };
          })

          setData(_data);
          setValidatorState('validation');

          message.success({
            content:
              response.message ??
              props.intl.formatMessage({
                id: 'successfullyUploaded',
                defaultMessage: 'File uploaded sucessfully',
              }),
            key,
          });
          setUploadingState(false);
        })
        .catch((error: APIResponse) => {
          message.error({
            content:
              error.message ??
              props.intl.formatMessage({
                id: 'failedToUpload',
                defaultMessage: 'Failed to upload file',
              }),
            key,
          });
        });
    }
  };

  const uploadProps: UploadProps = {
    customRequest: uploadExcelFile,
    showUploadList: false,
    accept: 'application/vnd.ms-excel, application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    beforeUpload: (file) => {
      const key = 'uploading';
      const isXls = file.type === 'application/vnd.ms-excel';
      const isXlsx = file.type === 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
      if (!isXls && !isXlsx) {
        message.error({
          content:
            props.intl.formatMessage({
              id: 'failedToUpload',
              defaultMessage: 'File format should be .xls or .xlsx',
            }),
          key,
        });
        return isXls || Upload.LIST_IGNORE;
      }
    },
  };

  return (
    <Card
      // style={{ height: '70vh' }}
      title={
        <>
          <Row gutter={20}>
            <Col span={24}>
              <Row justify={'center'}>
                {validatorState == 'validation' && errorCount > 0 ? (
                  <Col span={24}>
                    <Row justify={'center'}>
                      <Col>
                        <Image style={{ marginTop: 5 }} src={ErrorIcon} preview={false} />
                      </Col>
                    </Row>
                    <Row justify={'center'}>
                      <Col>
                        <p
                          style={{ marginTop: 7 }}
                        >{`${errorCount} issues found, reslove and try again`}</p>
                      </Col>
                    </Row>
                  </Col>
                ) : (
                  <></>
                )}
              </Row>
            </Col>
            <Col span={24}>{props.cardTitleRender}</Col>
          </Row>
        </>
      }
    >
      <Spin spinning={uploadingState} size="large">
        {validatorState == 'initial' || validatorState == 'success' ? (
          <Row justify="space-around" align="middle" className={styles.result}>
            <ResultTag
              status={
                validatorState == 'initial'
                  ? 'info'
                  : validatorState == 'success'
                    ? 'success'
                    : 'warning'
              }
              title={
                validatorState == 'initial'
                  ? props.intl.formatMessage({
                    id: 'initialUpload',
                    defaultMessage: 'Validate Your Data Sheet',
                  })
                  : validatorState == 'success'
                    ? <>{props.intl.formatMessage({
                      id: 'successUpload',
                      defaultMessage:
                        successCount > 1
                          ? `${successCount} records uploaded successfully`
                          : `${successCount} record uploaded successfully`,
                    })}
                      <Button
                        type='link'
                        icon={<ReloadOutlined />}
                        block
                        onClick={() => setValidatorState('initial')}
                      />
                    </>
                    : ''
              }
              subTitle={
                validatorState == 'initial'
                  ? props.intl.formatMessage({
                    id: 'initialUpload',
                    defaultMessage: 'Please upload your Excel sheet to validate its data',
                  })
                  : ''
              }
              extraRender={[
                <ValidateButton setValidatorState={setValidatorState} validateType={validatorState} uploadProps={uploadProps} />,
              ]}
            />
          </Row>
        ) : (
          <Row style={{ marginBottom: 12, overflowY: 'scroll' }}>
            <Col span={24}>
              <EditableProTable
                pagination={{ pageSize: 10, defaultPageSize: 10, hideOnSinglePage: true }}
                rowKey="id"
                columns={columns}
                value={data}
                onChange={setData}
                recordCreatorProps={false}
                options={false}
                search={false}
                scroll={{ x: 1440 }}
                rowClassName={(record: any) => record.hasError ? styles.rowError : ''}
              />
              <Divider />
              {errorCount > 0
                ? <ValidateButton setValidatorState={setValidatorState} validateType={validatorState} uploadProps={uploadProps} />
                :
                <>
                  <Button
                    type="primary"
                    onClick={async () => {
                      const key = 'saving';
                      message.loading({
                        content: props.intl.formatMessage({
                          id: 'saving',
                          defaultMessage: 'Saving...',
                        }),
                        key,
                      });
                      setUploadingState(true);
                      await props.onFinish(data)
                        .then((response: APIResponse) => {
                          if (response.error) {
                            message.error({
                              content:
                                response.message ??
                                props.intl.formatMessage({
                                  id: 'failedToComplete',
                                  defaultMessage: 'Failed to Complete',
                                }),
                              key,
                            });
                            setUploadingState(false);
                            return;
                          }

                          setSuccessCount(response.data.length);
                          setValidatorState('success');

                          message.success({
                            content:
                              response.message ??
                              props.intl.formatMessage({
                                id: 'successfullyUploaded',
                                defaultMessage: 'Sucessfully completed',
                              }),
                            key,
                          });
                          setUploadingState(false);
                        })
                        .catch((error: APIResponse) => {
                          message.error({
                            content:
                              error.message ??
                              props.intl.formatMessage({
                                id: 'failedToComplete',
                                defaultMessage: 'Failed to Complete',
                              }),
                            key,
                          });
                          setUploadingState(false);
                        });
                    }}
                  >
                    <FormattedMessage id="complete-salary-increment-bulk-upload" defaultMessage="Complete Upload" />
                  </Button>
                  <Button style={{ marginLeft: 10 }} key="back" onClick={() => setValidatorState('initial')}>
                    Cancel
                  </Button>
                </>
              }
            </Col>
          </Row>
        )}
      </Spin>
    </Card>
  );
};

export default Validator;
