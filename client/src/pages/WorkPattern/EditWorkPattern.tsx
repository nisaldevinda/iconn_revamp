import React, { useRef, useState, useEffect } from 'react';
import { PageContainer } from '@ant-design/pro-layout';
import {
  Form,
  Row,
  Col,
  Input,
  Button,
  Card,
  Space,
  Spin,
  Typography,
  message as Message,
  Tooltip,
  Popconfirm,
  message,
  Select,
  Image,
  Divider,
  Dropdown,
  Menu,
  Tag,
  Table,
  Checkbox
} from 'antd';
import { DeleteOutlined } from '@ant-design/icons';
import { useParams, history, useIntl,useAccess, Access} from 'umi';
import { updateWorkPattern , getWorkPattern , deleteWeek , checkWeekExist } from '@/services/workPattern';
import {  IParams ,  IWorkPatternForm } from './data';
import WeekTable from './components/weekTable';
import PermissionDeniedPage from './../403';
import { getCountriesList } from '@/services/countryService';
import { getLocationByCountryId } from '@/services/location';
import _ from 'lodash';
import CloneIcon from '../../assets/workPattern/icon-clone.svg';
import './weekTable.css';
import { getAllWorkShifts, getWorkShifts } from '@/services/workShift';
import { ReactComponent as LineOutlinedIconDisabled } from '../../assets/line.svg';
import { ReactComponent as DropDownIconDisabled } from '../../assets/dropDown.svg';
import { ReactComponent as DropdownIcon } from '../../assets/dropDownIcon.svg';
import { ReactComponent as LineOutlinedIcon } from '../../assets/lineOutlined.svg';
import styles from './styles.less';

