import React, { useState, useEffect } from 'react';
import _ from 'lodash';
import { FormattedMessage } from 'react-intl';
import { Button, Card, Col, Row, Tag, Timeline, Typography, Spin } from 'antd';
import { Link } from 'umi';
import { DownOutlined, UpOutlined, DownloadOutlined } from '@ant-design/icons';
import moment from 'moment';
import Modal from 'antd/lib/modal/Modal';
import { getAttachment } from '@/services/employeeJourney';
import { getFormTemplateJobInstances } from '@/services/template';
interface ResignationHistoryItemProps {
  data: any;
  record: any;
  employee: any;
}

const ResignationHistoryItem: React.FC<ResignationHistoryItemProps> = (props) => {
  const [isVisibleDetails, setIsVisibleDetails] = useState(false);
  const [attachment, setAttachment] = useState();
  const [isAttachmentModalVisible, setIsAttachmentModalVisible] = useState(false);
  const [jobInstances, setJobInstances] = useState([]);

  useEffect(() => {
    fetchAttachment();
    getjobInstances();
  }, []);

  const fetchAttachment = async () => {
    if (props.record.attachmentId) {
      const _attachment = await getAttachment(props.employee.id, props.record.attachmentId);
      setAttachment(_attachment?.data);
    }
  };

  const getjobInstances = async () => {
    const instances = await getFormTemplateJobInstances(props.record.id);
    setJobInstances(instances.data);
  };

  return (
    <Timeline.Item color="#FF4D4F" className="timeline-head-center">
      <Card className="timeline-card" style={{ borderColor: '#FF4D4F' }}>
        <div onClick={() => setIsVisibleDetails(!isVisibleDetails)}>
          <Button
            shape="circle"
            icon={isVisibleDetails ? <UpOutlined /> : <DownOutlined />}
            onClick={() => setIsVisibleDetails(!isVisibleDetails)}
            size="small"
            style={{
              position: 'relative',
              float: 'right',
            }}
          />
          <Typography.Text>
            <Tag color="#FF4D4F">
              <FormattedMessage id="employee_journey_update.resigned" defaultMessage="Resigned" />
            </Tag>
            {moment(props.record.effectiveDate, 'YYYY-MM-DD').format('DD MMM YYYY')}
          </Typography.Text>
          <Typography.Title level={5} style={{ marginTop: 0, marginBottom: 0 }}>
            {
              props.data?.jobTitles?.find((option) => option.value == props.record?.jobTitleId)
                ?.label
            }
          </Typography.Title>
        </div>
        {isVisibleDetails && (
          <Row style={{ marginTop: 18 }}>
            {/* Employee No */}
            <Col span={12}>
              <Row>
                <Col span={12}>
                  <FormattedMessage
                    id="employee_journey_update.employee_no"
                    defaultMessage="Employee No"
                  />
                </Col>
                <Col span={12}>
                  <Typography.Text className="colon-before-text">
                    {props.employee.employeeNumber}
                  </Typography.Text>
                </Col>
              </Row>
            </Col>
            {
              !_.isEmpty(props.record?.orgStructureEntity)
                ? Object.keys(props.record?.orgStructureEntity).map(level => <Col span={12}>
                    <Row>
                        <Col span={12}>{level}</Col>
                        <Col span={12}>
                            <Typography.Text className='colon-before-text'>
                                {props.record?.orgStructureEntity[level].name}
                            </Typography.Text>
                        </Col>
                    </Row>
                </Col>)
              : null
            }
            {/* Department */}
            {/* <Col span={12}>
              <Row>
                <Col span={12}>
                  <FormattedMessage
                    id="employee_journey_update.department"
                    defaultMessage="Department"
                  />
                </Col>
                <Col span={12}>
                  <Typography.Text className="colon-before-text">
                    {
                      props.data?.departments?.find(
                        (option) => option.value == props.record?.departmentId,
                      )?.label
                    }
                  </Typography.Text>
                </Col>
              </Row>
            </Col> */}
            {/* Job Category */}
            <Col span={12}>
              <Row>
                <Col span={12}>
                  <FormattedMessage
                    id="employee_journey_update.job_category"
                    defaultMessage="Job Category"
                  />
                </Col>
                <Col span={12}>
                  <Typography.Text className="colon-before-text">
                    {
                      props.data?.jobCategories?.find(
                        (option) => option.value == props.record?.jobCategoryId,
                      )?.label
                    }
                  </Typography.Text>
                </Col>
              </Row>
            </Col>
            {/* Division */}
            {/* <Col span={12}>
              <Row>
                <Col span={12}>
                  <FormattedMessage
                    id="employee_journey_update.division"
                    defaultMessage="Division"
                  />
                </Col>
                <Col span={12}>
                  <Typography.Text className="colon-before-text">
                    {
                      props.data?.divisions?.find(
                        (option) => option.value == props.record?.divisionId,
                      )?.label
                    }
                  </Typography.Text>
                </Col>
              </Row>
            </Col> */}
            {/* Job Title */}
            <Col span={12}>
              <Row>
                <Col span={12}>
                  <FormattedMessage
                    id="employee_journey_update.job_title"
                    defaultMessage="Job Title"
                  />
                </Col>
                <Col span={12}>
                  <Typography.Text className="colon-before-text">
                    {
                      props.data?.jobTitles?.find(
                        (option) => option.value == props.record?.jobTitleId,
                      )?.label
                    }
                  </Typography.Text>
                </Col>
              </Row>
            </Col>
            {/* Location */}
            <Col span={12}>
              <Row>
                <Col span={12}>
                  <FormattedMessage
                    id="employee_journey_update.location"
                    defaultMessage="Location"
                  />
                </Col>
                <Col span={12}>
                  <Typography.Text className="colon-before-text">
                    {
                      props.data?.locations?.find(
                        (option) => option.value == props.record?.locationId,
                      )?.label
                    }
                  </Typography.Text>
                </Col>
              </Row>
            </Col>
            {/* Pay Grade */}
            <Col span={12}>
              <Row>
                <Col span={12}>
                  <FormattedMessage
                    id="employee_journey_update.pay_grade"
                    defaultMessage="Pay Grade"
                  />
                </Col>
                <Col span={12}>
                  <Typography.Text className="colon-before-text">
                    {
                      props.data?.payGrades?.find(
                        (option) => option.value == props.record?.payGradeId,
                      )?.label
                    }
                  </Typography.Text>
                </Col>
              </Row>
            </Col>
            {/* Reporting Person */}
            <Col span={12}>
              <Row>
                <Col span={12}>
                  <FormattedMessage
                    id="employee_journey_update.reporting_person"
                    defaultMessage="Reporting Person"
                  />
                </Col>
                <Col span={12}>
                  <Typography.Text className="colon-before-text">
                    {
                      props.data?.employees?.find(
                        (option) => option.value == props.record?.reportsToEmployeeId,
                      )?.label
                    }
                  </Typography.Text>
                </Col>
              </Row>
            </Col>
            {/* Functional Reporting Person */}
            <Col span={12}>
              <Row>
                <Col span={12}>
                  <FormattedMessage
                    id="employee_journey_update.functional_reporting_person"
                    defaultMessage="Functional Reporting Person"
                  />
                </Col>
                <Col span={12}>
                  <Typography.Text className="colon-before-text">
                    {
                      props.data?.employees?.find(
                        (option) => option.value == props.record?.functionalReportsToEmployeeId,
                      )?.label
                    }
                  </Typography.Text>
                </Col>
              </Row>
            </Col>
            {/* Hire Date */}
            <Col span={12}>
              <Row>
                <Col span={12}>
                  <FormattedMessage
                    id="employee_journey_update.hire_date"
                    defaultMessage="Hire Date"
                  />
                </Col>
                <Col span={12}>
                  <Typography.Text className="colon-before-text">
                    {moment(props.employee?.hireDate, 'YYYY-MM-DD').format('DD-MM-YYYY')}
                  </Typography.Text>
                </Col>
              </Row>
            </Col>
            {/* Space */}
            <Col span={12}></Col>
            {/* Resignation Handed over */}
            <Col span={12}>
              <Row>
                <Col span={12}>
                  <FormattedMessage
                    id="employee_journey_update.resignation_handed_over"
                    defaultMessage="Resignation Handed over"
                  />
                </Col>
                <Col span={12}>
                  <Typography.Text className="colon-before-text">
                    {moment(props.record?.resignationHandoverDate, 'YYYY-MM-DD').format(
                      'DD-MM-YYYY',
                    )}
                  </Typography.Text>
                </Col>
              </Row>
            </Col>
            {/* Resignation Effective Date */}
            <Col span={12}>
              <Row>
                <Col span={12}>
                  <FormattedMessage
                    id="employee_journey_update.resignation_effective_date"
                    defaultMessage="Resignation Effective Date"
                  />
                </Col>
                <Col span={12}>
                  <Typography.Text className="colon-before-text">
                    {moment(props.record?.effectiveDate, 'YYYY-MM-DD').format('DD-MM-YYYY')}
                  </Typography.Text>
                </Col>
              </Row>
            </Col>
            {/* Last Worked Date */}
            <Col span={12}>
              <Row>
                <Col span={12}>
                  <FormattedMessage
                    id="employee_journey_update.last_worked_date"
                    defaultMessage="Last Worked Date"
                  />
                </Col>
                <Col span={12}>
                  <Typography.Text className="colon-before-text">
                    {moment(props.record?.lastWorkingDate, 'YYYY-MM-DD').format('DD-MM-YYYY')}
                  </Typography.Text>
                </Col>
              </Row>
            </Col>
            {/* Notice Period Completion Status */}
            <Col span={12}>
              <Row>
                <Col span={12}>
                  <FormattedMessage
                    id="employee_journey_update.notice_period_completion_status"
                    defaultMessage="Notice Period Completion Status"
                  />
                </Col>
                <Col span={12}>
                  <Typography.Text className="colon-before-text">
                    {props.record?.resignationNoticePeriodRemainingDays ||
                    props.record?.resignationNoticePeriodRemainingDays == 0 ? (
                      props.record?.resignationNoticePeriodRemainingDays > 0 ? (
                        <FormattedMessage
                          id="employee_journey_update.notice_period_completion_status.not_completed"
                          defaultMessage="Not Completed"
                        />
                      ) : (
                        <FormattedMessage
                          id="employee_journey_update.notice_period_completion_status.completed"
                          defaultMessage="Completed"
                        />
                      )
                    ) : (
                      <FormattedMessage
                        id="employee_journey_update.notice_period_completion_status.no_info"
                        defaultMessage=" "
                      />
                    )}
                  </Typography.Text>
                </Col>
              </Row>
            </Col>
            {/* Promotion Type */}
            <Col span={24}>
              <Row>
                <Col span={8}>
                  <FormattedMessage
                    id="employee_journey_update.resignation_type"
                    defaultMessage="Resignation Type"
                  />
                </Col>
                <Col span={16}>
                  <Typography.Text className="colon-before-text">
                    {
                      props.data?.resignationTypes?.find(
                        (option) => option.value == props.record?.resignationTypeId,
                      )?.label
                    }
                  </Typography.Text>
                </Col>
              </Row>
            </Col>
            {/* Resignation Reason */}
            <Col span={24}>
              <Row>
                <Col span={8}>
                  <FormattedMessage
                    id="employee_journey_update.resignation_reason"
                    defaultMessage="Resignation Reason"
                  />
                </Col>
                <Col span={16}>
                  <Typography.Text className="colon-before-text">
                    {
                      props.data?.resignationReasons?.find(
                        (option) => option.value == props.record?.resignationReasonId,
                      )?.label
                    }
                  </Typography.Text>
                </Col>
              </Row>
            </Col>
            {/* Resignation Remarks */}
            <Col span={24}>
              <Row>
                <Col span={8}>
                  <FormattedMessage
                    id="employee_journey_update.resignation_remark"
                    defaultMessage="Resignation Remarks"
                  />
                </Col>
                <Col span={16}>
                  <Typography.Text className="colon-before-text">
                    {props.record?.resignationRemarks}
                  </Typography.Text>
                </Col>
              </Row>
            </Col>
            {/* Attachment */}
            <Col span={24}>
              <Row>
                <Col span={8}>
                  <FormattedMessage
                    id="employee_journey_update.attachment"
                    defaultMessage="Attachment"
                  />
                </Col>
                <Col span={16}>
                  <Typography.Text className="colon-before-text">
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
                              <iframe
                                src={attachment.data}
                                style={{ width: '100%', height: '65vh' }}
                              />
                            )}
                          </Modal>
                        </>
                      )
                    )}
                  </Typography.Text>
                </Col>
              </Row>
            </Col>
            <Col span={24}>
              <Row>
                <Col span={8}>
                  <FormattedMessage
                    id="employee_journey_update.resignation_feedback_form"
                    defaultMessage="Employee Feedback Form"
                  />
                </Col>
                <Col span={16}>
                  <Typography.Text className="colon-before-text">
                    {jobInstances.length > 0
                      ? jobInstances.map((instance) => {
                          return (
                            <Link
                              key={instance.hash}
                              to={`/template-builder/${instance.hash}/interactive-viewer`}
                            >
                              {`Employee Form (${instance.status})`}
                            </Link>
                          );
                        })
                      : '-'}
                  </Typography.Text>
                </Col>
              </Row>
            </Col>
          </Row>
        )}
      </Card>
    </Timeline.Item>
  );
};

export default ResignationHistoryItem;
