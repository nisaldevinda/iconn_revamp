import React, { useState, useEffect } from 'react';
import { Column, G2 } from '@ant-design/charts';

export type CardProps = {
    cardWidth?: number,
    cardHeight: number,
}

const GroupBarChart: React.FC<CardProps> = (props) => {
    G2.registerInteraction('element-link', {
        start: [
            {
                trigger: 'interval:mouseenter',
                action: 'element-link-by-color:link',
            },
        ],
        end: [
            {
                trigger: 'interval:mouseleave',
                action: 'element-link-by-color:unlink',
            },
        ],
    });
 let dataSet =[]

    if (props.data) {
        dataSet = props.data
      }


    let config2 = {
        data: dataSet,
        xField: 'departmentName',
        yField: 'value',
        seriesField: 'gendername',
      meta: {
        value: {
          formatter: (item) => item * 100
        }
      },
      label: {
        position: 'middle',
        content: (item) => {
          return `${(item.value * 100).toFixed(2)}%`;
        },
        style: {
          fill: '#fff',
        },
      },
      xAxis: {
        label: {
          autoHide: false,
          offset:10,
          autoEllipsis : true
        },
        style :{
          width: 300,
          overflow: 'hidden',
          textOverflow: 'ellipsis',
          paddingLeft: 200
        }
      },
        isPercent: true,
        isStack: true,
        height: props.cardHeight -110,
        width:850,
        // label: {
        //     //   position: 'middle',
        //     content: function content(item: any) {
        //         return item.gender;
        //     },
        //     style: { fill: '#fff' },
        // },
        interactions: [{ type: 'element-highlight-by-color' }, { type: 'element-link' }],
    };
    return < Column {...config2} />;
};

export default GroupBarChart;