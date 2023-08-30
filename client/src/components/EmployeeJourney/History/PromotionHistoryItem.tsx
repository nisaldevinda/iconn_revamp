import React, { useState, useEffect } from 'react';
import _ from 'lodash';
import { FormattedMessage } from 'react-intl';
import { Button, Card, Col, Row, Tag, Timeline, Typography, Spin, Space } from 'antd';
import { DownOutlined, UpOutlined, DownloadOutlined } from '@ant-design/icons';
import moment from 'moment';
import Modal from 'antd/lib/modal/Modal';
import { getAttachment } from '@/services/employeeJourney';

interface PromotionHistoryItemProps {
    data: any,
    record: any,
    employee: any
}

const PromotionHistoryItem: React.FC<PromotionHistoryItemProps> = (props) => {
    const [isVisibleDetails, setIsVisibleDetails] = useState(false);
    const [attachment, setAttachment] = useState();
    const [isAttachmentModalVisible, setIsAttachmentModalVisible] = useState(false);

    useEffect(() => {
        fetchAttachment();
    }, [])

    const fetchAttachment = async () => {
        if (props.record.attachmentId) {
            const _attachment = await getAttachment(props.employee.id, props.record.attachmentId);
            setAttachment(_attachment?.data);
        }
    }

    return (
      <Timeline.Item color="#15AC88" className="timeline-head-center">
        <Card className="timeline-card" style={{ borderColor: '#15AC88' }}>
          <div onClick={() => setIsVisibleDetails(!isVisibleDetails)}>
            <Button
              shape="circle"
              icon={isVisibleDetails ? <UpOutlined /> : <DownOutlined />}
              onClick={() => {
                setIsVisibleDetails(!isVisibleDetails);
              }}
              size="small"
              style={{
                position: 'relative',
                float: 'right',
              }}
            />
            <Typography.Text>
              <Tag color="#15AC88">
                <FormattedMessage id="employee_journey_update.promoted" defaultMessage="Promoted" />
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
              {/* Org Structure */}
              {!_.isEmpty(props.record?.orgStructureEntity)
                ? Object.keys(props.record?.orgStructureEntity).map((level) => (
                    <Col span={12}>
                      <Row>
                        <Col span={12}>{level}</Col>
                        <Col span={12}>
                          <Typography.Text className="colon-before-text">
                            {props.record?.orgStructureEntity[level].name}
                          </Typography.Text>
                        </Col>
                      </Row>
                    </Col>
                  ))
                : null}
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
                                <Typography.Text className='colon-before-text'>
                                    {props.data?.departments?.find(option => option.value == props.record?.departmentId)?.label}
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
                                <Typography.Text className='colon-before-text'>
                                    {props.data?.divisions?.find(option => option.value == props.record?.divisionId)?.label}
                                </Typography.Text>
                            </Col>
                        </Row>
                    </Col> */}
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
              {/* Calendar */}
              <Col span={12}>
                <Row>
                  <Col span={12}>
                    <FormattedMessage
                      id="employee_journey_update.calendar"
                      defaultMessage="Calendar"
                    />
                  </Col>
                  <Col span={12}>
                    <Typography.Text className="colon-before-text">
                      {
                        props.data?.calendars?.find(
                          (option) => option.value == props.record?.calendarId,
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
              {/* Promotion Type */}
              <Col span={24}>
                <Row>
                  <Col span={8}>
                    <FormattedMessage
                      id="employee_journey_update.promotion_type"
                      defaultMessage="Promotion Type"
                    />
                  </Col>
                  <Col span={16}>
                    <Typography.Text className="colon-before-text">
                      {
                        props.data?.promotionTypes?.find(
                          (option) => option.value == props.record?.promotionTypeId,
                        )?.label
                      }
                    </Typography.Text>
                  </Col>
                </Row>
              </Col>
              {/* Promotion Reason */}
              <Col span={24}>
                <Row>
                  <Col span={8}>
                    <FormattedMessage
                      id="employee_journey_update.promotion_reason"
                      defaultMessage="Promotion Reason"
                    />
                  </Col>
                  <Col span={16}>
                    <Typography.Text className="colon-before-text">
                      {props.record?.promotionReason}
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
            </Row>
          )}
        </Card>
      </Timeline.Item>
    );
};

export default PromotionHistoryItem;
