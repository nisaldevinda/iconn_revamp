import React, { useEffect, useRef, useState } from 'react';
import { PageContainer } from '@ant-design/pro-layout';
import { Button, Tooltip, Popconfirm, message as Message ,Row, Space ,Badge,Checkbox ,Switch,Typography} from 'antd';
import type { ProColumns, ActionType } from '@ant-design/pro-table';
import ProTable from '@ant-design/pro-table';
import { history , useIntl, useAccess, Access ,FormattedMessage} from 'umi';
import { EditOutlined, DeleteOutlined, PlusOutlined  } from '@ant-design/icons';
import { getAllWorkShifts ,deleteWorkShifts} from '@/services/workShift';
import PermissionDeniedPage from './../403';
import { getModel } from '@/services/model';
import './style.css';
import moment from 'moment';
import _ from 'lodash';

import { ReactComponent as LeavetypeSettings } from '../../assets/attendance/leaveTypeSettings.svg';
export default (): React.ReactNode => {
  const tableRef = useRef<ActionType>();
  const [searchText, setSearchText] = useState('');
  const intl = useIntl();
  
  const access = useAccess();
  const { hasPermitted } = access;

  const [roundOffMethod , setRoundOffMethod] = useState([]);
  const [shiftTypes, setShiftTypes] = useState([]);
  const [roundOfftoNearest ,setRoundOffToNearest] = useState([]);

 
  const {Text} = Typography;
  
  useEffect(() =>{
    getShiftTypes();
  },[])
  const deleteShift = async (id: String) => {
    try {
      const { message } = await deleteWorkShifts(id);
      Message.success(message);
      tableRef.current?.reload();
    } catch (err) {
      console.log(err);
    }
  };
  const getShiftTypes= async() =>{
    const {data} = await getModel("workShifts");
    setShiftTypes(data.modelDataDefinition.fields.shiftType.values);
    
  }
  
  const generateShiftEnum = () => {
    const valueEnum = {};
    shiftTypes.forEach(element => {
      valueEnum[element.value] = {
        text: element.defaultLabel
      }
    });
    return valueEnum
  }

 
  const columns: ProColumns[] = [
    {
      key: 'name',
      title: 
      <>
        <Row>
            <span style={{whiteSpace:"noWrap"}}> 
              <FormattedMessage id="workShifts.shiftName" defaultMessage="Shift Name" />
            </span> 
        </Row>
      </>,
      width: 30,
      render:(record)=>{
        return (
        <Space>
          <span>{record.name}</span>
        </Space>
        )
      }
    },
    {
      key: 'shiftColor',
      title:
        <>
          <Row><span style={{ whiteSpace: "noWrap" }}>
            <FormattedMessage id="workShifts.shiftColor" defaultMessage="Shift Color" />
          </span>
          </Row>
        </>,
      render: (record) => {
        return (
          <Space>
            <div style={{ height: '15px', backgroundColor: record.color, width: '15px', borderRadius: '6px' }} />
            <span>{record.color}</span>
          </Space>
        )
      },
      width: 50
    },
    {
      key: 'shiftCode',
      title: 
        <>
          <Row><span style={{whiteSpace:"noWrap"}}>
            <FormattedMessage id="workShifts.shiftCode" defaultMessage="Shift Code" />
           </span>
           </Row>
        </>,   
      dataIndex: 'code',
      width:50
    },
    {
        key: 'shiftType',
        title: 
          <>
             <Row><span style={{whiteSpace:"noWrap"}}> <FormattedMessage id="workShifts.shiftType" defaultMessage="Shift Type" /></span></Row>
          </>,
        dataIndex: 'shiftType',
        width:50,
        filters: true,
        onFilter: true,
        valueEnum: generateShiftEnum()
    },
    {
        key: 'isActive',
        title: <FormattedMessage id="workShifts.status" defaultMessage="Status" />,
        dataIndex: 'isActive',
        width:50,
        //filters: true,
        onFilter: true,
        filters: [
          {
            text: 'Active',
            value: '1',
          },
          {
            text: 'InActive',
            value: '0',
          },
        ],
        //valueEnum: generateEnum()
        render: (_, record) => (
          <Space>
            {record.isActive == 1 &&
             <Badge status="success" text="Active"  />}
              {record.isActive == 0 &&
             <Badge status="error" text="InActive" />}
          </Space>
        ),
    },
    {
      key: 'actions',
      title: <FormattedMessage id="workShifts.actions" defaultMessage="Actions" />,
      dataIndex: 'option',
      valueType: 'option',
      width: 50,
      render: (_, record) => [
        <Access accessible={hasPermitted('work-shifts-read-write')}>
          {/* <Tooltip key="pay-config-tool-tip" title="Pay Configuration">
            <a
              key="pay-config-btn"
              onClick={() => {
                const { id } = record;
                history.push(`/settings/work-shifts/${id}`);
              }}
            >
              <LeavetypeSettings height={16} />
            </a>
          </Tooltip> */}
      </Access>,
        <Access accessible={hasPermitted('work-shifts-read-write')}>
          <Tooltip key="edit-tool-tip" title="Edit">
            <a
              key="edit-btn"
              onClick={() => {
                history.push(`/settings/work-shifts/${record.id}`);   
              }}
             style={{color :'#86C129'}}
            >
              <EditOutlined />
            </a>
          </Tooltip>
        </Access>,
        <Access accessible={hasPermitted('work-shifts-read-write')}>

          <div onClick={(e) => e.stopPropagation()}>
            <Popconfirm
              key="delete-pop-confirm"
              placement="topRight"
              title="Are you sure?"
              okText="Yes"
              cancelText="No"
              onConfirm={() => {
                const { id } = record;
                deleteShift(id);
              }}
            >
              <Tooltip key="delete-tool-tip" title="Delete">
                <a key="delete-btn">
                  <DeleteOutlined />
                </a>
              </Tooltip>
            </Popconfirm>
          </div>
        </Access>,
      ],
    },
  ];
  
  const handleSearch = () => {
    return {
      className: 'basic-container-search',
      placeholder: "Search by Work Shift Name",
      onChange: (value: any) => {
        setSearchText(value.target.value);
        if (_.isEmpty(value.target.value)) {
          tableRef.current?.reset();
          tableRef.current?.reload();
        }
      },
      value:searchText
    };
  };
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

   return (
    <Access
      accessible={hasPermitted('work-shifts-read-write')}
      fallback={<PermissionDeniedPage />}
    >
      <PageContainer>
        <ProTable<any>
           actionRef={tableRef}
           rowKey="id"
           search={false}
           toolbar={{
            search: handleSearch()
           }}
           toolBarRender={() => [
               <Access accessible={hasPermitted('work-shifts-read-write')}>
                  <Button
                    type="primary"
                    key="primary"
                    onClick={() => {
                      history.push('/settings/work-shifts/new')
                    }}
                  >
                    <PlusOutlined /> Add Work Shift
                  </Button>
                </Access>
            ]}
            options={{ fullScreen: false,
              search: true, 
              reload: () => {
                tableRef.current?.reset();
                tableRef.current?.reload();
                setSearchText('');
              }, 
              setting: false 
          }}
          request={async ({ pageSize, current }, sort , _filter) => {
            
            const filter = Object.keys(_filter)
            .filter((key) => !_.isEmpty(_filter[key]))
            .reduce((obj, key) => {
              obj[key] = _filter[key];
              return obj;
            }, {});
            const { data } = await  getAllWorkShifts({ pageSize, current, sort, searchText ,filter});
            return {
              data: data.data,
              success: true,
              total: data.total,
            };
          }}
          columns={columns}
          onRow={(record, rowIndex) => {
            return {
              onClick: async () => {
                history.push(`/settings/work-shifts/${record.id}`);
              },
            };
          }}
          
        />

      </PageContainer>
    </Access>
  );
};
