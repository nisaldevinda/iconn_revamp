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
    Badge,
    Avatar,
    Radio,
    Image,
    Checkbox,
    Spin,
    Menu,
    Dropdown,
    Divider,
} from 'antd';
import { DownOutlined ,DoubleRightOutlined , DoubleLeftOutlined } from '@ant-design/icons';
import { history, useIntl, FormattedMessage, useAccess, Access } from 'umi';
import ProForm, { ProFormSelect } from '@ant-design/pro-form';
import moment from 'moment';
import _ from "lodash";
import type { Moment } from 'moment';
import { getAllLocations } from '@/services/location';
import { getAllDepartment } from '@/services/department';
import { getWorkShiftsList , createAdhocWorkShifts ,  getWorkShifts } from '@/services/workShift';
import { getEmployeeListForDepartment, getEmployeeListForLocation, getEmployeeListForDepartmentAndLocation, getEmployeeList } from '@/services/dropdown';
import PermissionDeniedPage from '../403';
import { ReactComponent as EditIcon } from '../../assets/workSchedule/edit-schedule-icon.svg';
import { getWorkShedulesManagerView } from '@/services/workSchedule';
import styles from './styles.less';
import './styles.css';
import { ReactComponent as LineOutlinedIconDisabled } from '../../assets/line.svg';
import { ReactComponent as DropDownIconDisabled } from '../../assets/dropDown.svg';
import { ReactComponent as DropdownIcon } from '../../assets/dropDownIcon.svg';
import { ReactComponent as LineOutlinedIcon } from '../../assets/lineOutlined.svg';

moment.locale('en', {
    week: {
        dow: 1,
    },
});

moment.updateLocale('en', {
    weekdaysMin: ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"]
});

const { RangePicker } = DatePicker;

