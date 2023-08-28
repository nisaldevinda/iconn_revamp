import React, { useState, useEffect } from 'react';
import { Area } from '@ant-design/charts';

const AreaChart: React.FC = () => {
    const [data, setData] = useState([]);
    useEffect(() => {
        asyncFetch();
    }, []);
    const asyncFetch = () => {
        const data = [{
            "status": "DEV",
            "year": "1750",
            "value": 502
        }, {
            "status": "DEV",
            "year": "1800",
            "value": 635
        }, {
            "status": "DEV",
            "year": "1850",
            "value": 809
        }, {
            "status": "DEV",
            "year": "1900",
            "value": 947
        }, {
            "status": "DEV",
            "year": "1950",
            "value": 1100
        }, {
            "status": "DEV",
            "year": "1999",
            "value": 1350
        }, {
            "status": "DEV",
            "year": "2050",
            "value": 1650
        }, {
            "status": "QA",
            "year": "1750",
            "value": 206
        }, {
            "status": "QA",
            "year": "1800",
            "value": 207
        }, {
            "status": "QA",
            "year": "1850",
            "value": 211
        }, {
            "status": "QA",
            "year": "1900",
            "value": 233
        }, {
            "status": "QA",
            "year": "1950",
            "value": 321
        }, {
            "status": "QA",
            "year": "1999",
            "value": 767
        }, {
            "status": "QA",
            "year": "2050",
            "value": 1766
        }, {
            "status": "PROD",
            "year": "1750",
            "value": 163
        }, {
            "status": "PROD",
            "year": "1800",
            "value": 203
        }, {
            "status": "PROD",
            "year": "1850",
            "value": 276
        }, {
            "status": "PROD",
            "year": "1900",
            "value": 408
        }, {
            "status": "PROD",
            "year": "1950",
            "value": 547
        }, {
            "status": "PROD",
            "year": "1999",
            "value": 1229
        }, {
            "status": "PROD",
            "year": "2050",
            "value": 2400
        }];
        setData(data);
    };
    var config = {
        data: data,
        xField: 'year',
        yField: 'value',
        seriesField: 'status',
        height: 250,
        color: ['#2655e0', '#f2871f', '#10e02c'],
    };
    return <Area {...config} />;
};

export default AreaChart;