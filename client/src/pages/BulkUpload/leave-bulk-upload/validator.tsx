import React, { useState } from 'react';
import { APIResponse } from '@/utils/request';
import { ResultStatusType } from 'antd/lib/result';
import { Models } from '@/services/model';
import { getBase64 } from '@/utils/fileStore';
import { UploadProps } from 'antd/es/upload/interface';
import { FormattedMessage, IntlShape } from 'react-intl';
import ErrorIcon from '@/assets/bulkUpload/error-icon.svg';
import { ProFormText } from '@ant-design/pro-form';
import { UploadOutlined, DownloadOutlined, CheckCircleOutlined } from '@ant-design/icons';
import ValidateDataView from '@/pages/BulkUpload/leave-bulk-upload/validate-data-view';
import _ from 'lodash';
import { Access, useAccess, useIntl, history } from 'umi';
import { ReactComponent as ExcelFile } from '../../../assets/icon-excel-file.svg';
import { saveUploadedLeaveData } from '@/services/bulkUpload';
import moment from 'moment';
import {
  Upload,
  Button,
  DatePicker,
  Col,
  Result,
  Space,
  Form,
  Input,
  Row,
  List,
  Typography,
  message,
  Card,
  Image,
  Spin,
  Select,
} from 'antd';
import { uploadFile } from '@/services/documentManager';
// import styles from './index.less'
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
  uploadExcelFile: any;
  isYearSelected: boolean;
  setIsYearSelected: any;
  setLeavePeriodYear: any;
  uploadedFile: any;
}

type ResponseData = {
  errors: [];
  hasValidationErrors: boolean;
  addedCount: number;
};

type ValidatorState = 'initial' | 'error' | 'success' | 'validating-stage'; // main validator states

// sub component which renders the Intial and Success Notfication in the validator card
const ResultTag: React.FC<ResultTagProps> = (props) => {
  return (
    <Result
      status={props.status}
      title={props.title}
      extra={props.extraRender}
      subTitle={props.subTitle}
      icon={props.status == 'success' ? <CheckCircleOutlined></CheckCircleOutlined> : <></>}
      //   className={styles.result}
    />
  );
};

// sub component which renders the upload button according to the validator state
const ValidateButton: React.FC<ValidateButtonProps> = (props) => {
  const { Option } = Select;

  switch (props.validateType) {
    case 'initial':
      return !props.uploadedFile ? (
        <>
          <Row style={{ marginBottom: 15 }}>
            <Col span={20} offset={2}>
              <Select
                onChange={(value) => {
                  if (value) {
                    props.setIsYearSelected(true);
                    props.setLeavePeriodYear(value);
                  }
                }}
                style={{ width: 200 }}
                allowClear={true}
                placeholder={<FormattedMessage
                  id="selectLeavePeriod"
                  defaultMessage="Select Leave Period"
                />}
              >
                <Option value={moment().year()}>{moment().year()}</Option>
              </Select>
            </Col>
          </Row>

          <Row>
            <Col span={20} offset={2}>
              <Upload {...props.uploadProps}>
                <Button type="primary" disabled={!props.isYearSelected} icon={<UploadOutlined />}>
                  <FormattedMessage
                    id="bulk-upload-dataset-upload"
                    defaultMessage=" Upload Datasheet"
                  />
                </Button>
              </Upload>
            </Col>
          </Row>
        </>
      ) : (
        <Row>
          <Col span={24} style={{ marginTop: 80 }}>
            <Row style={{ marginBottom: 20 }} justify={'center'}>
              <ExcelFile></ExcelFile>
            </Row>
            <Row style={{ marginBottom: 10 }} justify={'center'}>
              {props.uploadedFile.name}
            </Row>
            <Row justify={'center'}>
              <Button style={{ width: 120 }} onClick={props.uploadExcelFile} type="primary">
                <FormattedMessage id="bulk-upload-dataset-validate" defaultMessage="Validate" />
              </Button>
            </Row>
          </Col>
        </Row>
      );
    case 'error':
      return (
        <Upload {...props.uploadProps}>
          <Button type="primary" icon={<UploadOutlined />}>
            <FormattedMessage
              id="bulk-upload-dataset-reUpload"
              defaultMessage=" Reupload Datasheet"
            />
          </Button>
        </Upload>
      );
    case 'validating-stage':
      return (
        <Upload {...props.uploadProps}>
          <Button type="primary" icon={<UploadOutlined />}>
            <FormattedMessage
              id="bulk-upload-dataset-reUpload"
              defaultMessage=" Reupload Datasheet"
            />
          </Button>
        </Upload>
      );
    case 'success':
      return (
        <Col span={24} style={{ marginTop: 80 }}>
          <Row justify={'center'}>
              <Button style={{ width: 120, borderRadius: 6}} onClick={props.uploadExcelFile} type="default">
                <FormattedMessage id="bulk-upload-dataset-validate" defaultMessage="Back" />
              </Button>
            </Row>
        </Col>
      );
  }
};

