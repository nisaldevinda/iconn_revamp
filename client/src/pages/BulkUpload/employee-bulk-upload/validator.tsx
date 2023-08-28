import React, { useState } from 'react';
import { APIResponse } from '@/utils/request';
import { ResultStatusType } from 'antd/lib/result';
import { Models } from '@/services/model';
import { getBase64 } from '@/utils/fileStore';
import { UploadProps } from 'antd/es/upload/interface';
import { FormattedMessage, IntlShape } from 'react-intl';
import ErrorIcon from '@/assets/bulkUpload/error-icon.svg';
import { ProFormText } from '@ant-design/pro-form';
import { UploadOutlined } from '@ant-design/icons';
import _ from 'lodash';
import {
  Upload,
  Button,
  Col,
  Result,
  Space,
  Form,
  Row,
  Typography,
  message,
  Card,
  Image,
  Spin,
} from 'antd';
import styles from './index.less'
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
  cardTitleRender: React.ReactNode;
}
interface ValidateButtonProps {
  validateType: ValidatorState;
  uploadProps: UploadProps;
}

type ResponseData = {
  errors: [];
  hasValidationErrors: boolean;
  addedCount: number;
};

type ValidatorState = 'initial' | 'error' | 'success'; // main validator states

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
    case 'error':
      return (
        <Upload  {...props.uploadProps}>
          <Button style={{marginTop: 35}} type="primary" icon={<UploadOutlined />}>
            <FormattedMessage
              id="bulk-upload-dataset-reUpload"
              defaultMessage=" Reupload Datasheet"
            />
          </Button>
        </Upload>
      );
    case 'success':
      return <></>;
  }
};

const Validator: React.FC<ValidatorProps> = (props) => {
  const [errorFeilds, setErrorFeilds] = useState<any>([]);
  const [errorCount, setErrorCount] = useState<any>(0);
  const [validatorState, setValidatorState] = useState<ValidatorState>('initial');
  const [addedCount, setAddedCount] = useState<number>(0);
  const [uploadingState, setUploadingState] = useState<boolean>(false);

  const uploadExcelFile = async (options: any) => {
    const { onSuccess, onError, file, onProgress } = options;
    if (!_.isUndefined(file) || !_.isEmpty(file)) {
      const fileURL = await getBase64(file);
      const queryParams = {
        modelName: Models.Employee,
        fileName:file.name,
        fileSize:file.size,
        fileType:file.type,
        file: fileURL.split(',')[1]
      };
      const key = 'saving';
      message.loading({
        content: props.intl.formatMessage({
          id: 'saving',
          defaultMessage: 'Saving...',
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

          if (!_.isUndefined(response)) {
            const responseData: ResponseData = response.data;
            if (!_.isUndefined(responseData) && !_.isNull(responseData)) {
              setAddedCount(responseData.addedCount);
              if (!responseData.hasValidationErrors) {
                setValidatorState('success');
              }
            }
          }
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
          if (!_.isUndefined(error)) {
            const responseData: ResponseData = error.data;
            if (!_.isUndefined(responseData) && !_.isNull(responseData)) {
              if (responseData.hasValidationErrors) {
                setValidatorState('error');
                setUploadingState(false);
                if (!_.isEmpty(responseData.errors) || !_.isUndefined(responseData.errors)) {
                  let errorCount = 0;
                  responseData.errors.map((feildData: any, index: number) => {
                    if (Object.keys(feildData).length > 0) {
                      errorCount ++;
                    }
                  });

                  setErrorCount(errorCount);
                  setErrorFeilds(responseData.errors);
                }
              }
            }
          }
        });
    }
  };

  const uploadProps: UploadProps = {
    customRequest: uploadExcelFile,
    showUploadList: false,
    accept: 'application/vnd.ms-excel',
    beforeUpload: (file) => {
      const key = 'uploading';
      const isXls = file.type === 'application/vnd.ms-excel';
      const isXlsx =  file.type === 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
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
      style={{ height: '70vh' }}
      title={
        <>
          <Row gutter={20}>
            <Col span={24}>
              <Row justify={'center'}>
                {validatorState == 'error' ? (
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
                  ? props.intl.formatMessage({
                      id: 'successUpload',
                      defaultMessage:
                        addedCount > 1
                          ? `${addedCount} records uploaded successfully`
                          : `${addedCount} record uploaded successfully`,
                    })
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
                <ValidateButton validateType={validatorState} uploadProps={uploadProps} />,
              ]}
            />
          </Row>
        ) : (
          <Row style={{ marginBottom: 12 }}>
            <Col span={24}>
              <Form autoComplete="off" layout="vertical">
                <Form.List name="validator-form">
                  {() => (
                    <>
                      <div style={{ overflowY: 'scroll', height: '35vh', width: '100%' }}>
                        {!_.isUndefined(errorFeilds) ||
                        !_.isEmpty(errorFeilds) ||
                        validatorState == 'error' ? (
                          errorFeilds.map((feildData: any, index: number) => {
                            let feildNames = Object.keys(feildData);
                            
                            return  Object.keys(feildData).length > 0 ?  (
                              <Row>
                                <Typography.Title style={{ marginTop: '5vh' }} level={5}>{`Row ${
                                  index + 1
                                }`}</Typography.Title>

                                <Col>
                                  <FeildSet feildNames={feildNames} feildData={feildData} />
                                </Col>
                              </Row>
                            ) : (<></>);

                          })
                        ) : (
                          <></>
                        )}
                      </div>
                    </>
                  )}
                </Form.List>

                <Form.Item className={styles.reupload}>
                  <ValidateButton validateType={validatorState} uploadProps={uploadProps} />
                </Form.Item>
              </Form>
            </Col>
          </Row>
        )}
      </Spin>
    </Card>
  );
};

export default Validator;
