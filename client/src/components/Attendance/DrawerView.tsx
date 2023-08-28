import React, { useEffect, useState } from 'react';
import { Button, Tooltip, message, Row, Col, Image } from 'antd';
import ProForm, {
    ProFormText,
    DrawerForm,
} from '@ant-design/pro-form';
import moment from 'moment';
var momentTZ = require('moment-timezone');
import { history, Link } from 'umi';
import './styles.css';

import PlayIcon from '../../assets/attendance/Play.svg';
import PauseIcon from '../../assets/attendance/Pause.svg';
import HandIcon from '../../assets/attendance/Hand.svg';
import BreakIcon from '../../assets/attendance/Break-02.svg';

import ClockInIcon from '../../assets/attendance/ClockIn.svg';
import ClockOutIcon from '../../assets/attendance/Clockout.svg';
import BreakTotalIcon from '../../assets/attendance/Break-01.svg';

import UnLikeIcon from '../../assets/attendance/LateIn-EarlyDeparture.svg';
import ShiftIcon from '../../assets/attendance/Shift.svg';
import ClockIcon from '../../assets/attendance/Clock.svg';
import BreaksCountIcon from '../../assets/attendance/Break-03.svg';
import hourGlassIcon from '../../assets/attendance/hourglass.gif';
import { getAttendance, manageAttendance, manageBreak } from '@/services/attendance';


export type DrawerViewProps = {
    visibility: boolean,
    setDrawerVisibility: (values: any) => void,
};