const ShiftChange: React.FC = () => {
    const access = useAccess();
    const { hasPermitted } = access;
    const { Text } = Typography;
    const { Option } = Select;
    const [form] = Form.useForm();
    const intl = useIntl();
    const [disabled, setDisabled] = useState(true);
    const [columndates, setColumnDates] = useState([]);
    const [startDate, setStartDate] = useState('');
    const [endDate, setEndDate] = useState('');
    const [selectedDate, setSelectedDate] = useState([]);
    const [currentDate, setCurrentDate] = useState('');
    const [currentData, setCurrentData] = useState([]);
    const [loading, setLoading] = useState(false);
    const [selectedEmp, setSelectedEmp] = useState([]);
    const [params, setParams] = useState([]);
    const [totalData, setTotalData] = useState(0);

    const [value, setValue] = useState(null);
    const [locations, setLocations] = useState([]);
    const [departments, setDepartments] = useState([]);
    const [locationId, setLocationId] = useState('');
    const [departmentId, setDepartmentId] = useState('');
    const [shifts, setShifts] = useState([]);
    const [employeeList, setEmployeesList] = useState([]);
    const [weekVal , setWeekVal] = useState([]);
    const [viewShift , setViewShift] = useState('Week');
    const [showDropdown , setShowDropdown] = useState(false);
    const [shiftId , setShiftId] = useState('');
    const [startingDate, setStartingDate] = useState('');
    const [endingDate, setEndingDate] = useState('');
    useEffect(() => {
        fetchData();
    }, []);

    const fetchData = async () => {
        try {
            setLoading(true);
            const { data } = await getWorkShiftsList();
            setShifts(data);

            const locationData = await getAllLocations();
            const locationArray = Object.values(locationData?.data.map(location => {
                return {
                    label: location.name,
                    value: location.id
                };
            }));
            setLocations(locationArray);

            const departmentData = await getAllDepartment();
            const departmentArray = Object.values(departmentData?.data.map(department => {
                return {
                    label: department.name,
                    value: department.id
                };
            }));
            setDepartments(departmentArray);

            const employeeArray = await getEmployeeList("MANAGER");
            setEmployeesList(employeeArray.data);
            setLoading(false);
        } catch (error) {
            console.log(error);
            setLoading(false);
        }
    }
    const menu = (
        <Menu className={styles.shiftDropdown}>
            <p className={styles.dropDownHeading}>
                {intl.formatMessage({
                    id: 'shifts.dropdown.heading',
                    defaultMessage: 'Shifts'
                })
                }
            </p>
            <Divider className={styles.divider} />
            {
                shifts.map((el) => {
                    return (
                        <Menu.Item key={el.id} >
                            <a onClick={async() => {
                                
                                setShiftId(el.id);
                                const { data } = await getWorkShifts(el.id);
                                let newArray = [] ;
                                
                                    setLoading(true);
                                    newArray = currentData.map((shiftData) => {
                                        selectedDate.forEach((item) => {
                                        if (shiftData.id == item.empId && shiftData[(moment(item.date,'YYYY-MM-DD')).format('ddd').toLowerCase()] ) {
                                            return shiftData[(moment(item.date,'YYYY-MM-DD')).format('ddd').toLowerCase()]   = [{
                                            color: data.color,
                                            date : moment(item.date,'YYYY-MM-DD').format("DD-MM-YYYY"),
                                            name :data.name,
                                            type : 'shift',
                                            empId: shiftData.id
                                            }]
                                        }
                                        return shiftData;
                                    })
                                })
                                
                                setCurrentData(currentData);
                                setLoading(false);
                                
                                
                            }}>
                                {el.name}
                            </a>
                        </Menu.Item>

                    )
                })
            }

        </Menu>
    );

    const empListmenu = () => {
        return <Menu className={styles.employeeDropdown}>
            <p className={styles.dropDownHeading}>
                <Checkbox
                    onClick={(e) => {
                        if (e.target.checked) {
                           setSelectedEmp([...selectedEmp, 'all']);
                        }else {
                            setSelectedEmp([]);
                        }   
                    }}
                    checked={selectedEmp.includes('all')}
                >
                    {intl.formatMessage({
                        id: 'employees.dropdown.heading',
                        defaultMessage: 'All Employees'
                    })
                    }
                </Checkbox>

            </p>
            <Divider className={styles.divider} />
            {
                employeeList.map((el) => {
                    return (
                        
                        <Menu.Item key={el.id} >
                             <Tooltip title={el.employeeName} > 
                             <a onClick={(e) => {
                                e.stopPropagation();
                            }}> 
                                <Checkbox
                                    onClick={(e) => {
                                        if (e.target.checked) {
                                           setSelectedEmp([...selectedEmp, el.id])
                                        } else {
                                            let index = selectedEmp.indexOf(el.id);
                                            let newRecord =  selectedEmp.splice( index, 1 );
                                            setSelectedEmp([...selectedEmp]);
                                            if (selectedEmp.length == 0 ) {
                                                setDisabled(true);
                                            }
                                        }
                                    }}
                                    checked={selectedEmp.includes('all') || selectedEmp.includes(el.id)}
                                >
                                 {el.employeeName} 
                                </Checkbox>
                            </a>
                            </Tooltip>
                        </Menu.Item>
                        
                    )
                })
            }
            <Divider className={styles.divider} />
            <Row className={styles.empFooter}>
                <Col span={24} className={styles.footer}>
                    <Form.Item>
                        <Space>
                            <Button
                                htmlType="button"
                                onClick={() => {
                                    setShowDropdown(false);
                                    setSelectedEmp([]);
                                    if (selectedEmp.length == 0 ) {
                                        setDisabled(true);
                                    }
                                }}
                            >
                                {intl.formatMessage({
                                    id: 'shiftAssign.cancel',
                                    defaultMessage: 'Cancel',
                                })}
                            </Button>
                            <Button type="primary" htmlType="submit" 
                               onClick={()=>{
                                  setShowDropdown(false);
                                  getWorkSheduleList();
                               }}
                            >
                                {intl.formatMessage({
                                    id: 'shiftAssign.select',
                                    defaultMessage: 'Select',
                                })}
                            </Button>
                        </Space>
                    </Form.Item>
                </Col>
            </Row>
        </Menu>
    }
    const employeeMenu = () => {
        return <div>
            <Row>
                <Dropdown 
                   overlay={empListmenu} 
                   visible={showDropdown}
                   onClick={() => {
                     setShowDropdown(true);
                   }} 
                   trigger={["click"]}
                   placement="bottomRight"
                >
                    
                    <Space>
                        <Text className={styles.employeeHeading}>{selectedEmp.length > 0 ? selectedEmp.includes('all') ?employeeList.length : selectedEmp.length: ''}</Text>
                        <Text className={styles.employeeHeading}>
                           {intl.formatMessage({
                                id: 'shiftAssign.employee',
                                defaultMessage: 'Selected Employees',
                            })}
                        </Text>
                        <DownOutlined className={styles.employeeDownIcon}/>
                    </Space>
                    
                </Dropdown>
            </Row>
        </div>
    };

    const onChange = (value) => {
        setValue(value);
        let dates = [];
        setColumnDates([]);
        setStartDate('');
        setEndDate('');
        if (!_.isNull(value)) {
            if (viewShift === 'Week') {
                const start = moment(value[0]).startOf('week');
                const end = moment(value[0]).endOf('week');
                setStartingDate(start.format('YYYY-MM-DD'));
                setEndingDate(end.format('YYYY-MM-DD'));
                const formattedStartDate = start.format('MMMM DD');
                const startDateIsValid = moment(formattedStartDate, "MMMM DD").isValid() ? formattedStartDate : '';
                setStartDate(startDateIsValid);
                const formattedendDate = end.format('MMMM DD');
                const endDateIsValid = moment(formattedendDate, "MMMM DD").isValid() ? formattedendDate : '';
                setEndDate(endDateIsValid);

                const weekValue = moment(value[0]).week();
                setWeekVal(weekValue);
                while (start.isSameOrBefore(end)) {
                    dates.push(start.format('ddd, DD '));
                    start.add(1, 'days');
                }

                setColumnDates(dates);
                setCurrentDate(value[0]);
            } else {
                const start = moment(value).format('MMMM,DD YYYY');
                setStartDate(start);
                const weekValue = moment(value).format('dddd');
                setWeekVal(weekValue);
                dates.push(value.format('ddd, DD'));
                setColumnDates(dates);
                setCurrentDate(value);
                setStartingDate(moment(value).format('YYYY-MM-DD'));
                
            }
            getWorkSheduleList();
        }else {
            setCurrentData([]);
            setSelectedEmp([]);
            setWeekVal([]);
        }
    }
    useEffect(() => {
        getWorkSheduleList();
    }, [startDate,endDate]);
    
    const getWorkSheduleList = async () => {
        try {
            if (selectedEmp.length !== 0 && startDate ) {
                let startingDateValue = '';
                let endingDateValue = '';
                if ( viewShift === 'Week') {
                   startingDateValue = startingDate;
                   endingDateValue =  endingDate ;
                } else {
                    startingDateValue= startingDate;
                    endingDateValue = startingDate;
                }
                let employeeId = [];
                if (selectedEmp.includes('all')) {
                    employeeId = employeeList.map((emp) =>{
                        return emp.id;
                    })
                }
                const requestData = {
                    start: startingDateValue,
                    end: endingDateValue,
                    currentPage: !_.isUndefined(params.currentPage) ? params.currentPage : 1,
                    pageSize: !_.isUndefined(params.pageSize) ? params.pageSize : 10,
                    employeeIds: selectedEmp.includes('all') ? employeeId.toString() : selectedEmp.toString()
                }

                const { message, data } = await getWorkShedulesManagerView(requestData);

                setTotalData(data.total);
                setCurrentData(data.data);
            }
        } catch (error) {
            console.log(error);
        }
    }

    const nextWeek = () => {
        setCurrentDate('');
        if (viewShift === 'Week') {
           const dateRange = [moment(currentDate).startOf('isoWeek').add(1, 'week'), moment(currentDate).endOf('isoWeek').add(1, 'week')];
           onChange(dateRange);
        }else {
            const date= moment(currentDate).add(1, 'days');
            onChange(date);
        }
    }
    const prevWeek = () => {
        setCurrentDate('');
        if (viewShift === 'Week') {
          const dateRange = [moment(currentDate).startOf('isoWeek').subtract(1, 'week'), moment(currentDate).endOf('isoWeek').subtract(1, 'week')];
          onChange(dateRange);
        }else {
            const date= moment(currentDate).subtract(1, 'days');
            onChange(date);
        }
    }

    const columns = [
        {
            title: employeeMenu(),
            dataIndex: 'name',
            key: 'name',
            width: '250px',
            fixed: 'left',
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
                                {record.workEmail}
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
        let color = "";

        columns.push({
            title:
                <>
                    <Row>
                        &nbsp;{element} &nbsp;&nbsp;

                    </Row>
                </>,
            dataIndex: string,
            key: string,
            width: '180px',
            render: string => (
                <>
                    {string && string.map(item => {
                        let color;
                        let textColor;
                        let checkedValue;
                        if (item.type == "shift" || item.type == "pattern") {
                            color = item.color;
                            textColor = "#036713";
                        }
                        if (item.type == "leave") {
                            color = "#FFCA79";
                            textColor = "#BD7200";
                        }
                        if (item.type == "holiday") {
                            color = item.color;
                            textColor = "#BD2E00";
                        }
                        if (!_.isUndefined(item.date)) {
                            const newDate = moment(item.date, "DD-MM-YYYY");
                            if (Number(newDate.format("DD")) == Number(date)) {
                                checkedValue = selectedDate.filter((record) => {
                                    if(record.date === moment(item.date,"DD-MM-YYYY").format("YYYY-MM-DD") && record.empId === item.empId) {
                                      return true
                                    }
                                    return false;
                                });
                                return (
                                    <Col span={24} className={styles.tagTooltip}>
                                        <Row className={styles.shiftTableCell}>
                                            <Tooltip title={item.name} >
                                                <Checkbox className={styles.tableCellCheckbox}
                                                  onClick={(e) =>{
                                                    if (e.target.checked) {
                                                      const record = {empId:item.empId , date:moment(item.date,"DD-MM-YYYY").format('YYYY-MM-DD') }
                                                      setSelectedDate([...selectedDate, record]);
                                                      setDisabled(false);
                                                    } else {
                                                        const filteredData = selectedDate.findIndex((record) => {

                                                            if (record.date === moment(item.date, "DD-MM-YYYY").format("YYYY-MM-DD") && record.empId === item.empId) {
                                                                return record;
                                                            }
                                                        });
                                                        const newRecord =  selectedDate.splice( filteredData, 1 );
                                                        setSelectedDate([...selectedDate]);
                                                        if (selectedDate.length == 0) {
                                                            setDisabled(true);
                                                        }
                                                    }
                                                  }}
                                                  checked={checkedValue.length > 0  && checkedValue  }
                                                />
                                                <Tag color={color} key={item.name} style={{ color: textColor }} className={styles.myTag} >
                                                    {(item.type == "shift" || item.type == "holiday" || item.type == "leave") ? item.name : item.patternName}
                                                </Tag>



                                            </Tooltip>
                                        </Row>
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

    const dayViewcolumns = [
        {
            title: employeeMenu(),
            dataIndex: 'name',
            key: 'name',
            width: '150px',
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
                                {record.workEmail}
                            </div>
                        </Col>
                    </Row>
                )
            },
        }
    ];

    {viewShift === 'Day' &&  columndates.forEach(element => {
        let date = '';
        let string = element.split(',');
        string = string[0].toLowerCase();
        date = element.split(',')[1];
        let color = "";
 
        dayViewcolumns.push({
            title:
                <>
                    <Row justify='center'>
                        &nbsp;{element} &nbsp;&nbsp;

                    </Row>
                </>,
            dataIndex: string,
            key: string,
            width: '480px',
            
            render: string => (
                <>
                    {string && string.map(item => {
                        let color;
                        let textColor;
                        let checkedValue ;
                        if (item.type == "shift" || item.type == "pattern") {
                            color = item.color;
                            textColor = "#036713";
                        }
                        if (item.type == "leave") {
                            color = "#FFCA79";
                            textColor = "#BD7200";
                        }
                        if (item.type == "holiday") {
                            color = item.color;
                            textColor = "#BD2E00";
                        }
                        if (!_.isUndefined(item.date)) {
                            const newDate = moment(item.date, "DD-MM-YYYY");
                            if (Number(newDate.format("DD")) == Number(date)) {
                                 checkedValue = selectedDate.filter((record) => {
                                    
                                    if(record.date === moment(item.date,"DD-MM-YYYY").format("YYYY-MM-DD") && record.empId === item.empId) {
                                      return true
                                    }
                                    return false;
                                })
                                return (
                                    
                                    <Col span={24} className={styles.tagTooltip}>
                                        <Row className={styles.shiftTableCell}>
                                            <Tooltip title={item.name} >
                                                <Checkbox className={styles.tableCellCheckbox}
                                                  onClick={(e) =>{
                                                    if (e.target.checked) {
                                                        const record = {empId:item.empId , date:moment(item.date,"DD-MM-YYYY").format('YYYY-MM-DD') }
                                                        setSelectedDate([...selectedDate, record]);
                                                        setDisabled(false);
                                                    } else {
                                                        const filteredData = selectedDate.findIndex((record) => {

                                                            if (record.date === moment(item.date, "DD-MM-YYYY").format("YYYY-MM-DD") && record.empId === item.empId) {
                                                                return record;
                                                            }
                                                        });
                                                        
                                                        
                                                        const newRecord =  selectedDate.splice( filteredData, 1 );
                                                        setSelectedDate([...selectedDate]);
                                                       
                                                        if (selectedDate.length == 0) {
                                                            setDisabled(true);
                                                        }
                                                    }
                                                  }}
                                                  checked={checkedValue.length > 0  && checkedValue ? true : false}
                                                />
                                                <Tag color={color} key={item.name} style={{ color: textColor }} className={styles.dayViewTag} >
                                                    {(item.type == "shift" || item.type == "holiday" || item.type == "leave") ? item.name : item.patternName}
                                                </Tag>



                                            </Tooltip>
                                        </Row>
                                    </Col>
                                )
                            }
                        }
                    })
                    }
                </>
            ),
            
        });
    });
    }
    return (
        <PageContainer>

            <Row>
                <Col span={11}>
                    <Space direction="horizontal">
                        {viewShift === 'Week' && startDate != '' && <Text className={styles.textHeading}> {startDate} - {endDate} </Text>}
                        {viewShift === 'Day' && <Text className={styles.textHeading}> {startDate} </Text>}
                        {viewShift === 'Week' &&  startDate != '' &&
                          <Text className={styles.subHeading}>
                             ({weekVal} 
                               {intl.formatMessage({
                                  id: 'week',
                                  defaultMessage: 'Week',
                               }) } )
                        </Text>}
                        {viewShift === 'Day' &&
                          <Text className={styles.subHeading}>
                            {weekVal} 
                        </Text>}
                    </Space>
                </Col>

                <Space>
                    <Col span={2}>
                        <span >
                            <Button
                                onClick={prevWeek}
                                className={styles.weekButton}
                            >
                               <span className={styles.nextIcon}> <DoubleLeftOutlined /> </span>
                            </Button>
                        </span>
                    </Col>
                    <Col span={4} className={styles.dateCol}>
                        {viewShift === 'Week' ? (
                        <RangePicker
                            className={styles.rangePicker}
                            onChange={(val) => {
                                setValue('');
                                onChange(val);
                            }}
                            format={'DD-MM-YYYY'}
                            
                           value={value ?? moment(value)}
                        /> ) :(
                           <DatePicker
                               className={styles.rangePicker}
                               onChange={(val) =>{
                                
                                  onChange(val);
                                }}
                                format={'DD-MM-YYYY'}
                                value={value ?? moment(value)}
                            />
                        )
                        }
                    </Col>
                    <Col span={2}>
                        <span >
                            <Button
                                onClick={nextWeek}
                                className={styles.weekButton}
                            >
                                <DoubleRightOutlined />
                            </Button>
                        </span>
                    </Col>
                </Space>

                <Col span={4} className={styles.weekCol} >
                    <div>
                            <Button className={styles.weekView}
                               onClick={() =>{
                                  setValue('');
                                  setWeekVal('');
                                  setStartDate('');
                                  setSelectedEmp([]);
                                  setColumnDates([]);
                                  setViewShift('Week');
                               }}
                            
                            >
                                <p className={styles.weekButtonTextSelection}>
                                    {intl.formatMessage({
                                        id: 'shifts.weekViewButton',
                                        defaultMessage: 'Week'
                                    })}
                                </p>
                            </Button>
                      
                            <Button className={styles.dayViewButton}
                               onClick={() =>{
                                setValue('');
                                setWeekVal('');
                                setStartDate('');
                                setSelectedEmp([]);
                                setColumnDates([]);
                                setViewShift('Day');
                            }}
                            
                            >
                                <p className={styles.dayButtonTextSelection}>
                                    {intl.formatMessage({
                                        id: 'shifts.dayViewButton',
                                        defaultMessage: 'Day'
                                    })}
                                </p>
                            </Button>
                    </div> 
                          
                   
                </Col>

            </Row>
            <br />
            <Card>

                <Row>
                    
                        <Col span={6} className={styles.formCol}>
                            <Row className={styles.formLabel}>
                                {intl.formatMessage({
                                    id: 'shift.location',
                                    defaultMessage: 'Location',
                                })}
                            </Row>
                            <ProFormSelect

                                name="location"
                                options={locations}

                                placeholder={intl.formatMessage({
                                    id: 'shiftAssign.location.placeholder',
                                    defaultMessage: 'Select Location',
                                })}
                                onChange={async (value) => {
                                    if (!_.isUndefined(value)) {
                                        setLocationId(value);

                                        const { data } = await getEmployeeListForLocation(value);
                                        const filteredArray = data.filter(function (locationEmpList) {
                                            return employeeList.some(function (employee) {
                                                return locationEmpList.id === employee.id; 
                                           });
                                        });
                                    
                                        setEmployeesList(filteredArray);
                                    }else {
                                        fetchData();
                                    }
                                    
                                }}
                            />

                        </Col>
                        <Col span={6} className={styles.formCol}>
                            <Row className={styles.formLabel}>
                                {intl.formatMessage({
                                    id: 'shift.department',
                                    defaultMessage: 'Department',
                                })}
                            </Row>
                            <ProFormSelect
                                width="lg"
                                name="department"
                                options={departments}

                                placeholder={intl.formatMessage({
                                    id: 'shiftChange.department.placeholder',
                                    defaultMessage: 'Select Department',
                                })}
                                onChange={async (value) => {
                                    setDepartmentId(value);
                                    let departmentArray = [];

                                    if (locationId && value) {
                                        const requestData = {
                                            locationId: locationId,
                                            departmentId: value
                                        }
                                        departmentArray = await getEmployeeListForDepartmentAndLocation(requestData);

                                    } else {
                                        departmentArray = await getEmployeeListForDepartment(value);
                                    }
                                    if (!_.isUndefined(value)) {
                                        const filteredArray = departmentArray.data.filter(function (departmentEmpList) {
                                            return employeeList.some(function (employee) {
                                               return departmentEmpList.id === employee.id; 
                                           });
                                        });
                                
                                        setEmployeesList(filteredArray);
                                    }else {
                                        fetchData();
                                    }
                                }}
                            />

                        </Col>
                       
                        <Col span={12} className={styles.changeShiftCol}>
                            <Dropdown
                                overlay={menu} placement="bottomLeft"
                                placement="bottomRight"
                                className={disabled ? styles.changeShiftDisabled : styles.changeShift}
                                disabled={disabled}
                            >
                             <Button className={disabled ? styles.changeShiftDisabled : styles.changeShift}>
                                
                                <p className={disabled ? styles.buttonTextDisabled : styles.buttonText}>
                                    <Space>
                                    <EditIcon fill={disabled ? "#BBC4C3" : "#FFFFFF"} />
                                    <span className={styles.text}>
                                        {intl.formatMessage({
                                            id: 'shift.changeShiftButton',
                                            defaultMessage: 'Change Shift'
                                        })}
                                    </span>
                                    {disabled ? <LineOutlinedIconDisabled /> :<LineOutlinedIcon/> }  
                                    {disabled ? <DropDownIconDisabled /> : <DropdownIcon /> } 
                                    </Space>
                                </p>
                                </Button>
                            </Dropdown>

                        </Col>
                    
                </Row>
                <br />

                <Table
                    columns={viewShift === 'Day' ? dayViewcolumns :columns}
                    dataSource={selectedEmp.length > 0 && currentData}
                    scroll={{ y: 400 }}
                    bordered
                    loading={loading}
                    pagination ={ false}
                />


                <br />
                <Row>
                    <Col span={24} className={styles.footer}>
                        <Form.Item>
                            <Space>
                                <Button
                                    htmlType="button"
                                    onClick={() => {               
                                        setValue('');
                                        setWeekVal('');
                                        setStartDate('');
                                        setEndDate('');
                                        setSelectedEmp([]);
                                        setColumnDates([]);
                                        setLocationId('');
                                        setDepartmentId('');
                                        setSelectedDate([]);
                                    }}
                                >
                                    {intl.formatMessage({
                                        id: 'shiftAssign.reset',
                                        defaultMessage: 'Reset',
                                    })}
                                </Button>
                                <Button type="primary" htmlType="submit"
                                    onClick={async() => {
                                        const requestData ={
                                            shiftId : shiftId,
                                            data: selectedDate
                                        }
                                        const {data,message} = await createAdhocWorkShifts(requestData);
                                        getWorkSheduleList();
                                        Message.success(message)
                                        setSelectedDate([]);
                                        getWorkSheduleList();
                                        setDisabled(true);
                                    }} disabled ={shiftId == '' ? true : false }
                                >
                                    {intl.formatMessage({
                                        id: 'shiftAssign.save',
                                        defaultMessage: 'Save',
                                    })}
                                </Button>
                            </Space>
                        </Form.Item>
                    </Col>
                </Row>

            </Card>

        </PageContainer>
    )
};
export default ShiftChange;