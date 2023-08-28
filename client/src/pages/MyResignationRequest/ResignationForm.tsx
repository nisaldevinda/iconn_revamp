import React, { useState, useEffect } from 'react';
import _ from 'lodash';
import { history } from 'umi';
import { useIntl } from 'react-intl';
import { Card, Col, message, Row, Typography, Space, Button, Form, Tag } from 'antd';
import ProForm, {
  ProFormDatePicker,
  ProFormDependency,
  ProFormSelect,
  ProFormTextArea,
  ProFormUploadButton,
} from '@ant-design/pro-form';
import { createResignation, sendResignationRequest } from '@/services/employeeJourney';
import { getEmployee } from '@/services/employee';
import { getBase64 } from '@/utils/fileStore';
import dayjs from 'dayjs';
import moment from 'moment';
import { parseHumanReadableDurationToDayCount } from '@/utils/utils';
import Alert from 'antd/lib/alert';

interface ResignationFormProps {
  data: any;
  employee: any;
  setEmployee: (values: any) => void;
  hasUpcomingJobs: boolean;
}

const ResignationForm: React.FC<ResignationFormProps> = (props) => {
  const intl = useIntl();
  const [formRef] = Form.useForm();

  const [loading, setLoading] = useState(false);
  const [fileList, setFileList] = useState([]);

  useEffect(() => {
    console.log(props.data);
    attachDocumentButtonLabelChange();
  });

  const onFinish = async (data: any) => {
    setLoading(true);
    const requestData = data;
    if (requestData.attachDocument && requestData.attachDocument.length > 0) {
      const base64File = await getBase64(data.attachDocument[0].originFileObj);
      requestData.fileName = data.attachDocument[0].name;
      requestData.fileSize = data.attachDocument[0].size;
      requestData.fileType = data.attachDocument[0].type;
      requestData.data = base64File;
    }
    const key = 'saving';
    message.loading({
      content: intl.formatMessage({
        id: 'saving',
        defaultMessage: 'Saving...',
      }),
      key,
    });

    sendResignationRequest(requestData)
      .then(async (response) => {
        setLoading(false);
        message.success(response.message);
        history.push('/ess/my-requests');
      })
      .catch((error) => {
        message.error({
          content:
            error?.message ??
            intl.formatMessage({
              id: 'failedToSave',
              defaultMessage: 'Failed to Save',
            }),
          key,
        });

        setLoading(false);
      });
  };

  const attachDocumentButtonLabelChange = () => {
    const btnDom = document.querySelectorAll('.attach-document-button span')[1];
    const replacement = document.createElement('span');
    replacement.innerHTML = intl.formatMessage({
      id: 'upload',
      defaultMessage: 'Upload',
    });
    btnDom?.parentNode?.replaceChild(replacement, btnDom);
  };

  return (
    <>
      <Typography.Title level={5} style={{ marginTop: 24 }}>
        {intl.formatMessage({
          id: 'employee_journey_update.create_resignation',
          defaultMessage: 'Create Resignation',
        })}
      </Typography.Title>
      <Card>
        {props.hasUpcomingJobs && (
          <Alert
            type="info"
            className="employee-journey-resignation-alert"
            message={intl.formatMessage({
              id: 'employee_journey_update.resignation_initial_validation_alert_msg',
              defaultMessage:
                'User cannot create resignation until the upcoming resignation is done',
            })}
          />
        )}
        <ProForm
          form={formRef}
          onFinish={onFinish}
          submitter={{
            render: (_props, doms) => {
              return [
                <Space style={{ float: 'right' }}>
                  <Button
                    disabled={props.hasUpcomingJobs}
                    onClick={() => {
                      setFileList([]);
                      _props.form?.resetFields();
                    }}
                  >
                    {intl.formatMessage({
                      id: 'reset',
                      defaultMessage: 'Reset',
                    })}
                  </Button>
                  <Button
                    disabled={props.hasUpcomingJobs}
                    type="primary"
                    loading={loading}
                    onClick={() => _props.form?.submit?.()}
                  >
                    {intl.formatMessage({
                      id: 'save',
                      defaultMessage: 'Save',
                    })}
                  </Button>
                </Space>,
              ];
            },
          }}
        >
          <Row gutter={12} style={{ width: '60%' }}>
            <Col span={12}>
              <ProFormDatePicker
                width="100%"
                format="DD-MM-YYYY"
                name="resignationHandoverDate"
                label={intl.formatMessage({
                  id: 'employee_journey_update.resignation_handover_date',
                  defaultMessage: 'Resignation Handover Date',
                })}
                disabled={props.hasUpcomingJobs}
                placeholder={intl.formatMessage({
                  id: 'employee_journey_update.select_resignation_handover_date',
                  defaultMessage: 'Select Resignation Handover Date',
                })}
                // rules={[{ required: true, message: 'Required' }]}
              />
            </Col>
            <Col span={12}>
              <ProFormDatePicker
                width="100%"
                format="DD-MM-YYYY"
                name="effectiveDate"
                label={intl.formatMessage({
                  id: 'employee_journey_update.resignation_effective_date',
                  defaultMessage: 'Resignation Effective Date',
                })}
                disabled={props.hasUpcomingJobs}
                placeholder={intl.formatMessage({
                  id: 'employee_journey_update.select_resignation_effective_date',
                  defaultMessage: 'Select Resignation Effective Date',
                })}
                rules={[
                  {
                    required: true,
                    message: intl.formatMessage({
                      id: 'required',
                      defaultMessage: 'Required',
                    }),
                  },
                ]}
              />
            </Col>
            <Col span={12}>
              <ProFormDatePicker
                width="100%"
                format="DD-MM-YYYY"
                name="lastWorkingDate"
                label={intl.formatMessage({
                  id: 'employee_journey_update.last_working_date',
                  defaultMessage: 'Last Working Date',
                })}
                disabled={props.hasUpcomingJobs}
                placeholder={intl.formatMessage({
                  id: 'employee_journey_update.select_last_working_date',
                  defaultMessage: 'Select Last Working Date',
                })}
                // rules={[{ required: true, message: 'Required' }]}
              />
            </Col>
            <ProFormDependency name={['effectiveDate', 'resignationHandoverDate']}>
              {({ effectiveDate, resignationHandoverDate }) => {
                const effectiveDateMoment = moment(effectiveDate, 'YYYY-MM-DD');
                const resignationHandoverDateMoment = moment(resignationHandoverDate, 'YYYY-MM-DD');
                const noticeDayCount = moment
                  .duration(effectiveDateMoment.diff(resignationHandoverDateMoment))
                  .asDays();

                let remainingDayCount = 0;
                if (props.employee.noticePeriod) {
                  const noticePeriod = parseHumanReadableDurationToDayCount(
                    props.employee.noticePeriod,
                  );
                  remainingDayCount =
                    noticePeriod - noticeDayCount > 0 ? noticePeriod - noticeDayCount : 0;
                }

                formRef.setFieldsValue({
                  resignationNoticePeriodRemainingDays: remainingDayCount,
                });

                return (
                  <Col span={12}>
                    <ProForm.Item
                      name="resignationNoticePeriodRemainingDays"
                      label={intl.formatMessage({
                        id: 'employee_journey_update.notice_period_completion_status',
                        defaultMessage: 'Notice Period Completion Status',
                      })}
                      disabled={props.hasUpcomingJobs}
                    >
                      {!effectiveDate || !resignationHandoverDate ? (
                        <Tag className="notice-period-status-tag" color="blue">
                          {intl.formatMessage({
                            id: 'employee_journey_update.not_yet_calculated',
                            defaultMessage: 'Not yet calculated',
                          })}
                        </Tag>
                      ) : remainingDayCount > 0 ? (
                        <Tag className="notice-period-status-tag" color="red">
                          {intl.formatMessage({
                            id: 'employee_journey_update.not_completed',
                            defaultMessage: 'Not Completed',
                          })}
                          {' - '.concat(remainingDayCount.toString()).concat(' ')}
                          {intl.formatMessage({
                            id: 'employee_journey_update.days',
                            defaultMessage: 'Days',
                          })}
                        </Tag>
                      ) : (
                        <Tag className="notice-period-status-tag" color="green">
                          {intl.formatMessage({
                            id: 'employee_journey_update.completed',
                            defaultMessage: 'Completed',
                          })}
                        </Tag>
                      )}
                    </ProForm.Item>
                  </Col>
                );
              }}
            </ProFormDependency>
            <Col span={12}>
              <ProFormSelect
                name="resignationTypeId"
                label={intl.formatMessage({
                  id: 'employee_journey_update.resignation_type',
                  defaultMessage: 'Resignation Type',
                })}
                disabled={props.hasUpcomingJobs}
                showSearch
                options={props.data?.resignationTypes}
                placeholder={intl.formatMessage({
                  id: 'employee_journey_update.select_resignation_type',
                  defaultMessage: 'Select Resignation Type',
                })}
                // rules={[{ required: true, message: 'Required' }]}
              />
            </Col>
            <Col span={12}>
              <ProFormSelect
                name="resignationReasonId"
                label={intl.formatMessage({
                  id: 'employee_journey_update.resignation_reason',
                  defaultMessage: 'Resignation Reason',
                })}
                disabled={props.hasUpcomingJobs}
                showSearch
                options={props.data?.resignationReasons}
                placeholder={intl.formatMessage({
                  id: 'employee_journey_update.select_resignation_reason',
                  defaultMessage: 'Select Resignation Reason',
                })}
                rules={[{ required: true, message: 'Required' }]}
              />
            </Col>
            <Col span={24}>
              <ProFormTextArea
                name="resignationRemarks"
                label={intl.formatMessage({
                  id: 'employee_journey_update.resignation_remark',
                  defaultMessage: 'Resignation Remarks',
                })}
                disabled={props.hasUpcomingJobs}
                placeholder={intl.formatMessage({
                  id: 'employee_journey_update.type_here',
                  defaultMessage: 'Type here',
                })}
                rules={[
                  {
                    max: 250,
                    message: intl.formatMessage({
                      id: 'employee_journey.250_max_length',
                      defaultMessage: 'Maximum length is 250 characters.',
                    }),
                  },
                ]}
              />
            </Col>
            <Col span={24}>
              <ProFormUploadButton
                name="attachDocument"
                label={intl.formatMessage({
                  id: 'employee_journey_update.attach_document',
                  defaultMessage: 'Attach Document (JPG or PDF)',
                })}
                disabled={props.hasUpcomingJobs}
                title={intl.formatMessage({
                  id: 'upload_max_3mb',
                  defaultMessage: 'Upload (Max 3MB)',
                })}
                max={1}
                listType="text"
                fieldProps={{
                  name: 'attachDocument',
                }}
                fileList={fileList}
                onChange={async (info: any) => {
                  let status = info?.file?.status;
                  if (status === 'error') {
                    const { fileList, file } = info;
                    const { uid } = file;
                    const index = fileList.findIndex((file: any) => file.uid == uid);
                    const newFile = { ...file };
                    if (index > -1) {
                      newFile.status = 'done';
                      newFile.percent = 100;
                      delete newFile.error;
                      fileList[index] = newFile;
                      setFileList(fileList);
                    }
                  } else {
                    setFileList(info.fileList);
                  }
                }}
                rules={[
                  {
                    validator: (_, upload) => {
                      if (upload !== undefined && upload && upload.length !== 0) {
                        //check file size .It should be less than 3MB
                        if (upload[0].size > 3145728) {
                          return Promise.reject(
                            new Error(
                              intl.formatMessage({
                                id: 'pages.resignation.filesize',
                                defaultMessage: 'File size is too large. Maximum size is 3 MB',
                              }),
                            ),
                          );
                        }
                        const isValidFormat = ['image/jpeg', 'application/pdf'];
                        //check file format
                        if (!isValidFormat.includes(upload[0].type)) {
                          return Promise.reject(
                            new Error(
                              intl.formatMessage({
                                id: 'pages.resignation.fileformat',
                                defaultMessage: 'File format should be jpg or pdf',
                              }),
                            ),
                          );
                        }
                      }
                      return Promise.resolve();
                    },
                  },
                ]}
              />
            </Col>
          </Row>
        </ProForm>
      </Card>
    </>
  );
};

export default ResignationForm;
