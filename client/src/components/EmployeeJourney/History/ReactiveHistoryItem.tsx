import React, { useState, useEffect } from 'react';
import _ from 'lodash';
import { FormattedMessage } from 'react-intl';
import { Button, Card, Col, Row, Tag, Timeline, Typography } from 'antd';
import { DownOutlined, UpOutlined } from '@ant-design/icons';
import moment from 'moment';

interface ReactiveHistoryItemProps {
  data: any,
  record: any,
  employee: any
}

const ReactiveHistoryItem: React.FC<ReactiveHistoryItemProps> = (props) => {
  const [isVisibleDetails, setIsVisibleDetails] = useState(false);
  const [oldEmployeeNumber, setOldEmployeeNumber] = useState();
  const [newEmployeeNumber, setNewEmployeeNumber] = useState();

  useEffect(() => {
    const reactiveComment = props.record?.reactiveComment;
    if (reactiveComment) {
      const [_oldEmployeeNumber, _newEmployeeNumber] = JSON.parse(reactiveComment
        .replace('Employee number changed from ', '["')
        .replace(' to ', '","')
        .replace('.', '"]'));

      setOldEmployeeNumber(_oldEmployeeNumber);
      setNewEmployeeNumber(_newEmployeeNumber);
    }
  }, [props.record]);

  return (
    <Timeline.Item color='#909A99' className='timeline-head-center'>
      <Card className='timeline-card' style={{ borderColor: '#909A99' }}>
        <div onClick={() => setIsVisibleDetails(!isVisibleDetails)}>
          <Button
            shape="circle"
            icon={isVisibleDetails ? <UpOutlined /> : <DownOutlined />}
            onClick={() => setIsVisibleDetails(!isVisibleDetails)}
            size='small'
            style={{
              position: 'relative',
              float: 'right',
            }}
          />
          <Typography.Text>
            <Tag color="#909A99">
              <FormattedMessage
                id="employee_journey_update.reactive"
                defaultMessage="Reactive"
              />
            </Tag>
            {moment(props.record.effectiveDate, 'YYYY-MM-DD').format("DD MMM YYYY")}
          </Typography.Text>
          <Typography.Title level={5} style={{ marginTop: 0, marginBottom: 0 }}>
            {props.data?.jobTitles?.find(option => option.value == props.record?.jobTitleId)?.label}
          </Typography.Title>
        </div>
        {isVisibleDetails && <Row style={{ marginTop: 18 }}>
          {/* New Employee Number */}
          {newEmployeeNumber && <Col span={12}>
            <Row>
              <Col span={12}>
                <FormattedMessage
                  id="employee_journey_update.new_emp_no"
                  defaultMessage="New Emp No"
                />
              </Col>
              <Col span={12}>
                <Typography.Text className='colon-before-text'>
                  {newEmployeeNumber}
                </Typography.Text>
              </Col>
            </Row>
          </Col>}
          {/* Old Employee Number */}
          {oldEmployeeNumber && <Col span={12}>
            <Row>
              <Col span={12}>
                <FormattedMessage
                  id="employee_journey_update.old_emp_no"
                  defaultMessage="Old Emp No"
                />
              </Col>
              <Col span={12}>
                <Typography.Text className='colon-before-text'>
                  {oldEmployeeNumber}
                </Typography.Text>
              </Col>
            </Row>
          </Col>}
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
                <Typography.Text className='colon-before-text'>
                  {props.data?.jobCategories?.find(option => option.value == props.record?.jobCategoryId)?.label}
                </Typography.Text>
              </Col>
            </Row>
          </Col>
          {/* Org Structure */}
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
                <Typography.Text className='colon-before-text'>
                  {props.data?.jobTitles?.find(option => option.value == props.record?.jobTitleId)?.label}
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
                <Typography.Text className='colon-before-text'>
                  {props.data?.payGrades?.find(option => option.value == props.record?.payGradeId)?.label}
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
                <Typography.Text className='colon-before-text'>
                  {props.data?.locations?.find(option => option.value == props.record?.locationId)?.label}
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
                <Typography.Text className='colon-before-text'>
                  {props.data?.employees?.find(option => option.value == props.record?.reportsToEmployeeId)?.label}
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
                <Typography.Text className='colon-before-text'>
                  {props.data?.employees?.find(option => option.value == props.record?.functionalReportsToEmployeeId)?.label}
                </Typography.Text>
              </Col>
            </Row>
          </Col>
        </Row>}
      </Card>
    </Timeline.Item>
  );
};

export default ReactiveHistoryItem;
