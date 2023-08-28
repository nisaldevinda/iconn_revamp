import React, { useState, useEffect } from 'react'

import { PageContainer } from '@ant-design/pro-layout';
import {
  Col,
  Space,
  Typography,
  message as Message,
  Row,
  Card,
  DatePicker,
  Table,
  Input,
  Button,
  Tag,
  Tooltip,
  Form,
  Select,
  Drawer,
  Badge,
  Avatar,
  Radio,
  Image,
  Calendar,
  Spin,
  Result
} from 'antd';
import { SearchOutlined, PlusOutlined, DoubleRightOutlined, DoubleLeftOutlined, ScheduleOutlined, MinusCircleOutlined } from '@ant-design/icons';
import { history, useIntl, FormattedMessage, useAccess, Access } from 'umi';
import ProForm, { ModalForm, ProFormText, ProFormSelect, ProFormTimePicker, ProFormDatePicker } from '@ant-design/pro-form';
import moment from 'moment';
import _ from "lodash";
import './index.css';
import styles from './styles.less';
import { getAllWorkPatterns } from '@/services/workPattern';
import PatternList from './components/patternList';
import PermissionDeniedPage from './../403';
import { ReactComponent as EditIcon } from '../../assets/workSchedule/edit-schedule-icon.svg';
import { ReactComponent as PlusIcon } from '../../assets/workScheduleCalender/plus.svg';


moment.locale('en', {
  week: {
    dow: 1,
  },
});

moment.updateLocale('en', {
  weekdaysMin: ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"]
});
export type workscheduleType={
  id?: string,
  service: any ,
  editable?: boolean,
  monthlyView?: boolean,
  values?: any,
  isFromMyWorkSchedule?: boolean
}

const initValues: object = {
  shiftDate: null,
  currentShift: null,
  newShift: null,
  reason: null,
};

