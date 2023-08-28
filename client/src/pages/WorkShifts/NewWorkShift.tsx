import React, { useEffect, useRef, useState } from 'react';
import { PageContainer } from '@ant-design/pro-layout';
import { Button, message as Message ,Row, Space ,Checkbox ,Switch,Typography ,Form , Card ,Col, message} from 'antd';
import type { ProColumns, ActionType } from '@ant-design/pro-table';

import { history , useIntl, useAccess, Access ,FormattedMessage ,useParams} from 'umi';
import { createWorkShifts ,updateWorkShifts , getWorkShifts , getWorkShiftDayType} from '@/services/workShift';
import PermissionDeniedPage from './../403';
import { getModel } from '@/services/model';
import ProForm, { ProFormText, ProFormSelect, ProFormTimePicker} from '@ant-design/pro-form';
import './style.css';
import styles from './styles.less';
import moment from 'moment';
import _ from 'lodash';
import { SketchPicker,  } from 'react-color';
import { BgColorsOutlined } from '@ant-design/icons';
import { getAllPayTypes } from '@/services/payType';
import { getAllDayTypes } from '@/services/workCalendarDayType';
import { checkTimeBasePayConfigState } from '@/services/workShiftPayConfiguration';
import PayTypeConfig  from './payTypeConfig';

