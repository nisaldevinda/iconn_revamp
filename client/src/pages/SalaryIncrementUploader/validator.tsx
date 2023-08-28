import React, { useState } from 'react';
import { APIResponse } from '@/utils/request';
import { ResultStatusType } from 'antd/lib/result';
import { Models } from '@/services/model';
import { getBase64 } from '@/utils/fileStore';
import { UploadProps } from 'antd/es/upload/interface';
import { FormattedMessage, IntlShape } from 'react-intl';
import ErrorIcon from '@/assets/bulkUpload/error-icon.svg';
import { ProFormText } from '@ant-design/pro-form';
import { UploadOutlined, ReloadOutlined } from '@ant-design/icons';
import _ from 'lodash';
import {
  Upload,
  Button,
  Col,
  Result,
  Space,
  List,
  Row,
  Typography,
  message,
  Card,
  Image,
  Spin,
  Divider,
} from 'antd';
import styles from './index.less'
import ProTable from '@ant-design/pro-table';
interface ResultTagProps {
  status: ResultStatusType;
  title: string;
  extraRender: React.ReactNode;
  subTitle: string;
}
interface FeildSetProps {
  feildNames: any[];
  feildData: any;
}
interface ValidatorProps {
  intl: IntlShape;
  onFileUpload: (formData: any) => Promise<APIResponse | void>;
  onFinish: (formData: any) => Promise<APIResponse | void>;
  cardTitleRender: React.ReactNode;
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

// sub component which renders the dynamic feildset in the validator card
const FeildSet: React.FC<FeildSetProps> = (props) => {
  return props.feildNames.map((feildKey: string, feildIndex: number) => {
    return (
      <>
        <Space
          key={feildIndex}
          style={{ display: 'inline-flex', marginLeft: 10, marginBottom: 30, marginTop: 10 }}
          align="start"
        >
          <ProFormText
            key={feildIndex}
            name={feildKey}
            validateStatus={'error'}
            help={props.feildData[feildKey].errorMessage}
            placeholder={props.feildData[feildKey].value}
            label={props.feildData[feildKey].name}
            disabled={true}
          />
        </Space>
      </>
    );
  });
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
          <Button style={{marginLeft: 10}} onClick={() => {
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

  const uploadExcelFile = async (options: any) => {
    const { onSuccess, onError, file, onProgress } = options;
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

          setData(response.data);
          const errorIncrementalList = response.data.filter(incremental => incremental.validation.error == true);

          let totalSalaryDetailErrors = 0;
          for (let key in response.data) {
            if (response.data[key].salaryDetails != null) {
              const salaryDetailErrorList = response.data[key].salaryDetails.filter(sld => sld.validation.error == true);
              totalSalaryDetailErrors += salaryDetailErrorList.length;
            }
          }

          setValidatorState('validation');
          setErrorCount(errorIncrementalList.length + totalSalaryDetailErrors);

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
              <ProTable
                pagination={{ pageSize: 10, defaultPageSize: 10, hideOnSinglePage: true }}
                rowKey="id"
                columns={[
                  {
                    title: 'Employee',
                    dataIndex: 'employee',
                    render: (_, record) => <Space direction='vertical'>
                      <p>{record.employeeNumber} - {record.employeeName}</p>
                      {record?.validation?.error && record?.validation?.msg == 'Employee not found' && <p style={{ color: '#cf1322' }}>{record?.validation?.msg}</p>}
                      {record?.validation?.error && record?.validation?.msg == 'Invalid pay grade' && <p style={{ color: '#cf1322' }}>{record?.validation?.msg}</p>}
                    </Space>
                  },
                  {
                    title: 'Effective Date',
                    dataIndex: 'effectiveDate',
                    render: (_, record) => <Space direction='vertical'>
                      <p>{record.effectiveDate}</p>
                      {record?.validation?.error && record?.validation?.msg == 'Invalid date' && <p style={{ color: '#cf1322' }}>{record?.validation?.msg}</p>}
                    </Space>
                  },
                  {
                    title: 'Salary Details',
                    dataIndex: 'salaryDetails',
                    render: (_, record) => <>
                      <List
                        size="small"
                        dataSource={record.salaryDetails ?? []}
                        renderItem={(salaryComponent) => <List.Item
                          style={record?.validation?.data?.includes(salaryComponent.salaryComponentName) || salaryComponent.validation?.error ? { backgroundColor: '#ffa39e', color: '#cf1322' } : {}}
                        >
                          {salaryComponent.salaryComponentName.concat(': ')}
                          <Space style={{ float: 'right' }}>
                            {salaryComponent.value}
                          </Space>
                          <br />
                          {salaryComponent?.validation?.error ? <p style={{ color: '#cf1322' }}>Invalid Value</p>
                          : <></>}

                        </List.Item>}
                      />
                      {record?.validation?.error && record?.validation?.msg == 'Invalid salary component' && <p style={{ color: '#cf1322' }}>Invalid highlighted salary component</p>}
                    </>
                  }
                ]}
                dataSource={data}
                options={false}
                search={false}
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
                          });
                      }}
                    >
                      <FormattedMessage id="complete-salary-increment-bulk-upload" defaultMessage="Complete Upload" />
                    </Button>
                    <Button style={{marginLeft: 10}} key="back" onClick={() => setValidatorState('initial')}>
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
