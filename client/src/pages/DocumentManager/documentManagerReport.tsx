import React, {useRef, useEffect, useState } from 'react';
import { PageContainer } from '@ant-design/pro-layout'
import { Card ,Col , Row, Transfer ,Button , Form ,Select, Space ,Switch ,Image, DatePicker ,Typography, message} from 'antd';
import ProTable from '@ant-design/pro-table';
import ProForm, { ProFormSelect } from "@ant-design/pro-form";
import type { ProColumns, ActionType } from '@ant-design/pro-table';
import { Access, useAccess, useIntl, FormattedMessage } from 'umi';
import ExportIcon from '../../assets/leaveEntitlementUsageReport/export-csv-file-icon.svg';
import { downloadBase64File } from '@/utils/utils';
import PermissionDeniedPage from '@/pages/403';
import moment from 'moment';
import { getEmployeeList ,getManagerList , getSubordinatesList} from '@/services/dropdown';
import { getAllLocations } from '@/services/location';
import { getdocumentManagerList } from '@/services/documentManager';
import { humanReadableFileSize } from "@/utils/utils"
import styles from './styles.less';
const DocumentManagerReport: React.FC = () => {
    const tableRef = useRef<ActionType>();
    const { Option } = Select;
    const { Text } = Typography;
    const [form] = Form.useForm();
    const intl = useIntl();
    const access = useAccess();
    const { hasPermitted } = access;
    const [fromDate, setFromDate] = useState<Date | null>(null);
    const [toDate, setToDate] = useState<Date | null>(null);
    const [reportData, setReportData] = useState({});
    const { RangePicker } = DatePicker;
    const [documentManagerRecord , setDocumentManagerRecord] = useState([]);
    const [selectedEmployees, setSelectedEmployees] = useState([]);
    const [initializing, setInitializing] = useState(false);
    const [audienceMethod, setAudienceMethod] = useState([]);
    const [audienceType, setAudienceType] = useState('');
    const [adminEmployees, setAdminEmployees] = useState([]);
    const [managers, setManagers] = useState([]);
    const [locations, setLocations] = useState([]);
    const [targetKeys, setTargetKeys] = useState<string[]>([]);
    const [managerEmployees, setManagerEmployees] = useState([]);
    const [employeesList , setEmployeesList] = useState([]);
    const [managerId , setManagerId] = useState('');

    useEffect(() =>{
      init();
    },[]);
    
    const init = async () => {
        setInitializing(true);

        const adminEmployeesRes = await getEmployeeList("ADMIN");
        setAdminEmployees(adminEmployeesRes?.data.map(employee => {
            return {
                title: employee.employeeNumber+' | '+employee.employeeName,
                key: employee.id
            };
        }));


        const managerRes = await getManagerList();
        setManagers(managerRes?.data.map(manager => {
            return {
                label: manager.employeeNumber+' | '+manager.employeeName,
                value: manager.id
            };
        }));

        const locationRes = await getAllLocations();
        setLocations(Object.values(locationRes?.data.map(location => {
            return {
                label: location.name,
                value: location.id
            };
        })));

        setInitializing(false);
        const _audienceMethod = [];
        _audienceMethod.push({ label: `${intl.formatMessage({ id: 'ALL', defaultMessage: 'All' })}`, value: 'ALL' });
        _audienceMethod.push({ label: `${intl.formatMessage({ id: 'ASSIGN_TO_MANAGER', defaultMessage: 'Assign To Manager' })}`, value: 'REPORT_TO' });
        _audienceMethod.push({ label: `${intl.formatMessage({ id: 'LOCATION', defaultMessage: 'Location' })}`, value: 'QUERY' });
        _audienceMethod.push({ label: `${intl.formatMessage({ id: 'CUSTOM', defaultMessage: 'Custom' })}`, value: 'CUSTOM' });
        setAudienceMethod(_audienceMethod);
    }
   const fetchSubordinateData = async (id:any) =>{
        try { 
           const { data } = await getSubordinatesList(id);
           setManagerEmployees(data.map(employee => {
           return {
              title: employee.employeeName,
              key: employee.id
            }}));
             setEmployeesList(data);
        }catch (error) {
            message.error(error.message);
        }
   }
    const changeDateRange = (ranges: object) => {
        if (ranges != null) {
          
          setFromDate(ranges[0].format('YYYY-MM-DD'));
          setToDate(ranges[1].format('YYYY-MM-DD'));
        } else {
          
          setFromDate(null);
          setToDate(null);
        }
    };
    
    const onFinish = async (params:any,) => {
      let audience = { ...selectedEmployees};
    
      switch (audienceType) {
        case 'REPORT_TO':
            audience = {
                employeeIds: targetKeys
              };
          break;
        case 'QUERY':
          audience = {
            locationId:  form.getFieldValue('queryLocation')
          };
          break;
        case 'CUSTOM':
          audience = {
            employeeIds: targetKeys
          };
          break;
        default:
          audience = {};
          break;
      }
      const requestData ={
          type: "table",
          pageNo: 1,
          pageCount: 10,
          fromDate: fromDate,
          toDate: toDate,
          audienceType: audienceType ? audienceType : null,
          audienceData : audience
      }
     
      setReportData(requestData);

      const {message,data} = await getdocumentManagerList(requestData);
      setDocumentManagerRecord(data);
    }

    const reset =() =>{
      form.resetFields();
      setDocumentManagerRecord([]);
    }
    const columns : ProColumns<any>[] =  [
        {
            key: 'employeeName',
            title: <span style={{whiteSpace:"noWrap"}}><FormattedMessage id="pages.documentManagerReport.employeeName" defaultMessage="Employee Name" /> </span>,
            dataIndex: 'employeeName',
            width:120
        },
        {
          key: 'documentName',
          title: <span style={{whiteSpace:"noWrap"}}> <FormattedMessage id="pages.documentManagerReport.name" defaultMessage="Document Name" /> </span>,
          dataIndex: 'documentName',
          width:120
        },
        {
          key: 'documentDescription',
          title: <span style={{whiteSpace:"noWrap"}}> <FormattedMessage id="pages.documentManagerReport.documentDescription" defaultMessage="Document Description" /> </span>,
          dataIndex: 'documentDescription',
          width:120
        },
        {
            key: 'fileName',
            title: <span style={{whiteSpace:"noWrap"}}> <FormattedMessage id="pages.documentManagerReport.fileName" defaultMessage="File Name" /> </span>,
            dataIndex: 'name',
            width:120
        },
        {
            key: 'fileSize',
            title: <span style={{whiteSpace:"noWrap"}}>  <FormattedMessage id="pages.documentManagerReport.fileSize" defaultMessage="File Size" /> </span>,
            width:120,
            render: (_, record) => humanReadableFileSize(record.size)
        },
        {
            key: 'isAcknowledged',
            title: <span style={{whiteSpace:"noWrap"}}>  <FormattedMessage id="pages.documentManagerReport.isAcknowledged" defaultMessage="Acknowledged" /> </span>,
           
            width:120,
            render:(_record) =>{
                return _record.isAcknowledged ? 'Yes': 'No';
            }
        },
        {
            key: 'acknowledgedDate',
            title: <span style={{whiteSpace:"noWrap"}}>  <FormattedMessage id="pages.documentManagerReport.acknowledgedDate" defaultMessage="Acknowledged Date" /> </span>,
         
            width:120,
            render:(record) =>{
               return record.isAcknowledged ?  moment(record.acknowledgedDate).format('DD-MM-YYYY HH:mm:ss') : '';
            }
        },
        
      ];
   
    return (
      <Access
        accessible={hasPermitted('document-manager-read-write')}
        fallback={<PermissionDeniedPage />}
      >
            <PageContainer>
                <Space direction="vertical" size={25} style={{ width: '100%' }}>
                    <div
                        style={{ borderRadius: '10px', background: '#FFFFFF', padding: '32px', width: '100%' }}
                    >
                        <Form form={form} onFinish={onFinish} autoComplete="off" layout="vertical" >
                            <Row>
                                <Col
                                    span={12}
                                    style={{
                                        height: 120,
                                        width: 250                               
                                    }}
                                >
                                    <Form.Item
                                        label={intl.formatMessage({
                                            id: 'pages.documentManagerReport.audience',
                                            defaultMessage: 'Audience',
                                        })}
                                        required
                                    >
                                        <Text type="secondary">
                                            {intl.formatMessage({
                                                id: 'pages.documentManagerReport.secondary.label',
                                                defaultMessage: 'Select the employees you want to generate the report.',
                                            })}
                                        </Text>
                                    </Form.Item>
                                    <ProFormSelect
                                        width="sm"
                                        name="audienceMethod"
                                        options={audienceMethod}
                                        onChange={(value) => {
                                        
                                            setManagerId('');
                                            setTargetKeys([]);
                                            setManagerEmployees([]);
                                            setAudienceType(value);
                                            
                                        }}
                                        rules={
                                            [
                                                {
                                                    required: true,
                                                    message: intl.formatMessage({
                                                        id: 'pages.documentManagerReport.audienceMethod',
                                                        defaultMessage: 'Required',
                                                    })
                                                },
                                            ]
                                        }
                                        placeholder={intl.formatMessage({
                                            id: 'pages.document.audienceType',
                                            defaultMessage: 'Select Audience Type',
                                        })}
                                    />
                                </Col>
                            </Row>   
                            <br /> 
                            <Row>
                                <Space>
                                    {!initializing && audienceType == 'REPORT_TO' &&
                                        <ProFormSelect
                                            width="sm"
                                            name="reportToManager"
                                            label={intl.formatMessage({
                                                id: 'pages.documentManagerReport.SELECT_A_MANAGER',
                                                defaultMessage: 'Select a Manager',
                                            })}
                                            options={managers}
                                            rules={
                                                [
                                                    {
                                                        required: true,
                                                        message: intl.formatMessage({
                                                            id: 'pages.documentManagerReport.topic',
                                                            defaultMessage: 'Required',
                                                        })
                                                    },
                                                ]
                                            }
                                            onChange={(value) => {
                                               setManagerId(value);
                                               fetchSubordinateData(value);
                                             }}
                                            placeholder={intl.formatMessage({
                                                id: 'pages.document.manager',
                                                defaultMessage: 'Select Manager',
                                            })}
                                        />
                                    }

                                    {!initializing && audienceType == 'QUERY' &&
                                        <ProFormSelect
                                            width="sm"
                                            name="queryLocation"
                                            label={intl.formatMessage({
                                                id: 'pages.documentManagerReport.SELECT_A_LOCATION',
                                                defaultMessage: 'Select a Location',
                                            })}
                                            options={locations}
                                            rules={
                                                [
                                                    {
                                                        required: true,
                                                        message: intl.formatMessage({
                                                            id: 'pages.documentManagerReport.topic',
                                                            defaultMessage: 'Required',
                                                        })
                                                    },
                                                ]
                                            }
                                            placeholder={intl.formatMessage({
                                                id: 'pages.document.location',
                                                defaultMessage: 'Select Location',
                                            })}
                                        />
                                    }
                                </Space>
                            </Row>
                            <Row>
                                {!initializing && (audienceType == 'CUSTOM'  || audienceType == 'REPORT_TO')&&
                                    <Transfer
                                       dataSource={ audienceType == 'CUSTOM' ? adminEmployees : managerEmployees}
                                        showSearch
                                        filterOption={(search, item) => { return item.title.toLowerCase().indexOf(search.toLowerCase()) >= 0; }}
                                        targetKeys={targetKeys}
                                        onChange={(newTargetKeys: string[]) => {
                                            setTargetKeys(newTargetKeys);
                                        }}
                                        render={item => item.title}
                                        listStyle={{
                                            width: 300,
                                            height: 300,
                                            marginBottom: 20
                                        }}
                                    />
                                }

                            </Row>
                            <Row>
                       
                                <Col
                                    span={6}
                                    style={{
                                        height: 35,
                                        width: 350,
                                        paddingLeft: 5,
                                        marginTop :5
                                    }}
                                >
                                    <Form.Item
                                        style={{ marginBottom: 16, width: 320 }}
                                        name="date"
                                        label="Date"
                                        rules={[
                                            {
                                                required: true,
                                                message: intl.formatMessage({
                                                    id: 'pages.documentManagerReport.date',
                                                    defaultMessage: 'Required',
                                                }),
                                            },
                                        ]}
                                    >
                                        <RangePicker
                                            format="DD-MM-YYYY"
                                            onChange={changeDateRange}
                                            style={{ width: '100%' }}
                                            placeholder={[intl.formatMessage({
                                                id :'attendance.startDate',
                                                defaultMessage :'Start Date'
                                              }), intl.formatMessage({
                                                id :'attendance.endDate',
                                                defaultMessage :'End Date'
                                              })]}
                                        />
                                    </Form.Item>
                                </Col>
                              
                                <Col span={4}
                                    className={styles.resetCol}
                                 >
                                        <Space >
                                            <Button onClick={reset} type="primary">
                                                <FormattedMessage id="pages.documentManagerReport.reset" defaultMessage="Reset" />
                                            </Button>
                                            <Button htmlType="submit" type="primary">
                                                <FormattedMessage id="pages.documentManagerReport.search" defaultMessage="Search" />
                                            </Button>
                                        </Space>
                                </Col>
                             

                            </Row>
                        </Form>
                    </div>
                    <br />
                    <Card>
                        <Row>
                            <Col span={24} style={{ textAlign: 'right', paddingRight: 25 }}>
                                <Button
                                    htmlType="button"
                                    style={{
                                        background: '#FFFFFF',
                                        border: '1px solid #B8B7B7',
                                        boxSizing: 'border-box',
                                        borderRadius: '6px'
                                    }}
                                    icon={<Image src={ExportIcon} preview={false} />}
                                    onClick={async () => {
                                        const excelData = reportData;
                                        excelData.type ="";
                                        const { data } = await getdocumentManagerList(excelData);
                                       
                                        if (data) {
                                            downloadBase64File(
                                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                                data,
                                                'DocumentManagerAcknowledged.xlsx',
                                            )
                                        }
                                    }}
                                >
                                    <span style={{ verticalAlign: 'top', paddingLeft: '4px' }}> Export</span>
                                </Button>
                            </Col>
                        </Row>
                        <br />
                        <ProTable<any>
                            actionRef={tableRef}
                            rowKey="id"
                            search={false}
                            options={false}
                            columns={columns}
                            dataSource={documentManagerRecord}
                            className="custom-table"
                            pagination={{ pageSize: 10, defaultPageSize: 10, hideOnSinglePage: true }}
                        />
                    </Card>
                </Space>
            </PageContainer>
      </Access>
    );
};

export default DocumentManagerReport;