export default (): React.ReactNode => {
  const access = useAccess();
  const { hasPermitted } = access;
  const { TextArea } = Input;
  const { Text } = Typography;
  const { Option } = Select;
  const { id } = useParams<IParams>();
  const [form] = Form.useForm();
  const intl = useIntl();
  const [weekVal, setWeekVal] = useState(1)
  const [currentValuesWeek, setCurrentValuesWeek] = useState([]);
  const [currentValuesWeek2, setCurrentValuesWeek2] = useState([]);
  const [weekTable1, setWeekTable1 ]= useState<[]>([]);
  const [weekTable2, setWeekTable2 ]= useState<[]>([]);
 
  const [loading, setLoading] = useState<boolean>(false);
  const [weekDayContent, setWeekDayContent] = useState([]);
  const [clone, setClone] = useState(false);
  
  const [selectedCountry, setSelectedCountry] = useState([]);
  const [locations, setLocations] = useState([]);
  const [countries, setCountries] = useState([]);
  const [weekDefinitionCount, setWeekDefinitionCount] = useState(1);

  const [weekDefinitionValues, setWeekDefinitionValues] = useState([]);
  const [weekDefinitionValuesWeek2, setWeekDefinitionValuesWeek2] = useState([]);
  const [shiftId, setShiftId] = useState('');
  const [workShifts , setWorkShifts] = useState([]);
  const [disabled, setDisabled] = useState(true);
  const [disabledWeek2, setDisabledWeek2] = useState(true);
 
  const tableHeader = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
  const onFinish = async (formData:  IWorkPatternForm) => {
    const { name, description ,locationId ,countryId} =
      formData;
    const key = 'label';
    const uniqueWeekTable1DataByKey = [...new Map( weekTable1.map(item =>
        [item[key], item])).values()];
  
    const uniqueWeekTable2DataByKey = [...new Map( weekTable2.map(item =>
          [item[key], item])).values()];

          let formattedWeek1Data = [];
          uniqueWeekTable1DataByKey.map((item)=>{
            let dayVal = '';
            if (item.label === 'mon') {
              dayVal  = 1;
            } else if (item.label === 'tue') {
              dayVal  = 2;
            } else if (item.label === 'wed') {
              dayVal = 3;
            } else if (item.label === 'thu') {
              dayVal  = 4;
            } else if (item.label === 'fri') {
              dayVal  = 5;
            } else if (item.label === 'sat') {
              dayVal  = 6;
            } else if (item.label === 'sun') {
              dayVal = 0 ;
            }
              formattedWeek1Data.push({shiftId : item.shiftId , tableIndex: 'Week 1' , day: dayVal})
          });
          
          let formattedWeek2Data = [];
          uniqueWeekTable2DataByKey.map((item)=>{
            let dayVal = '';
            if (item.label === 'mon') {
              dayVal  = 1;
            } else if (item.label === 'tue') {
              dayVal  = 2;
            } else if (item.label === 'wed') {
              dayVal = 3;
            } else if (item.label === 'thu') {
              dayVal  = 4;
            } else if (item.label === 'fri') {
              dayVal  = 5;
            } else if (item.label === 'sat') {
              dayVal  = 6;
            } else if (item.label === 'sun') {
              dayVal = 0 ;
            }
              formattedWeek2Data.push({shiftId : item.shiftId , tableIndex: 'Week 2' , day: dayVal})
          });
    const requestData = {
      name,
      description,
      locationId:locationId,
      countryId:countryId,
      weekTable1: formattedWeek1Data,
      weekTable2: formattedWeek2Data,
      clone,
    };
   
    try {
      // if (formData.countryId !== undefined &&  formData.countryId.length > 0) {
      //   if (formData.locationId === undefined || formData.locationId.length == 0) {
      //     form.setFields([{
      //       name: 'locationId',
      //       errors: ['Required'] 
      //       }
      //     ]);
      //     return;
      //   }
      // }
      // if (formData.countryId !== undefined && formData.countryId.length == 0 &&  formData.locationId === undefined) {
      //   form.setFields([{
      //     name: 'locationId',
      //     errors: [] 
      //   }
      //   ]);
      //   return;
      // }
      const { message, data } = await updateWorkPattern(id,requestData);
      Message.success(message);
        
    } catch (error:any) {
      if (!_.isEmpty(error.message)) {
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
        if (!_.isEmpty(error.data) && _.isObject(error.data)) {
          for (const fieldName in error.data) {
            form.setFields([
              {
                name: fieldName,
                errors: error.data[fieldName]
              }
            ]);

          }
        }
      }
    }
  }
  useEffect (() =>{
    const fetchLocation = async() => {
      
      let requestData = {
        id:id,
        countryId:selectedCountry.toString()
      };

      const {data} = await getLocationByCountryId(requestData);
      setLocations(data);

    }
    try {
      fetchLocation();
    } catch (error) {
      console.log(error);
    }
    
  },[selectedCountry]);

  useEffect(() => {
    const fetchData = async() => {
      setLoading(true);
      const { data } = await getWorkPattern(id);
      const shiftData = await getAllWorkShifts();
      setWorkShifts(shiftData.data);
     
      countryData();
      const { name , description } = data;
      
      const country = data.country;
      const location = data.location;
      if (country.length > 0) {
        setSelectedCountry(country); 
      }
      
      form.setFieldsValue({ name, description ,countryId:country,locationId:location});
      const contentData =data.worWeekDayContent
      setWeekDayContent(contentData);
      if (contentData.length > 1) {
        setWeekVal(weekVal+1);
        setWeekDefinitionCount(weekDefinitionCount+1);

        let week1 = [];
        week1 = contentData[0];
      
        _.forEach(week1, (item) => {
          if (item.workShiftId !== null) {
          let key= '';
          if (item.id == 1) {
              key ="mon";
          } else if (item.id == 2) {
              key ="tue";
          } else if (item.id == 3) {
              key ="wed";
          } else if (item.id == 4) {
              key ="thu";
          } else if (item.id == 5) {
              key ="fri";
          } else if (item.id == 6) {
              key ="sat";
          } else if (item.id == 0) {
              key ="sun";
          }
      
          weekTable1.push({shiftId:item.workShiftId ,weekIndex: 'Week 2', label: key, values: true , name: item.name , color: item.color});
          let weekData = [];
          const week1 = weekTable1.map((item) => {
            weekData[item.label] = {
              name: item.name,
              color: item.color,
              shiftId : item.shiftId,
              index: 'Week 1'
            }
            return weekData;
          })

          setCurrentValuesWeek(week1);
        }
        });
        
        let week2 = [];
        week2 = contentData[1];
      
        _.forEach(week2, (item) => {
          if (item.workShiftId !== null) {
          let key= '';
          if (item.id == 1) {
            key ="mon";
          } else if (item.id == 2) {
            key ="tue";
          } else if (item.id == 3) {
            key ="wed";
          } else if (item.id == 4) {
            key ="thu";
          } else if (item.id == 5) {
            key ="fri";
          } else if (item.id == 6) {
            key ="sat";
          } else if (item.id == 0) {
            key ="sun";
          }
    
          weekTable2.push({shiftId:item.workShiftId ,weekIndex: 'Week 2', label: key, values: true , name: item.name ,  color: item.color});
          let weekData = [];
          const week2 = weekTable2.map((item) => {
            weekData[item.label] = {
              name: item.name,
              color: item.color,
              shiftId : item.shiftId,
              index: 'Week 2'
            }
            return weekData;
          });
         
          setCurrentValuesWeek2(week2);
        }
        });
      } else {
        let week1= [];
        week1 = contentData[0];
      
        _.forEach(week1, (item) => {
          if (item.workShiftId !== null) {
          let key= '';
          if (item.id == 1) {
            key ="mon";
          } else if (item.id == 2) {
            key ="tue";
          } else if (item.id == 3) {
            key ="wed";
          } else if (item.id == 4) {
            key ="thu";
          } else if (item.id == 5) {
            key ="fri";
          } else if (item.id == 6) {
            key ="sat";
          } else if (item.id == 0) {
            key ="sun";
          }

          weekTable1.push({shiftId:item.workShiftId ,weekIndex: 'Week 1', label: key, values: true , name: item.name ,  color: item.color});
          let weekData = [];
          const week1 = weekTable1.map((item) => {
            weekData[item.label] = {
              name: item.name,
              color: item.color,
              shiftId : item.shiftId,
              index: 'Week 1'
            }
            return weekData;
          });

          setCurrentValuesWeek(week1);
        }
        });
                
      }
      setLoading(false); 
    };
    try {
      fetchData(); 
    } catch(error) {
       setLoading(false); 
       console.log(error);
    } 
  },[id])   

  
  const countryData = async() =>{
    const {data} = await getCountriesList({});
    setCountries(data);
  }
  const onChangeCountry =(value) =>{
    const newId= [...selectedCountry,value];
    setSelectedCountry(newId);
  }
  
 const menu = (index) => (
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
        workShifts.map((shift) => {
          return (
            <Menu.Item key={shift.id} >
              <a onClick={async () => {

                setShiftId(shift.id);
                const { data } = await getWorkShifts(shift.id);

                setLoading(true);
                if (index === 'Week 1') {
                  let weekData = [];
                  let shiftDataArray = [];
      
                  shiftDataArray = weekTable1.map((shiftData) => {
                    if (!shiftData.hasOwnProperty('color')) {
                      shiftData.color = data.color;
                      shiftData.name = data.name;
                      shiftData.shiftId = data.workShiftId;
                    }
                    return shiftData;
                  });
                  const week1 = shiftDataArray.map((item) => {
                    weekData[item.label] = {
                      name: item.name,
                      color: item.color,
                      shiftId : item.shiftId,
                      index: 'Week 1'
                    }
                    return weekData;
                  })

                  setCurrentValuesWeek(week1);
                  setWeekTable1(shiftDataArray);
                  setWeekDefinitionValues([]);
                }
                if (index === 'Week 2') {
                  let weekData = [];
                  let shiftDataArray = [];
                  shiftDataArray = weekTable2.map((shiftData) => {
                    if (!shiftData.hasOwnProperty('color')) {
                      shiftData.color = data.color;
                      shiftData.name = data.name;
                      shiftData.shiftId = data.workShiftId;
                    }
                    return shiftData;
                  });
                  const week2 = shiftDataArray.map((item) => {
                    weekData[item.label] = {
                      name: item.name,
                      color: item.color,
                      shiftId : item.shiftId,
                      index: 'Week 2'
                    }
                    return weekData;
                  })

                  setCurrentValuesWeek2(week2);
                  setWeekTable2(shiftDataArray);
                  setWeekDefinitionValuesWeek2([]);
                }

                setLoading(false);
              }}>
                <Tag color={shift.color} key={shift.name} style={{ color: '#036713' }} className={styles.myTag} >
                  {shift.name}
                </Tag>

              </a>
            </Menu.Item>

          )
        })
      }

    </Menu>
  );
 
  
  const onChangeWeekAllDays = (label: any, value: any, weekIndex: any) => {
    if (label === "alldays" && value === true) {
      let days = ["mon", "tue", "wed", "thu", "fri", "sat", "sun"];
      let weekDefinitionData = [];
      days.forEach((day) => {
        let weekDefinition = { label: day, values: value, weekIndex: weekIndex };
        weekDefinitionData.push(weekDefinition);

      });
      if (weekIndex === 'Week 1') {
        setWeekTable1(weekDefinitionData);
        setWeekDefinitionValues(weekDefinitionData);
      } else {
         setWeekDefinitionValuesWeek2(weekDefinitionData);
         setWeekTable2(weekDefinitionData);
      }
    }
  }

  const onChangeWeekDefnition = (label: any, value: any, weekIndex) => {

    if (weekDefinitionValues.find(x => x.label === label && x.weekIndex === weekIndex)) {

      weekDefinitionValues.splice(weekDefinitionValues.findIndex(a => a.label === label && a.weekIndex === weekIndex), 1)
      let weekDefinitionData = [];
      let weekData = [];
      let weekDefinition = { label: label, values: value, weekIndex: weekIndex };

      if (weekIndex === 'Week 1') {
        weekDefinitionData.push(...weekDefinitionValues, weekDefinition);
        weekData.push(...weekTable1, weekDefinition);
        setWeekDefinitionValues(weekDefinitionData);
        setWeekTable1(weekData);
      } else {
        weekDefinitionData.push(...weekDefinitionValuesWeek2, weekDefinition);
        weekData.push(...weekTable2, weekDefinition);
        setWeekDefinitionValuesWeek2(weekDefinitionData);
        setWeekTable2(weekData);
      }
    } else {

      let weekDefinitionData = [];
      let weekData = [];
      let weekDefinition = { label: label, values: value, weekIndex: weekIndex };

      if (weekIndex === 'Week 1') {
        weekDefinitionData.push(...weekDefinitionValues, weekDefinition);
        weekData.push(...weekTable1, weekDefinition);
        setWeekDefinitionValues(weekDefinitionData);
        setWeekTable1(weekData);
      } else {
        weekDefinitionData.push(...weekDefinitionValuesWeek2, weekDefinition);
        weekData.push(...weekTable2, weekDefinition);
        setWeekDefinitionValuesWeek2(weekDefinitionData);
        setWeekTable2(weekData);
      }

    }

  }


  const addWeekDefinition = () => {
    if (weekDefinitionCount < 2 && currentValuesWeek.length > 0) {
      setClone(true);
      setWeekDefinitionCount(weekDefinitionCount + 1);

      let weekData = [];
      const weekDefinition = [];

      const cloneData = [...weekTable1];
      _.forEach(cloneData, (item) => {
        weekDefinition.push(item);
      });

      const cloneWeek = _.forEach(weekDefinition, (item) => {
        item.weekIndex = 'Week 2';
        return item;
      });
      const week2 = weekDefinition.map((item) => {
        weekData[item.label] = {
          name: item.name,
          color: item.color,
          index: 'Week 2'
        }
        return weekData;
      });


      setWeekTable2(cloneWeek);
      setCurrentValuesWeek2(week2);
      setWeekDefinitionValuesWeek2([]);

    }
  }

  const deleteWeekId = async () => {

    if (weekDefinitionCount > 1) {
      setWeekDefinitionCount(weekDefinitionCount-1);
      setWeekTable2([]);
      setCurrentValuesWeek2([]);
      setWeekDefinitionValuesWeek2([]);
      const weekIndex = {
        weekIndexId :2
      }
      const {message} = await deleteWeek(id,weekIndex);
      Message.success(message);
    }
  }

  const weekOneColumns = [
    {
      title: 'All',
      dataIndex: 'alldays',
      key: 'alldays',
      render: (record, index) => {
        let allDayChecked = [];
        let dayValue = [];
        if (weekDefinitionValues.length > 0) {
          allDayChecked = weekDefinitionValues.filter(x => x.weekIndex === index['days'] && x.values === true);
        }
        if (currentValuesWeek.length > 0 && currentValuesWeek[0][index['days']]) {
          dayValue = currentValuesWeek[0][index['days']];
        }
        return (
          <Col span={24} className={styles.tagTooltip}>
            <Row className={styles.shiftTableCell}>
              <Tooltip title={dayValue.name}>
                <Checkbox 
                 className={styles.tableCellCheckbox}
                 onChange={(value) => {
                  let label = 'alldays';
                  if (value.target.checked) {
                    onChangeWeekAllDays(label, value.target.checked, index['days']);
                    setDisabled(false);
                  } else {
                    setWeekDefinitionValues([]);
                    setDisabled(true);
                  }
                }}
                  checked={allDayChecked.length > 0 && allDayChecked.length > 6}
                />
                {!_.isEmpty(dayValue) && <Tag color={dayValue.color} key={dayValue.name} style={{ color: '#036713' }} className={styles.shiftTag} >
                  {dayValue.name}
                </Tag>
                }
              </Tooltip>
            </Row>
          </Col>
        )
      },
      width: '80px'
    }
  ];

  tableHeader.forEach(element => {
    let day = element.toLowerCase();
    weekOneColumns.push({
      title: element,
      dataIndex: day,
      key: day,
      width: '180px',
      render: (string, index) => {

        let newArray = [];
        let dayValue = [];
        if (weekDefinitionValues.length > 0) {
          newArray = weekDefinitionValues.filter(x => x.label === day && x.weekIndex === index['days'] && x.values === true);

        }

        let checkedValue = newArray.length > 0 ?? (newArray.filter(x => x.label === day).weekIndex === index['days'] && (newArray.filter(x => x.label === day).values));

        if (currentValuesWeek.length > 0 && currentValuesWeek[0][day] && currentValuesWeek[0][day].index === index['days']) {
          dayValue = currentValuesWeek[0][day];
        }
        return (
          <Col span={24} className={styles.tagTooltip}>
            <Row className={styles.shiftTableCell}>
              <Tooltip title={dayValue.name}>
                <Checkbox
                  className={styles.tableCellCheckbox}
                  onChange={(value) => {
                    if (value.target.checked) {
                      onChangeWeekDefnition(day, value.target.checked, index['days']);
                      setDisabled(false);
                    } else {
                      const index = weekDefinitionValues.findIndex(a => a.label === day);
                  
                      const unchekedValue= weekDefinitionValues.splice(index,1) ;
                      const filteredData= weekDefinitionValues.filter(item => {
                         return item.label !== unchekedValue[0].label;
                      });
                  
                      setWeekDefinitionValues(filteredData);

                      const unchekedValueWeek = weekTable1.splice(weekTable1.findIndex(a => a.label === day),1);
              
                      const filteredTableData= weekTable1.filter(item => {
                         return item.label !== unchekedValueWeek[0].label;
                      });
                  
                      setWeekTable1(filteredTableData);
                      if (weekDefinitionValues.length == 0) {
                        setDisabled(true);
                      }
                    }

                  }}
                  checked={checkedValue}
                />
                {!_.isEmpty(dayValue) && <Tag color={dayValue.color} key={dayValue.name} style={{ color: '#036713' }} className={styles.shiftTag} >
                  {dayValue.name}
                </Tag>
                }
              </Tooltip>
            </Row>
          </Col>
        )
      }

    });
  });


  let columnData = [];
  if (weekDefinitionCount > 0) {
    for (let i = 1; i <= weekDefinitionCount; i++) {
      columnData = [
        {
          days: `${intl.formatMessage({
            id: 'week1',
            defaultMessage: 'Week 1',
          })}`,
        }

      ]
    }
  }

  let cloneWeekData = [];
  if (weekDefinitionCount > 1) {
    cloneWeekData.push(
      {
        days: `${intl.formatMessage({
          id: 'week2',
          defaultMessage: 'Week 2',
        })}`,
      }
    )
  }
  const weekTwoColumns = [
    {
      title: 'All',
      dataIndex: 'alldays',
      key: 'alldays',
      render: (record, index) => {
        let allDayChecked = [];
        let dayValue = [];
        if (weekDefinitionValuesWeek2.length > 0) {
          allDayChecked = weekDefinitionValuesWeek2.filter(x => x.weekIndex === index['days'] && x.values === true);
        }
        if (currentValuesWeek2.length > 0 && currentValuesWeek2[0][index['days']]) {
          dayValue = currentValuesWeek2[0][index['days']];
        }
        return (
          <Col span={24} className={styles.tagTooltip}>
            <Row className={styles.shiftTableCell}>
              <Tooltip title={dayValue.name}>
                <Checkbox 
                className={styles.tableCellCheckbox}
                onChange={(value) => {
                  let label = 'alldays';
                  if (value.target.checked) {
                    onChangeWeekAllDays(label, value.target.checked, index['days']);
                    setDisabledWeek2(false);
                  } else {
                    setWeekDefinitionValuesWeek2([]);
                    setDisabledWeek2(true);
                  }
                }}
                  checked={allDayChecked.length > 0 && allDayChecked.length > 6}
                />
                {!_.isEmpty(dayValue) && <Tag color={dayValue.color} key={dayValue.name} style={{ color: '#036713' }} className={styles.shiftTag} >
                  {dayValue.name}
                </Tag>
                }
              </Tooltip>
            </Row>
          </Col>
        )
      },
      width: '80px'
    }
  ];

  tableHeader.forEach(element => {
    let day = element.toLowerCase();
    weekTwoColumns.push({
      title: element,
      dataIndex: day,
      key: day,
      width: '180px',
      render: (string, index) => {

        let newArray = [];
        let dayValue = [];
        if (weekDefinitionValuesWeek2.length > 0) {
          newArray = weekDefinitionValuesWeek2.filter(x => x.label === day && x.weekIndex === index['days'] && x.values === true);

        }

        let checkedValue = newArray.length > 0 ?? (newArray.filter(x => x.label === day).weekIndex === index['days'] && (newArray.filter(x => x.label === day).values));

        if (currentValuesWeek2.length > 0 && currentValuesWeek2[0][day] && currentValuesWeek2[0][day].index === index['days']) {
          dayValue = currentValuesWeek2[0][day];
        }
        return (
          <Col span={24} className={styles.tagTooltip}>
            <Row className={styles.shiftTableCell}>
              <Tooltip title={dayValue.name}>
                <Checkbox
                  className={styles.tableCellCheckbox}
                  onChange={(value) => {
                    if (value.target.checked) {
                      onChangeWeekDefnition(day, value.target.checked, index['days']);
                      setDisabledWeek2(false);
                    } else {
                      const index = weekDefinitionValuesWeek2.findIndex(a => a.label === day);
                  
                      const unchekedValue= weekDefinitionValuesWeek2.splice(index,1) ;
                      const filteredData= weekDefinitionValuesWeek2.filter(item => {
                         return item.label !== unchekedValue[0].label;
                      });
                  
                      setWeekDefinitionValuesWeek2(filteredData);

                      const unchekedValueWeek = weekTable2.splice(weekTable1.findIndex(a => a.label === day),1);
              
                      const filteredTableData= weekTable2.filter(item => {
                         return item.label !== unchekedValueWeek[0].label;
                      });
                  
                      setWeekTable2(filteredTableData);
                      if (weekDefinitionValuesWeek2.length == 0) {
                        setDisabledWeek2(true);
                      }
                    }

                  }}
                  checked={checkedValue}
                />
                {!_.isEmpty(dayValue) && <Tag color={dayValue.color} key={dayValue.name} style={{ color: '#036713' }} className={styles.shiftTag} >
                  {dayValue.name}
                </Tag>


                }
              </Tooltip>
            </Row>
          </Col>
        )
      }

    });
  });
  return (
    <Access
      accessible={hasPermitted('work-pattern-read-write')}
      fallback={<PermissionDeniedPage />}
    >
    <PageContainer loading={loading}>
      {loading ? ( <Spin></Spin>) : 
      (
        <>   
          <Card>
            <Col offset={1} span={20}>
              <Form
                form={form}
                layout="vertical"
                onFinish={onFinish}
              >
             <Col span={10}>
                <Form.Item
                  name="name"
                  label={intl.formatMessage({
                    id: 'Name of Work Pattern',
                    defaultMessage: 'Name of Work Pattern',
                  })}
                  rules={[{ required: true, message: 'Name is required.' }, { max: 100 } ]}       
                >
                 <Input />
                </Form.Item>
              </Col>
              <Col span={10}>
                <Form.Item name="description" label={intl.formatMessage({
                id: 'Description',
                defaultMessage: 'Description',
              })} rules={[{ max: 250 }]}>
                  <TextArea rows={4} />
                </Form.Item>
              </Col>
              <Row>
                <Col span={4}>
                  <Text
                    style={{
                      fontStyle: 'normal',
                      fontWeight: 550,
                      fontSize: '16px',
                      lineHeight: 3
                    }}>
                    {intl.formatMessage({
                      id: 'pattern.definition',
                      defaultMessage: 'Pattern Definition'
                    })
                    }

                  </Text>
                </Col>
                <Col span={20}>
                  <Divider />
                </Col>
              </Row>
              <Row>
                <Col span={4} style={{ marginTop: 20 }}>
                  <Text style={{ fontWeight: 550 }}>
                    {intl.formatMessage({
                      id: 'pattern.week',
                      defaultMessage: 'Week 01'
                    })}
                  </Text>
                </Col>
                <Col span={20} style={{ textAlign: 'right' }}>
                  <Space>
                    <Col span={12} className={styles.changeShiftCol}>
                      <Dropdown
                        overlay={menu('Week 1')} placement="bottomLeft"
                        placement="bottomRight"
                        className={disabled ? styles.changeShiftDisabled : styles.changeShift}
                        disabled={disabled}
                      >
                        <Button className={disabled ? styles.changeShiftDisabled : styles.changeShift}>

                          <p className={disabled ? styles.buttonTextDisabled : styles.buttonText}>
                            <Space>
                              <span className={styles.assignText}>
                                {intl.formatMessage({
                                  id: 'shift.changeShiftButton',
                                  defaultMessage: 'Assign Shifts'
                                })}
                              </span>
                              {disabled ? <LineOutlinedIconDisabled /> : <LineOutlinedIcon />}
                              {disabled ? <DropDownIconDisabled /> : <DropdownIcon />}
                            </Space>
                          </p>
                        </Button>
                      </Dropdown>

                    </Col>
                    <Col className={styles.changeShiftCol} style={{ textAlign: 'right' }}>
                      <Button
                        htmlType="button"
                        onClick={addWeekDefinition}
                        icon={<Image src={CloneIcon} preview={false}  height={20} style={{ paddingTop:4}}/>}
                        className={styles.cloneWeek}
                        disabled={weekDefinitionCount ==2}
                      >
                        <span className={styles.cloneText} >
                          {intl.formatMessage({
                            id: 'shift.changeShiftButton',
                            defaultMessage: 'Clone Week'
                          })
                          }
                        </span>
                      </Button>
                    </Col>
                  </Space>
                </Col>
              </Row>
              <br />
              <Table columns={weekOneColumns} dataSource={columnData} pagination={false} scroll={{ y: 400 }} loading={loading} />

              <br />
              {weekDefinitionCount > 1 &&
                <>
                  <Row>
                    <Col span={4} style={{ marginTop: 20 }}>
                      <Text style={{ fontWeight: 550 }}>
                        {intl.formatMessage({
                          id: 'pattern.week',
                          defaultMessage: 'Week 02'
                        })}
                      </Text>
                    </Col>
                    <Col span={20} style={{ textAlign: 'right' }}>
                      <Space>
                        <Col span={12} className={styles.changeShiftCol}>
                          <Dropdown
                            overlay={menu('Week 2')} placement="bottomLeft"
                            placement="bottomRight"
                            className={disabledWeek2 ? styles.changeShiftDisabled : styles.changeShift}
                            disabled={disabledWeek2}
                          >
                            <Button className={disabledWeek2 ? styles.changeShiftDisabled : styles.changeShift}>

                              <p className={disabledWeek2 ? styles.buttonTextDisabled : styles.buttonText}>
                                <Space>
                                  <span className={styles.assignText}>
                                    {intl.formatMessage({
                                      id: 'shift.changeShiftButton',
                                      defaultMessage: 'Assign Shifts'
                                    })}
                                  </span>
                                  {disabledWeek2 ? <LineOutlinedIconDisabled /> : <LineOutlinedIcon />}
                                  {disabledWeek2 ? <DropDownIconDisabled /> : <DropdownIcon />}
                                </Space>
                              </p>
                            </Button>
                          </Dropdown>

                        </Col>
                        {/* <Col className={styles.changeShiftCol} style={{ textAlign: 'right' }}>
                          <Button
                            htmlType="button"
                            onClick={addWeekDefinition}
                            icon={<Image src={CloneIcon} preview={false} />}
                            className={styles.cloneWeek}
                          >
                            <span className={styles.cloneText} >
                              {intl.formatMessage({
                                id: 'shift.changeShiftButton',
                                defaultMessage: 'Clone Week'
                              })
                              }
                            </span>
                          </Button>
                        </Col> */}
                        <Col className={styles.changeShiftCol}>
                          <Popconfirm
                            key="delete-pop-confirm"
                            placement="topRight"
                            title="Are you sure?"
                            okText="Yes"
                            cancelText="No"
                            onConfirm={deleteWeekId}
                          >
                            <Tooltip key="delete-tool-tip" title="Delete">
                              <Button htmlType="button" 
                                style={{ 
                                  background: '#FFFFFF',
                                  border: '1px solid #B8B7B7',
                                  borderRadius: '6px'
                                 }}
                              >
                                <DeleteOutlined /> {intl.formatMessage({
                                  id:'pattern.deleteWeek',
                                  defaultMessage : 'Delete Week'
                                })}
                              </Button>
                            </Tooltip>
                          </Popconfirm>
                        </Col>
                      </Space>
                    </Col>
                  </Row>
                  <br />
                  <Table columns={weekTwoColumns} dataSource={cloneWeekData} pagination={false} scroll={{ y: 400 }} loading={loading} />

                  <br />
                </>
              }
              <>
                {/* <Row>
                  <Text
                    style={{
                      fontStyle: 'normal',
                      fontWeight: 550,
                      fontSize: '16px',
                      lineHeight: 3
                    }}

                  >
                    {intl.formatMessage({
                      id: 'pattern.setUp',
                      defaultMessage: 'Setup Work Pattern'
                    })}
                  </Text>
                </Row> */}
                {/* <Row>
                  <Col span={10}>
                    <Form.Item
                      name="countryId"
                      label={intl.formatMessage({
                        id: 'pattern.country',
                        defaultMessage: 'Countries'
                      })}>
                      <Select
                        showSearch
                        style={{ width: 340 }}
                        mode='multiple'
                        placeholder="Select Country"
                        onChange={onChangeCountry}
                        optionFilterProp="children"
                      >
                        {countries.map((country) => {
                          return (
                            <Option key={country.id} value={country.id} >
                              {country.name}
                            </Option>
                          );
                        })}
                      </Select>
                    </Form.Item>
                  </Col>
                </Row> */}
                {/* <Row>
                  <Col span={10}>
                    <Form.Item
                      name="locationId"
                      label={intl.formatMessage({
                        id: 'pattern.location',
                        defaultMessage: 'Locations'
                      })}
                    >
                      <Select
                        showSearch
                        style={{ width: 340 }}
                        mode='multiple'
                        placeholder="Select Location"
                        optionFilterProp="children"
                        onChange={(value) => {
                          if (value) {
                            form.setFields([{
                              name: 'locationId',
                              errors: []
                            }
                            ]);
                          }
                        }}
                      >
                        {locations.map((location) => {
                          return (
                            <Option key={location.id} value={location.id}>
                              {location.name}
                            </Option>
                          );
                        })}
                      </Select>
                    </Form.Item>
                  </Col>
                </Row> */}

                <Row>
                  <Col span={24} style={{ textAlign: 'right' }}>
                    <Form.Item>
                      <Space>
                        <Button
                          htmlType="button"
                          onClick={() => {
                            form.resetFields();
                            setCurrentValuesWeek([]);
                            setCurrentValuesWeek2([]);
                            setWeekTable1([]);
                            setWeekTable2([]);
                            setDisabled(true);
                            setDisabledWeek2(true);
                          }}
                        >
                          {intl.formatMessage({
                            id: 'pattern.Reset',
                            defaultMessage: 'Reset'
                          })}
                        </Button>
                        <Button type="primary" htmlType="submit" >
                          {intl.formatMessage({
                            id: 'pattern.update',
                            defaultMessage: 'Update'
                          })}
                        </Button>
                      </Space>
                    </Form.Item>
                  </Col>
                </Row>
              </>
        </Form>
      </Col>
    </Card>
  </>
)}
</PageContainer>
</Access>  
  );
};
