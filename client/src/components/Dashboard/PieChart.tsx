import React, { useState, useEffect } from 'react';
import { Pie } from '@ant-design/charts';

const PieChart: React.FC = (props) => {
let data = [
 ];
  if (props.data) {
    data = props.data

  }
  let config = {
        appendPadding: 10,
        data: data,
        angleField: 'value',
        colorField: 'type',
        radius: 1,
        height: props.height ?? 400,
        width: props.width ?? 400,
        legend: {
          layout: 'horizontal',
          position: 'bottom'
        },
    label: {
        type: 'inner',
        offset: '-50%',
        autoRotate:false,
        content: function content(_ref) {
          return _ref.value;

        },
        style: {
          fontSize: 16,
          textAlign: 'center',
        },
      },
      interactions: [{ type: 'element-active' }],
    };
    return < Pie {...config} />;
};

export default PieChart;