const WorkSchedule: React.FC<workscheduleType> = (props) => {
  const access = useAccess();
  const { hasPermitted } = access;
  const { Text } = Typography;
  const { Option } = Select;
  const [form] = Form.useForm();

  const [weekVal, setWeekVal] = useState('');
  const [searchText, setSearchText] = useState('');
  const [searchedColumn, setSearchedColumn] = useState('');
  const [ModalVisible, setModalVisible] = useState(false);
  const intl = useIntl();
  const [disabled, setDisabled] = useState(true);
  const [columndates, setColumnDates] = useState([]);
  const [startDate, setStartDate] = useState('');
  const [endDate, setEndDate] = useState('');
  const [selectedDate, setSelectedDate] = useState('');
  const [currentDate, setCurrentDate] = useState('');
  const [todayDate, setTodayDate] = useState('');
  const [loading, setLoading] = useState(false);
  const [selectedEmp, setSelectedEmp] = useState([]);
  const [currentData, setCurrentData] = useState([]);
  const [selectedEmployee, setSelectedEmployee] = useState('');
  const [hasMidnightCrossOver, sethasMidnightCrossOver] = useState(false);

  const [startTime, setStartTime] = useState<moment.Moment>();
  const [endTime, setEndTime] =  useState<moment.Moment>();
  const [breakTime, setBreakTime] =  useState <moment.Moment>();
  const [validatedStatusEndTime, setValidateEndTime] = useState<"" | "error" | "warning">("");
  const [helpEndTime, setHelpEndTime] = useState('');
  const [totalHours, setTotalHours] = useState('');
  const [editPatternModalVisible, setEditPatternModalVisible] = useState(false);
  const [workPattern, setWorkPattern] = useState([]);
  const [relatedWorkShifts, setRelatedWorkShifts] = useState([]);
  const [fieldsVal, setFieldsVal] = useState([]);
  const [currentValues, setCurrentValues] = useState<[]>([]);
  const [selectedEmpName, setSelectedEmpName] = useState('');
  const [params, setParams] = useState({});
  const [totalData, setTotalData] = useState(0);

  const [shiftId, setShiftId] = useState('');
  const [currentShift, setCurrentShift] = useState(null);
  const [shiftRecord, setShiftRecord] = useState({});
  const [editable,setEditable]=useState(false);
  const [monthlyView,setMonthlyView]=useState(false);
  const [currentMonth,setCurrentMonth]=useState(moment().format("DD-MM-YYYY"));
  const [calenderData,setCalenderData]=useState([]);
  const [viewWorkShiftModalVisible , setViewWorkShiftModalVisible] = useState(false);
  const [viewShiftRecord , setViewShiftRecord] = useState([]);
  const [empFullName , setEmpFullName] = useState('');
  const [refresh , setRefresh] = useState(0);
  const [shiftChangeModalVisible, handleShiftChangeModalVisible] = useState(false);
  const [isShiftChangeSaved, setIsShiftChangeSaved] = useState(false);
  const [startingDate, setStartingDate] = useState('');
  const [endingDate, setEndingDate] = useState('');
  const {
    getMyWorkSchedule,
    getEmployeeWorkSchedule,
    addWorkShifts,
    getWorkShedules,
    getEmployeeWorkPattern,
    addEmployeeWorkPattern,
    getWorkShifts,
    getWorkShiftById,
    getDateWiseShiftData,
    getWorkShiftsForShiftChange,
    saveShiftChangeRequest
}  = props.service;

//for monthly view
  useEffect(() => {
    if (props.editable) {
      setEditable(props.editable)
    }
    else {
      setEditable(false)
    }
    if (props.monthlyView) {
      setMonthlyView(props.monthlyView)
    }
    else {
      setMonthlyView(false)
    }
  }, [props])

  useEffect(() => {
    if(props.monthlyView){
      fetchData()
    }

  }, [currentMonth])


  useEffect(() => {
    if (shiftChangeModalVisible) {
      getWorkShiftList();
      form.setFieldsValue({
        shiftDate: null,
        currentShift: null,
        newShift: null,
        reason: null
      })
    }

  }, [shiftChangeModalVisible])

  useEffect(() => {
    if (currentShift) {
      getWorkShiftList();
    }

  }, [currentShift])

const fetchData = async () => {
    await setLoading(true)
    if(props.id){
        setSelectedEmployee(props.id)
        setSelectedEmp([{id:props.id}])
        const { data } = await getEmployeeWorkSchedule({
            id:props.id,
            start: moment(currentMonth,"DD-MM-YYYY").startOf('month').format('YYYY-MM-DD'),
            end: moment(currentMonth,"DD-MM-YYYY").endOf('month').format('YYYY-MM-DD')
        })
        await setCalenderData(data)
        await setLoading(false)
    }
    else{
        const { data } = await getMyWorkSchedule({
            start: moment(currentMonth,"DD-MM-YYYY").startOf('month').format('YYYY-MM-DD'),
            end: moment(currentMonth,"DD-MM-YYYY").endOf('month').format('YYYY-MM-DD')
        })
        await setCalenderData(data)
        await setLoading(false)
    }
}

const getShiftDataByDate = async (dateString) => {
  let res = await getDateWiseShiftData({
      date: dateString
  })

  if (res.data) {
    form.setFieldsValue({
      currentShift : res.data.workShiftName
    });

    setCurrentShift(res.data.id);

    form.setFields([
      {
        name: 'shiftDate',
        errors: [],
      },
    ]);
  } else {
    form.setFieldsValue({
      currentShift : 'No Any Current Shift Assign'
    });
    setCurrentShift(null);
  }  
}

const getWorkShiftList = async () => {
  const data = await getWorkShiftsForShiftChange({});
  let shiftList = data.data.map((value) => {
    console.log(currentShift+'--'+value.id);
    return {
      label: value.name,
      value: value.id,
      disabled: (currentShift && value.id == currentShift) ? true : false
    };
  });

  setRelatedWorkShifts(shiftList);
}

const applyShiftChange = async (params) => {
  try {
    const data = await saveShiftChangeRequest(params);
    setIsShiftChangeSaved(true);
  } catch (error) {
    if (!_.isEmpty(error)) {
      console.log(error);
      if (error.message) {
        Message.error({
          content: error.message ? (
            <>
              {error.message}
            </>
          ) : (
            <></>
          ),
        });
      }
    }
  }

}

const prevMonth = () => {
  const prevMonthValue = moment(currentMonth,"DD-MM-YYYY").subtract(1, 'M').format("DD-MM-YYYY")
  console.log(prevMonthValue)
  setCurrentMonth(prevMonthValue)
}

const nextMonth = () => {
  const nextMonthValue = moment(currentMonth,"DD-MM-YYYY").add(1, 'M').format("DD-MM-YYYY")
  setCurrentMonth(nextMonthValue)
}

const onMonthChange = (date, dateString) => {
  const monthValue =date.format("DD-MM-YYYY")
  setCurrentMonth(monthValue)
}

const currMonth = ()=>{
  setCurrentMonth(moment().format("DD-MM-YYYY"))
}

const dateFullRender =(value)=>{

  if (!moment(currentMonth, "DD-MM-YYYY").isSame(value, 'month')) {
      return <div style={{background: "#F1F3F6"}} />
  }
  const workShiftsDay = _.get(calenderData, value.format('DD-MM-Y'), false)
  let element;
  if (workShiftsDay) {
    element = workShiftsDay.map((el) => {
      let color;
      let textColor;
      if (el.type == "shift" || el.type == "pattern") {
        color = el.color;
        textColor = "#036713";
      }
      if (el.type == "leave") {
        color = "#FFCA79";
        textColor = "#BD7200";
      }
      if (el.type == "holiday") {
        color = '#FCACAC';
        textColor = "#BD2E00";
      }
      return (
        <div className={styles.nameBox} style={{ background: color, color: textColor }} onClick={() =>{
          setViewShiftRecord([]);
          if (el.type == "shift" || el.type == "pattern") {
            setViewWorkShiftModalVisible(true);
            let hours = Math.trunc(el['breakTime'] / 60);
            let minutes = el['breakTime'] % 60;
            let hoursVal = hours < 10 ? '0' + hours : hours;
            let minutesVal = minutes < 10 ? '0' + minutes : minutes;
            let breakTimeInMinAndHours = hoursVal + ":" + minutesVal;
            let workHours = Math.trunc(el['workHours'] / 60);
            let workMinutes = el['workHours'] % 60;
            let workHoursVal = workHours < 10 ? '0' + workHours : workHours;
            let workMinutesVal = workMinutes < 10 ? '0' + workMinutes : workMinutes;
            let workHoursInMinAndHours = workHoursVal + ":" + workMinutesVal;
            el['breakTimeInMinAndHours'] = breakTimeInMinAndHours;
            el['workHoursInMinAndHours'] = workHoursInMinAndHours;
            setViewShiftRecord(el);
            if (el['hasMidnightCrossOver']) {
              setValidateEndTime('warning');
              setHelpEndTime('Next Day');
            } else {
              setValidateEndTime('');
              setHelpEndTime('');
            }
          }
        }}>
          <span className={styles.midText}>   {(el.type == "shift" || el.type == "holiday") ?  el.name : el.patternName} </span>
        </div>
       )
    })
  }
  else {
    element = <></>
  }
  return (
    <div className={styles.datecell}>
      <Row className={styles.dateRow}>
        <Col span={12} style={{ textAlign: "left" }}>
          <span className={styles.dateText}>  {value.format("DD")}</span>
        </Col>
        {editable && workShiftsDay.length > 0 ?
          <Col span={12} style={{ textAlign: "end" }}>
            <span>
              <Tooltip key="add-tool-tip" title="Add New Work Shift">
                <PlusIcon height={14}
                  onClick={() => {
                    console.log("huhu", selectedEmp.length)
                    if (selectedEmp.length > 0) {
                      setModalVisible(true);
                      setSelectedDate(value.format("ddd, DD ,MM "));
                      setShiftId('');
                      setShiftRecord([]);
                      sethasMidnightCrossOver(false);
                      setBreakTime();
                      setStartTime();
                      setEndTime();
                      setTotalHours();
                    }
                  }} />
              </Tooltip>
            </span>
          </Col> :
          <></>}
      </Row>
      <Row>
        {element}
      </Row>
    </div>
  )
}

  const Header: React.FC = () => {
    return (
      <Row>
        <Col span={24}>
          <Row style={{float: 'right'}}>
            {
              props.isFromMyWorkSchedule ? (
                <Col>
                  <Button
                    type="primary"
                    danger={true}
                    style={{ backgroundColor: '#FFA500', borderColor: '#FFA500' }}
                    // icon={}
                    onClick={()=> {
                      setIsShiftChangeSaved(false);
                      handleShiftChangeModalVisible(true);
                    }}
                  >
                    <div style={{ display: 'flex' }}>
                      <Space style={{ padding: 5 }}>
                        <EditIcon fill="#FFFFFF" width={18} height={18} />
                      </Space>

                      {intl.formatMessage({
                        id: 'shiftChange',
                        defaultMessage: 'Shift Change Request',
                      })}
                    </div>
                  </Button>
                  &nbsp;&nbsp;
                </Col>
              ) : (
                <></>
              )
              
            }
            <Col>
              <span className="btnParentClass">
                <Button onClick={prevMonth} className={styles.weekButton}>
                  <DoubleLeftOutlined />
                </Button>{' '}
              </span>
              &nbsp;&nbsp;
            </Col>
            <Col>
              <DatePicker
                allowClear={false}
                value={moment(currentMonth, 'DD-MM-YYYY')}
                onChange={onMonthChange}
                picker="month"
                format={'MMM YYYY'}
              />{' '}
              &nbsp;&nbsp;
            </Col>
            <Col>
              <span className="btnParentClass">
                <Button className={styles.weekButton} onClick={nextMonth}>
                  <DoubleRightOutlined />
                </Button>
              </span>
              &nbsp;&nbsp;
            </Col>
            <Col>
              <span className="btnParentClass">
                <Button className={styles.todayButton} onClick={currMonth}>
                  {intl.formatMessage({
                    id: 'today',
                    defaultMessage: 'Today',
                  })}
                </Button>
              </span>{' '}
              &nbsp;&nbsp;
            </Col>
            <Col>
              {editable ? (
                <Tooltip
                  key="add-tool-tip"
                  title={intl.formatMessage({
                    id: 'edit',
                    defaultMessage: 'Assign Work Pattern',
                  })}
                >
                  <Button
                    style={{ width: '46px' }}
                    className={styles.assignWorkschedule}
                    onClick={() => {
                      if (selectedEmployee) {
                        setFieldsVal([]);
                        form.resetFields(['pattern']);
                        employeePattern();
                        setEditPatternModalVisible(true);
                      }
                    }}
                    icon={
                      <span className={styles.editIcon}>
                        {' '}
                        <EditIcon fill="#FFFFFF" width={20} height={20} />
                      </span>
                    }
                  />
                </Tooltip>
              ) : (
                <></>
              )}
            </Col>
          </Row>
        </Col>
      </Row>
    )
  }
//for weekly view
  useEffect(() => {
    setLoading(true);
    let dateString = moment().format("YYYY -wo");
    onChange(moment(), dateString);
    setCurrentDate(moment());
    patternList();
    setLoading(false);

  }, []);

  const onChange = (date, dateString) => {
    let dates = [];
    setColumnDates([]);
    setStartDate('');
    setEndDate('');
    const start = moment(date).startOf('week');
    const formattedStartDate = start.format('MMM DD').toUpperCase() ;
    const startDateIsValid = moment(formattedStartDate, "MMM DD").isValid() ? formattedStartDate :'';
    setStartDate(startDateIsValid);

    const end = moment(date).endOf('week');
    const formattedendDate = end.format('MMM DD').toUpperCase() ;
    const endDateIsValid = moment(formattedendDate, "MMM DD").isValid() ? formattedendDate :'';
    setEndDate(endDateIsValid);
    setStartingDate(start.format('YYYY-MM-DD'));
    setEndingDate(end.format('YYYY-MM-DD'));
    while (start.isSameOrBefore(end)) {
      dates.push(start.format('ddd, DD ,MM'));
      start.add(1, 'days');
    }
    setColumnDates(dates);
    setCurrentDate(date);
    if (!_.isNull(dateString)) {
      const val = dateString.split('-');
      setWeekVal(val[1]);
    }
  }
  const patternList = async () => {
    const { data } = await getAllWorkPatterns({});
    setWorkPattern(data);
  }
  const employeePattern = async () => {
    const request = {
      selectedEmp: selectedEmployee
    }
    const { data } = await getEmployeeWorkPattern(request);

    if (data[0]) {
      const { employeeName, workpatternId, effectiveDate } = data[0];
      let newFields = [];
      data.map((element) => {
        newFields.push(element)
      });
      setFieldsVal(newFields);
      form.setFieldsValue({ firstName: employeeName });
      setEmpFullName(employeeName);
    } else {

      if(props.id){
        const employeeName = props.values?.employeeName;
        form.setFieldsValue({ firstName: employeeName });
        setEmpFullName(employeeName);
      } else {

        if (currentData.length > 0) {
          const index = currentData.findIndex((item) => selectedEmployee == item.id);
    
          const employeeName = currentData[index]['name'];
          form.setFieldsValue({ firstName: employeeName });
          setEmpFullName(employeeName);
        }
      }

    }

  }


  useEffect(() => {
    if(!monthlyView){
      
      if (moment(startDate, "MMM DD").isValid()) {
        getWorkSheduleList();
      } else {
        setCurrentData([]);
        setTotalData(0);

      }
    }


  }, [startDate, params]);
  
  const onChangepageSize = (page, size) => {
    setParams({ currentPage: page, pageSize: size });
  }
  const getWorkSheduleList = async () => {
    try {
  
      const requestData = {
        start: startingDate ,
        end: endingDate,
        currentPage: !_.isUndefined(params.currentPage) ? params.currentPage : 1,
        pageSize: !_.isUndefined(params.pageSize) ? params.pageSize : 10,
        search: searchText
      }

      const { message, data } = await getWorkShedules(requestData);

      setTotalData(data.total);
      setCurrentData(data.data);
      // Message.success(message);
    } catch (error) {
      console.log(error);
    }
  }

  const onSelectTodayDate = () => {
    try {
      setLoading(true);
      let date = moment().format("DD");
      onChange(moment(), moment().format("YYYY -wo"));
      setTodayDate(date)
      setLoading(false);
    } catch (error) {

      setLoading(false)
    }
  }

  const nextWeek = () => {
    setCurrentDate('');
    onChange(moment(currentDate).startOf('isoWeek').add(1, 'week'), moment(currentDate).startOf('isoWeek').add(1, 'week').format("YYYY-wo"))
  }
  const prevWeek = () => {
    setCurrentDate('');
    onChange(moment(currentDate).startOf('isoWeek').subtract(1, 'week'), moment(currentDate).startOf('isoWeek').subtract(1, 'week').format("YYYY-wo"))
  }

  const onchangeStart = (value) => {
    const start = moment(value).format('HH:mm');
    setStartTime(start);
  }

  const onchangeEnd = (value) => {
    const end = moment(value).format('HH:mm');
    setEndTime(end);
    if (moment(value).format('HH:mm') < startTime) {
      sethasMidnightCrossOver(true);
    } else {
      sethasMidnightCrossOver(false);
    }
  }
  const onFinish = async (values: any) => {
    try {
      setLoading(true);
      const workPatternData = values;
      workPatternData.employeeId = selectedEmployee;
      workPatternData.currentPattern = Object.assign({}, ...currentValues);
      const { message, data } = await addEmployeeWorkPattern(workPatternData);
      Message.success(message);
      setEditPatternModalVisible(false);
      if(monthlyView){
        fetchData()
      }
      else{
        getWorkSheduleList();
      }
      employeePattern();
      setLoading(false);
    } catch (error) {
      let errorMessage;
      let errorMessageInfo;
      if (error.message.includes(".")) {
        let errorMessageData = error.message.split(".");
        errorMessage = errorMessageData.slice(0, 1);
        errorMessageInfo = errorMessageData.slice(1).join('.');
      }
      Message.error({
        content:
          error.message ?
            <>
              {errorMessage ?? error.message}
              <br />
              <span style={{ fontWeight: 150, color: '#A9A9A9', fontSize: '14px' }}>
                {errorMessageInfo ?? ''}
              </span>
            </>
            : intl.formatMessage({
              id: 'failedToUpdate',
              defaultMessage: 'Cannot Update',
            }),
      });
    }
    setLoading(false);
  };
  const onChangeSchedule = (value, id) => {
    setRefresh(0);
    let data = [];
    let updatedData = [];
    let newRecord = {};
    if (moment.isMoment(value)) {
      const date = moment(value).toDate();
      newRecord = { effectiveDate: moment(date).format('YYYY-MM-DD'), id: id };
    } else {
      newRecord = { workPatternId: value, id: id };
    }
    data.push(...currentValues, newRecord);

    updatedData = fieldsVal.map((item) =>{
       if (item.id === id) {
          if (moment.isMoment(value)) {
            item.effectiveDate = newRecord.effectiveDate;
          }else {
            item.workPatternId = newRecord.workPatternId;
          }
       }
       return item;
    });
    setFieldsVal(updatedData);
    setCurrentValues(data);
    setRefresh(refresh+1);
  }

  const getColumnSearchProps = (dataIndex) => ({
    filterDropdown: ({ setSelectedKeys, selectedKeys, confirm, clearFilters }) => (
      <div style={{ padding: 8 }}>
        <Input
          placeholder={`Search ${dataIndex}`}
          value={selectedKeys[0]}
          onChange={e => setSelectedKeys(e.target.value ? [e.target.value] : [])}
          onPressEnter={() => handleSearch(selectedKeys, confirm, dataIndex)}
          style={{ marginBottom: 8, display: 'block' }}
        />
        <Space>
          <Button
            type="primary"
            onClick={() => handleSearch(selectedKeys, confirm, dataIndex)}
            icon={<SearchOutlined />}
            size="small"
            style={{ width: 90 }}
          >
            Search
          </Button>
          <Button onClick={() => handleReset(clearFilters)} size="small" style={{ width: 90 }}>
            Reset
          </Button>
          <Button
            type="link"
            size="small"
            onClick={() => {
              confirm({ closeDropdown: false });
              setSearchText(selectedKeys[0]);
              setSearchedColumn(dataIndex)
            }}
          >
            Filter
          </Button>
        </Space>
      </div>
    ),
    filterIcon: filtered => <SearchOutlined style={{ color: filtered ? '#1890ff' : undefined, paddingRight: 8 }} />,
    onFilter: (value, record) =>
      record[dataIndex]
        ? record[dataIndex].toString().toLowerCase().includes(value.toLowerCase())
        : '',
    onFilterDropdownVisibleChange: visible => {
      if (visible) {
        // setTimeout(() => searchInput.select(), 100);
      }
    },
    render: text =>
      searchedColumn === dataIndex ? (
        text
      ) : (
        text
      ),
  });

  const handleSearch = (selectedKeys, confirm, dataIndex) => {
    setLoading(true);
    confirm();
    setSearchText(selectedKeys[0]);
    setSearchedColumn(dataIndex);
    setLoading(false);
  };

  const handleReset = clearFilters => {
    clearFilters();
    setSearchText('');
  }
  
  const getShiftsById = async (id: string) => {
    const { data } = await getWorkShiftById(id);
    let hours = Math.trunc(data['breakTime'] / 60);
    let minutes = data['breakTime'] % 60;
    let hoursVal = hours < 10 ? '0' + hours : hours;
    let minutesVal = minutes < 10 ? '0' + minutes : minutes;
    let breakTimeInMinAndHours = hoursVal + ":" + minutesVal;
    let workHours = Math.trunc(data['workHours'] / 60);
    let workMinutes = data['workHours'] % 60;
    let workHoursVal = workHours < 10 ? '0' + workHours : workHours;
    let workMinutesVal = workMinutes < 10 ? '0' + workMinutes : workMinutes;
    let workHoursInMinAndHours = workHoursVal + ":" + workMinutesVal;
    data['breakTimeInMinAndHours'] = breakTimeInMinAndHours;
    data['workHoursInMinAndHours'] = workHoursInMinAndHours;

    setShiftRecord(data);
    setBreakTime(data['breakTimeInMinAndHours']);
    setTotalHours(data['workHoursInMinAndHours']);
    if (data['hasMidnightCrossOver']) {
      setValidateEndTime('warning');
      setHelpEndTime('Next Day');
    } else {
      setValidateEndTime('');
      setHelpEndTime('');
    }
  };

  const onBreakChange = (value) => {
    const breakValue = moment(value).format('HH:mm');
    setBreakTime(breakValue);
    let hours;
    if (startTime && endTime) {
      let timeStart = calculateTotalHours(startTime);
      let timeEnd = calculateTotalHours(endTime);
    
      if (endTime < startTime) {
        let midnight = calculateTotalHours('24:00');
        hours = (timeEnd + midnight) - timeStart;
      } else {
        hours = timeEnd - timeStart;
      }
    }

    let breakHours  = calculateTotalHours(breakValue);
    let totalWorkHours = convertToTime(hours - breakHours);
    if (startTime && endTime) {
      setTotalHours(totalWorkHours);
    }
  }

  const calculateTotalHours = (time) => {
    let total = 0;
    const timestrToSec = (timestr: any) => {
      var parts = timestr.split(":");
      return (parts[0] * 3600) +
        (parts[1] * 60);
    }

    if (!_.isNull(time)) {
      total += timestrToSec(time);
    }
    return (total);
  }

  const convertToTime = (value) => {
    const pad = (num: any) => {
      if (num < 10) {
        return "0" + num;
      } else {
        return "" + num;
      }
    }

    const formatTime = (seconds: any) => {
      return [pad(Math.floor(seconds / 3600)),
      pad(Math.floor(seconds / 60) % 60)
      ].join(":");
    }
    return formatTime(value);
  }
  const disabledShiftDates = (current) => {
    let compareDate = moment(current, 'YYYY-MM-DD').format('YYYY-MM-DD');
    let currentDate = moment().format('YYYY-MM-DD');
    const isPreviousDay = moment(compareDate, 'YYYY-MM-DD') < moment(currentDate, 'YYYY-MM-DD');

    return isPreviousDay;
  };

  const handleAdd = async (fields: any) => {
    try {
      let date = moment(selectedDate, "ddd, DD ,MM ");

      let breakHours = breakTime.split(":");
      let breakTimeInMin = Number(breakHours[0]) * 60 + Number(breakHours[1]);

      let hours = totalHours.split(":");
      let workHoursInMin = Number(hours[0]) * 60 + Number(hours[1]);

      let newData = {};
      newData['startTime'] = startTime;
      newData['endTime'] = endTime;
      newData['breakTime'] = breakTimeInMin.toString();
      newData['date'] = date.format("YYYY-MM-DD");
      newData['workHours'] = workHoursInMin.toString();
      newData['employeeId'] = selectedEmp;
      newData['hasMidnightCrossOver'] = hasMidnightCrossOver ? "1" : "0";
      newData['name'] = fields.name;
      newData['noOfDay'] = fields.noOfDay;
      newData['shiftId'] = fields.shiftId;

      const { message, data } = await addWorkShifts(newData);
      Message.success(message);

      setModalVisible(false);
      if(monthlyView){
        fetchData()
      }
      else{
        getWorkSheduleList();
      }


    } catch (error: any) {
      let errorMessage;
      let errorMessageInfo;
      if (error.message.includes(".")) {
        let errorMessageData = error.message.split(".");
        errorMessage = errorMessageData.slice(0, 1);
        errorMessageInfo = errorMessageData.slice(1).join('.');
      }
      Message.error({
        content:
          error.message ?
            <>
              {errorMessage ?? error.message}
              <br />
              <span style={{ fontWeight: 150, color: '#A9A9A9', fontSize: '14px' }}>
                {errorMessageInfo ?? ''}
              </span>
            </>
            : intl.formatMessage({
              id: 'failedToSave',
              defaultMessage: 'Cannot Save',
            }),
      });
    }
  };

  const columns = [
    {
      title: `${intl.formatMessage({
        id: 'name',
        defaultMessage: 'Name',
      })}`,
      dataIndex: 'name',
      key: 'name',
      width: '200px',
      fixed: 'left',

      ...getColumnSearchProps('name'),
      sorter: (a, b) => a.name.length - b.name.length,
      sortDirections: ['descend', 'ascend'],
      render: (text, record) => {
        return (
          <Row justify="space-around" align="middle">
            <Col span={6}>
              <Avatar size="large" src={record.profilePic} />
            </Col>
            <Col span={18}>
              <div className={styles.nameElement}>
                <Tooltip title={record.name}>{record.name} </Tooltip>
              </div>
              <div className={styles.emailElement}>
              <Tooltip title={record.workEmail}> {record.workEmail} </Tooltip>
              </div>
            </Col>
          </Row>
        )
      },
    }
  ];

  columndates.forEach(element => {
    let date = '';
    let string = element.split(',');
    string = string[0].toLowerCase();
    date = element.split(',')[1];
    let Date = element.slice(0, -3);
    let color = "";
    if (Number(date) == Number(todayDate)) {
      color = styles.highlight;
    }
    columns.push({
      title:
        <>
          <Row>
            &nbsp;{Date} &nbsp;&nbsp;
            <Col span={10} style={{ textAlign: 'right' }}>
              <Tooltip key="add-tool-tip" title="Add New Work Shift">
                <a
                  key="edit-btn"
                  onClick={() => {
                    if (selectedEmp.length > 0) {
                      setModalVisible(true);
                      setSelectedDate(element);
                      setShiftId('');
                      setShiftRecord([]);
                      sethasMidnightCrossOver(false);
                      setBreakTime();
                      setStartTime();
                      setEndTime();
                      setTotalHours();
                    }
                  }}
                  disabled={disabled }
                  className="icon"
                >
                  <PlusOutlined />
                </a>
              </Tooltip>
            </Col>
          </Row>
        </>,
      dataIndex: string,
      key: string,
      width: '130px',
      render: string => (
        <>
          {string && string.map(item => {
            let color;
            let textColor;
            if (item.type == "shift" || item.type == "pattern" ) {
              color = item.color;
              textColor = "#036713";
            }
            if (item.type == "leave") {
              color = "#FFCA79";
              textColor = "#BD7200";
            }
            if (item.type == "holiday") {
              color = item.color;
              textColor="#BD2E00";
            }
            if (!_.isUndefined(item.date)) {
              const newDate = moment(item.date, "DD-MM-YYYY");
              if (Number(newDate.format("DD")) == Number(date)) {
                return (
                  <Col span={24} className={styles.tagTooltip}>
                    <Tooltip title={item.name} >
                      <a onClick={()=> {
                         setViewShiftRecord([]);
                        if (item.type == "shift" || item.type == "pattern") {
                          setViewWorkShiftModalVisible(true);
                          let hours = Math.trunc(item['breakTime'] / 60);
                          let minutes = item['breakTime'] % 60;
                          let hoursVal = hours < 10 ? '0' + hours : hours;
                          let minutesVal = minutes < 10 ? '0' + minutes : minutes;
                          let breakTimeInMinAndHours = hoursVal + ":" + minutesVal;
                          let workHours = Math.trunc(item['workHours'] / 60);
                          let workMinutes = item['workHours'] % 60;
                          let workHoursVal = workHours < 10 ? '0' + workHours : workHours;
                          let workMinutesVal = workMinutes < 10 ? '0' + workMinutes : workMinutes;
                          let workHoursInMinAndHours = workHoursVal + ":" + workMinutesVal;
                          item['breakTimeInMinAndHours'] = breakTimeInMinAndHours;
                          item['workHoursInMinAndHours'] = workHoursInMinAndHours;
                          setViewShiftRecord(item);
                          if (item['hasMidnightCrossOver']) {
                            setValidateEndTime('warning');
                            setHelpEndTime('Next Day');
                          } else {
                            setValidateEndTime('');
                            setHelpEndTime('');
                          }
                        }
                      }}
                      >
                        <Tag color={color} key={item.name} style={{ color: textColor }} className={styles.myTag} >
                          {(item.type == "shift" || item.type == "holiday" || item.type == "leave") ? item.name : item.patternName}
                        </Tag>
                      </a>
                    </Tooltip>
                  </Col>
                )
              }
            }
          })
          }
        </>
      ),
      className: color
    });
  });

  const rowSelection = {
    onChange: (selectedRowKeys: React.Key[], selectedRows: DataType[]) => {
      setDisabled(false);
      setSelectedEmp(selectedRows);

      // edit drawer selected employee
      setSelectedEmployee(selectedRows.length > 0 ? selectedRows[0].id : '')
      setSelectedEmpName(selectedRows.length > 0 ? selectedRows[0].name : '')
    }
  };

  return !loading ?
    <>
      {monthlyView ?
        <Card className='workScheduleComponent'  >
          <Row style={{ marginBottom: 25 }}>
            <Col span={24}>
              <Space direction="vertical">
                <Text className={styles.textHeading}>
                  {moment(currentMonth, "DD-MM-YYYY").startOf('month').format("DD MMM YYYY")}&nbsp;-&nbsp;
                  {moment(currentMonth, "DD-MM-YYYY").endOf('month').format('DD MMM YYYY')}</Text>
                <Text type="secondary">
                  {moment(currentMonth, "DD-MM-YYYY").format('Mo')} Month
                </Text>

              </Space>
            </Col>
          </Row>
          <Row >
            <Spin spinning={loading}>

              <Calendar
                className='workschedule-calender'
                headerRender={({ value, type, onChange, onTypeChange }) => { return <Header /> }}
                mode={"month"}
                value={moment(currentMonth, "DD-MM-YYYY")}
                dateFullCellRender={dateFullRender}
              />
            </Spin>
          </Row>
        </Card>
        :
        <Card className='workScheduleComponent'>
          <Row>
            <Col span={24}>
              <Space direction="vertical">
                <Text className={styles.textHeading}>{ startDate } - { endDate }</Text>
                <Text className={styles.text}>
                  {weekVal} {intl.formatMessage({
                    id: 'week',
                    defaultMessage: 'Week',
                  })}
                </Text>
              </Space>
            </Col>
          </Row>
          <br />
          <Row gutter={{ xs: 8, sm: 16, md: 24, lg: 32 }}>
            <span className="btnParentClass">
              <Button
                onClick={prevWeek}
                className={styles.weekButton}
              >
                <DoubleLeftOutlined />
              </Button> </span>&nbsp;&nbsp;
            <DatePicker
              onChange={onChange}
              picker="week"
              value={currentDate}
            /> &nbsp;&nbsp;
            <span className="btnParentClass">
              <Button
                className={styles.weekButton}
                onClick={nextWeek}
              >
                <DoubleRightOutlined />
              </Button>
            </span>
            &nbsp;&nbsp;
            <span className="btnParentClass">
              <Button className={styles.todayButton} onClick={onSelectTodayDate}>
                {intl.formatMessage({
                  id: 'today',
                  defaultMessage: 'Today',
                })}
              </Button></span> &nbsp;&nbsp;
            <Tooltip key="add-tool-tip"
              title={intl.formatMessage({
                id: 'edit',
                defaultMessage: 'Assign Work Pattern',
              })} >
              <Button
                style={{ width: "46px" }}
                className={disabled ? styles.assignWorkscheduleDisabled : styles.assignWorkschedule}
                onClick={() => {
                  if (selectedEmployee) {
                    setFieldsVal([]);
                    form.resetFields(["pattern"]);
                    employeePattern();
                    setEditPatternModalVisible(true);
                  }
                }}
                disabled={disabled}

                icon={disabled ? <span className={styles.editIcon}> <EditIcon fill="#BBC4C3" width={20} height={20} /></span> : <span className={styles.editIcon}> <EditIcon fill="#FFFFFF" width={20} height={20} /></span>}
              >
              </Button>

            </Tooltip>
          </Row>
          <br />
          <Table
            columns={columns}
            dataSource={currentData}
            pagination={{
              showSizeChanger: true,
              total: totalData,
              showTotal: (totalData, range) => `${range[0]}-${range[1]} of ${totalData} items`,
              onChange: (page, pageSize) => onChangepageSize(page, pageSize)
            }}
            rowSelection={{
              type: 'checkbox',
              hideSelectAll: true,
              ...rowSelection,
            }}
            scroll={{ y: 400 }}
            bordered
            loading={loading}
            rowClassName={styles.workScheduleRow}
            className="work-schedule-table"
          />
        </Card>
      }
      {ModalVisible &&
        <ModalForm
          width={600}
          title={intl.formatMessage({
            id: 'workshift',
            defaultMessage: 'Add New Work Shift',
          })}
          onFinish={async (values: any) => {
            await handleAdd(values as any);
          }}
          visible={ModalVisible}
          onVisibleChange={setModalVisible}
          submitter={{
            searchConfig: {
              submitText: intl.formatMessage({
                id: 'save',
                defaultMessage: 'Save',
              }),
              resetText: intl.formatMessage({
                id: 'cancel',
                defaultMessage: 'Cancel',
              }),
            },
          }}
        >
          <ProForm.Group>
            <Row className={styles.dateField}>
              <ProFormDatePicker
                width="sm"
                name="date"
                label="Date"
                initialValue={moment(selectedDate, "ddd, DD ,MM ").format("YYYY-MM-DD")}
                disabled
              />
            </Row>
            {monthlyView ? <></> : <Row className={styles.dateField}>
              <Form.Item
                label={intl.formatMessage({
                  id: 'employeeName',
                  defaultMessage: ' Employee Name',
                })}
              >
                {selectedEmp.map((emp) => {
                  return (
                    <>
                      <Tooltip key={emp.id} title={emp.name} >
                        <Tag disabled  className ={styles.addEmployee} >
                          {emp.name}
                        </Tag>
                      </Tooltip> &nbsp;
                      
                    </>
                  );
                })
                }
              </Form.Item>
            </Row>}
           
            <Row className={styles.shiftField}>
              <ProFormSelect
                valuePropName="option"
                name="shiftId"
                width="sm"
                label={intl.formatMessage({
                  id: 'shiftName',
                  defaultMessage: 'Shift Name',
                })}
                request={async () => {
                  const data = await getWorkShifts({});
                  return data.data.map((value) => {
                    return {
                      label: value.name,
                      value: value.id,
                    };
                  });
                }}
                placeholder={intl.formatMessage({
                  id: 'shiftName',
                  defaultMessage: 'Shift Name',
                })}
                rules={[
                  {
                    required: true,
                    message: intl.formatMessage({
                      id: 'shiftName',
                      defaultMessage: 'Required',
                    })
                  }
                ]}
                fieldProps={{
                  onChange: (value) => {
                    setShiftId(value);
                    getShiftsById(value);
                  }
                }}
              />
            </Row>
            <Row className={styles.daysField}>
              <ProFormSelect
                name="noOfDay"
                label={intl.formatMessage({
                  id: 'days',
                  defaultMessage: 'Days',
                })}
                fieldProps={{
                  value: !_.isEmpty(shiftRecord) ? Math.floor(shiftRecord['noOfDay']) == 1 ? '1 day' : '0.5 day' : 'Select'
                }}

                disabled
              />
            </Row>
            <Row className={styles.timeField}>
              <ProFormTimePicker
                name="startTime"
                label={intl.formatMessage({
                  id: 'startTime',
                  defaultMessage: 'Start Time',
                })}
                placeholder={intl.formatMessage({
                  id: 'startTime',
                  defaultMessage: 'hh:mm ',
                })}
                width="xs"
                format="hh:mm a"
                fieldProps={{
                  value: shiftRecord['startTime']  && moment(shiftRecord['startTime'], 'h:m') 
                }}
                disabled
              /> &nbsp;&nbsp;
              <div>
                <ProFormTimePicker
                  name="endTime"
                  label={intl.formatMessage({
                    id: 'endTime',
                    defaultMessage: 'End Time',
                  })}
                  placeholder={intl.formatMessage({
                    id: 'endTime.placeholder',
                    defaultMessage: 'hh:mm',
                  })}
                  width="xs"
                  format="hh:mm a"
                  className="endTime"
                  validateStatus={validatedStatusEndTime}
                  help={helpEndTime}
                  fieldProps={{
                    value: shiftRecord['endTime'] && moment(shiftRecord['endTime'], 'h:m')
                  }}
                  disabled
                />

              </div>
              &nbsp;&nbsp;
              <ProFormText
                width="xs"
                name="breakTime"
                label={intl.formatMessage({
                  id: 'Break Time',
                  defaultMessage: 'Break Time',
                })}
                placeholder={intl.formatMessage({
                  id: 'BreakTime.placeholder',
                  defaultMessage: 'hh:mm',
                })}
                fieldProps={{

                  value: shiftRecord['breakTimeInMinAndHours']
                }}
                disabled
              />
              &nbsp;&nbsp;
              <ProFormText
                width="xs"
                name="workHours"
                label={intl.formatMessage({
                  id: 'Hours',
                  defaultMessage: 'Work Hours',
                })}
                placeholder={intl.formatMessage({
                  id: 'BreakTime.placeholder',
                  defaultMessage: 'hh:mm',
                })}
                fieldProps={{
                  value: shiftRecord['workHoursInMinAndHours']
                }}
                disabled
              />
            </Row>
             
          </ProForm.Group>
        </ModalForm>
      }
     
      {viewWorkShiftModalVisible &&
        <ModalForm
          width={600}
          title={intl.formatMessage({
            id: 'workshift',
            defaultMessage: 'View Work Shift',
          })}
          
          visible={viewWorkShiftModalVisible}
          onVisibleChange={setViewWorkShiftModalVisible}
          submitter= { {
            resetButtonProps: {
              type: 'dashed',
            },
            submitButtonProps: {
              style: {
                display: 'none',
              },
            },
          }}
        >
          <ProForm.Group>
            <Row className={styles.dateField}>
              <ProFormDatePicker
                width="sm"
                name="date"
               
                label= {intl.formatMessage({
                  id: 'viewShift.Date"',
                  defaultMessage: 'Date',
                  })}
                  format={'DD-MM-YYYY'}
                  initialValue={moment(viewShiftRecord['date'],'DD-MM-YYYY') }
               
                disabled
              />
            </Row>
            {monthlyView ? 
              <></> 
              : 
              <Row className={styles.dateField}>
                <Form.Item
                  label={intl.formatMessage({
                    id: 'employeeName',
                    defaultMessage: ' Employee Name',
                  })}
                >
                  <>
                    <Tooltip title={viewShiftRecord['empName']} >
                      <Tag disabled className={styles.viewEmployee} >
                         {viewShiftRecord['empName']}
                       </Tag>
                    </Tooltip>   &nbsp;
                    
                  </>
                </Form.Item>
              </Row>
            }
            <Row className={styles.shiftField}>
              <ProFormText
                width="sm"
                name="name"
                label={intl.formatMessage({
                  id: 'shiftName',
                  defaultMessage: `${viewShiftRecord['isWorkPatternShift'] ? 'Pattern Name' :'Shift Name'}`,
                })}
                fieldProps= {{
                  value: viewShiftRecord['name']
                }}
                disabled
              />
            </Row>
    
            <Row className={styles.daysField}>
              <ProFormSelect
                name="noOfDay"
                label={intl.formatMessage({
                  id: 'days',
                  defaultMessage: 'Days',
                })}
                fieldProps={{
                  value: Math.floor(viewShiftRecord['days']) == 1 ? '1 day' : '0.5 day'
                }}
                disabled
              />
            </Row>
            <Row className={styles.timeField}>
              <ProFormTimePicker
                name="startTime"
                label={intl.formatMessage({
                  id: 'startTime',
                  defaultMessage: 'Start Time',
                })}
                width="xs"
                format="hh:mm a"
                fieldProps={{
                  value: moment(viewShiftRecord['startTime'], 'h:m')
                }}
                disabled
              /> &nbsp;&nbsp;
              <div>
                <ProFormTimePicker
                  name="endTime"
                  label={intl.formatMessage({
                    id: 'endTime',
                    defaultMessage: 'End Time',
                  })}
                  width="xs"
                  format="hh:mm a"
                  className="endTime"
                  validateStatus={validatedStatusEndTime}
                  help={helpEndTime}
                  fieldProps={{
                    value: moment(viewShiftRecord['endTime'], 'h:m')
                  }}
                  disabled
                />
                 
              </div>
              &nbsp;&nbsp;
              <ProFormText
                width="xs"
                name="breakTime"
                label={intl.formatMessage({
                  id: 'Break Time',
                  defaultMessage: 'Break Time',
                })}
                fieldProps={{
                  value: viewShiftRecord['breakTimeInMinAndHours']
                }}
                disabled
              />
              &nbsp;&nbsp;
              <ProFormText
                width="xs"
                name="workHours"
                label={intl.formatMessage({
                  id: 'Hours',
                  defaultMessage: 'Work Hours',
                })}
                fieldProps={{
                  value: viewShiftRecord['workHoursInMinAndHours']
                }}
                 disabled
                />
            </Row>
          </ProForm.Group>
        </ModalForm>
      }
      {editPatternModalVisible &&
        <Drawer
          title={intl.formatMessage({
            id: 'workshift',
            defaultMessage: 'Assign Work Patterns',
          })}
          width={620}
          visible={editPatternModalVisible}
          destroyOnClose={true}
          onClose={() => setEditPatternModalVisible(false)}
        >
          <Form
            form={form}
            onFinish={onFinish}
            autoComplete="off"
          >
            <Col span={10}>
              <Tooltip title={empFullName} >
                <Form.Item
                  label={intl.formatMessage({
                    id: 'employeeName',
                    defaultMessage: 'Employee Name',
                  })}
                  name="firstName"
                >
                  <Input style={{ width: 150 , textOverflow:'ellipsis' ,overflow:'hidden' }} disabled />
                </Form.Item>
              </Tooltip>
            </Col>
            <Row>
              <Col span={6} style={{ textAlign: 'left' }}>
                <h3 className={styles.workPattern}>
                  {intl.formatMessage({
                    id: 'workPatterns',
                    defaultMessage: 'Work Patterns',
                  })}
                </h3>
              </Col>
              <Col span={18} style={{ textAlign: 'right' }}>
                <Form.Item>
                  <Space>
                    <Row>
                      <Badge status="success" />
                      <h3 className={styles.record}>
                        {intl.formatMessage({
                          id: 'currentRecord',
                          defaultMessage: 'Current Record',
                        })}
                      </h3>
                    </Row>
                    <Row>
                      <Badge status="warning" />
                      <h3 className={styles.record}> {intl.formatMessage({
                        id: 'upcomingRecord',
                        defaultMessage: 'Upcoming Record',
                      })}
                      </h3>
                    </Row>
                  </Space>
                </Form.Item>
              </Col>
            </Row>
            <Form.List name="pattern" >

              {(fields, { add, remove }) => (

                <>
                  <Form.Item>
                    <Button className={styles.addWorkPatternButton} onClick={() => add()} block icon={<PlusOutlined />}>
                      Add Work Pattern
                    </Button>
                  </Form.Item>
                  {fields.slice(0).reverse().map(({ key, name, ...restField }) => (
                    <Space key={key} style={{ display: 'flex', marginBottom: 8 }} align="baseline">
                      <Badge status="warning" />
                      <Col span={12}>
                        <Form.Item
                          {...restField}
                          name={[name, 'effectiveDate']}
                          label={intl.formatMessage({
                            id: 'effectiveDate',
                            defaultMessage: ' Effective Date',
                          })}
                        >
                          <DatePicker style={{ width: 190 }} format={'DD-MM-YYYY'} />
                        </Form.Item>
                      </Col>
                      <Col span={12}>
                        <Form.Item
                          {...restField}
                          name={[name, 'patternId']}
                          label={intl.formatMessage({
                            id: 'workpattern',
                            defaultMessage: ' Work Pattern',
                          })}
                        >
                          <Select
                            showSearch
                            style={{ width: 220 }}
                            placeholder={intl.formatMessage({
                              id: 'pattern',
                              defaultMessage: 'Select Pattern',
                            })}
                            optionFilterProp="children"
                          >
                            {workPattern.map((pattern) => {
                              return (
                                <Option key={pattern.id} value={pattern.id}>
                                  {pattern.name}
                                </Option>
                              );
                            })}
                          </Select>
                        </Form.Item>
                      </Col>
                      <Col span={2}>
                        <Form.Item>
                          <br />
                          <MinusCircleOutlined onClick={() => remove(name)} />
                        </Form.Item>
                      </Col>
                    </Space>
                  ))}

                </>
              )}
            </Form.List>
            {fieldsVal.length > 0 &&
              <PatternList
                fieldsVal={fieldsVal}
                workPattern={workPattern}
                onChange={onChangeSchedule}
                refresh={refresh}
              />
            }

            <Form.Item>
              <Space style={{ float: "right", position: "fixed", bottom: "40px", right: "40px" }}>
                <Button onClick={() => setEditPatternModalVisible(false)} className={styles.cancelBtn}>
                  <FormattedMessage id="CANCEL" defaultMessage="Cancel" />
                </Button>
                <Button type="primary" key="submit" htmlType="submit">
                  <FormattedMessage id="UPDATE" defaultMessage="Update" />
                </Button>
              </Space>
            </Form.Item>

          </Form>
        </Drawer>
      }

      <ModalForm
        width={isShiftChangeSaved ? 560 : 700}
        className={isShiftChangeSaved ? 'shiftChangeModalSuccesMode' : 'shiftChangeModalNormalMode'}
        title={
          !isShiftChangeSaved ? (
            <>
              <Row>
                <Col>
                  <Space style={{ paddingTop: 4 }}>
                    {intl.formatMessage({
                      id: 'pages.Workflows.addNewWorkflow',
                      defaultMessage: 'Shift Change Request',
                    })}
                  </Space>
                </Col>
              </Row>
            </>
          ) : (
            false
          )
        }
        modalProps={{
          destroyOnClose: true,
        }}
        onFinish={async (values: any) => {}}
        visible={shiftChangeModalVisible}
        onVisibleChange={handleShiftChangeModalVisible}
        initialValues={{
          useMode: 'chapter',
        }}
        submitter={
          !isShiftChangeSaved
            ? {
                render: () => {
                  return [
                    <>
                      <Button
                        onClick={(e) => {
                          e.stopPropagation();
                        }}
                        style={{ marginRight: 10, borderRadius: 6 }}
                        type={'default'}
                      >
                        Cancel
                      </Button>
                      <Button
                        onClick={async() => {
                          await form.validateFields();
                          let shiftDate = (form.getFieldValue('shiftDate')) ? form.getFieldValue('shiftDate').format('YYYY-MM-DD') : null;
                          let curentShiftId = currentShift;
                          let newShiftId = (form.getFieldValue('newShift')) ? form.getFieldValue('newShift') : null;
                          let reason = (form.getFieldValue('reason')) ? form.getFieldValue('reason') : null;

                          let params = {
                            'shiftDate': shiftDate,
                            'currentShiftId': curentShiftId,
                            'newShiftId' : newShiftId,
                            'reason' : reason
                          }
                          applyShiftChange(params);
                          
                        }}
                        type="primary"
                      >
                        Save
                      </Button>
                    </>,
                  ];
                },
              }
            : false
        }
      >
        {isShiftChangeSaved ? (
          <Result status="success" title="Shift Change Request Has Been Sent" />
        ) : (
          <>
            <Form form={form}  layout="vertical" style={{ width: '100%' }}>
              <Row>
                <Col span={24}>
                  <Row style={{marginBottom: 20}}>
                    <Col span={24}>
                      <Form.Item
                        // className="pro-field pro-field-md"
                        name={'shiftDate'}
                        label={<FormattedMessage id="Shift_Date" defaultMessage="Shift Date" />}
                        rules={[
                          {
                            required: true,
                            message: 'Required',
                          },
                        ]}
                        style={{ margin: 0 }}
                      >
                        <DatePicker
                          style={{ width: '35%' }}
                          format={'YYYY-MM-DD'}
                          // value={inDateModel}
                          onChange={(date, dateString) => {
                            if (date) {
                              getShiftDataByDate(dateString);
                            } else {
                              form.setFieldsValue({
                                currentShift : null
                              });
                            }
                          }}
                          disabledDate={disabledShiftDates}
                        />
                      </Form.Item>
                    </Col>
                  </Row>
                  <Row style={{marginBottom: 20}}>
                    <Col span={12}>
                      <Form.Item
                        // className="pro-field pro-field-md"
                        label={<FormattedMessage id="currentShift" defaultMessage="Current Shift" />}
                        name={'currentShift'}
                        style={{ width: '90%' }}
                      >
                        <ProFormText disabled></ProFormText>
                      </Form.Item>
                    </Col>
                    <Col span={12}>
                      <Form.Item
                        name="newShift"
                        label="New Shift"
                        style={{ width: '90%' }}
                        rules={[
                          {
                            required: true,
                            message: 'Required',
                          },
                        ]}
                      >
                        <ProFormSelect
                          showSearch
                          options={relatedWorkShifts}
                          fieldProps={{
                            optionItemRender(item) {
                              return item.label;
                            },
                          }}
                          placeholder="Select Employee"
                          style={{ marginBottom: 0 }}
                        />
                      </Form.Item>
                    </Col>
                  </Row>
                  <Row>
                    <Col span={24}>
                      <Form.Item
                        name="reason"
                        style={{ width: '95%' }}
                        label={<FormattedMessage id="reason" defaultMessage="Reason" />}
                        rules={[{ max: 250, message: 'Maximum length is 250 characters.' }]}
                      >
                        <Input.TextArea
                          maxLength={251}
                          rows={4}
                        />
                      </Form.Item>
                    </Col>
                  </Row>
                </Col>
              </Row>
            </Form>
          </>
        )}
      </ModalForm>
    </>
   :
     <Spin size='large' spinning={loading}/>
};


export default WorkSchedule;
