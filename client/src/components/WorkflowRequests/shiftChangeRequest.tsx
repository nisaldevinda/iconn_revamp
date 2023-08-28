import React, { useEffect, useState } from 'react';
import { Image, Col, Row, List, DatePicker, TimePicker, Space, Input, Avatar, message,Tooltip } from 'antd';
import { FormattedMessage } from 'react-intl';
import TextArea from 'antd/lib/input/TextArea';
import request, { APIResponse } from '@/utils/request';
import { getModel, ModelType } from '@/services/model';
import { getEmployeeCurrentDetails } from '@/services/employee';

import { DownloadOutlined, PaperClipOutlined } from '@ant-design/icons';
import './index.less';
import moment from 'moment';
import BreakTotalIcon from '../../assets/attendance/Break-01.svg';
import ApprovalLevelDetails from './approvalLevelDetails';

type ShiftChangeRequestProps = {
  shiftChangeRequestData: any;
  employeeFullName: any;
  scope: any;
  employeeId: any;
  actions?:any;
  setApproverComment: any
};

const ShiftChangeRequest: React.FC<ShiftChangeRequestProps> = (props) => {
  // const [attachementSet, setAttachementSet] = useState<any>(props.attachementList);
  const [shiftDateModel, setShiftDateModel] = useState<string | null>(null);
  const [currentShiftModel, setCurrentShiftModel] = useState<string | null>(null);
  const [newShiftModel, setNewShiftModel] = useState<string | null>(null);
  const [reasonModel, setReasonModel] = useState('');
  const [imageUrl, setimageUrl] = useState<string | null>(null);
  const [shiftChangeRequestId, setShiftChangeRequestId] = useState<string | null>(null);
  const [jobTitle, setJobTitle] = useState<string | null>(null);
  const [breakDetails, setBreakDetails] = useState<any | null>([]);

  useEffect(() => {
    const shiftDateRecord = props.shiftChangeRequestData.shiftDate
      ? moment(props.shiftChangeRequestData.shiftDate).format('YYYY-MM-DD')
      : '-';
    const currentShift = props.shiftChangeRequestData.currentShiftName
      ? props.shiftChangeRequestData.currentShiftName
      : '-';
    const newShift = props.shiftChangeRequestData.newShiftName
      ? props.shiftChangeRequestData.newShiftName
      : '-';

    setShiftDateModel(shiftDateRecord);
    setCurrentShiftModel(currentShift);
    setNewShiftModel(newShift);

    if (
      props.shiftChangeRequestData.reason == null ||
      props.shiftChangeRequestData.reason == undefined
    ) {
      setReasonModel('_');
    } else {
      setReasonModel(props.shiftChangeRequestData.reason);
    }
    setShiftChangeRequestId(props.shiftChangeRequestData.id);
  });

  useEffect(() => {
    if (props.shiftChangeRequestData.id != undefined) {
      const breaks =
        props.shiftChangeRequestData.breakDetails != undefined
          ? props.shiftChangeRequestData.breakDetails
          : [];
      setBreakDetails(breaks);
      getEmployeeProfileImage();
      getEmployeeRelateDataSet();
    }
  }, [shiftChangeRequestId]);

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
            <Row style={{ marginBottom: 30, marginTop: 30, width: '100%' }}>
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
        <Row style={{
            marginBottom: 20, color: '#626D6C', fontWeight: 400,
            fontSize: 20,
            width: '100%'
        }}>
            <Col span={8} style={{
                paddingTop: 10,
            }}>
              Date &nbsp;|&nbsp;{moment(shiftDateModel, 'YYYY-MM-DD').isValid()
                  ? moment(shiftDateModel).format('DD-MM-YYYY')
                  : null}
            </Col>
        </Row>
        <Row style={{ marginBottom: 20, width: '100%' }}>
          <Col span={16} style={{ backgroundColor: '' }}>
            <Row>
              <Col style={{ fontWeight: 500, fontSize: 18 }}>
                <FormattedMessage
                  id="ShiftChangeRecordDetails"
                  defaultMessage="Shift Change Details"
                />
              </Col>
            </Row>
          </Col>
        </Row>
        <Row style={{ marginBottom: 20, marginLeft: 2, color: 'gray', width: '100%' }}>
          <Col span={10}>
            <Row style={{width: 300 , height: 95, backgroundColor: '#F6FFED', borderRadius: 6 }}>
              <Row style={{paddingTop: 20, paddingLeft: 20, fontWeight: 400, fontSize: 15, color: '#86C129', width: '100%'}}>New Shift</Row>
              <Row style={{paddingTop: 10, paddingLeft: 20, paddingBottom: 20, fontWeight: 530, fontSize: 20, color: '#394241', width: '100%'}}>
                {
                  (newShiftModel) ? (
                    <>
                    {
                      newShiftModel.length <= 30 ? (
                        newShiftModel
                      ) : (
                        <Tooltip title={newShiftModel}>
                            <p >
                              {newShiftModel.substring(0, 30 - 3) + '...'}{' '}
                            </p>
                          </Tooltip> 
                      )
                    }
                    </>
                    
                  ) : (
                    <>'-'</>
                  )
                }
                
              </Row>
            </Row>
          </Col>
          <Col span={10} style={{ backgroundColor: '' }}>
            <Row style={{width: 300 , height: 95, backgroundColor: '#FFFBE6', borderRadius: 6, marginLeft: 5 }}>
              <Row style={{paddingTop: 20, paddingLeft: 20, fontWeight: 400, fontSize: 15, color: '#D48806', width: '100%'}}>Current Shift</Row>
              <Row style={{paddingTop: 10, paddingLeft: 20, paddingBottom: 20, fontWeight: 530, fontSize: 20, color: '#394241', width: '100%'}}>
                {
                  (currentShiftModel) ? (
                    <>
                    {
                      currentShiftModel.length <= 30 ? (
                        currentShiftModel
                      ) : (
                        <Tooltip title={currentShiftModel}>
                            <p >
                              {currentShiftModel.substring(0, 30 - 3) + '...'}{' '}
                            </p>
                          </Tooltip> 
                      )
                    }
                    </>
                    
                  ) : (
                    <>'-'</>
                  )
                }
                
              </Row>
            </Row>
          </Col>
        </Row>
        <Row style={{ marginBottom: 5, width: '100%' }}>
          <Col span={16} style={{ backgroundColor: '' }}>
            <Row>
              <Col style={{ fontWeight: 500, fontSize: 18 }}>
                <FormattedMessage id="reason" defaultMessage="Reason" />
              </Col>
            </Row>
          </Col>
        </Row>
        <Row style={{ marginBottom: 20, marginLeft: 2, color: 'gray', width: '100%' }}>
          <Col span={20}>
            <Row>
              <Col>{reasonModel}</Col>
            </Row>
          </Col>
        </Row>
        {
            props.shiftChangeRequestData.workflowInstanceId ? (
                <ApprovalLevelDetails workflowInstanceId= {props.shiftChangeRequestData.workflowInstanceId} setApproverComment={props.setApproverComment} actions={props.actions} scope ={props.scope} ></ApprovalLevelDetails>
            ) : (
                <></>
            )
        }
      </Row>
    </>
  );
};

export default ShiftChangeRequest;
