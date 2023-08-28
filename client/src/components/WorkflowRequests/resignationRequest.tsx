import React, { useEffect, useState } from 'react';
import {
  Image,
  Col,
  Row,
  List,
  DatePicker,
  TimePicker,
  Space,
  Input,
  Avatar,
  message,
  Spin,
  FormInstance,
  Typography,
} from 'antd';
import { FormattedMessage } from 'react-intl';
import { ProFormDatePicker } from '@ant-design/pro-form';
import TextArea from 'antd/lib/input/TextArea';
import request, { APIResponse } from '@/utils/request';
import { getModel, ModelType } from '@/services/model';
import { getEmployeeCurrentDetails } from '@/services/employee';
import AttachmnetList from './leaveAttchementList';

import { DownloadOutlined, PaperClipOutlined } from '@ant-design/icons';
import './index.less';
import moment from 'moment';
import BreakTotalIcon from '../../assets/attendance/Break-01.svg';
import ApprovalLevelDetails from './approvalLevelDetails';
import _ from 'lodash';
import { getResignationAttachment } from '@/services/employeeJourney';

type ResignationRequestProps = {
  resignationRequestData: any;
  employeeFullName: any;
  employeeNumber: any;
  hireDate: any;
  scope: any;
  employeeId: any;
  actions?: any;
  setApproverComment: any;
  form: FormInstance;
  workflowInstanceId: any;
  setResignationUpdatedEffectiveDate: any;
};

