import React from "react";
import { Table, Select, TimePicker ,Input ,Badge ,Tooltip} from 'antd';
import moment from 'moment';
moment.locale('en-US')
import _ from 'lodash';
import '../weekTable.css';
import { useIntl } from 'umi';

const { Option } = Select;


interface IProps {
  tableIndex :any;
  currentValuesWeek : any[],
  error :any[],
  workShifts:[],
}

const WeekTable: React.FC<IProps> = ({ onChange , tableIndex , currentValuesWeek, error,workShifts}) => {
 
  const intl = useIntl();
    const columns = [
        {
          title: `${intl.formatMessage({
            id: 'Time',
            defaultMessage: 'Time',
          })}`,
          dataIndex: 'time',
          key: 'time',
        },
        {
          title: 
          <>
           {intl.formatMessage({
              id: 'mon',
              defaultMessage: 'Mon',
            })}&nbsp;
            {((error && error.mon) || (currentValuesWeek && currentValuesWeek['midnight'].mon == '1' )) &&
              <Tooltip title="Midnight Crossover" color="gold" >
                <Badge status='warning' dot={true} /> 
              </Tooltip>  
            }
          </>,
          dataIndex: 'mon',
          key: 'mon',
        },
        {
          title: 
          <>
          {intl.formatMessage({
              id: 'tue',
              defaultMessage: 'Tue',
            })}&nbsp;
          {((error && error.tue ) || (currentValuesWeek && currentValuesWeek['midnight'].tue == 1 )) &&
              <Tooltip title="Midnight Crossover" color="gold" >
                <Badge status='warning' dot={true} /> 
              </Tooltip>  
          }
        </>,
          dataIndex: 'tue',
          key: 'tue',
        },
        {
          title: 
          <>
         {intl.formatMessage({
              id: 'wed',
              defaultMessage: 'Wed',
          })}&nbsp;
          {((error && error.wed ) || (currentValuesWeek && currentValuesWeek['midnight'].wed == 1 ))  &&
              <Tooltip title="Midnight Crossover" color="gold" >
                <Badge status='warning' dot={true} /> 
              </Tooltip>  
            }
          </>,
          dataIndex: 'wed',
          key: 'wed',
        },
        {
          title: 
          <>
         {intl.formatMessage({
              id: 'thu',
              defaultMessage: 'Thu',
          })}&nbsp;
          {((error && error.thu) || (currentValuesWeek && currentValuesWeek['midnight'].thu == 1 ))  &&
              <Tooltip title="Midnight Crossover" color="gold" >
                <Badge status='warning' dot={true} /> 
              </Tooltip>  
          }
          
          </>,
          dataIndex: 'thu',
          key: 'thu',
        },
        {
          title: 
          <>
         {intl.formatMessage({
              id: 'fri',
              defaultMessage: 'Fri',
          })}&nbsp;
          {((error && error.fri) || (currentValuesWeek && currentValuesWeek['midnight'].fri == 1 ))  &&
              <Tooltip title="Midnight Crossover" color="gold" >
                <Badge status='warning' dot={true} /> 
              </Tooltip>  
          }
          </>,
          dataIndex: 'fri',
          key: 'fri',
        },
        {
          title: 
          <>
         {intl.formatMessage({
              id: 'sat',
              defaultMessage: 'Sat',
          })}&nbsp;
          {((error && error.sat) || (currentValuesWeek && currentValuesWeek['midnight'].sat == 1 ))  &&
              <Tooltip title="Midnight Crossover" color="gold" >
                <Badge status='warning' dot={true} /> 
              </Tooltip>  
          }
          </>,
          dataIndex: 'sat',
          key: 'sat',
        },
        {
          title: 
          <>
         {intl.formatMessage({
              id: 'sun',
              defaultMessage: 'Sun',
          })}&nbsp;
          {((error && error.sun) || (currentValuesWeek && currentValuesWeek['midnight'].sun == 1 ))  &&
              <Tooltip title="Midnight Crossover" color="gold" >
                <Badge status='warning' dot={true} /> 
              </Tooltip>  
          }
          </>,
          dataIndex: 'sun',
          key: 'sun',
        },    
      ];
      
      const data = [     
        {
          time: `${intl.formatMessage({
            id: 'shift',
            defaultMessage: 'Shift',
          })}`,
          mon : <Select 
                  placeholder="Select"  
                  style={{ width: 80 , borderRadius:'6px', background: '#FFFFFF'}} 
                  defaultValue={ currentValuesWeek && currentValuesWeek['shifts'].mon }  
                  allowClear 
                  onChange={(value) =>{
                    let day= '1';
                    let newValue = !_.isNull(value) && !_.isUndefined(value) ? value : '';
                    onChange(newValue,day,tableIndex)
                  }} 
                  value={ currentValuesWeek && currentValuesWeek['shifts'].mon }
                >
                  { workShifts.map((shifts) => {
                      return (
                        <Option key={shifts.id} value={shifts.id}>
                          {shifts.name}
                        </Option>
                        );
                    })
                  }
                </Select>,
          tue : <Select 
                  placeholder="Select" 
                  style={{ width: 80 , borderRadius:'6px', background: '#FFFFFF' }} 
                  defaultValue={ currentValuesWeek && currentValuesWeek['shifts'].tue }  
                  allowClear
                  onChange={(value) =>{
                    let day= '2';
                    let newValue = !_.isNull(value) && !_.isUndefined(value) ? value : '';
                    onChange(newValue,day,tableIndex)
                  }} 
                >
                  { workShifts.map((shifts) => {
                      return (
                        <Option key={shifts.id} value={shifts.id}>
                          {shifts.name}
                        </Option>
                        );
                    })
                  }
                </Select>,
          wed : <Select 
                  placeholder="Select" 
                  style={{ width: 80 , borderRadius:'6px', background: '#FFFFFF' }} 
                  defaultValue={ currentValuesWeek && currentValuesWeek['shifts'].wed }  
                  allowClear
                  onChange={(value) =>{
                    let day= '3';
                    let newValue = !_.isNull(value) && !_.isUndefined(value) ? value : '';
                    onChange(newValue,day,tableIndex)
                  }}
                >
                  { workShifts.map((shifts) => {
                      return (
                        <Option key={shifts.id} value={shifts.id}>
                          {shifts.name}
                        </Option>
                        );
                    })
                  }
                </Select>,
          thu : <Select 
                  placeholder="Select" 
                  style={{ width: 80 , borderRadius:'6px', background: '#FFFFFF' }} 
                  defaultValue={ currentValuesWeek && currentValuesWeek['shifts'].thu }  
                  allowClear
                  onChange={(value) =>{
                    let day= '4';
                    let newValue = !_.isNull(value) && !_.isUndefined(value) ? value : '';
                    onChange(newValue,day,tableIndex)
                  }}
                >
                  { workShifts.map((shifts) => {
                      return (
                        <Option key={shifts.id} value={shifts.id}>
                          {shifts.name}
                        </Option>
                        );
                    })
                  }
                 </Select>,
          fri : <Select 
                  placeholder="Select" 
                  style={{ width: 80 , borderRadius:'6px', background: '#FFFFFF' }} 
                  defaultValue={ currentValuesWeek && currentValuesWeek['shifts'].fri }  
                  allowClear
                  onChange={(value) =>{
                    let day= '5';
                    let newValue = !_.isNull(value) && !_.isUndefined(value) ? value : '';
                    onChange(newValue,day,tableIndex)
                  }}
                >
                   { workShifts.map((shifts) => {
                      return (
                        <Option key={shifts.id} value={shifts.id}>
                          {shifts.name}
                        </Option>
                        );
                    })
                  }
                </Select>,
          sat : <Select 
                  placeholder="Select" 
                  style={{ width: 80 , borderRadius:'6px', background: '#FFFFFF'}} 
                  defaultValue={ currentValuesWeek && currentValuesWeek['shifts'].sat }  
                  allowClear
                  onChange={(value) =>{
                    let day= '6';
                    let newValue = !_.isNull(value) && !_.isUndefined(value) ? value : '';
                    onChange(newValue,day,tableIndex)
                  }}
                >
                  { workShifts.map((shifts) => {
                      return (
                        <Option key={shifts.id} value={shifts.id}>
                          {shifts.name}
                        </Option>
                        );
                    })
                  }
                </Select>,
          sun : <Select 
                  placeholder="Select" 
                  style={{ width: 80 , borderRadius:'6px', background: '#FFFFFF' }} 
                  defaultValue={ currentValuesWeek && currentValuesWeek['shifts'].sun }
                  allowClear
                  onChange={(value) =>{
                    let day= '0';
                    let newValue = !_.isNull(value) && !_.isUndefined(value) ? value : '';
                    onChange(newValue,day,tableIndex)
                  }}
                >
                   { workShifts.map((shifts) => {
                      return (
                        <Option key={shifts.id} value={shifts.id}>
                          {shifts.name}
                        </Option>
                        );
                    })
                  }
                </Select>
        },
        {
            time: `${intl.formatMessage({
              id: 'days',
              defaultMessage: 'Days',
            })}`,
            mon : <Select 
                    placeholder="Select"  
                    style={{ width: 80 , borderRadius:'6px', background: '#FFFFFF'}} 
                    defaultValue={ currentValuesWeek && currentValuesWeek['dayVal'].mon == 1 ? "1 Day" :currentValuesWeek && currentValuesWeek['dayVal'].mon == 0.5 ? "0.5 Day" :'Select'}  
                    allowClear 
                    disabled
                    value={ currentValuesWeek && currentValuesWeek['dayVal'].mon == 1 ? "1 Day" :currentValuesWeek && currentValuesWeek['dayVal'].mon == 0.5 ? "0.5 Day" :'Select'}  
                  >
                    <Option value="0.5">0.5 day</Option>
                    <Option value="1">1 day</Option>
                  </Select>,
            tue : <Select 
                    placeholder="Select" 
                    style={{ width: 80 , borderRadius:'6px', background: '#FFFFFF' }} 
                    defaultValue={ currentValuesWeek && currentValuesWeek['dayVal'].tue == 1 ? "1 Day" :currentValuesWeek && currentValuesWeek['dayVal'].tue == 0.5 ? "0.5 Day": "Select"} 
                    disabled
                    allowClear
                    value={ currentValuesWeek && currentValuesWeek['dayVal'].tue == 1 ? "1 Day" :currentValuesWeek && currentValuesWeek['dayVal'].tue == 0.5 ? "0.5 Day": "Select"} 
                  >
                    <Option value="0.5">0.5 day</Option>
                    <Option value="1">1 day</Option>
                  </Select>,
            wed : <Select 
                    placeholder="Select" 
                    style={{ width: 80 , borderRadius:'6px', background: '#FFFFFF' }} 
                    defaultValue={ currentValuesWeek && currentValuesWeek['dayVal'].wed == 1 ? "1 Day" :currentValuesWeek && currentValuesWeek['dayVal'].wed == 0.5 ? "0.5 Day": "Select"} 
                    disabled
                    allowClear
                    value={ currentValuesWeek && currentValuesWeek['dayVal'].wed == 1 ? "1 Day" :currentValuesWeek && currentValuesWeek['dayVal'].wed == 0.5 ? "0.5 Day": "Select"} 
                  >
                    <Option value="0.5">0.5 day</Option>
                    <Option value="1">1 day</Option>
                  </Select>,
            thu : <Select 
                    placeholder="Select" 
                    style={{ width: 80 , borderRadius:'6px', background: '#FFFFFF' }} 
                    defaultValue={ currentValuesWeek && currentValuesWeek['dayVal'].thu == 1 ? "1 Day" :currentValuesWeek && currentValuesWeek['dayVal'].thu == 0.5 ? "0.5 Day": "Select"}  
                    disabled
                    allowClear
                    value={ currentValuesWeek && currentValuesWeek['dayVal'].thu == 1 ? "1 Day" :currentValuesWeek && currentValuesWeek['dayVal'].thu == 0.5 ? "0.5 Day": "Select"}  
                  >
                    <Option value="0.5">0.5 day</Option>
                    <Option value="1">1 day</Option>
                   </Select>,
            fri : <Select 
                    placeholder="Select" 
                    style={{ width: 80 , borderRadius:'6px', background: '#FFFFFF' }} 
                    defaultValue={ currentValuesWeek && currentValuesWeek['dayVal'].fri == 1 ? "1 Day" : currentValuesWeek && currentValuesWeek['dayVal'].fri == 0.5 ? "0.5 Day": "Select"} 
                    disabled
                    allowClear
                    value={ currentValuesWeek && currentValuesWeek['dayVal'].fri == 1 ? "1 Day" : currentValuesWeek && currentValuesWeek['dayVal'].fri == 0.5 ? "0.5 Day": "Select"} 
                  >
                    <Option value="0.5">0.5 day</Option>
                    <Option value="1">1 day</Option>
                  </Select>,
            sat : <Select 
                    placeholder="Select" 
                    style={{ width: 80 , borderRadius:'6px', background: '#FFFFFF'}} 
                    defaultValue={ currentValuesWeek && currentValuesWeek['dayVal'].sat == 1 ? "1 Day" :currentValuesWeek && currentValuesWeek['dayVal'].sat == 0.5 ? "0.5 Day": "Select"} 
                    disabled
                    allowClear
                    value={ currentValuesWeek && currentValuesWeek['dayVal'].sat == 1 ? "1 Day" :currentValuesWeek && currentValuesWeek['dayVal'].sat == 0.5 ? "0.5 Day": "Select"} 
                  >
                    <Option value="0.5">0.5 day</Option>
                    <Option value="1">1 day</Option>
                  </Select>,
            sun : <Select 
                    placeholder="Select" 
                    style={{ width: 80 , borderRadius:'6px', background: '#FFFFFF' }} 
                    defaultValue={ currentValuesWeek && currentValuesWeek['dayVal'].sun == 1 ? "1 Day" : currentValuesWeek && currentValuesWeek['dayVal'].sun == 0.5 ? "0.5 Day": "Select"} 
                    disabled
                    allowClear
                    value={ currentValuesWeek && currentValuesWeek['dayVal'].sun == 1 ? "1 Day" : currentValuesWeek && currentValuesWeek['dayVal'].sun == 0.5 ? "0.5 Day": "Select"} 
                  >
                    <Option value="0.5">0.5 day</Option>
                    <Option value="1">1 day</Option>
                  </Select>
        },
        {
          
          time: `${intl.formatMessage({
            id: 'startTime',
            defaultMessage: 'Start Time',
          })}`,
          mon: <TimePicker 
                use12Hours 
                format="h:mm a" 
                style={{ width: 80 , borderRadius:'6px', background: '#FFFFFF'}} 
                defaultValue={currentValuesWeek && currentValuesWeek['start'].mon && moment((currentValuesWeek['start'].mon), 'h:mm a') } 
                disabled
                placeholder={intl.formatMessage({
                  id: 'startTime',
                  defaultMessage: 'hh:mm',
               })}
               value={currentValuesWeek && currentValuesWeek['start'].mon && moment((currentValuesWeek['start'].mon), 'h:mm a') } 
              />, 
          tue: <TimePicker 
                  use12Hours 
                  format="h:mm a" 
                  style={{ width: 80 , borderRadius:'6px', background: '#FFFFFF' }} 
                  defaultValue={currentValuesWeek && currentValuesWeek['start'].tue && moment((currentValuesWeek['start'].tue), 'h:mm a') } 
                  disabled
                  placeholder={intl.formatMessage({
                    id: 'startTime',
                    defaultMessage: 'hh:mm',
                  })}
                  value={currentValuesWeek && currentValuesWeek['start'].tue && moment((currentValuesWeek['start'].tue), 'h:mm a') } 
                  />,
          wed: <TimePicker 
                  use12Hours 
                  format="h:mm a" 
                  style={{ width: 80 , borderRadius:'6px', background: '#FFFFFF'}} 
                  defaultValue={currentValuesWeek && currentValuesWeek['start'].wed && moment((currentValuesWeek['start'].wed), 'h:mm a') } 
                  disabled
                  placeholder={intl.formatMessage({
                    id: 'startTime',
                    defaultMessage: 'hh:mm',
                 })}
                 value={currentValuesWeek && currentValuesWeek['start'].wed && moment((currentValuesWeek['start'].wed), 'h:mm a') } 
                />,
          thu: <TimePicker 
                  use12Hours 
                  format="h:mm a" 
                  style={{ width: 80 ,borderRadius:'6px', background: '#FFFFFF' }} 
                  defaultValue={currentValuesWeek  && currentValuesWeek['start'].thu && moment((currentValuesWeek['start'].thu), 'h:mm a') } 
                  disabled
                  placeholder={intl.formatMessage({
                    id: 'startTime',
                    defaultMessage: 'hh:mm',
                  })}
                  value={currentValuesWeek  && currentValuesWeek['start'].thu && moment((currentValuesWeek['start'].thu), 'h:mm a') } 
                />,
          fri: <TimePicker 
                  use12Hours 
                  format="h:mm a" 
                  style={{ width: 80, borderRadius: '6px', background: '#FFFFFF' }} 
                  defaultValue={currentValuesWeek && currentValuesWeek['start'].fri && moment((currentValuesWeek['start'].fri), 'h:mm a')} 
                  disabled
                  placeholder={intl.formatMessage({
                    id: 'startTime',
                    defaultMessage: 'hh:mm',
                 })} 
                 value={currentValuesWeek && currentValuesWeek['start'].fri && moment((currentValuesWeek['start'].fri), 'h:mm a')} 
                />,
          sat: <TimePicker 
                  use12Hours 
                  format="h:mm a" 
                  style={{ width: 80 , borderRadius:'6px', background: '#FFFFFF'}} 
                  defaultValue={currentValuesWeek && currentValuesWeek['start'].sat && moment((currentValuesWeek['start'].sat), 'h:mm a') } 
                 
                  disabled
                  placeholder={intl.formatMessage({
                    id: 'startTime',
                    defaultMessage: 'hh:mm',
                 })}
                 value={currentValuesWeek && currentValuesWeek['start'].sat && moment((currentValuesWeek['start'].sat), 'h:mm a') } 
                />,
          sun: <TimePicker 
                  use12Hours 
                  format="h:mm a" 
                  style={{ width: 80 , borderRadius:'6px', background: '#FFFFFF' }} 
                  defaultValue={currentValuesWeek && currentValuesWeek['start'].sun && moment((currentValuesWeek['start'].sun), 'h:mm a') } 
                  disabled
                  placeholder={intl.formatMessage({
                    id: 'startTime',
                    defaultMessage: 'hh:mm',
                  })}
                  value={currentValuesWeek && currentValuesWeek['start'].sun && moment((currentValuesWeek['start'].sun), 'h:mm a') } 
                />
        },
        {
          time: `${intl.formatMessage({
            id: 'End Time',
            defaultMessage: 'End Time',
          })}`,
          mon: <TimePicker 
                  use12Hours 
                  format="h:mm a" 
                  style={{ width: 80 , borderRadius:'6px', background: '#FFFFFF' }} 
                  defaultValue={currentValuesWeek && currentValuesWeek['end'].mon && moment((currentValuesWeek['end'].mon), 'h:mm a') }  
                  disabled
                  className={((error && error.mon) || (currentValuesWeek && currentValuesWeek['midnight'].mon == 1 ))  ? 'time-picker-border' :''}
                  placeholder={intl.formatMessage({
                    id: 'endTime',
                    defaultMessage: 'hh:mm',
                  })}
                  value={currentValuesWeek && currentValuesWeek['end'].mon && moment((currentValuesWeek['end'].mon), 'h:mm a') }  
               />,
          tue: <TimePicker 
                  use12Hours 
                  format="h:mm a" 
                  style={{ width: 80 , borderRadius:'6px', background: '#FFFFFF' }} 
                  defaultValue={currentValuesWeek && currentValuesWeek['end'].tue && moment((currentValuesWeek['end'].tue), 'h:mm a') }  
                  disabled
                  className={((error && error.tue) || (currentValuesWeek && currentValuesWeek['midnight'].tue == 1 ))  ? 'time-picker-border' :''}
                  placeholder={intl.formatMessage({
                    id: 'endTime',
                    defaultMessage: 'hh:mm',
                  })}
                  value={currentValuesWeek && currentValuesWeek['end'].tue && moment((currentValuesWeek['end'].tue), 'h:mm a') } 

               />,
          wed: <TimePicker 
                  use12Hours 
                  format="h:mm a" 
                  style={{ width: 80 , borderRadius:'6px', background: '#FFFFFF' }}  
                  defaultValue={currentValuesWeek && currentValuesWeek['end'].wed && moment((currentValuesWeek['end'].wed), 'h:mm a') } 
                  disabled
                  className={((error && error.wed) || (currentValuesWeek && currentValuesWeek['midnight'].wed == 1 ))  ? 'time-picker-border' :''}
                  placeholder={intl.formatMessage({
                    id: 'endTime',
                    defaultMessage: 'hh:mm',
                  })}
                  value={currentValuesWeek && currentValuesWeek['end'].wed && moment((currentValuesWeek['end'].wed), 'h:mm a') } 
               />,
          thu: <TimePicker 
                  use12Hours 
                  format="h:mm a" 
                  style={{ width: 80 , borderRadius:'6px', background: '#FFFFFF'}}  
                  defaultValue={currentValuesWeek && currentValuesWeek['end'].thu && moment((currentValuesWeek['end'].thu), 'h:mm a') } 
                  disabled
                  className={((error && error.thu) || (currentValuesWeek && currentValuesWeek['midnight'].thu == 1 ))  ? 'time-picker-border' :''}
                  placeholder={intl.formatMessage({
                    id: 'endTime',
                    defaultMessage: 'hh:mm',
                 })}
                 value={currentValuesWeek && currentValuesWeek['end'].thu && moment((currentValuesWeek['end'].thu), 'h:mm a') } 
                />,
          fri: <TimePicker 
                  use12Hours 
                  format="h:mm a" 
                  style={{ width: 80 , borderRadius:'6px', background: '#FFFFFF'}}  
                  defaultValue={currentValuesWeek && currentValuesWeek['end'].fri && moment((currentValuesWeek['end'].fri), 'h:mm a') } 
                  disabled
                  className={((error && error.fri) || (currentValuesWeek && currentValuesWeek['midnight'].fri == 1 ))  ? 'time-picker-border' :''} 
                  placeholder={intl.formatMessage({
                    id: 'endTime',
                    defaultMessage: 'hh:mm',
                  })}
                  value={currentValuesWeek && currentValuesWeek['end'].fri && moment((currentValuesWeek['end'].fri), 'h:mm a') } 
               />,
          sat: <TimePicker 
                  use12Hours 
                  format="h:mm a" 
                  style={{ width: 80 , borderRadius:'6px', background: '#FFFFFF'}}  
                  defaultValue={currentValuesWeek && currentValuesWeek['end'].sat && moment((currentValuesWeek['end'].sat), 'h:mm a') } 
                  disabled
                  className={((error && error.sat) || (currentValuesWeek && currentValuesWeek['midnight'].sat == 1 ))  ? 'time-picker-border' :''}
                  placeholder={intl.formatMessage({
                    id: 'endTime',
                    defaultMessage: 'hh:mm',
                  })}
                  value={currentValuesWeek && currentValuesWeek['end'].sat && moment((currentValuesWeek['end'].sat), 'h:mm a') } 
                />,
          sun: <TimePicker 
                  use12Hours 
                  format="h:mm a" 
                  style={{ width: 80 , borderRadius:'6px', background: '#FFFFFF'}}   
                  defaultValue={currentValuesWeek && currentValuesWeek['end'].sun && moment((currentValuesWeek['end'].sun), 'h:mm a') } 
                  disabled
                  className={((error && error.sun) || (currentValuesWeek && currentValuesWeek['midnight'].sun == 1 ))  ? 'time-picker-border' :''}
                  placeholder={intl.formatMessage({
                    id: 'endTime',
                    defaultMessage: 'hh:mm',
                  })}
                  value={currentValuesWeek && currentValuesWeek['end'].sun && moment((currentValuesWeek['end'].sun), 'h:mm a') } 

                />
        },
        {
          time:`${intl.formatMessage({
            id: 'Break Time',
            defaultMessage: 'Break',
          })}`,
          mon: <TimePicker 
                  minuteStep={5}  
                  format= "HH:mm" 
                  style={{ width: 80 , borderRadius:'6px', background: '#FFFFFF'}} 
                  defaultValue={ currentValuesWeek && currentValuesWeek['break'].mon && moment((currentValuesWeek['break'].mon), 'hh:mm ') }  
                  disabled
                  allowClear 
                  placeholder={intl.formatMessage({
                    id: 'Break Time',
                    defaultMessage: 'hh:mm',
                  })} 
                  value={ currentValuesWeek && currentValuesWeek['break'].mon && moment((currentValuesWeek['break'].mon), 'hh:mm ') }  

                /> , 
          tue:  <TimePicker 
                  minuteStep={5}  
                  format= "HH:mm" 
                  style={{ width: 80 , borderRadius:'6px', background: '#FFFFFF'}} 
                  defaultValue={ currentValuesWeek && currentValuesWeek['break'].tue && moment((currentValuesWeek['break'].tue), 'hh:mm ')} 
                  disabled
                  allowClear 
                  placeholder={intl.formatMessage({
                    id: 'Break Time',
                    defaultMessage: 'hh:mm',
                  })} 
                  value={ currentValuesWeek && currentValuesWeek['break'].tue && moment((currentValuesWeek['break'].tue), 'hh:mm ') }  

                /> ,
          wed:  <TimePicker 
                  minuteStep={5}  
                  format= "HH:mm" 
                  style={{ width: 80 , borderRadius:'6px', background: '#FFFFFF'}} 
                  defaultValue={ currentValuesWeek && currentValuesWeek['break'].wed && moment((currentValuesWeek['break'].wed), 'hh:mm ')} 
                  disabled
                  allowClear 
                  placeholder={intl.formatMessage({
                    id: 'Break Time',
                    defaultMessage: 'hh:mm',
                  })} 
                  value={ currentValuesWeek && currentValuesWeek['break'].wed && moment((currentValuesWeek['break'].wed), 'hh:mm ') }  
                /> ,
          thu: <TimePicker 
                  minuteStep={5}  
                  format= "HH:mm"  
                  style={{ width: 80 , borderRadius:'6px', background: '#FFFFFF'}} 
                  defaultValue={ currentValuesWeek && currentValuesWeek['break'].thu && moment((currentValuesWeek['break'].thu), 'hh:mm ')} 
                  disabled
                  allowClear 
                  placeholder={intl.formatMessage({
                    id: 'Break Time',
                    defaultMessage: 'hh:mm',
                  })} 
                  value={ currentValuesWeek && currentValuesWeek['break'].thu && moment((currentValuesWeek['break'].thu), 'hh:mm ') }  
                /> ,
          fri: <TimePicker 
                  minuteStep={5}  
                  format= "HH:mm" 
                  style={{ width: 80 , borderRadius:'6px', background: '#FFFFFF'}} 
                  defaultValue={ currentValuesWeek && currentValuesWeek['break'].fri && moment((currentValuesWeek['break'].fri), 'hh:mm ')} 
                  disabled
                  allowClear 
                  placeholder={intl.formatMessage({
                    id: 'Break Time',
                    defaultMessage: 'hh:mm',
                  })} 
                  value={ currentValuesWeek && currentValuesWeek['break'].fri && moment((currentValuesWeek['break'].fri), 'hh:mm ') }  
                /> ,
          sat:<TimePicker 
                minuteStep={5}  
                format= "HH:mm" 
                style={{ width: 80 , borderRadius:'6px', background: '#FFFFFF'}} 
                defaultValue={ currentValuesWeek && currentValuesWeek['break'].sat && moment((currentValuesWeek['break'].sat), 'hh:mm ')} 
                disabled
                allowClear 
                placeholder={intl.formatMessage({
                  id: 'Break Time',
                  defaultMessage: 'hh:mm',
                })} 
                value={ currentValuesWeek && currentValuesWeek['break'].sat && moment((currentValuesWeek['break'].sat), 'hh:mm ') }  

              /> ,
          sun: <TimePicker 
                  minuteStep={5}  
                  format= "HH:mm" 
                  style={{ width: 80 , borderRadius:'6px', background: '#FFFFFF'}} 
                  defaultValue={ currentValuesWeek && currentValuesWeek['break'].sun && moment((currentValuesWeek['break'].sun), 'hh:mm ')} 
                  disabled
                  allowClear 
                  placeholder={intl.formatMessage({
                    id: 'Break Time',
                    defaultMessage: 'hh:mm',
                  })} 
                  value={ currentValuesWeek && currentValuesWeek['break'].sun && moment((currentValuesWeek['break'].sun), 'hh:mm ') }  
                />
        },
        {
          time:`${intl.formatMessage({
            id: 'Hours',
            defaultMessage: 'Work Hours',
          })}`,
          mon: <Input 
                  style={{ width: 80 , borderRadius:'6px', background: '#FFFFFF'}} 
                  defaultValue={ currentValuesWeek && currentValuesWeek['work'].mon} 
                  value={currentValuesWeek && currentValuesWeek['work'].mon} 
                  allowClear
                  placeholder={intl.formatMessage({
                    id: 'Hours',
                    defaultMessage: 'hh:mm',
                  })} 
                  disabled
                /> ,
          tue: <Input 
                  style={{ width: 80 , borderRadius:'6px', background: '#FFFFFF'}}  
                  defaultValue={ currentValuesWeek && currentValuesWeek['work'].tue} 
                  value={currentValuesWeek && currentValuesWeek['work'].tue}  
                  allowClear 
                  placeholder={intl.formatMessage({
                    id: 'Hours',
                    defaultMessage: 'hh:mm',
                  })} 
                  disabled
                /> ,
          wed: <Input 
                  style={{ width: 80 , borderRadius:'6px', background: '#FFFFFF'}} 
                  defaultValue={ currentValuesWeek && currentValuesWeek['work'].wed} 
                  value={currentValuesWeek && currentValuesWeek['work'].wed} 
                  allowClear 
                  placeholder={intl.formatMessage({
                    id: 'Hours',
                    defaultMessage: 'hh:mm',
                  })}
                  disabled
                /> ,
          thu: <Input 
                  style={{ width: 80 , borderRadius:'6px', background: '#FFFFFF'}} 
                  defaultValue={ currentValuesWeek && currentValuesWeek['work'].thu} 
                  value={currentValuesWeek && currentValuesWeek['work'].thu} 
                  allowClear 
                  placeholder={intl.formatMessage({
                    id: 'Hours',
                    defaultMessage: 'hh:mm',
                  })} 
                  disabled
                /> ,
          fri: <Input 
                  style={{ width: 80 , borderRadius:'6px', background: '#FFFFFF'}} 
                  defaultValue={ currentValuesWeek && currentValuesWeek['work'].fri} 
                  value={currentValuesWeek && currentValuesWeek['work'].fri}  
                  allowClear  
                  placeholder={intl.formatMessage({
                    id: 'Hours',
                    defaultMessage: 'hh:mm',
                  })}
                  disabled
                /> ,
          sat: <Input 
                  style={{ width: 80 , borderRadius:'6px', background: '#FFFFFF'}} 
                  defaultValue={ currentValuesWeek && currentValuesWeek['work'].sat}  
                  value={ currentValuesWeek && currentValuesWeek['work'].sat}  
                  allowClear 
                  placeholder={intl.formatMessage({
                    id: 'Hours',
                    defaultMessage: 'hh:mm',
                  })} 
                  disabled
                /> ,
          sun: <Input  
                  style={{ width: 80 , borderRadius:'6px', background: '#FFFFFF'}} 
                  defaultValue={ currentValuesWeek && currentValuesWeek['work'].sun} 
                  value={currentValuesWeek && currentValuesWeek['work'].sun} 
                  allowClear 
                  placeholder={intl.formatMessage({
                    id: 'Hours',
                    defaultMessage: 'hh:mm',
                  })} 
                  disabled
                />
        },
        
      ];
    
      return   <Table dataSource={data} columns={columns}   pagination={false} />;
  
};

export default WeekTable;