const DrawerView: React.FC<DrawerViewProps> = (props) => {
    const [punchedStatus, setPunchedStatus] = useState('CLOCK IN');
    const [employeeZone, setEmployeeZone] = useState('Asia/Colombo');
    const [clockInTime, setClockInTime] = useState<string | null>(null);
    const [currentClockInTime, setCurrentClockInTime] = useState<string | null>(null);
    const [clockOutTime, setClockOutTime] = useState<string | null>(null);
    const [earlyDepartureTime, setEarlyDepartureTime] = useState('00:00');
    const [lateInTime, setLateInTime] = useState('00:00');
    const [totalWorkedTime, setTotalWorked] = useState('00:00');
    const [shift, setShift] = useState('');
    const [breakInTime, setBreakInTime] = useState('00:00');
    const [totalBreakTime, setTotalBreakTime] = useState('00:00:00');
    const [currentTotalBreakTime, setCurrentTotalBreakTime] = useState('00:00:00');
    const [recentBreakTime, setRecentBreakTime] = useState('00:00:00');

    const [loggedTime, setLoggedTime] = useState('00:00');
    const [loggedBreakTime, setLoggedBreakTime] = useState("0");
    const [currentTime, setCurrentTime] = useState("0");
    const [currentDate, setCurrentDate] = useState("0");
    const [summaryDate, setSummaryDate] = useState("");
    const [intervalLoggedTime, setIntervalLoggedTime] = useState(0);
    const [intervalCurrentTime, setIntervalCurrentTime] = useState(0);
    const [getCalledAPI, setCalledAPI] = useState(false);


    useEffect(() => {
        startCounting();
        // checkAttendance();
    }, []);

    useEffect(() => {
        if (props.visibility)
            checkAttendance();
    }, [props.visibility]);

    useEffect(() => {
        if (punchedStatus === 'CLOCK OUT') {
            const text = calculateRealtime(currentClockInTime, false);
            setLoggedTime(text);
        } else if (punchedStatus === 'BREAK OUT') {
            const text = calculateRealtime(breakInTime, true);
            setLoggedBreakTime(text);
        } else {
            setLoggedTime('00:00:00');
            setLoggedBreakTime('00:00:00');
        }
    }, [intervalLoggedTime]);

    useEffect(() => {
        const now = new Date();
        const time = now.toLocaleString('en-US', { hour: '2-digit', hour12: true, minute: '2-digit', timeZone: employeeZone });
        const date = now.toLocaleString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric', timeZone: employeeZone });
        const dateForSummary = moment(date).format('YYYY-MM-DD')
        setCurrentTime(time);
        setCurrentDate(date);
        // setSummaryDate(dateForSummary);
    }, [intervalCurrentTime]);

    function countUpWorkedTime() {
        setIntervalLoggedTime(Math.random());
    }
    function countUpCurrentTime() {
        setIntervalCurrentTime(Math.random());
    }
    function startCounting() {
        setInterval(countUpWorkedTime, 1000);
        setInterval(countUpCurrentTime, 1000);
    }
    async function checkAttendance() {
        setCalledAPI(true);
        await getAttendance().then((response) => {
            if (response) {
                setPunchedStatus(response.data.status);
                setEmployeeZone(response.data.zone);
                setClockInTime(response.data.clockIn);
                setCurrentClockInTime(response.data.currentClockIn);
                setClockOutTime(response.data.clockOut);
                setEarlyDepartureTime(response.data.early);
                setLateInTime(response.data.late);
                setSummaryDate(response.data.date);
                setTotalWorked(response.data.workedHours);
                setShift(response.data.shift === null ? '-' : response.data.shift);
                setTotalBreakTime(response.data.totalBreak);
                setCurrentTotalBreakTime(response.data.currentTotalBreak);
                setRecentBreakTime(response.data.recentBreakTime);
                setBreakInTime(response.data.breakInTime);
            }
        }).then((resData: any) => {
            setCalledAPI(false);
        }).catch(() => {
            setCalledAPI(false);
        });
    }

    function showTimeWithoutSeconds(timeString: string) {
        const timeObj = moment(timeString, 'HH:mm:ss');
        return timeObj.isValid() ? timeObj.format('HH:mm') : timeString;
    }

    function calculateRealtime(receivedDateTime: string, inBreak: boolean): string {
        const now = new Date();
        const dateTime = momentTZ(now).tz(employeeZone).format();
        const nowTime = moment(dateTime);
        const punchedTime = moment(receivedDateTime); //db today logged
        const nowMil = nowTime.valueOf();
        const gotMil = punchedTime.valueOf();
        // const breakTime = totalBreakTime ? ((parseInt(totalBreakTime.split(':')[0]) * 60 * 60) + (parseInt(totalBreakTime.split(':')[1]) * 60) + parseInt(totalBreakTime.split(':')[2])) : 0;
        const breakTime = moment.duration(currentTotalBreakTime).asSeconds();

        if (inBreak) {
            const workedTimeBeforeBreak = gotMil - moment(currentClockInTime).valueOf() - (breakTime * 1000);
            const workedTimeBeforeBreakMils = workedTimeBeforeBreak > 0 ? workedTimeBeforeBreak : 0
            const hours = workedTimeBeforeBreakMils / (60 * 60 * 1000);
            const mins = (hours - Math.floor(hours)) * 60;
            const secs = (mins - Math.floor(mins)) * 60;
            const text =
                (hours < 10 ? ("0" + Math.floor(hours)) : Math.floor(hours)) + ":" +
                (mins < 10 ? ("0" + Math.floor(mins)) : Math.floor(mins)) + ":" +
                (secs < 10 ? ("0" + Math.floor(secs)) : Math.floor(secs));
            setLoggedTime(text);
        }

        const diffMil = nowMil - gotMil;
        const inBreakMil = !inBreak ? (breakTime * 1000) : 0;
        const diffMilBreak = diffMil - inBreakMil > 0
        const different = diffMil > 0 && diffMilBreak ? (diffMil - inBreakMil) : 0;// in milliseconds
        const hours = different / (60 * 60 * 1000);
        const mins = (hours - Math.floor(hours)) * 60;
        const secs = (mins - Math.floor(mins)) * 60;

        const text =
            (hours < 10 ? ("0" + Math.floor(hours)) : Math.floor(hours)) + ":" +
            (mins < 10 ? ("0" + Math.floor(mins)) : Math.floor(mins)) + ":" +
            (secs < 10 ? ("0" + Math.floor(secs)) : Math.floor(secs));

        return text;
    }

    const reFreshDrawer = (response: any) => {
        setPunchedStatus(response.status);
        setEmployeeZone(response.zone);
        setClockInTime(response.clockIn);
        setCurrentClockInTime(response.currentClockIn);
        setClockOutTime(response.clockOut);
        setEarlyDepartureTime(response.early);
        setLateInTime(response.late);
        setTotalWorked(response.workedHours);
        setShift(response.shift === null ? '-' : response.shift);
        setTotalBreakTime(response.totalBreak);
        setCurrentTotalBreakTime(response.data.currentTotalBreak);
        setRecentBreakTime(response.recentBreakTime);
        setBreakInTime(response.breakInTime);
    }

    let circleColor;
    let playWorkIcon;
    let circleIcon;

    const addViewProps = {
        submitter: {
            searchConfig: {
                submitText: '',
                resetText: ''
            },
        },
    };

    switch (punchedStatus) {
        case 'CLOCK IN':
            circleColor = '#86C129';
            playWorkIcon = PlayIcon;
            circleIcon = <Image src={HandIcon} style={{ width: 50, height: 65 }} preview={false} />
            break;
        case 'CLOCK OUT':
            circleColor = '#E22E2E';
            playWorkIcon = PauseIcon;
            circleIcon = <Image src={HandIcon} style={{ width: 50, height: 65 }} preview={false} />
            break;
        case 'BREAK OUT':
            circleColor = '#FFB039';
            playWorkIcon = PlayIcon;
            circleIcon = <Image src={BreakIcon} style={{ width: 55, height: 65 }} preview={false} />
            break;
        default:
            circleColor = 'green';
            playWorkIcon = PlayIcon;
            // circleIcon = <MinusOutlined style={styleIcon} />;
            break;
    }

    return (
        <>
            <DrawerForm
                className={'attendanceClockDrawer'}
                title="Punch"
                width="23vw"
                visible={props.visibility}
                onVisibleChange={props.setDrawerVisibility}
                onFinish={async () => {
                    message.success('提交成功');
                    return false;
                }}
                {...addViewProps}
            >
                <table style={{ width: '100%', backgroundColor: '' }}>
                    <tbody style={{ textAlign: 'center', alignContent: 'center' }}>
                        <tr>
                            <td style={{ fontWeight: 'normal', fontSize: 45, color: '#da5258' }}>
                                {loggedTime}
                            </td>
                        </tr>
                        <tr>
                            <td valign='top' style={{ backgroundColor: '' }}>
                                <table style={{ margin: 'auto', }}>
                                    <tbody style={{ textAlign: 'center', alignContent: 'center' }}>
                                        <tr>
                                            <td style={{}}>
                                                <Image src={playWorkIcon} preview={false} style={{ height: 25 }} />
                                            </td>
                                            <td valign='top'>
                                                <span style={{ fontWeight: 'normal', fontSize: 18, color: '#da5258', paddingLeft: 10, }}>
                                                    Work Hours
                                                </span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </td>
                        </tr>
                        {punchedStatus !== 'CLOCK OUT' && !getCalledAPI ?
                            <tr><td style={{ height: 32.5 }}></td></tr> : <></>}
                        {!getCalledAPI ? <>
                            <tr>
                                <td style={{ paddingTop: 15, backgroundColor: '', }}>
                                    <Tooltip title={"Click to " + punchedStatus.toLocaleLowerCase()}>
                                        <Button
                                            style={{ borderRadius: '50%', width: 130, height: 130, backgroundColor: circleColor, margin: 'auto' }}
                                            onClick={async () => {
                                                setCalledAPI(true);
                                                const date = moment(currentDate).format('YYYY-MM-DD');
                                                const now = new Date();
                                                const dateTime = momentTZ(now).tz(employeeZone).format();

                                                const attendanceData = {
                                                    status: punchedStatus,
                                                    shiftDate: date,
                                                    punchedTime: dateTime,
                                                }

                                                if (punchedStatus === 'CLOCK IN' || punchedStatus === 'CLOCK OUT') {
                                                    await manageAttendance(attendanceData).then((response) => {
                                                        setCalledAPI(false);
                                                        if (response.data.resetStatus === 'RESET') {
                                                            reFreshDrawer(response.data);
                                                        } else if (response.data.status === 'CLOCK OUT') {
                                                            setPunchedStatus(response.data.status);
                                                            setClockInTime(response.data.clockIn);
                                                            setCurrentClockInTime(response.data.currentClockIn);
                                                            setClockOutTime(response.data.clockOut);
                                                            setEarlyDepartureTime(response.data.early);
                                                            setLateInTime(response.data.late);
                                                        } else if (response.data.status === 'CLOCK IN') {
                                                            setPunchedStatus(response.data.status);
                                                            setClockOutTime(response.data.clockOut);
                                                            setEarlyDepartureTime(response.data.early);
                                                            setTotalWorked(response.data.workedHours);
                                                            setTotalBreakTime(response.data.totalBreak);
                                                            setCurrentTotalBreakTime("00:00:00");
                                                            setRecentBreakTime(response.data.recentBreakTime);
                                                        }
                                                    }).catch(() => {
                                                        setCalledAPI(false);
                                                    });
                                                }
                                                else if (punchedStatus === 'BREAK OUT') {
                                                    await manageBreak(attendanceData).then((response) => {
                                                        setCalledAPI(false);
                                                        if (response.data.resetStatus === 'RESET') {
                                                            reFreshDrawer(response.data);
                                                        } else {
                                                            setPunchedStatus(response.data.status);
                                                            // setTotalWorked(response.data.workedHours);
                                                            setTotalBreakTime(response.data.totalBreak);
                                                            setCurrentTotalBreakTime(response.data.currentTotalBreak);
                                                            setRecentBreakTime(response.data.recentBreakTime);
                                                        }
                                                    }).catch(() => {
                                                        setCalledAPI(false);
                                                    });
                                                }
                                            }}
                                        >
                                            <table style={{ width: '100%', }}>
                                                <tbody>
                                                    <tr>
                                                        <td valign='middle'>{circleIcon}</td>
                                                    </tr>
                                                    <tr>
                                                        <td style={{ fontSize: 15, fontWeight: 'bold', color: 'white' }}>
                                                            {punchedStatus}
                                                        </td>
                                                    </tr>
                                                    {punchedStatus === "BREAK OUT" ? <tr>
                                                        <td style={{ color: 'white' }}>
                                                            {loggedBreakTime}
                                                        </td>
                                                    </tr> : <></>}
                                                </tbody>
                                            </table>
                                        </Button>
                                    </Tooltip>
                                </td>
                            </tr></>
                            : <></>}
                        {punchedStatus === 'CLOCK OUT' && !getCalledAPI ? <tr>
                            <td style={{ paddingTop: 15, backgroundColor: '', }}>
                                <Tooltip title={"Click to " + punchedStatus.toLocaleLowerCase()}>
                                    <Button
                                        style={{ borderRadius: '40px', width: 120, height: 50, backgroundColor: '#FFB039', margin: 'auto' }}
                                        onClick={async () => {
                                            setCalledAPI(true);
                                            const date = moment(currentDate).format('YYYY-MM-DD');
                                            const now = new Date();
                                            const dateTime = momentTZ(now).tz(employeeZone).format();
                                            const breakData = {
                                                status: punchedStatus,
                                                shiftDate: date,
                                                punchedTime: dateTime,
                                            }
                                            await manageBreak(breakData).then((response) => {
                                                setCalledAPI(false);

                                                if (response.data.resetStatus === 'RESET') {
                                                    reFreshDrawer(response.data);
                                                } else {
                                                    setLoggedBreakTime('00:00:00');
                                                    setPunchedStatus(response.data.status);
                                                    // setTotalWorked(response.data.workedHours);
                                                    setBreakInTime(response.data.breakInTime);
                                                    if (response.data.status === 'CLOCK OUT') {
                                                        setTotalBreakTime(response.data.totalBreak);
                                                        setCurrentTotalBreakTime(response.data.currentTotalBreak);
                                                        setRecentBreakTime(response.data.recentBreakTime);
                                                    }
                                                }
                                            }).catch(() => {
                                                setCalledAPI(false);
                                            });
                                        }}
                                    >
                                        <table style={{ width: '100%', }}>
                                            <tbody>
                                                <tr>
                                                    <td valign='middle'>
                                                        <Image src={BreakIcon} style={{ width: 25, height: 30 }} preview={false} />
                                                    </td>
                                                    <td style={{ fontSize: 15, fontWeight: 'bold', color: 'white' }}>
                                                        {"BREAK"}
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </Button>
                                </Tooltip>
                            </td>
                        </tr>
                            : <tr><td style={{ height: 32.5 }}></td></tr>
                        }
                        {getCalledAPI ? <tr>
                            <td style={{ paddingTop: 15, backgroundColor: '', }}>
                                <Image src={hourGlassIcon} style={{ width: 150, height: 158 }} preview={false} />
                            </td>
                        </tr>
                            : <></>}
                        <tr>
                            <td style={{ paddingTop: 15, }} >
                                <span style={{ fontWeight: 'bold', fontSize: 30, color: '#676669', paddingLeft: 10 }}>{currentTime}</span>
                            </td>
                        </tr>
                        <tr>
                            <td >
                                <span style={{ fontWeight: 'normal', fontSize: 20, color: '#676669', paddingLeft: 10 }}>{currentDate}</span>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <table style={{ width: '100%', backgroundColor: 'white', marginTop: 20 }}>
                    <tbody style={{ textAlign: 'center', alignContent: 'center' }}>
                        <tr>
                            <td>
                                <Image src={ClockInIcon} style={{ width: 30, height: 40 }} preview={false} />
                            </td>
                            <td>
                                <Image src={BreakTotalIcon} style={{ width: 40, height: 40 }} preview={false} />
                            </td>
                            <td>
                                <Image src={ClockOutIcon} style={{ width: 30, height: 40 }} preview={false} />
                            </td>
                        </tr>
                        <tr>
                            <td style={{ fontWeight: 'bold', fontSize: 20, }}>
                                {clockInTime ? clockInTime.substring(11, 16) : '-'}
                            </td>
                            <td style={{ fontWeight: 'bold', fontSize: 20, }}>
                                {showTimeWithoutSeconds(totalBreakTime)}
                            </td>
                            <td style={{ fontWeight: 'bold', fontSize: 20, }}>
                                {clockOutTime ? clockOutTime.substring(11, 16) : '-'}
                            </td>
                        </tr>
                        <tr>
                            <td style={{ color: '#676669', fontSize: 15, }}>
                                Clocked In
                            </td>
                            <td style={{ color: '#676669', fontSize: 15, }}>
                                Break
                            </td>
                            <td style={{ color: '#676669', fontSize: 15, }}>
                                Clocked Out
                            </td>
                        </tr>
                        <tr>
                            <td colSpan={3} style={{ paddingTop: 15 }}>
                                <hr style={{ width: '84%' }} />
                            </td>
                        </tr>
                    </tbody>
                </table>

                <table style={{ width: '100%', backgroundColor: 'white', marginTop: 10 }}>
                    <tbody style={{ alignContent: 'center' }}>
                        <tr>
                            <td valign="top">
                                <Image src={UnLikeIcon} style={{ width: 30, height: 40 }} preview={false} />
                            </td>
                            <td style={{ fontSize: 15, width: '38%', }} valign="top">
                                <Row>
                                    <Col span={24} style={{ fontWeight: 'bold' }}>
                                        {showTimeWithoutSeconds(lateInTime)}
                                    </Col>
                                </Row>
                                <Row>
                                    <Col span={24} style={{ color: '#676669', fontSize: 14, }}>
                                        Late In
                                    </Col>
                                </Row>
                            </td>
                            <td valign="top">
                                <Image src={UnLikeIcon} style={{ width: 30, height: 40 }} preview={false} />
                            </td>
                            <td style={{ fontSize: 15, width: '38%', }} valign="top">
                                <Row>
                                    <Col span={24} style={{ fontWeight: 'bold' }}>
                                        {showTimeWithoutSeconds(earlyDepartureTime)}
                                    </Col>
                                </Row>
                                <Row>
                                    <Col span={24} style={{ color: '#676669', fontSize: 14, }}>
                                        Early Departure
                                    </Col>
                                </Row>
                            </td>
                        </tr>
                        <tr>
                            <td valign="top">
                                <Image src={ShiftIcon} style={{ width: 30, height: 40 }} preview={false} />
                            </td>
                            <td colSpan={3} style={{ fontSize: 15, width: '88%', }} valign="middle">
                                <Row>
                                    <Col span={24} style={{ fontWeight: 'bold' }}>
                                        {shift}
                                    </Col>
                                </Row>
                            </td>
                        </tr>
                        <tr>
                            <td valign="middle">
                                <Image src={BreaksCountIcon} style={{ width: 30, height: 40 }} preview={false} />
                            </td>
                            <td colSpan={3} style={{ fontSize: 15, width: '88%', }} valign="top">
                                <Row>
                                    <Col span={24} style={{ fontWeight: 'bold' }}>
                                        {showTimeWithoutSeconds(recentBreakTime) != "00:00" ? showTimeWithoutSeconds(recentBreakTime) : "00:00"}
                                    </Col>
                                </Row>
                                <Row>
                                    <Col span={24} style={{ color: '#676669', fontSize: 14, }}>
                                        Last Break Taken
                                    </Col>
                                </Row>
                            </td>
                        </tr>
                        <tr>
                            <td valign="top" style={{ paddingTop: 0 }}>
                                <Image src={ClockIcon} style={{ width: 30, height: 40 }} preview={false} />
                            </td>
                            <td colSpan={3} style={{ fontSize: 15, width: '88%', }} valign="top">
                                <Row>
                                    <Col span={24} style={{ fontWeight: 'bold' }}>
                                        {showTimeWithoutSeconds(totalWorkedTime)}
                                    </Col>
                                </Row>
                                <Row>
                                    <Col span={24} style={{ color: '#676669', fontSize: 14, }}>
                                        Total Hours Worked
                                    </Col>
                                </Row>
                            </td>
                        </tr>
                        <tr>
                            <td colSpan={4} style={{ fontWeight: 'bold', fontSize: 14, paddingTop: 5 }}>
                                <Link
                                    to={{
                                        pathname: `/attendance-manager/summary`,
                                        state: { summaryDate: summaryDate, viewType: 'myView' }
                                    }}>
                                    Daily Summary View
                                </Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </DrawerForm>
        </>
    );
};

export default DrawerView;