export default (): React.ReactNode => {
  
  const intl = useIntl();
  const access = useAccess();
  const { hasPermitted } = access;
  const [hasMidnightCrossOver, sethasMidnightCrossOver] = useState(false);
  const [form] = Form.useForm();
  const [startTime, setStartTime] = useState<moment.Moment>();
  const [validatedStatusStartTime, setValidateStartTime] = useState<"" | "error">("");
  const [helpStartTime, setHelpStartTime] = useState('');

  const [endTime, setEndTime] =  useState<moment.Moment>();
  const [validatedStatusEndTime, setValidateEndTime] = useState<"" | "error" | "warning">("");
  const [helpEndTime, setHelpEndTime] = useState('');

  const [breakTime, setBreakTime] =  useState <moment.Moment>();
  const [flexiWorkHours, setFlexiWorkHours] =  useState <moment.Moment>();
  const [validatedStatusBreakTime, setValidateBreakTime] = useState<"" | "error">("");
  const [validatedStatusFlexiWorkHours, setValidateStatusFlexiWorkHours] = useState<"" | "error">("");
  const [helpBreakTime, setHelpBreakTime] = useState('');
  const [helpFlexiWorkHours, setHelpFlexiWorkHours] = useState('');
  const [halfDayDuration, setHalfDayDuration] =  useState <moment.Moment>();
  const [gracePeriod, setGracePeriod] =  useState <moment.Moment>();
  const [minimumOT, setMinimumOT] =  useState <moment.Moment>();
  const [inOvertime , setInOverTime] = useState(false);
  const [outOvertime , setOutOverTime] = useState(false);
  const [isBehaveAsNonWorkingDay , setIsBehaveAsNonWorkingDay] = useState(false);
  const [deductLateFromOvertime , setDeductLateFromOvertime ] = useState(false);
  const [totalHours, setTotalHours] = useState('');
  const [roundUp,setRoundUp]= useState(false);
  const [isOTEnabled, setIsOTEnabled]= useState(false);
  const [editShiftModal , setEditShiftModal] = useState(false);
  const [showBehaveNonworkingCheckBox , setShowBehaveNonworkingCheckBox] = useState(true);
  const [roundOffMethod , setRoundOffMethod] = useState([]);
  const [shiftTypes, setShiftTypes] = useState([]);
  const [roundOfftoNearest ,setRoundOffToNearest] = useState([]);
  const [formInitialValues,setFormInitialValues]=useState({});
  const [color, setColor] = useState('#000000');
  const [payConfigType, setPayConfigType] = useState(null);
  const [iconColor, setIconColor] = useState('#000000');
  const [colorCode, setColorCode] = useState('');
  const [showColorPicker, setShowColorPicker] = useState<boolean>(false);
  
  const {Text} = Typography;
  const { id } = useParams();
  const [buttonText , setButtonText] = useState('Save');
  const  [ dayTypes , setDayTypes] = useState([]);
  const  [loading , setLoading] = useState(false);
  const [enablePayConfig , setEnablePayConfig] = useState(false);
  const [isMaintainTimeBasePayConfig , setIsMaintainTimeBasePayConfig] = useState(false);
  const [dayType , setDayType] = useState({});
  const [dayTypeList ,setDayTypeList] = useState([]);
  const [payTypeData , setPayTypeData] = useState([]);
  const [dayTypeEnumList, setDayTypeEnumList] = useState([]);
  const [payTypeEnumList, setPayTypeEnumList] = useState([]);
  const [selectedDayTypes, setSelectedDayTypes] = useState<any>([]);
  const [selectedDayTypeIds, setSelectedDayTypeIds] = useState<any>([]);
  const [selectedShiftType, setSelectedShiftType] = useState<any>('GENERAL');
  const [refresh, setRefresh] = useState(0);
  useEffect(() =>{
    getShiftTypes();
    checkIsMaintainTimeBasePayConfig();
  },[]);

  useEffect(() => {
    if (id !== undefined) {
      viewShift();
      setButtonText('Update');
    }
  },[id]);

  useEffect(() => {
      if(selectedShiftType == 'GENERAL' && isMaintainTimeBasePayConfig) {
        setPayConfigType('TIME_BASE');
      } else {
        setPayConfigType('HOUR_BASE');
      }
    
  },[selectedShiftType, isMaintainTimeBasePayConfig]);


  useEffect(() => {

    setShowBehaveNonworkingCheckBox(true);
    // if (form.getFieldValue('dayType') != 1) {
    //    setShowBehaveNonworkingCheckBox(true);
    // } else {
    //    setShowBehaveNonworkingCheckBox(false);
    //    setIsBehaveAsNonWorkingDay(false);
    //    form.setFieldsValue({
    //        isBehaveAsNonWorkingDay : false
    //    });

    // }
  },[form.getFieldValue('dayType')]);

  useEffect(() => {
    if (isBehaveAsNonWorkingDay) {
        form.setFieldsValue({
            startTime: setStartTime(moment('00:00', 'HH:mm')),
            endTime: setEndTime(moment('00:00', 'HH:mm'))
        });

        setValidateStartTime('');
        setHelpStartTime('');
        setValidateEndTime('');
        setHelpEndTime('');

        if (selectedShiftType == 'FLEXI') {
            form.setFieldsValue({
                flexiWorkHours: setFlexiWorkHours(moment('00:00', 'HH:mm')),
            });

            setValidateStatusFlexiWorkHours('');
            setHelpFlexiWorkHours('');
        }
    } else {
        setStartTime(null);
        setEndTime(null);
        form.setFieldsValue({
            startTime: undefined,
            endTime: undefined
        });

        if (selectedShiftType == 'FLEXI') {
            setFlexiWorkHours(undefined);

            form.setFieldsValue({
                flexiWorkHours: undefined,
            });
        }

    }
  },[isBehaveAsNonWorkingDay]);

  const getShiftTypes= async() =>{
    try {
       setLoading(true);
       const {data} = await getModel("workShifts");
       
       const shiftTypeArray = data.modelDataDefinition.fields.shiftType.values.map(shift =>{
        return {
            label: shift.defaultLabel,
            value: shift.value
         };
       })
       setShiftTypes(shiftTypeArray);
       const workShiftDayType = await getModel("workShiftDayType");
       const roundOffArray = workShiftDayType.data.modelDataDefinition.fields.roundOffMethod.values.map(method =>{
        return {
            label: method.defaultLabel,
            value: method.value
         };
       })
       setRoundOffMethod(roundOffArray);
       const roundOffToNearestArray= workShiftDayType.data.modelDataDefinition.fields.roundOffToNearest.values.map(roundOff =>{
        return {
            label: roundOff.defaultLabel,
            value: roundOff.value
         };
       })
       setRoundOffToNearest(roundOffToNearestArray);
       
       const dayTypeResponse = await getAllDayTypes();
        const dayTypeArray = dayTypeResponse?.data.map(dayType => {
           return {
              label: dayType.name,
              value: dayType.id
           };
        });
       setDayTypeList(dayTypeResponse.data);
       setDayTypes(dayTypeArray);
     
       setLoading(false);
    } catch(error) {
        Message.error(error);
        setLoading(false);
    }
  }

  const checkIsMaintainTimeBasePayConfig= async() =>{
    try {
       setLoading(true);       
       const res = await checkTimeBasePayConfigState({});
       if (res.data) {
        setIsMaintainTimeBasePayConfig(res.data.isMaintainTimeBasePayConfig);
       } else {
        setIsMaintainTimeBasePayConfig(false);
       }
     
       setLoading(false);
    } catch(error) {
        Message.error(error);
        setLoading(false);
    }
  }

  const fetchWorkShiftDayType = async (dayTypeId) =>{
    try {
        if (id !== undefined && dayTypeId !== undefined) {
            const requestParams = {
                dayTypeId: dayTypeId,
                workshiftId: id
            }
            const { data } = await getWorkShiftDayType(requestParams);
            setIsOTEnabled(false);
            setRoundUp(false);
            setStartTime();
            setEndTime();
            setBreakTime();
            setFlexiWorkHours();
            setTotalHours();
            setMinimumOT();
            setGracePeriod();
            setHalfDayDuration();
            setInOverTime(false);
            setOutOverTime(false);
            setDeductLateFromOvertime(false);
            setSelectedDayTypes([]);
            setSelectedDayTypeIds([]);
            if (data?.roundOffMethod === 'ROUND_UP' || data?.roundOffMethod === 'ROUND_DOWN') {
                setRoundUp(true);
            }

            let StartTime = data?.startTime && moment(data.startTime, 'HH:mm');
            let EndTime = data?.endTime && moment(data.endTime, "HH:mm");
            let BreakTime = !_.isNull(data) && data?.breakTime ? convertMinutesToHoursAndMin(data.breakTime) : _.isNull(data) ? undefined : moment('00:00', 'HH:mm');
            let WorkHours = !_.isNull(data) && data?.workHours ? convertMinutesToHoursAndMin(data.workHours) : _.isNull(data) ? undefined : moment('00:00', 'HH:mm');
            let breakHoursInMinutes  =  data?.breakTime && !_.isNull(data.breakTime) ? Number(data.breakTime) : 0;
            let workHoursInMinutes  = data?.workHours && !_.isNull(data.workHours) ? Number(data.workHours) : 0;
            let totalFlexiWorkHrs = breakHoursInMinutes + workHoursInMinutes;
            let HalfDayLength = data?.halfDayLength && convertMinutesToHoursAndMin(data.halfDayLength);
            let GracePeriod = data?.gracePeriod && convertMinutesToHoursAndMin(data.gracePeriod);
            let MinimumOT = data?.minimumOT && convertMinutesToHoursAndMin(data.minimumOT);


            if (!_.isNull(data) && data.payData['data'].length > 0) {
                setEnablePayConfig(true);
                
                let filtereIdList = [];

                let payDatArr = data.payData['data'].map((daytype) => {
                    filtereIdList.push(daytype.shortCode);

                    if (data.payConfigType == 'TIME_BASE') {
                        let payDetailArr = daytype.payTypeDetails.map((payTypeDetail) => {
                            payTypeDetail.validTime = moment(payTypeDetail.validTime, 'hh:mm:ss');
                            return payTypeDetail;
                        });

                        dayType.payTypeDetails = payDetailArr;
                    }
                    return daytype;
                });
                

                
                setSelectedDayTypes(payDatArr);
                setSelectedDayTypeIds(filtereIdList);
            }
            if (data?.hasMidnightCrossOver) {     
                setValidateEndTime('warning');
                setHelpEndTime('Next Day');
            }  else {
                setValidateEndTime('');
                setHelpEndTime('');
            }

            let noOfDay =   data?.noOfDay && data.noOfDay == '1.00' ? '1' : data?.noOfDay && data.noOfDay == '0.50' ? '0.5' : data?.noOfDay && data.noOfDay == '0.00' ? '0' : undefined;
            setFormInitialValues({
                name: formInitialValues.name,
                code: formInitialValues.code,
                shiftType: formInitialValues.shiftType,
                isActive: formInitialValues.isActive,
                noOfDay: noOfDay,
                isOTEnabled: data?.isOTEnabled && data.isOTEnabled == 1 ? setIsOTEnabled(true) : setIsOTEnabled(false),
                isBehaveAsNonWorkingDay: data?.isBehaveAsNonWorkingDay == 1 ? setIsBehaveAsNonWorkingDay(true) : setIsBehaveAsNonWorkingDay(false),
                startTime: !_.isUndefined(StartTime) ? setStartTime(StartTime) : data?.startTime,
                endTime: !_.isUndefined(EndTime) ? setEndTime(EndTime) : data?.endTime,
                breakTime: !_.isUndefined(BreakTime) && !_.isNull(BreakTime) ? setBreakTime(BreakTime.format('HH:mm')) : data?.breakTime,
                workHours: !_.isUndefined(WorkHours)  && !_.isNull(WorkHours) ? setTotalHours(WorkHours.format('HH:mm')) : data?.workHours,
                flexiWorkHours: totalFlexiWorkHrs > 0 ? setFlexiWorkHours(convertMinutesToHoursAndMin(totalFlexiWorkHrs))  :setFlexiWorkHours(moment('00:00', 'HH:mm')),
                halfDayLength: !_.isUndefined(HalfDayLength) && !_.isNull(HalfDayLength) ? setHalfDayDuration(HalfDayLength.format('HH:mm')) : data?.hafDayLength,
                gracePeriod: !_.isUndefined(GracePeriod) && !_.isNull(GracePeriod) ? setGracePeriod(GracePeriod.format('HH:mm')) : data?.gracePeriod,
                minimumOT: !_.isUndefined(MinimumOT) && !_.isNull(MinimumOT) ? setMinimumOT(MinimumOT.format('HH:mm')) : data?.minimumOT,
                inOvertime: data?.inOvertime == 1 ? setInOverTime(true) : setInOverTime(false),
                outOvertime: data?.outOvertime == 1 ? setOutOverTime(true) : setOutOverTime(false),
                deductLateFromOvertime: data?.deductLateFromOvertime == 1 ? setDeductLateFromOvertime(true) : setDeductLateFromOvertime(false),
                roundOffMethod: data?.roundOffMethod,
                roundOffToNearest: data?.roundOffToNearest,
                dayType: data?.dayTypeId,
                hasMidnightCrossOver: data?.hasMidnightCrossOver ? sethasMidnightCrossOver(true) : sethasMidnightCrossOver(false)
            });
            form.setFieldsValue({
                roundOffMethod: data?.roundOffMethod,
                roundOffToNearest: data?.roundOffToNearest,
                noOfDay: noOfDay
            })
            styles.colorVal = { fontSize: '20px', color: color };
        }

    } catch(error) {
        Message.error(error);
    }
  }
  const addWorkShifts = async (fields: any) => {
 
    try {
      let breakHours = moment.isMoment(breakTime) ? (moment(breakTime).format('HH:mm')).split(":") : breakTime.split(":");
      let breakTimeInMin = Number(breakHours[0]) * 60 + Number(breakHours[1]);

      let hours = (totalHours) ?  totalHours.split(":") : 0;
      let workHoursInMin = Number(hours[0]) * 60 + Number(hours[1]);
      let gracePeriodInMin;
      let halfDayDurationInMin;
      let minimumOTInMin;
      let dataSet;
      if(gracePeriod) {
        let gracePeriodValueInMinAndHours = gracePeriod.split(":");
        gracePeriodInMin = Number( gracePeriodValueInMinAndHours[0]) * 60 + Number( gracePeriodValueInMinAndHours[1]);
      }
      if(halfDayDuration) {
        let halfDayDurationInMinAndHours = halfDayDuration.split(":");
        halfDayDurationInMin = Number( halfDayDurationInMinAndHours[0]) * 60 + Number( halfDayDurationInMinAndHours[1]);
      }
      if(minimumOT){
        let minimumOTInMinAndHours = minimumOT.split(":");
        minimumOTInMin = Number( minimumOTInMinAndHours[0]) * 60 + Number( minimumOTInMinAndHours[1]);
      }

      var filterData = [];
      if (enablePayConfig) {
        let payTypeNullCount = 0;
        let hourPerDayNullCount = 0;
        let validTimeNullCount = 0;
        let selectedOldDayTypeIds = [];

        
        selectedDayTypes.map((data) => {
            if (data.id != 'new') {
                selectedOldDayTypeIds.push(data.id);
            }

            let selectedOldPayTypeIds = [];
            let payTypeDetails = [];
            data.payTypeDetails.map((el) => {

                if (el.payTypeId == null) {
                    payTypeNullCount++;
                }
                if (payConfigType == 'HOUR_BASE' && el.hoursPerDay == null) {
                    hourPerDayNullCount++;
                }

                if (el.id != 'new') {
                    selectedOldPayTypeIds.push(el.id);
                }

                if (payConfigType == 'TIME_BASE' && el.validTime == null) {
                    validTimeNullCount++;
                }

                if (payConfigType == 'TIME_BASE' && el.validTime) {
                    el.validTimeString = el.validTime.format('HH:mm:ss')
                }
                
                payTypeDetails.push(el);
            })

            let tempObj = {
                'id': data.id,
                'dayTypeId': data.dayTypeId,
                'payTypeThresholdDetails': payTypeDetails,
                'selectedOldThresholdIds': selectedOldPayTypeIds
            }
            filterData.push(tempObj);

        })

        if (payTypeNullCount > 0) {
            message.error('Pay type field is required field , So it must be define in each threshold that you create');
            return;
        }
        if (hourPerDayNullCount > 0) {
            message.error('Hours per day field is required field , So it must be define in each threshold that you create');
            return;
        }

        if (validTimeNullCount > 0) {
            message.error('Valid time period field is required field , So it must be define in each threshold that you create');
            return;
        }


        if (payConfigType == 'TIME_BASE') {

            if (filterData[0].payTypeThresholdDetails.length == 2) {
    
                let threshold1ValidTime = filterData[0].payTypeThresholdDetails[0].validTime;
                let threshold2ValidTime = filterData[0].payTypeThresholdDetails[1].validTime;
    
                if (!threshold2ValidTime.isAfter(threshold1ValidTime)) {
                    message.error('Threshold No 2 valid time period should be greater than threshold No 1 valid time period');
                    return;
                }
    
            }
    
            if (filterData[0].payTypeThresholdDetails.length == 3) {
    
                let threshold1ValidTime = filterData[0].payTypeThresholdDetails[0].validTime;
                let threshold2ValidTime = filterData[0].payTypeThresholdDetails[1].validTime;
                let threshold3ValidTime = filterData[0].payTypeThresholdDetails[2].validTime;
    
                if (!threshold2ValidTime.isAfter(threshold1ValidTime)) {
                    message.error('Threshold No 2 valid time period should be greater than threshold No 1 valid time period');
                    return;
                }
    
                if (!threshold3ValidTime.isAfter(threshold1ValidTime)) {
                    message.error('Threshold No 3 valid time period should be greater than threshold No 1 valid time period');
                    return;
                }
    
                if (!threshold3ValidTime.isAfter(threshold2ValidTime)) {
                    message.error('Threshold No 3 valid time period should be greater than threshold No 2 valid time period');
                    return;
                }
    
            }
    
            if (filterData[0].payTypeThresholdDetails.length == 4) {
    
                let threshold1ValidTime = filterData[0].payTypeThresholdDetails[0].validTime;
                let threshold2ValidTime = filterData[0].payTypeThresholdDetails[1].validTime;
                let threshold3ValidTime = filterData[0].payTypeThresholdDetails[2].validTime;
                let threshold4ValidTime = filterData[0].payTypeThresholdDetails[3].validTime;
    
                if (!threshold2ValidTime.isAfter(threshold1ValidTime)) {
                    message.error('Threshold No 2 valid time period should be greater than threshold No 1 valid time period');
                    return;
                }
    
                if (!threshold3ValidTime.isAfter(threshold1ValidTime)) {
                    message.error('Threshold No 3 valid time period should be greater than threshold No 1 valid time period');
                    return;
                }
    
                if (!threshold4ValidTime.isAfter(threshold1ValidTime)) {
                    message.error('Threshold No 4 valid time period should be greater than threshold No 1 valid time period');
                    return;
                }
    
                if (!threshold3ValidTime.isAfter(threshold2ValidTime)) {
                    message.error('Threshold No 3 valid time period should be greater than threshold No 2 valid time period');
                    return;
                }
    
                if (!threshold4ValidTime.isAfter(threshold2ValidTime)) {
                    message.error('Threshold No 4 valid time period should be greater than threshold No 2 valid time period');
                    return;
                }
    
                if (!threshold4ValidTime.isAfter(threshold3ValidTime)) {
                    message.error('Threshold No 4 valid time period should be greater than threshold No 3 valid time period');
                    return;
                }
    
            }
        }

        dataSet = {
            'id': id,
            'payConfigData': JSON.stringify(filterData),
            'selectedOldDayTypeIds': selectedOldDayTypeIds
        }
      }
      const requestData = fields;
      requestData.isActive = fields.isActive == 1 ? true :false;
      requestData.startTime = moment(startTime,'hh:mm a').isValid() ? moment(startTime,'hh:mm a').format('HH:mm') : startTime ;
      requestData.endTime =  moment(endTime,'hh:mm a').isValid() ? moment(endTime,'hh:mm a').format('HH:mm') :endTime ;
      requestData.breakTime = breakTimeInMin.toString();
      requestData.workHours = workHoursInMin.toString();
      requestData.isOTEnabled = isOTEnabled;
      requestData.inOvertime = inOvertime;
      requestData.outOvertime = outOvertime;
      requestData.isBehaveAsNonWorkingDay = isBehaveAsNonWorkingDay;
      requestData.deductLateFromOvertime = deductLateFromOvertime;
      requestData.gracePeriod = !_.isUndefined(gracePeriodInMin) ? gracePeriodInMin.toString() : null;
      requestData.halfDayLength =!_.isUndefined(halfDayDurationInMin) ? halfDayDurationInMin.toString() : null;
      requestData.minimumOT = !_.isUndefined(minimumOTInMin) ? minimumOTInMin.toString() : null;
      requestData.hasMidnightCrossOver = hasMidnightCrossOver;
      requestData.color = color !== '#000000' ? color :'';
      requestData.enablePayConfig = enablePayConfig;
      requestData.payConfigData = dataSet;
      requestData.payConfigType = payConfigType;
 
      if (id == undefined) {
        const {message,data} = await createWorkShifts(requestData);
        Message.success(message);
        history.push(`/settings/work-shifts`);
       } else {
         let shiftTypeValue ="";
         if (fields.shiftType === 'General') {
            shiftTypeValue = 'GENERAL';
         } else if (fields.shiftType === 'Flexi') {
            shiftTypeValue = 'FLEXI';
         }
         requestData.shiftType = shiftTypeValue!= "" ? shiftTypeValue : fields.shiftType;
         const {message,data} = await updateWorkShifts(id,requestData);
         Message.success(message);
       }

    } catch (error) {
        if (!_.isEmpty(error.data) && _.isObject(error.data)) {
            for (const fieldName in error.data) {
                form.setFields([
                  {
                    name: fieldName,
                    errors: error.data[fieldName]
                  }
                ]);
    
            }
          } else {
            if (!_.isEmpty(error.message)) {
              let errorMessage;
              let errorMessageInfo;
              if (error.message.includes('.')) {
                let errorMessageData = error.message.split('.');
                errorMessage = errorMessageData.slice(0, 1);
                errorMessageInfo = errorMessageData.slice(1).join('.');
              }
              Message.error({
                content: error.message ? (
                  <>
                    {errorMessage ?? error.message}
                    <br />
                    <span style={{ fontWeight: 150, color: '#A9A9A9', fontSize: '14px' }}>
                      {errorMessageInfo ?? ''}
                    </span>
                  </>
                ) : (
                  intl.formatMessage({
                    id: 'failedToSave',
                    defaultMessage: 'Cannot Save',
                  })
                ),
              });
            }
          }
    }
  };
  const payTypeConfigData = async(dayTypeData) => {
    try {
        const tempDaytypeArr = [];
        // setDayTypeEnumList([]);
        // setSelectedDayTypes([]);
        const res = await getAllPayTypes();
        let valueEnum = [];
        if (res.data) {
            valueEnum = res.data.map((value) => {
                return {
                    'payTypeId': value.id,
                    'name' : value.name,
                    'disabled' : false,
                    'code' : value.code
                };
            });
        }
        
        if (!selectedDayTypeIds.includes(dayTypeData.shortCode)) {
            if (selectedDayTypes.length > 0) {
                selectedDayTypeIds.push(dayTypeData.shortCode);
                let enumData = dayTypeList.map((el: any, index) => {
                    let shortCode = el.shortCode;
                    let tempObj = {
                        'id': 'new',
                        'dayTypeId': el.id,
                        'name' : el.name,
                        'disabled' : false,
                        'shortCode' : el.shortCode,
                        'payTypeDetails': [],
                        'payTypeEnumList': valueEnum
                    };
    
                    if (selectedDayTypeIds.includes(shortCode)) {
                        let thresholdKey = tempObj.payTypeDetails.length + 1;
                        let payTypeObj = {
                            'id': 'new',
                            'payTypeId': null,
                            'thresholdType': 'After',
                            'hoursPerDay': null,
                            'validTime': null,
                            'thresholdKey': thresholdKey,
                            'showAddBtn' : true
                        }
                        tempObj.disabled = true; 
                        tempObj.payTypeDetails.push(payTypeObj); 
                    }
    
                    return tempObj;
                    
                });
                setDayTypeEnumList(enumData);
    
                let selectedData = [...selectedDayTypes];
                let newPayTypeObj = {
                    'id': 'new',
                    'payTypeId': null,
                    'thresholdType': 'After',
                    'hoursPerDay': null,
                    'validTime': null,
                    'thresholdKey': 1,
                    'showAddBtn' : true
                }
    
                let newObj = {
                    'id': 'new',
                    'dayTypeId': dayTypeData.dayTypeId,
                    'name' : dayTypeData.name,
                    'disabled' : false,
                    'shortCode' : dayTypeData.shortCode,
                    'payTypeDetails': [],
                    'payTypeEnumList': valueEnum
                };
    
    
                newObj.payTypeDetails.push(newPayTypeObj);
    
                selectedData.push(newObj);
                setSelectedDayTypes(selectedData);
            } else {
                selectedDayTypeIds.push(dayTypeData.shortCode);
                let enumData = dayTypeList.map((el: any, index) => {
                    let shortCode = el.shortCode;
                    let tempObj = {
                        'id': 'new',
                        'dayTypeId': el.id,
                        'name' : el.name,
                        'disabled' : false,
                        'shortCode' : el.shortCode,
                        'payTypeDetails': [],
                        'payTypeEnumList': valueEnum
                    };
    
                    if (selectedDayTypeIds.includes(shortCode)) {
                        let thresholdKey = tempObj.payTypeDetails.length + 1;
                        let payTypeObj = {
                            'id': 'new',
                            'payTypeId': null,
                            'thresholdType': 'After',
                            'hoursPerDay': null,
                            'validTime': null,
                            'thresholdKey': thresholdKey,
                            'showAddBtn' : true
                        }
                        tempObj.disabled = true; 
                        tempObj.payTypeDetails.push(payTypeObj); 
                        tempDaytypeArr.push(tempObj);
                    }
    
                    return tempObj;
                    
                });
                setDayTypeEnumList(enumData);
                setSelectedDayTypes(tempDaytypeArr);
            }
        }
        
    } catch (err) {
        console.log(err);
    }

  }
  
  
  const handleClose = (fieldName: any) => {
    setShowColorPicker(false);
  }
  const handleChange = (color: any) => {
    setColor(color.hex.toUpperCase());
    setIconColor(color.hex.toUpperCase());
   
    setColorCode(color.hex.toUpperCase());
  };
  const popover = {
    position: 'absolute',
    zIndex: '2',
  }
  const style = {
    colorVal: {
        fontSize: '20px', color: iconColor
    },
    cover: {
      position: 'fixed',
      top: '0px',
      right: '0px',
      bottom: '0px',
      left: '0px',
    }
  };
  const viewShift = async () => {
        try {
            const { data } = await getWorkShifts(id);
            setIsOTEnabled(false);
            setRoundUp(false);
            setStartTime();
            setEndTime();
            setBreakTime();
            setFlexiWorkHours();
            setTotalHours();
            setMinimumOT();
            setGracePeriod();
            setHalfDayDuration();
            setInOverTime(false);
            setOutOverTime(false);
            setDeductLateFromOvertime(false);
            setColor();
            setColorCode();
            setIconColor();
            setSelectedDayTypes([]);
            setSelectedDayTypeIds([]);

            if (data.roundOffMethod === 'ROUND_UP' || data.roundOffMethod === 'ROUND_DOWN') {
                setRoundUp(true);
            }

            let StartTime = moment(data.startTime, 'HH:mm');
            let EndTime = moment(data.endTime, "HH:mm");
            let breakHoursInMinutes  = !_.isNull(data.breakTime) ? Number(data.breakTime) : 0;
            let workHoursInMinutes  = !_.isNull(data.workHours) ? Number(data.workHours) : 0;
            let totalFlexiWorkHrs = data.isBehaveAsNonWorkingDay ? 0 : breakHoursInMinutes + workHoursInMinutes;
            let BreakTime = !_.isNull(data.breakTime) && convertMinutesToHoursAndMin(data.breakTime);
            let WorkHours = !_.isNull(data.workHours) ? convertMinutesToHoursAndMin(data.workHours) : 0;
            let HalfDayLength = !_.isNull(data.halfDayLength) && convertMinutesToHoursAndMin(data.halfDayLength);
            let GracePeriod = !_.isNull(data.gracePeriod) && convertMinutesToHoursAndMin(data.gracePeriod);
            let MinimumOT = !_.isNull(data.minimumOT) && convertMinutesToHoursAndMin(data.minimumOT);
            setSelectedShiftType(data.shiftType);
            console.log(WorkHours);

            setShowBehaveNonworkingCheckBox(true);

            // if (data.dayTypeId != 1) {
            //     setShowBehaveNonworkingCheckBox(true);
            //  } else {
            //     setShowBehaveNonworkingCheckBox(false);
            //     setIsBehaveAsNonWorkingDay(false);
            //     form.setFieldsValue({
            //         isBehaveAsNonWorkingDay : false
            //     });
         
            //  }

            styles.colorVal = { fontSize: '20px', color: data.color };
            let dayTypeId = data.dayTypeId;
            if (!_.isNull(data) && data.payData['data'].length > 0) {
                setEnablePayConfig(true);
                let filtereIdList = [];

                let payDatArr = data.payData['data'].map((daytype) => {
                    filtereIdList.push(daytype.shortCode);

                    if (data.payConfigType == 'TIME_BASE') {
                        let payDetailArr = daytype.payTypeDetails.map((payTypeDetail) => {

                            payTypeDetail.validTime = moment(payTypeDetail.validTime, 'hh:mm:ss');
                            return payTypeDetail;
                        });

                        dayType.payTypeDetails = payDetailArr;
                    }
                    return daytype;
                });

                setSelectedDayTypes(payDatArr);
                setSelectedDayTypeIds(filtereIdList);
            } 

            const dayTypeResponse = await getAllDayTypes();
            setDayTypeList(dayTypeResponse.data);
            const filteredData = dayTypeResponse.data.map((dayType) => {
                if (dayType.id == dayTypeId) {
                    return {
                        'dayTypeId': dayType.id,
                        'name': dayType.name,
                        'shortCode': dayType.shortCode,
                        'payTypeDetails': []
                    };
                }
            });
            const dayTypeData = filteredData.filter(function (element) {
                return element !== undefined;
            });
            setDayType(dayTypeData);
            if (data?.hasMidnightCrossOver) {     
                setValidateEndTime('warning');
                setHelpEndTime('Next Day');
            } else {
                setValidateEndTime('');
                setHelpEndTime('');
            }

            let noOfDay = data?.noOfDay && data.noOfDay == '1.00' ? '1' : data?.noOfDay && data.noOfDay == '0.50' ? '0.5' : data?.noOfDay && data.noOfDay == '0.00' ? '0' : undefined;
            form.setFieldsValue({
                id: data.id,
                name: data.name,
                code: data.code,
                shiftType: data.shiftType === "GENERAL" ? 'General' : 'Flexi',
                noOfDay: noOfDay,
                isOTEnabled: data.isOTEnabled == 1 ? setIsOTEnabled(true) : setIsOTEnabled(false),
                isBehaveAsNonWorkingDay: data.isBehaveAsNonWorkingDay == 1 ? setIsBehaveAsNonWorkingDay(true) : setIsBehaveAsNonWorkingDay(false),
                startTime: StartTime.isValid() ? setStartTime(StartTime) : data.startTime,
                endTime: EndTime.isValid() ? setEndTime(EndTime) : data.endTime,
                breakTime: (BreakTime) && BreakTime.isValid() ? setBreakTime(BreakTime) : data.breakTime,
                workHours: (WorkHours) && WorkHours.isValid() ? setTotalHours(WorkHours.format('HH:mm')) : data.workHours,
                flexiWorkHours: totalFlexiWorkHrs > 0 ? setFlexiWorkHours(convertMinutesToHoursAndMin(totalFlexiWorkHrs))  : moment('00:00', 'HH:mm'),
                halfDayLength: HalfDayLength ? setHalfDayDuration(HalfDayLength.format('HH:mm')) : halfDayDuration,
                gracePeriod: GracePeriod ? setGracePeriod(GracePeriod.format('HH:mm')) : gracePeriod,
                minimumOT: MinimumOT ? setMinimumOT(MinimumOT.format('HH:mm')) : minimumOT,
                inOvertime: data.inOvertime == 1 ? setInOverTime(true) : setInOverTime(false),
                outOvertime: data.outOvertime == 1 ? setOutOverTime(true) : setOutOverTime(false),
                deductLateFromOvertime: data.deductLateFromOvertime == 1 ? setDeductLateFromOvertime(true) : setDeductLateFromOvertime(false),
                roundOffMethod: data.roundOffMethod,
                roundOffToNearest: data.roundOffToNearest,
                isActive: data.isActive == 1 ? '1' : '0',
                color: data.color !== null ? setColor(data.color) : '',
                iconColor: data.color !== null ? setIconColor(data.color) : '',
                dayType: data.dayTypeId,
                hasMidnightCrossOver: data?.hasMidnightCrossOver ? sethasMidnightCrossOver(true) : sethasMidnightCrossOver(false)
            });
        } catch (error) {
            Message.error(error);
        }
    }

  const convertMinutesToHoursAndMin =(time) => {

    let hours = Math.trunc(time/60);
    let minutes = time % 60;
    let hoursVal = hours < 10 ? '0' + hours : hours; 
    let minutesVal = minutes < 10 ? '0' + minutes : minutes; 
    
    let timeInMinAndHours = hoursVal +":"+ minutesVal ;
    return (moment(timeInMinAndHours, 'HH:mm'));
}
  
  const onchangeStart = (value) => {
    const start = moment(value).format('HH:mm');
    setStartTime(start);

    if (selectedShiftType == 'GENERAL') {
        let endTimeValue =  moment.isMoment(endTime) ? moment(endTime).format('HH:mm') : endTime;
        if ( endTimeValue && (endTimeValue < start)) {
            sethasMidnightCrossOver(true);
            setValidateEndTime('warning');
            setHelpEndTime('Next Day');
        } else {
            sethasMidnightCrossOver(false);
        }
        let breakTimeVal = _.isUndefined(breakTime) ? '00:00' : breakTime;
        if (breakTime) {
            onBreakChange(breakTimeVal ,null);
        } 
        
        if ( !_.isUndefined(start) && !_.isUndefined(endTime) && _.isUndefined(breakTime) && _.isUndefined(id)) {
            onBreakChange(breakTimeVal ,null);
        }
    }

  }

  const onchangeEnd = (value) => { 
    const end = moment(value).format('HH:mm');
    setEndTime(end);

    if (selectedShiftType == 'GENERAL') {
        let startTimeVal = moment.isMoment(startTime) ? moment(startTime).format('HH:mm') : startTime
        
        if (moment(value).format('HH:mm') < startTimeVal) {
          sethasMidnightCrossOver(true);
        } else {
          sethasMidnightCrossOver(false);
        }
        let breakTimeVal = _.isUndefined(breakTime) ? '00:00' : breakTime;
    
        if (!_.isUndefined(startTime) && !_.isUndefined(end) && _.isUndefined(breakTime) && _.isUndefined(id) ) {
            onBreakChange(breakTimeVal,end);
        } 
        if (breakTimeVal) {
            onBreakChange(breakTimeVal,end);
        }
    }

  }

  const onBreakChange = (value,endTimeValue) => {
    const breakValue = moment.isMoment(value) ? moment(value).format('HH:mm') :value;
    setBreakTime(breakValue);
    
    if (selectedShiftType == 'GENERAL') {
        let hours;
        let endTimeVal = !_.isNull(endTimeValue) ? endTimeValue : endTime; 
        let start =  moment.isMoment(startTime) ? moment(startTime).format('HH:mm') : startTime;
        let end =  moment.isMoment(endTimeVal) ? moment(endTimeVal).format('HH:mm') : endTimeVal;
     
        if (start && end) {
          let timeStart = start ? calculateTotalHours(start) : '00:00';
          let timeEnd = end ? calculateTotalHours(end) : '00:00';
        
          if ( end < start) {
            let midnight = calculateTotalHours('24:00');
            hours = (timeEnd + midnight) - timeStart;
          } else {
            hours = timeEnd - timeStart;
          }
        }
    
        let breakHours  = calculateTotalHours(breakValue);
        let totalWorkHours = convertToTime(hours - breakHours);
        if (start && end) {
          setTotalHours(totalWorkHours);
        }
    } else {
        let flexiWorkHoursValue = moment.isMoment(flexiWorkHours) ? moment(flexiWorkHours).format('HH:mm') :flexiWorkHours;
        if (flexiWorkHours) {
            let breakHours  = calculateTotalHours(breakValue);
            let flexiHours  = calculateTotalHours(flexiWorkHoursValue);
            let totalWorkHours = convertToTime(flexiHours - breakHours);
            setTotalHours(totalWorkHours);
        }
    }
   
  }

  const onFlexiHourChange = (value,endTimeValue) => {
    const flexiWorkHourValue = moment.isMoment(value) ? moment(value).format('HH:mm') :value;
    const breakValue = moment.isMoment(breakTime) ? moment(breakTime).format('HH:mm') :breakTime;
    setFlexiWorkHours(flexiWorkHourValue);
    let hours;
    let flexiWorkHoursVal  = calculateTotalHours(flexiWorkHourValue);

    if (breakTime) {
        let breakHours  = calculateTotalHours(breakValue);
        let totalWorkHours = convertToTime(flexiWorkHoursVal - breakHours);
        setTotalHours(totalWorkHours);
        setFlexiWorkHours(value);
    } else {
        let totalWorkHours = convertToTime(flexiWorkHoursVal);
        setTotalHours(totalWorkHours);
        setFlexiWorkHours(value);
    }
    form.setFieldsValue({
        'flexiWorkHours': value
    });

  }

  const calculateTotalHours = (time) => {
    let total = 0;
    const timestrToSec = (timestr: any) => {
      let time = timestr.split(":");
      return (time[0] * 3600) +
        (time[1] * 60);
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

    return (!loading && 
        <Access
            accessible={hasPermitted('work-shifts-read-write')}
            fallback={<PermissionDeniedPage />}
        >
            <PageContainer>
                <Card>
                   
                    <ProForm 
                        form={form} 
                        onFinish={addWorkShifts} 
                        autoComplete="off" 
                        layout="vertical" 
                        initialValues={formInitialValues }
                        submitter={{
                            resetButtonProps: {
                                style: {
                                  display: 'none',
                                }
                              },
                              submitButtonProps: {
                                style: {
                                  display: 'none',
                                },
                              },
                        }}
                    >
                        <ProForm.Group>
                            <Col span={24}>
                                <Row className={styles.row}>

                                    <Col
                                        className={styles.shiftNameField}
                                        span={9}
                                    >
                                        <ProFormText
                                            name="name"
                                            label={intl.formatMessage({
                                                id: 'workShifts.shiftName',
                                                defaultMessage: 'Shift Name',
                                            })}
                                            rules={[
                                                {
                                                    required: true,
                                                    message: (
                                                        <FormattedMessage
                                                            id="shiftName.required"
                                                            defaultMessage="Required"
                                                        />
                                                    ),
                                                },
                                                {
                                                    max: 100,
                                                    message: (
                                                        <FormattedMessage
                                                            id="shiftName.max"
                                                            defaultMessage="Maximum length is 100 characters."
                                                        />
                                                    ),
                                                }
                                            ]}
                                            value={formInitialValues.name} 
                                           
                                        />
                                    </Col>
                                    <Col className={styles.shiftCodeField}
                                        span={6}
                                    >

                                        <ProFormText
                                            name="code"
                                            label={intl.formatMessage({
                                                id: 'workshifts.shiftCode',
                                                defaultMessage: 'Shift Code',
                                            })}
                                            rules={[
                                                {
                                                    required: true,
                                                    message: (
                                                        <FormattedMessage
                                                            id="shiftCode.required"
                                                            defaultMessage="Required"
                                                        />
                                                    ),
                                                },
                                                {
                                                    max: 10,
                                                    message: (
                                                        <FormattedMessage
                                                            id="shiftCode.max"
                                                            defaultMessage="Maximum length is 10 characters."
                                                        />
                                                    ),
                                                }
                                            ]}
                                            value={formInitialValues.code}
                                        />
                                    </Col>

                                </Row>
                                <Row className={styles.row}>
                                    <Col
                                        span={5}
                                        className={styles.shiftTypeField}
                                    >
                                        <ProFormSelect
                                            name="shiftType"
                                            label={intl.formatMessage({
                                                id: 'workShifts.type',
                                                defaultMessage: 'Shift Type',
                                            })}
                                            initialValue={'GENERAL'}
                                            options={shiftTypes}
                                            rules={[
                                                {
                                                    required: true,
                                                    message: (
                                                        <FormattedMessage
                                                            id="workShifts.type.required"
                                                            defaultMessage="Required"
                                                        />
                                                    ),
                                                },
                                            ]}
                                            disabled={!_.isUndefined(id)}
                                            fieldProps={{
                                                onChange: (value) => {
                                                    setSelectedShiftType(value);
                                                },
                                            }}
                                        />
                                    </Col>
                                    <Col
                                        span={10}
                                        className={styles.dayTypeField}
                                    >
                                        <ProFormSelect
                                            name="dayType"
                                            label={intl.formatMessage({
                                                id: 'workShift.DayType',
                                                defaultMessage: 'Day Type',
                                            })}
                                            options={dayTypes}
                                            rules={[
                                                {
                                                    required: true,
                                                    message: (
                                                        <FormattedMessage
                                                            id="dayType.required"
                                                            defaultMessage="Required"
                                                        />
                                                    ),
                                                },
                                            ]}
                                            onChange={(value) =>{
                                                fetchWorkShiftDayType(value);
                                                setEnablePayConfig(false)
                                                const filteredData = dayTypeList.map((dayType) => {
                                                    if (dayType.id == value) {
                                                        return {
                                                          'dayTypeId': dayType.id,
                                                          'name' : dayType.name,
                                                          'shortCode' : dayType.shortCode,
                                                         'payTypeDetails': []
                                                       };
                                                   }  
                                                });
                                                const data = filteredData.filter(function( element ) {
                                                    return element !== undefined;
                                                 });
                                                
                                                setDayType(data);
                                            }}
                                           value={formInitialValues.dayType}
                                        />

                                    </Col>
                                </Row>
                                {
                                    showBehaveNonworkingCheckBox ? (
                                        <Row className={styles.row}>
                                            <Col
                                                span={8}
                                                style={{marginTop: 10, marginBottom: 30}}
                                            >
                                                <Checkbox
                                                    name="outOvertime"
                                                    className={styles.inOvertimeField}
                                                    onChange={(value) => {
                                                        setIsBehaveAsNonWorkingDay(value.target.checked);

                                                        if (value.target.checked) {
                                                            setTotalHours('00:00');
                                                        }

                                                        if (!value.target.checked && form.getFieldValue('noOfDay') == 0) {
                                                            form.setFieldsValue({
                                                                noOfDay: undefined
                                                            });
                                                        }
                                                        setRefresh(prev => prev + 1);
                                                    }}
                                                    checked={isBehaveAsNonWorkingDay}
                                                >

                                                    {intl.formatMessage({
                                                        id: 'workShifts.behaveAsNonworkig',
                                                        defaultMessage: 'Behave As Non Working Day',
                                                    })}
                                                </Checkbox>
                                            </Col>
                                        </Row>
                                    ) : (
                                        <></>
                                    )
                                }
                                <Row className={styles.row}>
                                    <Col
                                        span={5}
                                        className={styles.daysField}
                                    >
                                        <ProFormSelect
                                            name="noOfDay"
                                            width={'150px'}
                                            label={intl.formatMessage({
                                                id: 'days',
                                                defaultMessage: 'Days',
                                            })}
                                            valueEnum={isBehaveAsNonWorkingDay && form.getFieldValue('dayType') != 1 ? {
                                                '1': `${intl.formatMessage({
                                                    id: '1day',
                                                    defaultMessage: '1 Day',
                                                })}`,
                                                '0.5': `${intl.formatMessage({
                                                    id: '0.5day',
                                                    defaultMessage: '0.5 Day',
                                                })}`,
                                                '0': `${intl.formatMessage({
                                                    id: '0 day',
                                                    defaultMessage: '0 Day',
                                                })}`,
                                            } : isBehaveAsNonWorkingDay && form.getFieldValue('dayType') == 1 ?  {
                                                '0': `${intl.formatMessage({
                                                    id: '0 day',
                                                    defaultMessage: '0 Day',
                                                })}`,
                                            } : {
                                                '1': `${intl.formatMessage({
                                                    id: '1day',
                                                    defaultMessage: '1 Day',
                                                })}`,
                                                '0.5': `${intl.formatMessage({
                                                    id: '0.5day',
                                                    defaultMessage: '0.5 Day',
                                                })}`,
                                            }}
                                            rules={[
                                                {
                                                    required: true,
                                                    message: (
                                                        <FormattedMessage
                                                            id="days.required"
                                                            defaultMessage="Required"
                                                        />
                                                    ),
                                                },
                                            ]}
                                            value={formInitialValues.noOfDay}
                                        />
                                    </Col>
                                </Row>
                                <Row style={{width: 1000}}>
                                    <Col span={4} >
                                        <ProFormTimePicker
                                            name="startTime"
                                            width={'150px'}
                                            label={intl.formatMessage({
                                                id: 'startTime',
                                                defaultMessage: 'Start Time',
                                            })}
                                            placeholder={intl.formatMessage({
                                                id: 'startTime',
                                                defaultMessage: 'HH:mm',
                                            })}
                                            format="HH:mm"
                                            value={startTime && moment(startTime, 'HH:mm')}
                                            validateStatus={validatedStatusStartTime}
                                            help={helpStartTime}
                                            disabled = {isBehaveAsNonWorkingDay}
                                            required
                                            rules={[
                                                {
                                                    validator: (rule, value) => {
                                                        if (!startTime && moment(startTime, 'HH:mm')) {

                                                            setValidateStartTime('error');
                                                            setHelpStartTime('Required');
                                                            return Promise.reject();
                                                        } else {
                                                            setValidateStartTime('');
                                                            setHelpStartTime('');
                                                            return Promise.resolve();
                                                        }
                                                    },
                                                },
                                            ]}
                                            fieldProps={{

                                                onSelect: (value) => {
                                                    onchangeStart(value);
                                                    if (!value) {
                                                        setValidateStartTime('error');
                                                        setHelpStartTime('Required');
                                                    } else {
                                                        setValidateStartTime('');
                                                        setHelpStartTime('');
                                                    }
                                                },

                                            }}
                                        />
                                    </Col>
                                    <Col span={4} className={styles.timeField}>
                                        <div >
                                            <ProFormTimePicker
                                                name="endTime"
                                                label={intl.formatMessage({
                                                    id: 'endTime',
                                                    defaultMessage: 'End Time',
                                                })}
                                                placeholder={intl.formatMessage({
                                                    id: 'endTime.placeholder',
                                                    defaultMessage: 'HH:mm',
                                                })}
                                                width={'150px'}
                                                format="HH:mm"
                                                disabled = {isBehaveAsNonWorkingDay}
                                                className="endTime"
                                                validateStatus={validatedStatusEndTime}
                                                help={helpEndTime}
                                                required
                                                rules={[
                                                    {
                                                        validator: (rule, value) => {
                                                            if (!endTime && moment(endTime, 'HH:mm')) {
                                                                setValidateEndTime('error');
                                                                setHelpEndTime('Required');
                                                                return Promise.reject();
                                                            } else {
                                                                setValidateEndTime('');
                                                                setHelpEndTime('');
                                                                return Promise.resolve();
                                                            }
                                                        },
                                                    },
                                                ]}

                                                fieldProps={{
                                                    onSelect: (value) => {
                                                        onchangeEnd(value);
                                                        let startTimeVal = moment.isMoment(startTime) ? moment(startTime).format('HH:mm') : startTime;
                                                        if (!value) {
                                                            setValidateEndTime('error');
                                                            setHelpEndTime('Required');
                                                        } else if (moment(value).format('HH:mm') < startTimeVal) {     
                                                            setValidateEndTime('warning');
                                                            setHelpEndTime('Next Day');
                                                        } else {
                                                            setValidateEndTime('');
                                                            setHelpEndTime('');
                                                        }
                                                    }
                                                }}
                                                value={endTime && moment(endTime, 'HH:mm')}

                                            />
                                           
                                        </div>
                                    </Col>
                                    <Col span={4} className={styles.timeField}>

                                        <ProFormTimePicker
                                            name="breakTime"
                                            width={'150px'}
                                            label={intl.formatMessage({
                                                id: 'BreakTime',
                                                defaultMessage: 'Break Time',
                                            })}
                                            placeholder={intl.formatMessage({
                                                id: 'BreakTime.placeholder',
                                                defaultMessage: 'hh:mm',
                                            })}
                                            format="HH:mm"
                                            required
                                            validateStatus={validatedStatusBreakTime}
                                            help={helpBreakTime}
                                            rules={[
                                                {
                                                    validator: (rule, value) => {
                                                        if (!breakTime && moment(breakTime, 'HH:mm')) {
                                                            setValidateBreakTime('error');
                                                            setHelpBreakTime('Required');
                                                            return Promise.reject();
                                                        } else {
                                                            setValidateBreakTime('');
                                                            setHelpBreakTime('');
                                                            return Promise.resolve();
                                                        }
                                                    },
                                                }
                                            ]}
                                            fieldProps={{
                                                minuteStep: 5,
                                                onSelect: (value) => {
                                                    onBreakChange(value,null);
                                                    if (!value) {
                                                        setValidateBreakTime('error');
                                                        setHelpBreakTime('Required');
                                                    } else {
                                                        setValidateBreakTime('');
                                                        setHelpBreakTime('');
                                                    }
                                                }
                                            }}

                                            value={breakTime && moment(breakTime, 'HH:mm')}
                                        />
                                    </Col>
                                    {
                                        selectedShiftType == 'FLEXI' ? (
                                            <Col span={4} className={styles.timeField}>

                                                <ProFormTimePicker
                                                    name="flexiWorkHours"
                                                    label={intl.formatMessage({
                                                        id: 'flexiWorkHours',
                                                        defaultMessage: 'Work Hours',
                                                    })}
                                                    placeholder={intl.formatMessage({
                                                        id: 'flexiWorkHours.placeholder',
                                                        defaultMessage: 'HH:mm',
                                                    })}
                                                    format="HH:mm"
                                                    required
                                                    width={'150px'}
                                                    validateStatus={validatedStatusFlexiWorkHours}
                                                    help={helpFlexiWorkHours}
                                                    disabled = {isBehaveAsNonWorkingDay}
                                                    rules={[
                                                        {
                                                            validator: (rule, value) => {
                                                                if (!flexiWorkHours && moment(flexiWorkHours, 'HH:mm')) {
                                                                    setValidateStatusFlexiWorkHours('error');
                                                                    setHelpFlexiWorkHours('Required');
                                                                    return Promise.reject();
                                                                } else {
                                                                    setValidateStatusFlexiWorkHours('');
                                                                    setHelpFlexiWorkHours('');
                                                                    return Promise.resolve();
                                                                }
                                                            },
                                                        }
                                                    ]}
                                                    fieldProps={{
                                                        minuteStep: 5,
                                                        onSelect: (value) => {
                                                            onFlexiHourChange(value,null);
                                                            if (!value) {
                                                                setValidateStatusFlexiWorkHours('error');
                                                                setHelpFlexiWorkHours('Required');
                                                            } else {
                                                                setValidateStatusFlexiWorkHours('');
                                                                setHelpFlexiWorkHours('');
                                                            }
                                                        }
                                                    }}

                                                    value={flexiWorkHours && moment(flexiWorkHours, 'HH:mm')}
                                                />
                                            </Col>

                                        ) : (
                                            <></>
                                        )
                                    }
                                    {
                                        !isBehaveAsNonWorkingDay ? (
                                            <Col span={8} className={styles.timeField}>
                                                <Form.Item
                                                name="workHours"
                                                label={intl.formatMessage({
                                                    id: 'Hours',
                                                    defaultMessage: 'Total Work Hours',
                                                })}
                                                >
                                                <span className={styles.workHoursField}> {totalHours ? totalHours : 0} {intl.formatMessage({
                                                    id: 'workHours',
                                                    defaultMessage : 'Hours'
                                                })} </span>
                                                </Form.Item>
                                            </Col>
                                        ) : (
                                            <></>
                                        )
                                    }
                                    
                                </Row>
                                {
                                    !isBehaveAsNonWorkingDay ?(
                                        <Row className={styles.row}>
                                            <Col className={styles.duration} span={5}>
                                                <ProFormTimePicker
        
                                                    name="halfDayLength"
                                                    label={intl.formatMessage({
                                                        id: 'workShift.halfDayDuration',
                                                        defaultMessage: 'Half Day Duration',
                                                    })}
                                                    placeholder={intl.formatMessage({
                                                        id: 'halfDayDuration.placeholder',
                                                        defaultMessage: 'hh:mm',
                                                    })}
                                                    format="HH:mm"
                                                    width={'150px'}
                                                    fieldProps={{
                                                        minuteStep: 5,
                                                        onSelect: (value) => {
                                                            const halfDayDurationValue = moment(value).format('HH:mm');
                                                            setHalfDayDuration(halfDayDurationValue);
        
                                                        }
                                                    }}
        
                                                    value={halfDayDuration && moment(halfDayDuration, 'HH:mm')}
                                                />
                                            </Col>
                                            {
                                                selectedShiftType != 'FLEXI' ? (
                                                    <Col className={styles.gracePeriodField} span={5}>
                                                        <ProFormTimePicker
                                                            tooltip={intl.formatMessage({
                                                                id: 'workShift.tooltip',
                                                                defaultMessage: 'This time will not be counted as over time',
                                                            })}
                
                                                            name="gracePeriod"
                                                            label={intl.formatMessage({
                                                                id: 'workShift.GracePeriod',
                                                                defaultMessage: 'Grace Period',
                                                            })}
                                                            placeholder={intl.formatMessage({
                                                                id: 'GracePeriod.placeholder',
                                                                defaultMessage: 'hh:mm',
                                                            })}
                                                            format="HH:mm"
                                                            width={'150px'}
                                                            fieldProps={{
                                                                minuteStep: 5,
                                                                onSelect: (value) => {
                                                                    const gracePeriodValue = moment(value).format('HH:mm');
                                                                    setGracePeriod(gracePeriodValue);
                                                                }
                                                            }}
                
                                                            value={gracePeriod && moment(gracePeriod, 'HH:mm')}
                                                        />
                                                    </Col>

                                                ) : (
                                                    <></>
                                                )
                                            }
                                            
                                        </Row>
                                    ) : (
                                        <></>
                                    )
                                }
                                <Row className={styles.row}>
                                    <Col className={styles.duration} span={5}>
                                        <div style={{ display: 'flex' }} className="color-input">
                                            <ProFormText
                                                width={'105px'}
                                                name='color'
                                                label='Color Code'
                                                rules={[
                                                    {
                                                        pattern: /^#[0-9A-F]{6}$/i,
                                                        message: intl.formatMessage({
                                                            id: 'name',
                                                            defaultMessage: 'Invalid hex value.',
                                                        }),
                                                    },
                                                ]}
                                                fieldProps={{
                                                    onChange: (value) => {

                                                        if (value.target.value) {

                                                            setColor(value.target.value);
                                                            setIconColor(value.target.value);
                                                        } else {

                                                            setColor('#000000');
                                                            setIconColor('#000000');
                                                        }
                                                    },
                                                    autoComplete: "none",
                                                    value: color !== '#000000' ? color : ''
                                                }}

                                                initialValue={null}

                                            />
                                            <Button onClick={() => {
                                                if (!showColorPicker) {
                                                    setShowColorPicker(true);
                                                } else {
                                                    setShowColorPicker(false);
                                                }
                                            }} style={{ borderTopLeftRadius: 0, borderBottomLeftRadius: 0, marginLeft: -4, marginTop: 30 }} ><BgColorsOutlined style={style.colorVal} /></Button>

                                        </div>

                                        {
                                            showColorPicker ? (
                                                <>
                                                    <div style={style.cover} onClick={handleClose} />
                                                    <div style={popover}>
                                                        <SketchPicker color={color} onChange={handleChange} />
                                                    </div>
                                                </>

                                            ) : (
                                                <></>
                                            )
                                        }
                                     </Col>
                                     <Col className={styles.gracePeriodField} span={5}>
                                        <ProFormSelect
                                            name="isActive"
                                            label={intl.formatMessage({
                                                id: 'workShifts.status',
                                                defaultMessage: 'Status',
                                            })}
                                            width={'150px'}
                                            valueEnum={{
                                                1: `${intl.formatMessage({
                                                    id: 'workShifts.status.Active',
                                                    defaultMessage: 'Active',
                                                })}`,
                                                0: `${intl.formatMessage({
                                                    id: 'workShifts.type.InActive',
                                                    defaultMessage: 'Inactive',
                                                })}`,
                                            }}
                                            initialValue={'1'}
                                        />
                                    </Col>    
                                </Row>
                                <Row className={styles.row}>
                                    <Text className={styles.otTextField}>
                                        {intl.formatMessage({
                                            id: 'workShifts.CalculateOT',
                                            defaultMessage: 'Calculate OT',
                                        })}
                                    </Text>
                                    <Switch
                                        checkedChildren="Yes"
                                        unCheckedChildren="No"
                                        defaultChecked={false}
                                        onChange={(checked: boolean, event: Event) => {
                                            setIsOTEnabled(checked);

                                            if (!checked) {
                                                setEnablePayConfig(false);
                                            }
                                        }}
                                        checked={isOTEnabled}
                                    />

                                </Row>
                                {isOTEnabled &&
                                    <>
                                        <Row className={styles.overTime}>
                                        {
                                            selectedShiftType == 'GENERAL' && !isBehaveAsNonWorkingDay ? (
                                                <Checkbox
                                                    name="inOvertime"
                                                    className={styles.inOvertimeField}
                                                    onChange={(value) => {
                                                        setInOverTime(value.target.checked)
                                                    }}
                                                    checked={inOvertime}
                                                >
                                                    {intl.formatMessage({
                                                        id: 'workShifts.InOvertime',
                                                        defaultMessage: 'In Overtime',
                                                    })}
                                                </Checkbox>
                                            ) : (
                                                <></>
                                            )
                                        }
                                        
                                        <Checkbox
                                            name="outOvertime"
                                            className={styles.inOvertimeField}
                                            onChange={(value) => {
                                                setOutOverTime(value.target.checked)
                                            }}
                                            checked={outOvertime}
                                        >

                                            {intl.formatMessage({
                                                id: 'workShifts.outOvertime',
                                                 defaultMessage: 'Out Overtime',
                                            })}
                                        </Checkbox>

                                        {
                                            !isBehaveAsNonWorkingDay ? (
                                                <Checkbox
                                                    name="deductLateFromOvertime "
                                                    className={styles.deductTimeField}
                                                    onChange={(value) => {
                                                        setDeductLateFromOvertime(value.target.checked)
                                                    }}
                                                    checked={deductLateFromOvertime}
                                                >
                                                    {intl.formatMessage({
                                                        id: 'workShifts.DeductLateFromOvertime',
                                                        defaultMessage: 'Deduct late from Overtime ',
                                                    })}

                                                </Checkbox>
                                            ) : (
                                                <></>
                                            )
                                        }
                                        
                                        </Row>

                                        <Row className={styles.overTime}>
                                            <Col className={styles.duration} span={5}>
                                                <ProFormTimePicker
                                                    name="minimumOT"
                                                    label={intl.formatMessage({
                                                        id: 'workShift.MinimumOTDuration',
                                                        defaultMessage: 'Minimum OT Duration',
                                                    })}
                                                    placeholder={intl.formatMessage({
                                                        id: 'Minimum OT.placeholder',
                                                        defaultMessage: 'hh:mm',
                                                    })}
                                                    format="HH:mm"
                                                    width={'150px'}
                                                    fieldProps={{
                                                        minuteStep: 5,
                                                        onSelect: (value) => {
                                                            
                                                            const minimumOTValue = moment(value).format('HH:mm');
                                                            setMinimumOT(minimumOTValue);
                                                        },
                                                        onChange: (value) => {
                                                            if (value == null) {
                                                                setMinimumOT(null);
                                                            } else {
                                                                const minimumOTValue = moment(value).format('HH:mm');
                                                                setMinimumOT(minimumOTValue);
                                                            }
                                                        }
                                                        
                                                    }}

                                                    value={minimumOT && moment(minimumOT, 'HH:mm')}
                                                />
                                            </Col>
                                            <Col className={styles.gracePeriodField} span={5}>
                                                <ProFormSelect
                                                    name="roundOffMethod"
                                                    label={intl.formatMessage({
                                                        id: 'workShifts.Roundoffmethod',
                                                        defaultMessage: 'Round off method',
                                                    })}
                                                    options={roundOffMethod}
                                                    width={'150px'}
                                                    fieldProps={{
                                                        onSelect: (value) => {
                                                            if (value === 'ROUND_UP' || value === 'ROUND_DOWN') {
                                                                setRoundUp(true);
                                                            }
                                                            if (value === 'NO_ROUNDING') {
                                                                setRoundUp(false);
                                                            }
                                                        }
                                                    }}
                                                    value={formInitialValues.roundOffMethod}
                                                />
                                            </Col>
                                            <Col className={styles.gracePeriodField} span={5}>
                                                {roundUp &&
                                                    <ProFormSelect
                                                        name="roundOffToNearest"
                                                        width={'150px'}
                                                        label={intl.formatMessage({
                                                            id: 'workShifts.Roundoftonearest',
                                                            defaultMessage: 'Round off to nearest',
                                                        })}
                                                        options={roundOfftoNearest}
                                                        value={formInitialValues.roundOffToNearest}

                                                    />
                                                }
                                            </Col>
                                        </Row>
                                    </>
                                }
                                {
                                    isOTEnabled ? (
                                        <>
                                            <Row className={styles.enablePayColField }>
                                                <Text className={styles.enablePayTextField}>
                                                    {intl.formatMessage({
                                                        id: 'workShifts.payConfig',
                                                        defaultMessage: 'Enable Pay Configuration',
                                                    })}
                                                </Text>
                                                <Switch
                                                    checkedChildren="Yes"
                                                    unCheckedChildren="No"
                                                    defaultChecked={false}
                                                    onChange={(checked: boolean, event: Event) => {
                                                        setEnablePayConfig(checked);
                                                        payTypeConfigData(dayType[0]);
                                                        
                                                    }}
                                                    checked={enablePayConfig}
                                                />
                                            </Row>
                                            <Row className={styles.enablePayColField }>
                                                <Text className={styles.subHeading }>
                                                    {intl.formatMessage({
                                                        id: 'workShifts.secondaryText',
                                                        defaultMessage: 'Set up the pay configuration for the selected Work Shift.',
                                                    })}
                                                </Text>
                                            </Row>
                                            <>
                                            { enablePayConfig  &&
                                                <>
                                                    <Col span={20} style={{ marginBottom: 10 }}>
                                                        <div style={{ backgroundColor: '#f0f2f5', borderRadius: 10 }}>
                                                            <div onClick={() => {

                                                                let selectedListCopy = [...selectedDayTypes];
                                                                let dayTypeEnulListCopy = [...dayTypeEnumList];
                                                                let filteredList = [];
                                                                let filtereIdList = [];

                                                                selectedListCopy.map((daytype) => {
                                                                    if (daytype.dayTypeId != dayType[0].dayTypeId) {
                                                                        filteredList.push(daytype);
                                                                        filtereIdList.push(daytype.shortCode);
                                                                    }
                                                                })


                                                                let filteredEnumList = dayTypeEnulListCopy.map((enumItem) => {
                                                                    if (enumItem.dayTypeId == dayType[0].dayTypeId) {
                                                                        enumItem.disabled = false;
                                                                    }
                                                                    return enumItem;
                                                                })

                                                                setSelectedDayTypes(filteredList);
                                                                setSelectedDayTypeIds(filtereIdList);
                                                                setDayTypeEnumList(filteredEnumList);

                                                                setRefresh(prev => prev + 1);
                                                            }} style={{ float: 'right', paddingRight: 15, paddingTop: 10, cursor: 'pointer' }}>{selectedDayTypes.length > 0 && 'X'}</div>
                                                            <Row style={{ marginLeft: 20, marginBottom: 20 }}>

                                                            </Row>
                                                            <PayTypeConfig isBehaveAsNonWorkingDay={isBehaveAsNonWorkingDay} refresh={refresh} dayTypeArrIndex={0} selectedShiftType = {selectedShiftType}  isMaintainTimeBasePayConfig={isMaintainTimeBasePayConfig}  selectedDayTypes={selectedDayTypes} setValues={setSelectedDayTypes}  />
                                                        </div>
                                                    </Col>
                                                </>
                                            
                                            }
                                            </>
                                        </>

                                    ) : (
                                        <></>
                                    )
                                }
                                
                                
                            </Col>
                        </ProForm.Group>
                        <Row>
                            <Col span={24} className={styles.footer}>
                                <Form.Item>
                                    <Space>
                                        <Button
                                            htmlType="button"
                                            onClick={() => {
                                                form.resetFields();
                                                setIsOTEnabled(false);
                                                setRoundUp(false);
                                                setStartTime();
                                                setEndTime();
                                                setBreakTime();
                                                setTotalHours();
                                                setMinimumOT();
                                                setGracePeriod();
                                                setHalfDayDuration();
                                                setInOverTime(false);
                                                setOutOverTime(false);
                                                setDeductLateFromOvertime(false);
                                                setColor();
                                                setColorCode();;
                                                setIconColor();
                                            }}
                                        >
                                            {intl.formatMessage({
                                                id: "pages.workshift.reset",
                                                defaultMessage: "Reset"
                                            })}
                                        </Button>
                                        <Button type="primary" htmlType="submit" >
                                            {intl.formatMessage({
                                                id: "pages.workshift.save",
                                                defaultMessage: `${buttonText}`
                                            })}
                                        </Button>
                                    </Space>
                                </Form.Item>
                            </Col>
                        </Row>

                    </ProForm>
                   
                </Card>
                
            </PageContainer>
        </Access>
    );
};
