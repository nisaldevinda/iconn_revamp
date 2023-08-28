import React, { useState, useEffect } from 'react';
import { Liquid } from '@ant-design/charts';

const PieChartLiquid: React.FC = () => {
  var config = {
    percent: 0.40,
    outline: {
      border: 4,
      distance: 8,
    },
    wave: { length: 128 },
    height: 250,
  };
  return <Liquid {...config} />;
};

export default PieChartLiquid;