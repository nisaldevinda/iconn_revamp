import {
    Button, Checkbox, Col, Tabs, Form, Radio, Row, Select, Spin, message as Message, Card, Table, Menu, Dropdown,
} from 'antd';
import React, { useState, useRef, Key, useEffect } from 'react';
import { useIntl, FormattedMessage, useParams, history, Access, useAccess } from 'umi';
import ProTable, { ProColumns } from '@ant-design/pro-table';
import { PageContainer } from '@ant-design/pro-layout';
import { BarChartOutlined, DownloadOutlined, FileTextOutlined, PieChartOutlined, StockOutlined } from '@ant-design/icons';
import _ from 'lodash';
import type { IProps, TableType } from './data.d';
import './reportViewTableStyles.css';
import { queryReportById, queryReportWithDynamicFilters, printToPdf, getReportDataById, updateReport, queryChartById, downloadReportByFormat } from '@/services/reportService';
import PermissionDeniedPage from '../403';
import { ModalForm, ProFormRadio } from '@ant-design/pro-form';
import { APIResponse } from '@/utils/request';
import PieChart from '@/components/Dashboard/PieChart';
import BarChart from '@/components/Dashboard/BarChart';
import PieChartCj from '@/components/Dashboard/PieChartCj';
import { getPrivileges } from '@/utils/permission';
import './reportViewTableStyles.css';
import moment from 'moment';
import { downloadBase64File } from '@/utils/utils';


export type ViewReportRouteParams = {
    reportId: string,
};

