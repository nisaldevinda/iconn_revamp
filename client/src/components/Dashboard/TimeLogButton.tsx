import { getAttendance, getLastLogged } from '@/services/attendance';
import { Col, Row, Button } from 'antd';
import _ from 'lodash';
import moment from 'moment';
import { useEffect, useState } from 'react';
import { useAccess } from 'umi';
import DrawerView from '../Attendance/DrawerView';
import styles from './Dashboard.less';
var momentTZ = require('moment-timezone');

const TimeLogButton = () => {
  moment.locale('en')
  const access = useAccess();
  const { hasPermitted } = access;

  const [drawerVisible, setDrawerVisible] = useState(false);
  const [lastLogged, setLastLogged] = useState(null)
  const [loggedTime, setLoggedTime] = useState("0");
  const [punchedStatus, setPunchedStatus] = useState('CLOCK IN');
  const [clockInTime, setClockInTime] = useState(new Date().toString());
  const [employeeZone, setEmployeeZone] = useState('Asia/Colombo');
  const [totalBreakTime, setTotalBreakTime] = useState('00:00:00');
  const [intervalLoggedTime, setIntervalLoggedTime] = useState(0);

  useEffect(() => {
    fetchData()
    startCounting();
    // checkAttendance();
  }, [drawerVisible])

  useEffect(() => {
    startCounting();
    // checkAttendance();
  }, [])

  useEffect(() => {
    const text = calculateRealtime(clockInTime, false);

    setLoggedTime(text);

  }, [intervalLoggedTime])

  const checkAttendance = async () => {
    await getAttendance().then((response) => {
      if (response) {
        setPunchedStatus(response.data.status);
        setClockInTime(response.data.clockIn);
        setEmployeeZone(response.data.zone);
        setTotalBreakTime(response.data.totalBreak);
      }
    });
  }

  function countUpWorkedTime() {
    setIntervalLoggedTime(Math.random());
  }

  function startCounting() {
    setInterval(countUpWorkedTime, 1000);
  }

  const calculateRealtime = (receivedDateTime: string, inBreak: boolean): string => {
    const now = new Date();
    const dateTime = momentTZ(now).tz(employeeZone).format();
    var nowTime = moment(dateTime);
    var punchedTime = moment(receivedDateTime); //db today logged

    var nowMil = nowTime.valueOf();
    var gotMil = punchedTime.valueOf();
    const breakTime = totalBreakTime ? ((parseInt(totalBreakTime.split(':')[0]) * 60 * 60) + (parseInt(totalBreakTime.split(':')[1]) * 60) + parseInt(totalBreakTime.split(':')[2])) : 0;

    if (inBreak) {
      var workedTimeBeforeBreak = gotMil - moment(clockInTime).valueOf() - breakTime * 1000;
      var hours = workedTimeBeforeBreak / (60 * 60 * 1000);
      var mins = (hours - Math.floor(hours)) * 60;
      var secs = (mins - Math.floor(mins)) * 60;
      const text =
        (hours < 10 ? ("0" + Math.floor(hours)) : Math.floor(hours)) + ":" +
        (mins < 10 ? ("0" + Math.floor(mins)) : Math.floor(mins)) + ":" +
        (secs < 10 ? ("0" + Math.floor(secs)) : Math.floor(secs));
      setLoggedTime(text);
    }

    var diffMil = nowMil - gotMil - (!inBreak ? (breakTime * 1000) : 0);// in milliseconds
    var hours = diffMil / (60 * 60 * 1000);
    var mins = (hours - Math.floor(hours)) * 60;
    var secs = (mins - Math.floor(mins)) * 60;

    const text =
      (hours < 10 ? ("0" + Math.floor(hours)) : Math.floor(hours)) + ":" +
      (mins < 10 ? ("0" + Math.floor(mins)) : Math.floor(mins)) + ":" +
      (secs < 10 ? ("0" + Math.floor(secs)) : Math.floor(secs));

    return text;
  }

  const fetchData = async () => {
    const response = await getLastLogged();
    const { data } = response;
    if (data) {
      setLastLogged(_.get(data, 'lastLoggedTime', ''));
    }
  }

  return <>
    {hasPermitted('my-attendance') && <Col span={12} style={{ alignItems: 'right', paddingRight: '32px' }}>
      <Row justify="end" gutter={[28, 36]}>
        <Col span={18}>
          <Row justify="end" style={{ marginTop: '6px', marginBottom: '8px' }}>
            <div className={styles.mainText}>Last Logged</div>
          </Row>
          <Row justify="end">
            {(lastLogged === null) ? "-" : (
              <div className={styles.subText}>{`${moment(lastLogged).format(
                'DD MMM YYYY',
              )} at ${moment(lastLogged).format('LT')}`}</div>
            )}
          </Row>
        </Col>
        <Col span={6}>
          {/* <Button onClick={() => {
              setDrawerVisible(true);
            }} type="primary" size={"large"} className={styles.clockinButton} >{loggedTime}</Button> */}
          <Button
            onClick={() => {
              setDrawerVisible(true);
            }}
            type="primary"
            size={'large'}
            className={styles.clockinButton}
          >
            Log Time
          </Button>
        </Col>
      </Row>
      <DrawerView visibility={drawerVisible} setDrawerVisibility={setDrawerVisible} />
    </Col>}
  </>
};

export default TimeLogButton;