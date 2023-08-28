import { getReportDataById, queryChartById, queryReportById } from '@/services/reportService';
import { getPrivileges } from '@/utils/permission';
import { Spin } from 'antd';
import React, { useState, useEffect } from 'react';
import BarChart from './BarChart';
import PieChart from './PieChart';

export type ReportWidgetProps = {
    reportId: string,
    setTitle: (values: any) => void,
    width?: number,
    height?: number
}

const ReportWidget: React.FC<ReportWidgetProps> = (props) => {
    const [loading, setLoading] = useState(false)
    const [reportChartData, setReportChartData] = useState({})
    const [chartData, setChartData] = useState([])

    const privilege = getPrivileges();

    useEffect(() => {
        init();
    }, []);

    const init = async () => {
        setLoading(true);
        const reportData = await getReportDataById(props.reportId);

        props.setTitle(reportData?.data?.reportName);
        setReportChartData(reportData?.data);

        await queryReportById(privilege, props.reportId).then(async (response) => {
            if (response && response.data) {
                if (reportData.data.isChartAvailable) {
                    console.log("chartAvailable")
                    const chartDataValues = await queryChartById(privilege, props.reportId)
                    const chartDataArray = []
                    chartDataValues.data.data.forEach(element => {
                        chartDataArray.push({
                            type: element[reportData.data.groupBy],
                            ele: element,
                            value: Math.round(Number(element.value) * 100) / 100,
                        })
                    })

                    setChartData(chartDataArray)
                }
            }
        })

        setLoading(false);
    }

    return <Spin spinning={loading}>
        {reportChartData.chartType === "barChart"
            ? <BarChart
                data={chartData}
                width={props.width ? props.width - 100 : 340}
                height={props.height ? props.height - 100 : 210}
            />
            : <PieChart
                data={chartData}
                width={props.width ? props.width - 100 : 340}
                height={props.height ? props.height - 100 : 210}
            />
        }
    </Spin>

}

export default ReportWidget;