const ResignationRequest: React.FC<ResignationRequestProps> = (props) => {
  const [employeeNumber, setEmployeeNumber] = useState<string | null>(null);
  const [hireDate, setHireDate] = useState<string | null>(null);
  const [resignationHandOverDate, setResignationHandOverDate] = useState<string | null>(null);
  const [lastWorkingDate, setLastWorkingDate] = useState<string | null>(null);
  const [effectiveDateModel, setEffectiveDateModel] = useState<string | null>(null);
  const [updatedEffectiveDateModel, setUpdatedEffectiveDateModel] = useState<string | null>(null);
  const [resignationType, setResignationType] = useState<string | null>(null);
  const [outTimeModel, setOutTimeModel] = useState<string | null>(null);
  const [reasonModel, setReasonModel] = useState('');
  const [imageUrl, setimageUrl] = useState<string | null>(null);
  const [timeChangeRequestId, setTimeChangeRequestId] = useState<string | null>(null);
  const [jobTitle, setJobTitle] = useState<string | null>(null);
  const [breakDetails, setBreakDetails] = useState<any | null>([]);
  const [approvalLevelList, setApprovalLevelList] = useState<any>([]);
  const [isGettingLevels, setIsGettingLevels] = useState<boolean>(false);
  const [attachment, setAttachment] = useState<any>([]);
  const [workflowInstanceId, setWorkflowInstanceId] = useState<string | null>(null);
  const [attachmentId, setAttachmentId] = useState<string | null>(null);

  useEffect(() => {
    const empNumber = props.employeeNumber ? props.employeeNumber : '-';
    const hireDate = props.hireDate ? props.hireDate : '-';
    const handOverDate = props.resignationRequestData.resignationHandoverDate
      ? props.resignationRequestData.resignationHandoverDate
      : '-';
    const lastWorkingDay = props.resignationRequestData.lastWorkingDate
      ? props.resignationRequestData.lastWorkingDate
      : '-';
    const effectiveDate = props.resignationRequestData.effectiveDate
      ? props.resignationRequestData.effectiveDate
      : '-';
    const type = props.resignationRequestData.resignationType
      ? props.resignationRequestData.resignationType
      : '-';
    const reason = props.resignationRequestData.resignationReason
      ? props.resignationRequestData.resignationReason
      : '-';
    const attanchment = props.resignationRequestData.attachmentId
      ? props.resignationRequestData.attachmentId
      : null;
    setWorkflowInstanceId(props.workflowInstanceId);
    setAttachmentId(attanchment);
    setEmployeeNumber(empNumber);
    setHireDate(hireDate);
    setResignationHandOverDate(handOverDate);
    setLastWorkingDate(lastWorkingDay);
    setResignationType(type);
    setReasonModel(reason);
    setEffectiveDateModel(effectiveDate);
  });

  useEffect(() => {
    if (props.resignationRequestData.updatedEffectiveDate) {
      const updatedEffectiveDateRecord = props.resignationRequestData.updatedEffectiveDate
        ? props.resignationRequestData.updatedEffectiveDate
        : '-';
      props.form.setFieldsValue({
        effectiveDate: moment(updatedEffectiveDateRecord, 'YYYY-MM-DD').isValid()
          ? moment(updatedEffectiveDateRecord, 'YYYY-MM-DD')
          : undefined,
      });
      props.setResignationUpdatedEffectiveDate(
        moment(updatedEffectiveDateRecord, 'YYYY-MM-DD').format('YYYY-MM-DD'),
      );
      setUpdatedEffectiveDateModel(updatedEffectiveDateRecord);
    } else {
      props.setResignationUpdatedEffectiveDate(null);
    }
  }, [props.resignationRequestData.updatedEffectiveDate]);

  useEffect(() => {
    if (attachmentId) {
      getAttachments();
    }
  }, [attachmentId]);

  const getAttachments = async () => {
    const _attachment = await getResignationAttachment(
      props.resignationRequestData.employeeId,
      props.resignationRequestData.attachmentId,
    );
    setAttachment(_attachment?.data);
  };

  const getEmployeeProfileImage = async () => {
    try {
      const actions: any = [];
      const response = await getModel('employee');
      let path: string;

      if (!_.isEmpty(response.data)) {
        path =
          `/api${response.data.modelDataDefinition.path}/` + props.employeeId + `/profilePicture`;
        const result = await request(path);
        if (result['data'] !== null) {
          setimageUrl(result['data']['data']);
        }
      }
    } catch (error) {
      console.log(error);
    }
  };

  const getEmployeeRelateDataSet = () => {
    try {
      getEmployeeCurrentDetails(props.employeeId).then((res) => {
        if (res.data) {
          let jobTitle = res.data.jobTitle != null ? res.data.jobTitle : '-';
          setJobTitle(jobTitle);
        }
      });
    } catch (error) {
      if (_.isEmpty(error)) {
        const hide = message.loading('Error');
        message.error('Validation errors');
        hide();
      }
    }
  };

  return (
    <>
      <Row style={{ width: '100%', marginLeft: 20 }}>
        {props.scope != 'EMPLOYEE' ? (
          <>
            <Row style={{ marginBottom: 30, marginTop: 10, width: '100%' }}>
              <Col span={16} style={{ backgroundColor: '' }}>
                <Row>
                  <Col>
                    {imageUrl ? (
                      <Avatar style={{ fontSize: 22, border: 1 }} src={imageUrl} size={55} />
                    ) : (
                      <Avatar style={{ backgroundColor: 'blue', fontSize: 18 }} size={55}>
                        {props.employeeFullName != null
                          ? props.employeeFullName
                              .split(' ')
                              .map((x) => x[0])
                              .join('')
                          : ''}
                      </Avatar>
                    )}
                  </Col>
                  <Col style={{ paddingLeft: 10 }}>
                    <Row style={{ fontWeight: 500, fontSize: 20, color: '#394241' }}>
                      {props.employeeFullName}
                    </Row>
                    <Row style={{ fontWeight: 400, fontSize: 16, color: '#626D6C', paddingTop: 0 }}>
                      {jobTitle}
                    </Row>
                  </Col>
                </Row>
              </Col>
            </Row>
          </>
        ) : (
          <></>
        )}
        <Row style={{ marginBottom: 20, width: '100%' }}>
          <Col span={16} style={{ backgroundColor: '' }}>
            <Row>
              <Col style={{ fontWeight: 'bold', fontSize: 16 }}>
                <FormattedMessage id="ResignationDetails" defaultMessage="Resignation Details" />
              </Col>
            </Row>
          </Col>
        </Row>
        <Row style={{ marginBottom: 20, marginLeft: 2, color: 'gray', width: '100%' }}>
          <Space direction="vertical" style={{ width: '100%' }} size="large">
            <Row>
              <Col span={6} style={{ paddingLeft: 10, color: '#909A99' }}>
                <Row>
                  <Col>{'Employee Number'}</Col>
                </Row>
              </Col>
              <Col span={1} style={{ backgroundColor: '' }}>
                <Row>
                  <Col>{':'}</Col>
                </Row>
              </Col>
              <Col span={3} style={{ paddingLeft: 5, color: '#626D6C' }}>
                {employeeNumber}
              </Col>
              <Col span={6} style={{ paddingLeft: 15, color: '#909A99' }}>
                <Row>
                  <Col>{'Hire Date'}</Col>
                </Row>
              </Col>
              <Col span={1} style={{ backgroundColor: '' }}>
                <Row>
                  <Col>{':'}</Col>
                </Row>
              </Col>
              <Col span={3} style={{ paddingLeft: 5, color: '#626D6C' }}>
                {moment(hireDate, 'YYYY-MM-DD').isValid()
                  ? moment(hireDate).format('DD-MM-YYYY')
                  : null}
              </Col>
            </Row>
            <Row>
              <Col span={6} style={{ paddingLeft: 10, color: '#909A99' }}>
                <Row>
                  <Col>{'Handed over Date'}</Col>
                </Row>
              </Col>
              <Col span={1} style={{ backgroundColor: '' }}>
                <Row>
                  <Col>{':'}</Col>
                </Row>
              </Col>
              <Col span={3} style={{ paddingLeft: 5, color: '#626D6C' }}>
                {moment(resignationHandOverDate, 'YYYY-MM-DD').isValid()
                  ? moment(resignationHandOverDate).format('DD-MM-YYYY')
                  : null}
              </Col>
              <Col span={6} style={{ paddingLeft: 15, color: '#909A99' }}>
                <Row>
                  <Col>{'Last Working Date'}</Col>
                </Row>
              </Col>
              <Col span={1} style={{ backgroundColor: '' }}>
                <Row>
                  <Col>{':'}</Col>
                </Row>
              </Col>
              <Col span={3} style={{ paddingLeft: 5, color: '#626D6C' }}>
                {moment(lastWorkingDate, 'YYYY-MM-DD').isValid()
                  ? moment(lastWorkingDate).format('DD-MM-YYYY')
                  : null}
              </Col>
            </Row>
            <Row>
              <Col span={6} style={{ paddingLeft: 10, color: '#909A99' }}>
                <Row>
                  <Col>{'Notice Period Completed Status'}</Col>
                </Row>
              </Col>
              <Col span={1} style={{ backgroundColor: '' }}>
                <Row>
                  <Col>{':'}</Col>
                </Row>
              </Col>
              <Col span={3} style={{ color: '#626D6C' }}>
                <Typography.Text style={{ color: '#626D6C' }}>
                  {props.resignationRequestData?.resignationNoticePeriodRemainingDays ||
                  props.resignationRequestData?.resignationNoticePeriodRemainingDays == 0 ? (
                    props.resignationRequestData?.resignationNoticePeriodRemainingDays > 0 ? (
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
              <Col span={6} style={{ paddingLeft: 15, color: '#909A99' }}>
                <Row>
                  <Col>{'Resignation Type'}</Col>
                </Row>
              </Col>
              <Col span={1} style={{ backgroundColor: '' }}>
                <Row>
                  <Col>{':'}</Col>
                </Row>
              </Col>
              <Col span={3} style={{ paddingLeft: 5, color: '#626D6C' }}>
                {resignationType}
              </Col>
            </Row>
            <Row>
              <Col span={6} style={{ paddingLeft: 10, color: '#909A99' }}>
                <Row>
                  <Col>{'Requested Effective Date'}</Col>
                </Row>
              </Col>
              <Col span={1} style={{ backgroundColor: '' }}>
                <Row>
                  <Col>{':'}</Col>
                </Row>
              </Col>
              <Col span={3} style={{ paddingLeft: 5, color: '#626D6C' }}>
                {moment(effectiveDateModel, 'YYYY-MM-DD').isValid()
                  ? moment(effectiveDateModel).format('DD-MM-YYYY')
                  : null}
              </Col>
              <Col span={6} style={{ paddingLeft: 10, color: '#909A99' }}>
                <Row>
                  <Col>{'Updated Effective Date'}</Col>
                </Row>
              </Col>
              <Col span={1} style={{ backgroundColor: '' }}>
                <Row>
                  <Col>{':'}</Col>
                </Row>
              </Col>
              <Col span={4} style={{ paddingLeft: 5, color: '#626D6C' }}>
                {props.scope != 'EMPLOYEE' && props.actions.length > 0 ? (
                  <ProFormDatePicker
                    name="effectiveDate"
                    style={{ width: '100%', borderRadius: 6 }}
                    format={'DD-MM-YYYY'}
                    onChange={(value) => {
                      props.form.setFieldsValue({
                        effectiveDate: value,
                      });
                      props.setResignationUpdatedEffectiveDate(value.format('YYYY-MM-DD'));
                    }}
                  />
                ) : moment(updatedEffectiveDateModel, 'YYYY-MM-DD').isValid() ? (
                  moment(updatedEffectiveDateModel).format('DD-MM-YYYY')
                ) : null}
              </Col>
            </Row>
          </Space>
        </Row>

        <Row style={{ marginBottom: 5, width: '100%' }}>
          <Col span={16} style={{ backgroundColor: '' }}>
            <Row>
              <Col style={{ fontWeight: 'bold', fontSize: 16 }}>
                <FormattedMessage id="reason" defaultMessage="Reason" />
              </Col>
            </Row>
          </Col>
        </Row>
        <Row style={{ marginBottom: 20, marginLeft: 2, color: 'gray', width: '100%' }}>
          <Col span={20} style={{ paddingLeft: 10 }}>
            <Row>
              <Col>{reasonModel}</Col>
            </Row>
          </Col>
        </Row>
        {attachment.length > 0 ? (
          <AttachmnetList attachementList={attachment}></AttachmnetList>
        ) : (
          <>
            <Row style={{ marginBottom: 10 }}>
              <Col span={24} style={{ backgroundColor: '' }}>
                <Row>
                  <Col span={24} style={{ backgroundColor: '' }}>
                    <Row>
                      <Col style={{ fontWeight: 'bold' }}>
                        <FormattedMessage id="attachedDocuments" defaultMessage="Attachments:" />
                      </Col>
                    </Row>
                  </Col>
                </Row>
              </Col>
            </Row>
            <Row style={{ marginBottom: 10 }}>
              <Col>
                <Row>
                  <Col>{'--'}</Col>
                </Row>
              </Col>
            </Row>
          </>
        )}

        {props.workflowInstanceId ? (
          <ApprovalLevelDetails
            workflowInstanceId={props.workflowInstanceId}
            setApproverComment={props.setApproverComment}
            actions={props.actions}
            scope={props.scope}
          ></ApprovalLevelDetails>
        ) : (
          <></>
        )}
      </Row>
    </>
  );
};

export default ResignationRequest;
