import React, { useEffect, useState } from 'react';
import { Image ,Col, Row, List, DatePicker, TimePicker, Space, Input, Avatar, message, Spin } from 'antd';
import { FormattedMessage } from 'react-intl';
import TextArea from 'antd/lib/input/TextArea';
import request, { APIResponse } from "@/utils/request";
import { getModel, ModelType } from "@/services/model";
import { getEmployeeCurrentDetails } from '@/services/employee';

import {  DownloadOutlined, PaperClipOutlined } from '@ant-design/icons';
import './index.less';
import moment from 'moment';
import BreakTotalIcon from '../../assets/attendance/Break-01.svg';
import ApprovalLevelDetails from './approvalLevelDetails';

type TimeChangeRequestProps = {
    timeChangeRequestData: any,
    employeeFullName: any,
    scope: any,
    employeeId: any,
    actions?:any,
    setApproverComment: any,
    
};

const TimeChangeRequest: React.FC<TimeChangeRequestProps> = (props) => {
    // const [attachementSet, setAttachementSet] = useState<any>(props.attachementList);
    const [inTimeModel, setInTimeModel] = useState<string | null>( null);
    const [inDateModel, setInDateModel] = useState<string | null>( null);
    const [outDateModel, setOutDateModel] = useState<string | null>( null);
    const [outTimeModel, setOutTimeModel] = useState<string | null>( null);
    const [reasonModel, setReasonModel] = useState('');
    const [imageUrl, setimageUrl] = useState<string | null>(null);
    const [timeChangeRequestId, setTimeChangeRequestId] = useState<string | null>(null);
    const [jobTitle, setJobTitle] = useState<string | null>(null);
    const [breakDetails, setBreakDetails] = useState<any | null>([]);
    const [approvalLevelList, setApprovalLevelList] = useState<any>([]);
    const [isGettingLevels, setIsGettingLevels] = useState<boolean>(false);

    useEffect(() => {

        const inDateRecord = props.timeChangeRequestData.inDateTime ? moment(props.timeChangeRequestData.inDateTime).format('YYYY-MM-DD') : '-'
        const outDateRecord = props.timeChangeRequestData.outDateTime ? moment(props.timeChangeRequestData.outDateTime).format('YYYY-MM-DD') : '-';
        const inTimeRecord = props.timeChangeRequestData.inDateTime ? moment(props.timeChangeRequestData.inDateTime).format("hh:mm A") : '-';
        const outTimeRecord = props.timeChangeRequestData.outDateTime ?  moment(props.timeChangeRequestData.outDateTime).format("hh:mm A") : '-';

        setInDateModel(inDateRecord);
        setOutDateModel(outDateRecord);
        setInTimeModel(inTimeRecord);
        setOutTimeModel(outTimeRecord);

        if (props.timeChangeRequestData.reason == null || props.timeChangeRequestData.reason == undefined) {

            setReasonModel('_');
        } else {
            setReasonModel(props.timeChangeRequestData.reason);
        }
        setTimeChangeRequestId(props.timeChangeRequestData.id)
            
    });

    useEffect(() => {
        if (props.timeChangeRequestData.id != undefined) {
            const breaks = (props.timeChangeRequestData.breakDetails != undefined) ?  props.timeChangeRequestData.breakDetails : [];
            setBreakDetails(breaks);
            getEmployeeProfileImage();
            getEmployeeRelateDataSet();
        }
    }, [timeChangeRequestId]);

    const getEmployeeProfileImage = async () => {
        try {
            const actions: any = []
            const response = await getModel('employee')
            let path: string

            if (!_.isEmpty(response.data)) {
                path = `/api${response.data.modelDataDefinition.path}/`+props.employeeId+`/profilePicture`;
                const result = await request(path);
                if (result['data'] !== null) {
                  setimageUrl(result['data']['data']);
                }
            }
        } catch (error) {
            console.log(error);
        }
        
    }

    const getEmployeeRelateDataSet = () => {
        try {
            getEmployeeCurrentDetails(props.employeeId).then((res)=> {
                if (res.data) {                
                    let jobTitle = (res.data.jobTitle != null) ? res.data.jobTitle : '-'
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
    }



    return (
        <>
            <Row style={{ width: '100%', marginLeft: 20}}>
                {
                    (props.scope != 'EMPLOYEE') ? 
                    <>
                        <Row style={{ marginBottom: 30, marginTop: 30, width: '100%' }} >
                            <Col span={16} style={{ backgroundColor: '' }}>
                                <Row>
                                    <Col >
                                        {imageUrl ? (
                                            <Avatar style={{ fontSize: 22, border: 1}} src={imageUrl}  size={55} />
                                        ) : (
                                            <Avatar
                                                style={{ backgroundColor: 'blue', fontSize: 18 }}
                                                size={55}>
                                                { (props.employeeFullName != null) ?   props.employeeFullName.split(' ').map(x => x[0]).join('') : ''}
                                            </Avatar>
                                        )} 
                                    </Col>
                                    <Col style={{paddingLeft: 10 }}>
                                        <Row style={{fontWeight : 500, fontSize:20,color:"#394241"}}>{props.employeeFullName}</Row>
                                        <Row style={{fontWeight : 400, fontSize:16,color:"#626D6C" ,paddingTop:0}}>{jobTitle}</Row>
                                    </Col>
                                </Row>
                            </Col>
                        </Row>
                    </> : (<></>)

                }
                <Row style={{ marginBottom: 20 ,  width: '100%'}}>
                    <Col span={16} style={{ backgroundColor: '' }}>
                        <Row><Col style={{fontWeight: 'bold'}}><FormattedMessage id="TimeRecordDetails" defaultMessage="Time Record Details" /></Col></Row>
                    </Col>
                </Row>
                <Row style={{ marginBottom: 20, marginLeft: 2, color: 'gray', width: '100%' }}>
                    <Col span={3} style={{ paddingLeft: 10 }}>
                        <Row><Col>{'In Date'}</Col></Row>
                    </Col>
                    <Col span={1} style={{ backgroundColor: '' }}>
                        <Row><Col>{':'}</Col></Row>
                    </Col>
                    <Col span={5} style={{ paddingLeft: 10 }}>
                        <Row><Col>{moment(inDateModel,"YYYY-MM-DD").isValid() ? moment(inDateModel).format("DD-MM-YYYY") : null}</Col></Row>
                    </Col>
                    <Col span={3} >
                        <Row><Col>{'Clock In'}</Col></Row>
                    </Col>
                    <Col span={1} style={{ backgroundColor: '' }}>
                        <Row><Col>{':'}</Col></Row>
                    </Col>
                    <Col span={5}>
                        <Row><Col>{inTimeModel}</Col></Row>
                    </Col>
                </Row>
                {
                    (breakDetails.length> 0) ? (
                        <Row style={{ marginBottom: 20, marginLeft: 2, color: 'gray', width: '100%' }}>
                            <Col span={3} style={{ paddingLeft: 10 }}>
                                <Row><Col>{'Breaks'}</Col></Row>
                            </Col>
                            <Col span={17} style={{ paddingLeft: 10 , width: 250, backgroundColor: '#f1f3f6', height: (breakDetails.length < 4) ? 'auto' : 170, overflowY: 'auto'}}>
                                <Row style={{paddingTop: 10, paddingBottom: 15, color: 'black', fontWeight: 'bold', fontSize: 12, paddingLeft: 20}}>
                                    <Col span={12}>Break Start</Col>
                                    <Col span={12} style={{paddingLeft: 15}}>Break End</Col>
                                </Row>
                                {
                                    breakDetails.map((el:object, dayTypeIndex:any) => {

                                        let breakInDate = moment(el.breakInDateTime).format('YYYY-MM-DD');
                                        let breakInTime = moment(el.breakInDateTime).format("hh:mm A");
                                        let breakOutDate = moment(el.breakOutDateTime).format('YYYY-MM-DD');
                                        let breakOutTime = moment(el.breakOutDateTime).format("hh:mm A");
                                        return (
                                            <Row style={{paddingLeft: 20}}>
                                                <Col span={2}><Image src={BreakTotalIcon} style={{ width: 20, height: 25, paddingBottom: 5}} preview={false} /></Col>
                                                <Col span={10}>{moment(breakInDate).format("DD-MM-YYYY")} <span style={{fontWeight: 'bold'}}>{breakInTime}</span></Col>
                                                <Col span={1} style={{paddingLeft: 5}}>-</Col>
                                                <Col span={11}>{moment(breakOutDate).format("DD-MM-YYYY")}  <span style={{fontWeight: 'bold'}}>{breakOutTime}</span></Col>
                                            </Row>  
                                        )
                                    })
                                }
                            </Col>
                            
                        </Row>
                    ) : (
                        <></>
                    )
                }
               
                <Row style={{ marginBottom: 20, marginLeft: 2, color: 'gray', width: '100%' }}>
                    <Col span={3} style={{ paddingLeft: 10 }}>
                        <Row><Col>{'Out Date'}</Col></Row>
                    </Col>
                    <Col span={1} style={{ backgroundColor: '' }}>
                        <Row><Col>{':'}</Col></Row>
                    </Col>
                    <Col span={5} style={{ paddingLeft: 10 }}>
                        <Row><Col>{moment(outDateModel,"YYYY-MM-DD").isValid() ? moment(outDateModel).format("DD-MM-YYYY") : null}</Col></Row>
                    </Col>
                    <Col span={3} >
                        <Row><Col>{'Clock Out'}</Col></Row>
                    </Col>
                    <Col span={1} style={{ backgroundColor: '' }}>
                        <Row><Col>{':'}</Col></Row>
                    </Col>
                    <Col span={5}>
                        <Row><Col>{outTimeModel}</Col></Row>
                    </Col>
                </Row>
                <Row style={{ marginBottom: 5,  width: '100%' }}>
                    <Col span={16} style={{ backgroundColor: '' }}>
                        <Row><Col style={{fontWeight: 'bold'}}><FormattedMessage id="reason" defaultMessage="Reason" /></Col></Row>
                    </Col>
                </Row>
                <Row style={{ marginBottom: 20, marginLeft: 2, color: 'gray', width: '100%' }}>
                    <Col span={20} style={{ paddingLeft: 10 }}>
                        <Row><Col>{reasonModel}</Col></Row>
                    </Col>
                </Row>

                {
                    props.timeChangeRequestData.workflowInstanceId ? (
                        <ApprovalLevelDetails workflowInstanceId= {props.timeChangeRequestData.workflowInstanceId}  setApproverComment={props.setApproverComment} actions={props.actions} scope ={props.scope}></ApprovalLevelDetails>
                    ) : (
                        <></>
                    )
                }
            </Row>
        </>
    );
};

export default TimeChangeRequest;