const Validator: React.FC<ValidatorProps> = (props) => {
  const [errorFeilds, setErrorFeilds] = useState<any>([]);
  const [validationTableData, setValidationTableData] = useState<any>([]);
  const [errorCount, setErrorCount] = useState<any>(0);
  const [validatorState, setValidatorState] = useState<ValidatorState>('initial');
  const [addedCount, setAddedCount] = useState<number>(0);
  const [uploadingState, setUploadingState] = useState<boolean>(false);
  const [isYearSelected, setIsYearSelected] = useState<boolean>(false);
  const [leavePeriodYear, setLeavePeriodYear] = useState<any>(null);
  const [uploadedFile, setUploadFile] = useState<any>(null);
  const intl = useIntl();
  const [refresh, setRefresh] = useState(0);

  const saveUploadedData = async () => {
    let errorEntilementCount = 0;
    const key = 'saving';

    //check all errors are fixed
    validationTableData.forEach((entitlement) => {
      if (entitlement.hasErrors) {
        errorEntilementCount++;
      }
    });

    if (errorEntilementCount > 0) {
      message.error({
        content: props.intl.formatMessage({
          id: 'uploadedDataSaveError',
          defaultMessage: 'Please resolve all data errors before save',
        }),
      });
      return;
    }

    let params = {
      entitlementData: JSON.stringify(validationTableData),
    };
    await saveUploadedLeaveData(params)
      .then((response: APIResponse) => {
        if (!_.isUndefined(response)) {
          const responseData: ResponseData = response.data;
          if (!_.isUndefined(responseData) && !_.isNull(responseData)) {
            if (!responseData.hasValidationErrors) {
              setValidatorState('success');
              setAddedCount(responseData.addedCount)
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
          setRefresh((prev) => prev + 1);
          const responseData: ResponseData = error.data;
          setValidationTableData(responseData.validatedData);
          setErrorCount(responseData.errorCount);
          setValidatorState('validating-stage');
        }
      });
  };
  const uploadExcelFile = async () => {
    const file = uploadedFile;
    if (!_.isUndefined(file) || !_.isEmpty(file)) {
      const fileURL = await getBase64(file.originFileObj);
      const queryParams = {
        modelName: Models.Employee,
        fileName: file.name,
        fileSize: file.size,
        fileType: file.type,
        file: fileURL.split(',')[1],
      };
      const key = 'Validating';
      message.loading({
        content: props.intl.formatMessage({
          id: 'validating',
          defaultMessage: 'Validating...',
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
              setValidationTableData(responseData.validatedData);
              setErrorCount(responseData.errorCount);
              if (!responseData.hasValidationErrors) {
                setValidatorState('validating-stage');
              }
            }
          }
          message.success({
            content: props.intl.formatMessage({
              id: 'validateFinishe',
              defaultMessage: 'Validating Finished...',
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
                  setErrorFeilds(responseData.errors);
                }
              }
            }
          }
        });
    }
  };

  const uploadProps: UploadProps = {
    // customRequest: uploadExcelFile,
    onChange({ file, fileList }) {
      setUploadFile(file);
    },
    showUploadList: false,
    multiple: false,
    accept: 'application/vnd.ms-excel, application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    beforeUpload: (file) => {
      const key = 'uploading';
      const isXls = file.type === 'application/vnd.ms-excel';
      const isXlsx =  file.type === 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
      if (!isXls && !isXlsx) {
        message.error({
          content: props.intl.formatMessage({
            id: 'failedToUpload',
            defaultMessage: 'File format should be .xls or .xlsx',
          }),
          key,
        });
        return isXls || isXlsx || Upload.LIST_IGNORE;
      }
    },
  };

  return (
    <Card
      style={{
        height: validatorState == 'initial' || validatorState == 'success' ? '70vh' : 'auto',
      }}
      className='uploadCard'
      title={
        <>
          <Row gutter={20}>
            <Col span={12} style={{ marginTop: 10 }}>
              {'Leave Bulk Upload'}
            </Col>
            <Col span={12}>{props.cardTitleRender}</Col>
          </Row>
        </>
      }
    >
      <Spin
        spinning={uploadingState}
        tip={validatorState == 'initial' ? 'Validating...' : 'Saving...'}
        size="large"
        style={{fontSize: 22}}
      >
        {validatorState == 'initial' || validatorState == 'success' ? (
          <Row justify="space-around" align="middle">
            <ResultTag
              status={
                validatorState == 'initial'
                  ? 'info'
                  : validatorState == 'success'
                  ? 'success'
                  : 'warning'
              }
              title={
                validatorState == 'initial' && !uploadedFile
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
                validatorState == 'initial' && !uploadedFile
                  ? props.intl.formatMessage({
                      id: 'initialUpload',
                      defaultMessage:
                        'Please select leave year and  upload your Excel sheet to validate its data',
                    })
                  : ''
              }
              extraRender={[
                <ValidateButton
                  uploadedFile={uploadedFile}
                  setLeavePeriodYear={setLeavePeriodYear}
                  setIsYearSelected={setIsYearSelected}
                  isYearSelected={isYearSelected}
                  validateType={validatorState}
                  uploadProps={uploadProps}
                  uploadExcelFile={uploadExcelFile}
                />,
              ]}
            />
          </Row>
        ) : validatorState == 'validating-stage' ? (
          <Row style={{ marginBottom: 50 }}>
            <Col span={24}>
              <Row justify={'center'}>
                {validatorState == 'validating-stage' ? (
                  <Col span={24}>
                    {errorCount == 0 ? (
                      <>
                        <Row justify={'center'}>
                          <Col>
                            <CheckCircleOutlined style={{ color: '#74b425', fontSize: 35 }} />
                          </Col>
                        </Row>
                        <Row justify={'center'}>
                          <Col>
                            <p
                              style={{ marginTop: 7 }}
                            >{`There is no issues found, you can save this data set`}</p>
                          </Col>
                        </Row>
                      </>
                    ) : (
                      <>
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
                      </>
                    )}
                  </Col>
                ) : (
                  <></>
                )}
              </Row>
            </Col>
            <Col span={24}>
              <ValidateDataView
                refresh={refresh}
                tableData={validationTableData}
                setErrorCount={setErrorCount}
                setTableData={setValidationTableData}
              ></ValidateDataView>
            </Col>
            <Col style={{ marginTop: 25, marginLeft: 40 }} span={23}>
              <Row justify="end">
                <Button
                  onClick={() => {
                    setValidatorState('initial');
                    setValidationTableData([]);
                    setUploadFile(null);
                  }}
                >
                  {intl.formatMessage({
                    id: 'cancel',
                    defaultMessage: 'Cancel',
                  })}
                </Button>
                <Button type="primary" onClick={saveUploadedData} style={{ marginLeft: 15 }}>
                  {intl.formatMessage({
                    id: 'save',
                    defaultMessage: 'Save',
                  })}
                </Button>
              </Row>
            </Col>
          </Row>
        ) : (
          <></>
        )}
      </Spin>
    </Card>
  );
};

export default Validator;
