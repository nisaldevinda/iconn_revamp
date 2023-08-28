import React, { useState, useEffect } from 'react';
import _ from 'lodash';
import { FormattedMessage } from 'react-intl';
import { useIntl } from 'umi';
import { Button, Col, Row, Typography, Spin, Space, message } from 'antd';
import { DownloadOutlined } from '@ant-design/icons';
import Modal from 'antd/lib/modal/Modal';
import {
  getAttachment,
  reupdateUpcomingEmployeeJourneyMilestone,
  rollbackUpcomingEmployeeJourneyMilestone,
} from '@/services/employeeJourney';
import {
  ModalForm,
  ProFormDatePicker,
  ProFormDependency,
  ProFormSelect,
  ProFormTextArea,
  ProFormUploadButton,
} from '@ant-design/pro-form';
import { getEmployee } from '@/services/employee';
import moment from 'moment';
import OrgSelector from '@/components/OrgSelector';

interface ContactRenewalUpcomingItemProps {
  data: any;
  record: any;
  employee: any;
  setEmployee: (values: any) => void;
}

const ContactRenewalUpcomingItem: React.FC<ContactRenewalUpcomingItemProps> = (props) => {
  const intl = useIntl();

  const [attachment, setAttachment] = useState();
  const [isAttachmentModalVisible, setIsAttachmentModalVisible] = useState(false);
  const [isReupdateModalVisible, setIsReupdateModalVisible] = useState(false);
  const [isRollbackModalVisible, setIsRollbackModalVisible] = useState(false);
  const [fileList, setFileList] = useState([]);
  const [orgStructureEntityId, setOrgStructureEntityId] = useState<number>();

  useEffect(() => {
    fetchAttachment();
  }, []);

  const fetchAttachment = async () => {
    if (props.record.attachmentId) {
      const _attachment = await getAttachment(props.employee.id, props.record.attachmentId);
      setAttachment(_attachment?.data);

      const _fileList = [
        {
          uid: '1',
          name: _attachment?.data?.name,
          status: 'done',
        },
      ];
      setFileList(_fileList);
    }
  };

  const calculateContractRenewalDueDate = (record: any) => {
    const employmentStatus = props.data?.employmentStatus?.find(
      (option) => option.value == record?.employmentStatusId,
    )?.record;
    const effectiveDate = record?.effectiveDate;

    const value = moment(effectiveDate, 'YYYY-MM-DD')
      .add(employmentStatus?.period, employmentStatus?.periodUnit?.toLowerCase())
      .format('DD-MM-YYYY');

    let label;
    switch (employmentStatus?.category) {
      case 'PROBATION':
        label = (
          <FormattedMessage
            id="employee_journey_update.probation_renewal_due_date"
            defaultMessage="Probation Renewal Due Date"
          />
        );
        break;
      case 'PERMANENT':
        label = (
          <FormattedMessage
            id="employee_journey_update.confirmation_renewal_due_date"
            defaultMessage="Confirmation Renewal Due Date"
          />
        );
        break;
      default:
        label = (
          <FormattedMessage
            id="employee_journey_update.contract_renewal_due_date"
            defaultMessage="Contract Renewal Due Date"
          />
        );
        break;
    }

    return { label, value };
  };

  const tbody = (
    <>
      <thead>
        <tr>
          <th colSpan={2} style={{ color: '#2A85FF' }}>
            <FormattedMessage id="employee_journey_update.upcoming_item.new" defaultMessage="New" />
          </th>
          <th colSpan={2} style={{ color: '#9C50FF' }}>
            <FormattedMessage
              id="employee_journey_update.upcoming_item.previous"
              defaultMessage="Previous"
            />
          </th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td className="property-name">
            <FormattedMessage
              id="employee_journey_update.job_category"
              defaultMessage="Job Category"
            />
          </td>
          <td className="property-value">
            {
              props.data?.jobCategories?.find(
                (option) => option.value == props.record?.jobCategoryId,
              )?.label
            }
          </td>
          <td className="property-name">
            <FormattedMessage
              id="employee_journey_update.job_category"
              defaultMessage="Job Category"
            />
          </td>
          <td className="property-value">
            {
              props.data?.jobCategories?.find(
                (option) => option.value == props.record?.previousRecord?.jobCategoryId,
              )?.label
            }
          </td>
        </tr>
        <tr>
          <td className="property-name">
            <FormattedMessage id="employee_journey_update.job_title" defaultMessage="Job Title" />
          </td>
          <td className="property-value">
            {
              props.data?.jobTitles?.find((option) => option.value == props.record?.jobTitleId)
                ?.label
            }
          </td>
          <td className="property-name">
            <FormattedMessage id="employee_journey_update.job_title" defaultMessage="Job Title" />
          </td>
          <td className="property-value">
            {
              props.data?.jobTitles?.find(
                (option) => option.value == props.record?.previousRecord?.jobTitleId,
              )?.label
            }
          </td>
        </tr>
        <tr>
          <td className="property-name">
            <FormattedMessage id="employee_journey_update.pay_grade" defaultMessage="Pay Grade" />
          </td>
          <td className="property-value">
            {
              props.data?.payGrades?.find((option) => option.value == props.record?.payGradeId)
                ?.label
            }
          </td>
          <td className="property-name">
            <FormattedMessage id="employee_journey_update.pay_grade" defaultMessage="Pay Grade" />
          </td>
          <td className="property-value">
            {
              props.data?.payGrades?.find(
                (option) => option.value == props.record?.previousRecord?.payGradeId,
              )?.label
            }
          </td>
        </tr>
        <tr>
          <td className="property-name">
            <FormattedMessage id="employee_journey_update.calendar" defaultMessage="Calendar" />
          </td>
          <td className="property-value">
            {
              props.data?.calendars?.find((option) => option.value == props.record?.calendarId)
                ?.label
            }
          </td>
          <td className="property-name">
            <FormattedMessage id="employee_journey_update.calendar" defaultMessage="Calendar" />
          </td>
          <td className="property-value">
            {
              props.data?.calendars?.find(
                (option) => option.value == props.record?.previousRecord?.calendarId,
              )?.label
            }
          </td>
        </tr>
        <tr>
          <td className="property-name">{calculateContractRenewalDueDate(props.record)?.label}</td>
          <td className="property-value">{calculateContractRenewalDueDate(props.record)?.value}</td>
          <td className="property-name">
            {calculateContractRenewalDueDate(props.record?.previousRecord)?.label}
          </td>
          <td className="property-value">
            {calculateContractRenewalDueDate(props.record?.previousRecord)?.value}
          </td>
        </tr>
        {/* <tr>
                <td className='property-name'>
                    <FormattedMessage
                        id="employee_journey_update.department"
                        defaultMessage="Department"
                    />
                </td>
                <td className='property-value'>
                    {props.data?.departments?.find(option => option.value == props.record?.departmentId)?.label}
                </td>
                <td className='property-name'>
                    <FormattedMessage
                        id="employee_journey_update.department"
                        defaultMessage="Department"
                    />
                </td>
                <td className='property-value'>
                    {props.data?.departments?.find(option => option.value == props.record?.previousRecord?.departmentId)?.label}
                </td>
            </tr>
            <tr>
                <td className='property-name'>
                    <FormattedMessage
                        id="employee_journey_update.division"
                        defaultMessage="Division"
                    />
                </td>
                <td className='property-value'>
                    {props.data?.divisions?.find(option => option.value == props.record?.divisionId)?.label}
                </td>
                <td className='property-name'>
                    <FormattedMessage
                        id="employee_journey_update.division"
                        defaultMessage="Division"
                    />
                </td>
                <td className='property-value'>
                    {props.data?.divisions?.find(option => option.value == props.record?.previousRecord?.divisionId)?.label}
                </td>
            </tr> */}
        <tr>
          <td className="property-name">
            <FormattedMessage id="employee_journey_update.location" defaultMessage="Location" />
          </td>
          <td className="property-value">
            {
              props.data?.locations?.find((option) => option.value == props.record?.locationId)
                ?.label
            }
          </td>
          <td className="property-name">
            <FormattedMessage id="employee_journey_update.location" defaultMessage="Location" />
          </td>
          <td className="property-value">
            {
              props.data?.locations?.find(
                (option) => option.value == props.record?.previousRecord?.locationId,
              )?.label
            }
          </td>
        </tr>
        <tr>
          <td className="property-name">
            <FormattedMessage
              id="employee_journey_update.reporting_person"
              defaultMessage="Reporting Person"
            />
          </td>
          <td className="property-value">
            {
              props.data?.employees?.find(
                (option) => option.value == props.record?.reportsToEmployeeId,
              )?.label
            }
          </td>
          <td className="property-name">
            <FormattedMessage
              id="employee_journey_update.reporting_person"
              defaultMessage="Reporting Person"
            />
          </td>
          <td className="property-value">
            {
              props.data?.employees?.find(
                (option) => option.value == props.record?.previousRecord?.reportsToEmployeeId,
              )?.label
            }
          </td>
        </tr>
        <tr>
          <td className="property-name">
            <FormattedMessage
              id="employee_journey_update.reporting_person"
              defaultMessage="Functional Reporting Person"
            />
          </td>
          <td className="property-value">
            {
              props.data?.employees?.find(
                (option) => option.value == props.record?.functionalReportsToEmployeeId,
              )?.label
            }
          </td>
          <td className="property-name">
            <FormattedMessage
              id="employee_journey_update.reporting_person"
              defaultMessage="Functional Reporting Person"
            />
          </td>
          <td className="property-value">
            {
              props.data?.employees?.find(
                (option) =>
                  option.value == props.record?.previousRecord?.functionalReportsToEmployeeId,
              )?.label
            }
          </td>
        </tr>
        <tr>
          <td className="property-name">
            <FormattedMessage
              id="employee_journey_update.confirmation_reason"
              defaultMessage="Confirmation Reason"
            />
          </td>
          <td className="property-value" colSpan={3}>
            {
              props.data?.confirmationReasons?.find(
                (option) => option.value == props.record?.confirmationReasonId,
              )?.label
            }
          </td>
        </tr>
        <tr>
          <td className="property-name">
            <FormattedMessage id="employee_journey_update.remarks" defaultMessage="Remarks" />
          </td>
          <td className="property-value" colSpan={3}>
            {props.record?.confirmationRemark}
          </td>
        </tr>
        <tr>
          <td className="property-name">
            <FormattedMessage
              id="employee_journey_update.completed_evaluation"
              defaultMessage="Completed Evaluation"
            />
          </td>
          <td className="property-value" colSpan={3}>
            <Typography.Text>
              {props.record.attachmentId && !attachment ? (
                <Spin size="small" />
              ) : (
                props.record.attachmentId &&
                attachment?.name && (
                  <>
                    <Button type="link" onClick={() => setIsAttachmentModalVisible(true)}>
                      {attachment.name}
                    </Button>
                    <Modal
                      title={attachment.name}
                      visible={isAttachmentModalVisible}
                      destroyOnClose={true}
                      onCancel={() => setIsAttachmentModalVisible(false)}
                      centered
                      width="80vw"
                      footer={[
                        <Row>
                          <Col span={12}>
                            <Button
                              style={{ float: 'left' }}
                              type="link"
                              key="download"
                              onClick={() => {
                                let a = document.createElement('a');
                                a.href = attachment.data;
                                a.download = attachment.name;
                                a.click();
                              }}
                            >
                              <DownloadOutlined style={{ marginRight: 8 }} />
                              <FormattedMessage id="download" defaultMessage="Download" />
                            </Button>
                          </Col>
                          <Col span={12}>
                            <Button
                              style={{ float: 'right' }}
                              key="back"
                              onClick={() => setIsAttachmentModalVisible(false)}
                            >
                              <FormattedMessage id="cancel" defaultMessage="Cancel" />
                            </Button>
                          </Col>
                        </Row>,
                      ]}
                    >
                      {attachment?.type.includes('image') ? (
                        <img
                          src={attachment.data}
                          style={{ height: '65vh', margin: '0 auto', display: 'block' }}
                        />
                      ) : (
                        <iframe src={attachment.data} style={{ width: '100%', height: '65vh' }} />
                      )}
                    </Modal>
                  </>
                )
              )}
            </Typography.Text>
          </td>
        </tr>
      </tbody>
    </>
  );

  return (
    <>
      <table className="employee-journey-upcoming-table">{tbody}</table>

      <Space style={{ display: 'flow-root' }}>
        <Space style={{ float: 'right' }}>
          <ModalForm
            title={intl.formatMessage({
              id: 'employee_journey.rollback_confirmation',
              defaultMessage: 'Rollback Confirmation',
            })}
            visible={isRollbackModalVisible}
            onVisibleChange={setIsRollbackModalVisible}
            trigger={
              <Button key="rollback" className="rollback-btn">
                <FormattedMessage id="rollback" defaultMessage="Rollback" />
              </Button>
            }
            submitter={{
              searchConfig: {
                submitText: intl.formatMessage({
                  id: 'rollback',
                  defaultMessage: 'Rollback',
                }),
                resetText: intl.formatMessage({
                  id: 'cancel',
                  defaultMessage: 'Cancel',
                }),
              },
            }}
            modalProps={{
              destroyOnClose: true,
            }}
            onFinish={async (values) => {
              const key = 'rollbacking';
              message.loading({
                content: intl.formatMessage({
                  id: 'rollbacking',
                  defaultMessage: 'Rollbacking...',
                }),
                key,
              });

              const data = { ...props.record, ...values };
              rollbackUpcomingEmployeeJourneyMilestone(props?.employee?.id, props?.record?.id, data)
                .then(async (response) => {
                  const _response = await getEmployee(props.employee.id);
                  if (response.error) location.reload();
                  props?.setEmployee(_response.data);

                  message.success({
                    content:
                      response.message ??
                      intl.formatMessage({
                        id: 'successfullyRollback',
                        defaultMessage: 'Successfully Rollback',
                      }),
                    key,
                  });

                  return true;
                })
                .catch((error) => {
                  message.error({
                    content:
                      error.message ??
                      intl.formatMessage({
                        id: 'failedToRollback',
                        defaultMessage: 'Failed to rollback',
                      }),
                    key,
                  });
                });
            }}
          >
            <Typography.Title level={5}>{props.title}</Typography.Title>
            <table className="employee-journey-upcoming-table  employee-journey-rollback-table">
              {tbody}
            </table>
            <br />
            <ProFormTextArea
              name="rollbackReason"
              label={intl.formatMessage({
                id: 'employee_journey.rollback_reason',
                defaultMessage: 'Rollback Reason',
              })}
              placeholder={intl.formatMessage({
                id: 'employee_journey.type_here',
                defaultMessage: 'Type here',
              })}
              rules={[
                {
                  required: true,
                  message: intl.formatMessage({
                    id: 'employee_journey.required',
                    defaultMessage: 'Required',
                  }),
                },
                {
                  max: 250,
                  message: intl.formatMessage({
                    id: 'employee_journey.250_max_length',
                    defaultMessage: 'Maximum length is 250 characters.',
                  }),
                },
              ]}
            />
          </ModalForm>
          <ModalForm
            title={intl.formatMessage({
              id: 'employee_journey.reupdate_confirmation',
              defaultMessage: 'Reupdate Confirmation',
            })}
            visible={isReupdateModalVisible}
            onVisibleChange={setIsReupdateModalVisible}
            trigger={
              <Button key="reupdate" className="reupdate-btn">
                <FormattedMessage id="reupdate" defaultMessage="Reupdate" />
              </Button>
            }
            submitter={{
              searchConfig: {
                submitText: intl.formatMessage({
                  id: 'reupdate',
                  defaultMessage: 'Reupdate',
                }),
                resetText: intl.formatMessage({
                  id: 'cancel',
                  defaultMessage: 'Cancel',
                }),
              },
            }}
            modalProps={{
              destroyOnClose: true,
            }}
            onFinish={async (values) => {
              const key = 'reupdating';
              message.loading({
                content: intl.formatMessage({
                  id: 'reupdating',
                  defaultMessage: 'Reupdating...',
                }),
                key,
              });

              const data = { ...props.record, ...values, orgStructureEntityId };
              reupdateUpcomingEmployeeJourneyMilestone(props?.employee?.id, props?.record?.id, data)
                .then(async (response) => {
                  const _response = await getEmployee(props.employee.id);
                  if (response.error) location.reload();
                  props?.setEmployee(_response.data);

                  message.success({
                    content:
                      response.message ??
                      intl.formatMessage({
                        id: 'successfullyReupdate',
                        defaultMessage: 'Successfully Reupdate',
                      }),
                    key,
                  });

                  setIsReupdateModalVisible(false);
                })
                .catch((error) => {
                  message.error({
                    content:
                      error.message ??
                      intl.formatMessage({
                        id: 'failedToReupdate',
                        defaultMessage: 'Failed to reupdate',
                      }),
                    key,
                  });
                });
            }}
            initialValues={{ ...props.record, orgStructureEntityId }}
          >
            <Typography.Title level={5}>{props.title}</Typography.Title>
            <Row gutter={12}>
              <Col span={8}>
                <ProFormSelect
                  name="confirmationAction"
                  label={intl.formatMessage({
                    id: 'employee_journey_update.action',
                    defaultMessage: 'Action',
                  })}
                  showSearch
                  options={props.data?.confirmationActions}
                  placeholder={intl.formatMessage({
                    id: 'employee_journey_update.select_action',
                    defaultMessage: 'Select Action',
                  })}
                  // rules={[{ required: true, message: 'Required' }]}
                />
              </Col>
              <ProFormDependency name={['confirmationAction']}>
                {({ confirmationAction }) => (
                  <Col span={8}>
                    <ProFormSelect
                      name="employmentStatusId"
                      label={intl.formatMessage({
                        id: 'employee_journey_update.new_employment_status',
                        defaultMessage: 'New Employment Status',
                      })}
                      showSearch
                      options={
                        confirmationAction == 'ABSORB_TO_PERMANENT_CARDER'
                          ? props.data?.employmentStatus.filter((option) => option.record?.id == 1)
                          : confirmationAction == 'EXTEND_THE_PROBATION'
                          ? props.data?.employmentStatus.filter(
                              (option) => option.record?.name == 'PROBATION',
                            )
                          : confirmationAction == 'CONTRACT_RENEWAL'
                          ? props.data?.employmentStatus.filter(
                              (option) => option.record?.name == 'CONTRACT',
                            )
                          : props.data?.employmentStatus
                      }
                      placeholder={intl.formatMessage({
                        id: 'employee_journey_update.select_new_employment_status',
                        defaultMessage: 'Select New Employment Status',
                      })}
                      // rules={[{ required: true, message: 'Required' }]}
                    />
                  </Col>
                )}
              </ProFormDependency>
              <Col span={8}>
                <ProFormSelect
                  name="locationId"
                  label={intl.formatMessage({
                    id: 'employee_journey_update.location',
                    defaultMessage: 'Location',
                  })}
                  showSearch
                  options={props.data?.locations}
                  placeholder={intl.formatMessage({
                    id: 'employee_journey_update.select_location',
                    defaultMessage: 'Select Location',
                  })}
                  // rules={[{ required: true, message: 'Required' }]}
                />
              </Col>
              <OrgSelector
                span={8}
                value={orgStructureEntityId}
                setValue={(value: number) => setOrgStructureEntityId(value)}
              />
              <Col span={8}>
                <ProFormSelect
                  name="locationId"
                  label={intl.formatMessage({
                    id: 'employee_journey_update.new_location',
                    defaultMessage: 'Location',
                  })}
                  showSearch
                  options={props.data?.locations}
                  placeholder={intl.formatMessage({
                    id: 'employee_journey_update.select_location',
                    defaultMessage: 'Select Location',
                  })}
                  // rules={[{ required: true, message: 'Required' }]}
                />
              </Col>
              <Col span={8}>
                <ProFormSelect
                  name="reportsToEmployeeId"
                  label={intl.formatMessage({
                    id: 'employee_journey_update.reporting_person',
                    defaultMessage: 'Reporting Person',
                  })}
                  showSearch
                  options={props.data?.managers}
                  placeholder={intl.formatMessage({
                    id: 'employee_journey_update.select_reporting_person',
                    defaultMessage: 'Select Reporting Person',
                  })}
                  // rules={[{ required: true, message: 'Required' }]}
                />
              </Col>
              <Col span={8}>
                <ProFormSelect
                  name="functionalReportsToEmployeeId"
                  label={intl.formatMessage({
                    id: 'employee_journey_update.functional_reporting_person',
                    defaultMessage: 'Functional Reporting Person',
                  })}
                  showSearch
                  options={props.data?.managers}
                  placeholder={intl.formatMessage({
                    id: 'employee_journey_update.select_functional_reporting_person',
                    defaultMessage: 'Select Functional Reporting Person',
                  })}
                  // rules={[{ required: true, message: 'Required' }]}
                />
              </Col>
              <Col span={8}>
                <ProFormSelect
                  name="jobCategoryId"
                  label={intl.formatMessage({
                    id: 'employee_journey_update.job_category',
                    defaultMessage: 'Job Category',
                  })}
                  showSearch
                  options={props.data?.jobCategories}
                  placeholder={intl.formatMessage({
                    id: 'employee_journey_update.select_job_category',
                    defaultMessage: 'Select Job Category',
                  })}
                  // rules={[{ required: true, message: 'Required' }]}
                />
              </Col>
              <Col span={8}>
                <ProFormSelect
                  name="jobTitleId"
                  label={intl.formatMessage({
                    id: 'employee_journey_update.job_title',
                    defaultMessage: 'Job Title',
                  })}
                  showSearch
                  options={props.data?.jobTitles}
                  placeholder={intl.formatMessage({
                    id: 'employee_journey_update.select_job_title',
                    defaultMessage: 'Select Job Title',
                  })}
                  // rules={[{ required: true, message: 'Required' }]}
                />
              </Col>
              <Col span={8}>
                <ProFormSelect
                  name="payGradeId"
                  label={intl.formatMessage({
                    id: 'employee_journey_update.pay_grade',
                    defaultMessage: 'Pay Grade',
                  })}
                  showSearch
                  options={props.data?.payGrades}
                  placeholder={intl.formatMessage({
                    id: 'employee_journey_update.select_pay_grade',
                    defaultMessage: 'Select Pay Grade',
                  })}
                  // rules={[{ required: true, message: 'Required' }]}
                />
              </Col>
              <Col span={8}>
                <ProFormSelect
                  name="calendarId"
                  label={intl.formatMessage({
                    id: 'employee_journey_update.calendar',
                    defaultMessage: 'Calendar',
                  })}
                  showSearch
                  options={props.data?.calendars}
                  placeholder={intl.formatMessage({
                    id: 'employee_journey_update.select_calendar',
                    defaultMessage: 'Select Calendar',
                  })}
                  // rules={[{ required: true, message: 'Required' }]}
                />
              </Col>
            </Row>
            <Row gutter={12}>
              <Col span={16}>
                <ProFormSelect
                  name="confirmationReasonId"
                  label={intl.formatMessage({
                    id: 'employee_journey_update.confirmation_reason',
                    defaultMessage: 'Confirmation Reason',
                  })}
                  showSearch
                  options={props.data?.confirmationReasons}
                  placeholder={intl.formatMessage({
                    id: 'employee_journey_update.select_confirmation_reason',
                    defaultMessage: 'Select Confirmation Reason',
                  })}
                  // rules={[{ required: true, message: 'Required' }]}
                />
              </Col>
            </Row>
            <Row gutter={12}>
              <Col span={16}>
                <ProFormTextArea
                  name="confirmationRemark"
                  label={intl.formatMessage({
                    id: 'employee_journey_update.remarks',
                    defaultMessage: 'Remarks',
                  })}
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
            </Row>
            <Row gutter={12}>
              <Col span={8}>
                <ProFormDatePicker
                  width="md"
                  format="DD-MM-YYYY"
                  name="effectiveDate"
                  label={intl.formatMessage({
                    id: 'employee_journey_update.renewal_effective_date',
                    defaultMessage: 'Renewal Effective Date',
                  })}
                  placeholder={intl.formatMessage({
                    id: 'employee_journey_update.select_renewal_effective_date',
                    defaultMessage: 'Select Renewal Effective Date',
                  })}
                  rules={[{ required: true, message: 'Required' }]}
                />
              </Col>
            </Row>
            <Typography.Title level={5}>
              <FormattedMessage
                id="employee_journey_update.completed_evaluation"
                defaultMessage="Completed Evaluation"
              />
            </Typography.Title>
            <Row gutter={12}>
              <Col span={8}>
                <ProFormUploadButton
                  name="attachDocument"
                  label={intl.formatMessage({
                    id: 'employee_journey_update.attach_document',
                    defaultMessage: 'Attach Document (JPG or PDF)',
                  })}
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
                                  id: 'pages.confirmation.filesize',
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
                                  id: 'pages.confirmation.fileformat',
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
          </ModalForm>
        </Space>
      </Space>
    </>
  );
};

export default ContactRenewalUpcomingItem;
