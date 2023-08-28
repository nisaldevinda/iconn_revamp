import React, { useEffect, useState } from 'react';
import moment from 'moment';
import { Image, Row, Col, Card, Collapse, Timeline, Spin } from 'antd';
import { ClockCircleOutlined, PoweroffOutlined } from '@ant-design/icons';
import { FormattedMessage } from 'umi';

import { getAttendanceSummaryData, getOthersAttendanceSummaryData } from '@/services/attendance';
import '../../components/Attendance/summaryPanelStyle.css';

import ClockInIcon from '../../assets/attendance/ClockIn.svg';
import ClockOutIcon from '../../assets/attendance/Clockout.svg';
import BreakTotalIcon from '../../assets/attendance/Break-01.svg';
import UnLikeIcon from '../../assets/attendance/LateIn-EarlyDeparture.svg';
import ShiftIcon from '../../assets/attendance/Shift.svg';
import ClockIcon from '../../assets/attendance/clock-icon-teal.svg';

const { Panel } = Collapse;

export type SummaryViewProps = {
    datePassed: string,
    employeeId?: number,
    viewType: string,
};

const SummaryView: React.FC<SummaryViewProps> = (props) => {

    const dateForSummary = new Date(props.datePassed).toLocaleString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });

    const [loading, setLoading] = useState(true);
    const [summaryData, setSummaryData] = useState([]);
    const [employeeName, setEmployeeName] = useState('');
    const [summaryDate, setSummaryDate] = useState(dateForSummary);
    const [shiftsData, setShiftsData] = useState([]);
    const [workedHours, setWorkedHours] = useState('00:00:00');
    const [breakHours, setBreakHours] = useState('00:00:00');
    const [inTime, setInTime] = useState('00:00 AM');
    const [outTime, setOutTime] = useState('00:00 PM');

    useEffect(() => {
        setLoading(true);
        callAttendanceSummaryData();
    }, []);

    async function callAttendanceSummaryData() {
        const params = {
            date: props.datePassed,
			employeeId: props.employeeId,
			scope: "EMPLOYEE"
		};

        if (props.viewType === 'managerView') {
			params.scope = "MANAGER";
			await getOthersAttendanceSummaryData(params).then((response: any) => {
				if (response) {
					setResponceData(response);
				}
			});
        } else if (props.viewType === 'adminView') {
			params.scope = 'ADMIN';
			await getOthersAttendanceSummaryData(params).then((response: any) => {
				if (response) {
					setResponceData(response);
				}
			});
        } else if (props.viewType === 'myView') {
			await getAttendanceSummaryData(params).then((response: any) => {
				if (response) {
					setResponceData(response);
				}
			});
        }
    }

    function setResponceData(response: any) {
        const dateForSummary = new Date(response.data.summaryDate).toLocaleString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });

        setSummaryData(response.data);
        setShiftsData(response.data.shiftRecords);
        setInTime(response.data.shiftsInTime);
        setOutTime(response.data.shiftsOutTime);
        setWorkedHours(response.data.totalWorkedTime);
        setBreakHours(response.data.totalBreakTime);
        setEmployeeName(response.data.employeeName);
        setSummaryDate(dateForSummary);
        setLoading(false);
    }

    function showTimeWithoutSeconds(timeString: string) {
        const timeObj = moment(timeString, 'HH:mm:ss');
        return timeObj.isValid() ? timeObj.format('HH:mm') : timeString;
    }

    return (
        <>
            <Row>
                <Col>
                    <Spin size="large" spinning={loading} style={{ height: '100%', width: '100%', }} />
                </Col>
            </Row>
            {!loading ?
                <Row style={{ height: '100%', width: '100%', }}>
                    <Col style={{ width: '49.5%', flex: '0 1 auto', height: '100%' }}>
                        <Card style={{ width: '100%', height: '100%' }}>
                            <div style={{ height: '50%', width: '100%', }}>
                                <Row><Col span={24} style={{ fontSize: 22, fontWeight: 'bold' }}>{employeeName}</Col></Row>
                                <Row><Col span={24} style={{ fontSize: 18, fontWeight: 'bold' }}>{summaryDate}</Col></Row>
                                <Row><Col span={24} style={{ fontSize: 12 }}><FormattedMessage id="Attendance_Summary" defaultMessage="Attendance Summary" /></Col></Row>

                                <Row style={{ paddingTop: 25 }}>
                                    <Col style={{ width: 40 }}>
                                        <Image src={ClockIcon} style={{ width: 24, height: 40, }} preview={false} />
                                    </Col>
                                    <Col span={22}>
                                        <Row>
                                            <Col style={{ fontSize: 21, color: '#56b1a6' }}>{showTimeWithoutSeconds(workedHours)}</Col>
                                        </Row>
                                        <Row>
                                            <Col style={{ fontSize: 14, }}><FormattedMessage id="Total_Hours_Worked" defaultMessage="Total Hours Worked" /></Col>
                                        </Row>
                                    </Col>
                                </Row>

                                <Row style={{ paddingTop: 25, width: '100%', }}>
                                    <Col style={{ width: 40 }}>
                                        <Image src={ClockInIcon} style={{ width: 30, height: 40, }} preview={false} />
                                    </Col>
                                    <Col span={6} >
                                        <Row>
                                            <Col style={{ fontSize: 18, fontWeight: 'bold' }}>{inTime ? new Date(inTime).toLocaleString('en-US', { hour: '2-digit', minute: 'numeric', hour12: true }) : '-'}</Col>
                                        </Row>
                                        <Row>
                                            <Col style={{ fontSize: 14, }}><FormattedMessage id="Work_Started" defaultMessage="Work Started" /></Col>
                                        </Row>
                                    </Col>

                                    <Col style={{ width: 40, }}>
                                        <Image src={BreakTotalIcon} style={{ width: 35, height: 40, }} preview={false} />
                                    </Col>
                                    <Col span={6} style={{}}>
                                        <Row>
                                            <Col style={{ fontSize: 18, fontWeight: 'bold' }}>{showTimeWithoutSeconds(breakHours)}</Col>
                                        </Row>
                                        <Row>
                                            <Col style={{ fontSize: 14, }}><FormattedMessage id="Total_Break_Time" defaultMessage="Total Break Time" /></Col>
                                        </Row>
                                    </Col>

                                    <Col style={{ width: 40, }}>
                                        <Image src={ClockOutIcon} style={{ width: 30, height: 40, }} preview={false} />
                                    </Col>
                                    <Col span={6} style={{}}>
                                        <Row>
                                            <Col style={{ fontSize: 18, fontWeight: 'bold' }}>{outTime ? new Date(outTime).toLocaleString('en-US', { hour: '2-digit', minute: 'numeric', hour12: true }) : '-'}</Col>
                                        </Row>
                                        <Row>
                                            <Col style={{ fontSize: 14, }}><FormattedMessage id="Work_Ended" defaultMessage="Work Ended" /></Col>
                                        </Row>
                                    </Col>
                                </Row>
                            </div>

                            <div style={{ paddingTop: 30 }} >
                                <hr />
                            </div>

                            <div style={{ height: '50%', width: '100%', }}>
                                {shiftsData ? shiftsData.map((shift: any, index) => {
                                    return (
                                        <Row style={{ paddingTop: 25, width: '100%', }}>
                                            <Col style={{ width: 40 }}>
                                                <Image src={ShiftIcon} style={{ width: 30, height: 40, }} preview={false} color='#60b6ab' />
                                            </Col>
                                            <Col span={22} >
                                                <Row>
                                                    <Col style={{ fontSize: 15, fontWeight: 'bold' }}>{shift.shiftName}</Col>
                                                </Row>
                                                <Row>
                                                    <Col>
                                                        <Row>
                                                            <Col>
                                                                <Row>
                                                                    <Col style={{ display:'flex' }}>
                                                                        <Image src={UnLikeIcon} style={{ width: 20, height: 20 }} preview={false} />           
                                                                        <Row style={{marginLeft:8, marginBottom:9 }}>
                                                                            <Col>
                                                                            <FormattedMessage id="Late_In" defaultMessage="Late In" />
                                                                            <br/>
                                                                            {showTimeWithoutSeconds(shift.lateTime)}     
                                                                            </Col>  
                                                                        </Row>
                                                                    </Col>
                                                                    <Col>
                                                                        
                                                                    </Col>
                                                                </Row>
                                                            </Col>
                                                            <Col></Col>
                                                        </Row>
                                                    </Col>
                                                </Row>
                                            </Col>
                                        </Row>
                                    );
                                }) : null}
                            </div>
                        </Card>
                    </Col>

                    <Col style={{ width: '1%', }}>
                    </Col>

                    <Col style={{ width: '49.5%', }}>
                        <Card style={{ width: '100%', height: '100%' }}>
                            <Row><Col style={{ fontSize: 16, fontWeight: 'bold', paddingBottom: 25 }}><FormattedMessage id="Attendance_Records" defaultMessage="Attendance Records" /></Col></Row>

                            <Collapse
                                defaultActiveKey={['0']}
                                ghost
                                bordered={false}
                                className="site-collapse-custom-collapse"
                            >
                                {shiftsData ? shiftsData.map((shift: any, index) => {
                                    return (
                                        <Panel
                                            className={'attendanceSummaryPanel'}
                                            header={shift.shiftName}
                                            key={index}
                                            style={{ backgroundColor: 'white', fontSize: 14, fontWeight: 'bold' }}
                                            extra={'Total Spent ' + showTimeWithoutSeconds(shift.workedTime)}
                                        >

                                            {shift.attendanceRecords ? shift.attendanceRecords.map((attend: any) => {
                                                const clockedInTime = new Date(attend.clockIn).toLocaleString('en-US', { hour: '2-digit', minute: 'numeric', hour12: true });
                                                const clockedOutTime = attend.clockOut ? new Date(attend.clockOut).toLocaleString('en-US', { hour: '2-digit', minute: 'numeric', hour12: true }) : '-';
                                                return (
                                                    <Timeline style={{ fontSize: 11, fontWeight: 'normal', paddingBottom: 0 }}>
                                                        <Timeline.Item dot={<ClockCircleOutlined />} color="green" style={{ paddingBottom: attend.breakRecords.length > 0 ? 0 : 25, }}>
                                                            <FormattedMessage id="Clocked_In" defaultMessage="Clocked In" /> {clockedInTime}

                                                            {attend.breakRecords.length > 0 ?
                                                                attend.breakRecords.map((breakRecord: any) => {
                                                                    const breakInTime = new Date(breakRecord.breakIn).toLocaleString('en-US', { hour: '2-digit', minute: 'numeric', hour12: true });
                                                                    const breakOutTime = breakRecord.breakOut ? new Date(breakRecord.breakOut).toLocaleString('en-US', { hour: '2-digit', minute: 'numeric', hour12: true }) : '-';
                                                                    return (
                                                                        <Timeline style={{ paddingLeft: 20, paddingTop: 25, paddingBottom: 0, }}>
                                                                            <Timeline.Item dot={<Image src={BreakTotalIcon} style={{ width: 20, height: 20, }} preview={false} />} color="red" >
                                                                                <Row>
                                                                                    <Col style={{ width: '60%' }}><FormattedMessage id="Break_Start" defaultMessage="Break Start" /> {breakInTime}</Col>
                                                                                    <Col><FormattedMessage id="Spent" defaultMessage="Spent" /> {showTimeWithoutSeconds(breakRecord.breakTime)}</Col>
                                                                                </Row>
                                                                            </Timeline.Item>
                                                                            <Timeline.Item color="green" style={{ height: 30, paddingBottom: 0, }}>
                                                                                <FormattedMessage id="Break_End" defaultMessage="Break End" /> {breakOutTime}
                                                                            </Timeline.Item>
                                                                        </Timeline>
                                                                    );
                                                                })
                                                                : null}
                                                        </Timeline.Item>

                                                        <Timeline.Item dot={<PoweroffOutlined />} color="red" style={{ paddingBottom: 0 }}>
                                                            <FormattedMessage id="Clocked_Out" defaultMessage="Clocked Out" /> {clockedOutTime}
                                                        </Timeline.Item>
                                                    </Timeline>
                                                );
                                            }) : null}

                                        </Panel>
                                    );
                                }) : <></>}
                            </Collapse>
                        </Card>
                    </Col>
                </Row>
                : <></>}
        </>
    );
};

export default SummaryView;
