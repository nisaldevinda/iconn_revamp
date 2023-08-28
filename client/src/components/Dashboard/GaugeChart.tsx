import React, { useState, useEffect } from 'react';
import { Gauge } from '@ant-design/charts';

const GaugeChart: React.FC = () => {
  var config = {
    percent: 0.75,
    type: 'meter',
    innerRadius: 0.75,
    height: 250,
    range: {
      ticks: [0, 1 / 3, 2 / 3, 1],
      color: ['#F4664A', '#FAAD14', '#30BF78'],
    },
    indicator: {
      pointer: { style: { stroke: '#D0D0D0' } },
      pin: { style: { stroke: '#D0D0D0' } },
    },
    statistic: {
      content: {
        style: {
          fontSize: '36px',
          lineHeight: '36px',
        },
      },
    },
  };
  return <Gauge {...config} />;
};

export default GaugeChart;