const ViewReport: React.FC = () => {

    const [reportData, setReportData] = useState<any>();
    const [columnData, setColumnData] = useState<any>([]);
    const { reportId } = useParams<ViewReportRouteParams>();
    const [reportName, setReportName] = useState()
    const [exportType, setExportType] = useState<any>();
    const [loading, setLoading] = useState(true);
    const [addChartsVisible, setaddChartsVisible] = useState(false)
    const [hideDetailedTableValue, setHideDetailedTableValue] = useState(false)
    const [aggregateFieldVisible, setAggregateFieldVisible] = useState(false)
    const [reportChartData, setReportChartData] = useState({})
    const [isChartAvailable, setIsChartAvailable] = useState(false)
    const [chartData, setChartData] = useState([])
    const [chartUpdated, setChartUpdated] = useState(false)
    const [isSystemReport, setIsSystemReport] = useState(false)
    const access = useAccess();
    const { hasPermitted } = access;
    const { Option } = Select;
    const { TabPane } = Tabs;
    const [form] = Form.useForm();
    const [loadingExcelDownload, setLoadingExcelDownload] = useState(false);
    const privilege = getPrivileges();

    useEffect(() => {
        fetchReportData();
    }, [chartUpdated]);

    const fetchReportData = async () => {
        setLoading(true);
        const reportResponse = await getReportDataById(reportId)
        await setReportChartData(reportResponse.data)
        await setHideDetailedTableValue(reportResponse.data.hideDetailedData)
        await setIsChartAvailable(reportResponse.data.isChartAvailable)
        // await setIsSystemReport(reportResponse.data.isSystemReport)

        if (reportResponse.data.aggregateType !== "count") {
            setAggregateFieldVisible(true)

        }

        form.resetFields()
        //     if(reportResponse.data.isSystemReport){
        //         const chartDataValues =  await queryChartById(privilege ,reportId)
        //         await setColumnData(chartDataValues.data.columnData);
        //         await setReportData(chartDataValues.data.data);
        //     }
        //   else{
        await queryReportById(privilege, reportId).then(async (response) => {
            if (response && response.data) {

                await setColumnData(response.data.columnData);
                await setReportData(response.data.data);
                if (reportResponse.data.isChartAvailable) {
                    console.log("chartAvailable")
                    const chartDataValues = await await queryChartById(privilege, reportId)
                    const chartDataArray = []
                    chartDataValues.data.data.forEach(element => {
                        chartDataArray.push({
                            type: element[reportResponse.data.groupBy],
                            ele: element,
                            value: Math.round(Number(element.value) * 100) / 100,
                        })
                    })
                    setChartData(chartDataArray)
                }

            }
        })
        //}



        setLoading(false);

    }
    const getChartDataIndex = () => {
        const index = _.find(columnData, o => o.dataIndex === reportChartData.groupBy[0])
        return _.get(index, "title", null)
    }
    const tableRef = useRef<TableType>();
    const intl = useIntl();
    const openChartModal = () => {
        setaddChartsVisible(true)
    }
    const generateSummerryCol = () => {
        if (reportChartData.aggregateType === "count") {
            return "Count"
        }
        if (reportChartData.aggregateType === "sum") {

            const index = _.find(columnData, o => o.dataIndex === reportChartData.aggregateField)
            const title = _.get(index, "title", null)
            return `Sum of ${title} `
        }
        const index = _.find(columnData, o => o.dataIndex === reportChartData.aggregateField)
        const title = _.get(index, "title", null)
        return `Average of ${title} `
    }
    const chartCol = [

        {
            title: getChartDataIndex,
            dataIndex: 'type',
            key: 'type',
        },
        {
            title: generateSummerryCol,
            dataIndex: 'value',
            key: 'value',
        },
    ]

    const aggregateTypeOnChange = (e) => {
        if (e === "count") {
            form.setFieldsValue({ aggregateField: form.getFieldValue("groupBy") })
            setAggregateFieldVisible(false)

        }
        else {
            form.setFieldsValue({ aggregateField: null })
            setAggregateFieldVisible(true)
        }
    }

    const performExportAction = async (type:any) => {
        try {
            const dynamicHeader = [];
            const dynamicRows = [];
            columnData.forEach(element => {
                dynamicHeader.push(element.title)
            });
            reportData.forEach(element => {
                const dynamicRow = []
                for (const _key in element) {
                    dynamicRow.push(element[_key])
                }

                dynamicRows.push(dynamicRow)
            });

            if (type === "csv" || type === "xls") {
                dynamicRows.unshift(dynamicHeader);
                let csvContent = "data:text/xls;charset=utf-8," + dynamicRows.map(e => e.join(",")).join("\n");
                let encodedUri = encodeURI(csvContent);
                let link = document.createElement("a");
                link.setAttribute("href", encodedUri);
                let date = moment().format('YYYY-MM-DD_HH_mm_ss');

                if (type === "csv") {
                    setLoadingExcelDownload(true);
                    const params = {
                        format: 'csv',
                      };

                    console.log(params);
                    await downloadReportByFormat(privilege,reportId, params)
                      .then((response: any) => {
                        setLoadingExcelDownload(false);
                        if (response.data) {
                          downloadBase64File(
                            'text/csv',
                            response.data,
                            `${reportChartData.reportName}_${date}.csv`,
                          );
                        }
                      })
                      .catch((error: APIResponse) => {
                        setLoadingExcelDownload(false);
                      });


                }
                else {
                    setLoadingExcelDownload(true);
                    const params = {
                        format: 'xls',
                      };

                    console.log(params);
                    await downloadReportByFormat(privilege,reportId, params)
                      .then((response: any) => {
                        setLoadingExcelDownload(false);
                        if (response.data) {
                          downloadBase64File(
                            'application/vnd.ms-excel',
                            response.data,
                            `${reportChartData.reportName}_${date}.xls`,
                          );
                        }
                      })
                      .catch((error: APIResponse) => {
                        setLoadingExcelDownload(false);
                      });
                }
                document.body.appendChild(link);
                link.click();
            } else if (type === "pdf") {
                printToPdf(dynamicHeader, dynamicRows, reportChartData.reportName);
            }


        } catch (err) {
            console.log(err);
        }
    }


    const formOnFinish = async () => {
        try {
            const requestData = reportChartData;
            requestData["id"] = reportId;
            requestData['aggregateField'] = form.getFieldValue('aggregateField')
            requestData['aggregateType'] = form.getFieldValue('aggregateType')
            requestData['chartType'] = form.getFieldValue('chartType')
            requestData['hideDetailedData'] = form.getFieldValue('hideDetailedData')
            requestData['groupBy'] = [form.getFieldValue('groupBy')]
            requestData['showSummeryTable'] = form.getFieldValue('showSummeryTable')
            requestData['isChartAvailable'] = true

            const { message, data } = await updateReport(reportId, requestData);
            Message.success(message);
            await setHideDetailedTableValue(form.getFieldValue('hideDetailedData'))
            setIsChartAvailable(true)
            setaddChartsVisible(false)
            setChartUpdated(!chartUpdated)


        } catch (err) {
            ////console.log(err);
        }
    }

    const menu = (
        <Menu>
            <Menu.Item key="Excel" >
                <a onClick={(event) => performExportAction('xls')}>
                    Excel
                </a>
            </Menu.Item>
            <Menu.Item key="CSV">
                <a onClick={(event) => performExportAction('csv')}>
                    CSV
                </a>
            </Menu.Item>
            <Menu.Item key="PDF">
                <a onClick={(event) => performExportAction('pdf')}>
                    PDF
                </a>
            </Menu.Item>
        </Menu>
    );


    const renderCharts = () => {
        return (
            <Card>
                <Row style={{ marginBottom: 10 }} justify="end">
                    <Col span={12} >
                        <Button onClick={openChartModal} style={{ float: "right" }}><PieChartOutlined /> Edit Chart</Button>

                    </Col>
                    <Col>
                        <Button
                            key="3"
                            onClick={(e) => {

                                history.push(`/report-engine/report-wizard/${reportId}`);
                            }}

                            className='editBtn'
                            disabled={reportChartData.isSystemReport}
                        >
                            {intl.formatMessage({
                                id: 'editReport',
                                defaultMessage: 'Edit Report',
                            })}
                        </Button>
                    </Col>

                </Row>
                <Row>
                    <Col span={reportChartData.showSummeryTable ? 12 : 24}>
                        {reportChartData.chartType === "barChart" ? <BarChart data={chartData} /> : <PieChart data={chartData} />}

                    </Col>
                    {reportChartData.showSummeryTable ?
                        <Col span={12}>
                            <Table dataSource={chartData}
                                columns={chartCol}
                                pagination={false} />

                        </Col> : <></>}
                </Row>
                {/* <Row>
                <Col span={12}>
                <PieChartCj data={chartData} />

                </Col>
            </Row> */}
            </Card>

        )
    }

    const renderDetailedTable = () => {
        return (
            !loading ? (
                <ProTable
                    scroll={columnData.length > 5 ? { x: "1500" } : { x: false }}
                    columns={columnData?.map((column: any) => {
                        if (column.valueType == 'render:salaryDetails') {
                            return {
                                ...column,
                                valueType: 'text',
                                render: (value: any) => <pre>{value}</pre>
                            };
                        }

                        return column;
                    })}
                    request={async (params, sorter, filter) => {
                        return {
                            data: reportData,
                            success: true,

                        }
                    }}
                    actionRef={tableRef}

                    rowKey="id"
                    search={false}
                    pagination={{
                        pageSize: 5, defaultPageSize: 5, hideOnSinglePage: true
                    }}
                    dateFormatter="string"
                    // headerTitle={intl.formatMessage({
                    //     id: 'Reports',
                    //     defaultMessage: reportName,
                    // })}

                    toolBarRender={() => [
                        isChartAvailable ? <></> : <Button onClick={openChartModal}><PieChartOutlined />Create Chart</Button>,
                        <Dropdown overlay={menu} placement="bottomLeft" arrow>
                            <Button type="primary" icon={<DownloadOutlined />}
                            >Export </Button>
                        </Dropdown>,
                        <Button
                            key="3"
                            onClick={(e) => {

                                history.push(`/report-engine/report-wizard/${reportId}`);
                            }}
                            className='editBtn'
                            disabled={reportChartData.isSystemReport}
                        >
                            {intl.formatMessage({
                                id: 'edit',
                                defaultMessage: 'Edit',
                            })}
                        </Button>


                    ]}
                />
            ) : (
                <Spin />
            ))
    }

    const tabsRender = () => {

        if (isChartAvailable) {
            if (hideDetailedTableValue) {
                return (renderCharts())
            }
            return (!loading ?
                <Tabs defaultActiveKey="1" >
                    <TabPane tab={<span> <StockOutlined />Chart </span>} key="1">
                        {renderCharts()}

                    </TabPane>
                    <TabPane tab={<span><FileTextOutlined />Report</span>} key="2">
                        {renderDetailedTable()}
                    </TabPane>
                </Tabs> : <Spin />)
        }

        return renderDetailedTable()





    }
    return (

        <PageContainer
            style={{
                backgroundColor: "white",
                height: "101%"
            }}
            title={reportChartData.reportName}
            extra={[

            ]}


        >
            {tabsRender()}

            <ModalForm
                form={form}
                onFinish={formOnFinish}
                title={isChartAvailable ? "Edit Chart" : "Add Chart"}
                visible={addChartsVisible}
                //  onValuesChange={formOnChange}
                onVisibleChange={setaddChartsVisible}
                initialValues={{
                    aggregateField: reportChartData.aggregateField,
                    aggregateType: reportChartData.aggregateType,
                    chartType: reportChartData.chartType,
                    groupBy: reportChartData.groupBy ? reportChartData.groupBy[0] : null,
                    showSummeryTable: reportChartData.showSummeryTable ? true : false,
                    hideDetailedData: reportChartData.hideDetailedData ? true : false
                }}
            >
                <Row >
                    <Form.Item
                        name="chartType"
                        label="Select Chart Type">

                        <Radio.Group buttonStyle="solid" >
                            <Radio.Button value="pieChart"><PieChartOutlined />Pie Chart</Radio.Button>
                            <Radio.Button value="barChart"><BarChartOutlined />Bar Chart</Radio.Button>
                        </Radio.Group>
                    </Form.Item>
                </Row>
                <Row gutter={16}>
                    <Col span={8}>
                        <Form.Item
                            name="groupBy"
                            label="Group By"
                            rules={[{ required: true }]}
                        >

                            <Select allowClear onChange={e => { form.getFieldValue("aggregateType") === "count" ? form.setFieldsValue({ aggregateField: e }) : form.setFieldsValue({ aggregateField: " " }) }}>
                                {columnData.map(e => <Option value={e.dataIndex}>{e.title}</Option>)}

                            </Select>
                        </Form.Item>
                    </Col>
                    <Col span={8}>
                        <Form.Item
                            name="aggregateType"
                            label="Aggregate Type"
                            rules={[{ required: true }]}
                        >
                            <Select allowClear onChange={aggregateTypeOnChange}>
                                <Option value="count">Count</Option>
                                <Option value="sum">Sum</Option>
                                <Option value="average">Average</Option>
                            </Select>
                        </Form.Item>
                    </Col>
                    <Col span={8}>
                        <Form.Item
                            name="aggregateField"
                            label="Aggregate Field"
                            style={!aggregateFieldVisible ? { display: 'none' } : {}}
                            rules={[{ required: true }]}

                        >
                            <Select allowClear disabled={!aggregateFieldVisible}>
                                {columnData.map(e => {
                                    if (e.valueType === "number") {
                                        return <Option value={e.dataIndex}>{e.title}</Option>
                                    }
                                }
                                )}
                            </Select>
                        </Form.Item>
                    </Col>



                </Row>
                <Row>
                    <Col span={12} className='horizontal-form-column' >
                        <Form.Item
                            name="showSummeryTable"
                            label="Show Summary Table"
                            valuePropName="checked"
                        >
                            <Col offset={2} span={8}>
                                <Checkbox defaultChecked={reportChartData.showSummeryTable} onChange={e => form.setFieldsValue({ showSummeryTable: e.target.checked })} /></Col>
                        </Form.Item>
                    </Col>
                    <Col span={12} className='horizontal-form-column' >
                        <Form.Item
                            name="hideDetailedData"
                            label="Hide Report Tab"
                            valuePropName="checked"
                        >
                            <Col offset={2} span={8}>
                                <Checkbox defaultChecked={reportChartData.hideDetailedData} onChange={e => form.setFieldsValue({ hideDetailedData: e.target.checked })} /></Col>
                        </Form.Item>
                    </Col>
                </Row>


            </ModalForm>
        </PageContainer>
    );
};

export default ViewReport;

