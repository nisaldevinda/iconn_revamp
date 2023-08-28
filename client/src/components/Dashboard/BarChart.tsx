import React, { useState, useEffect } from 'react';
import { Column, G2 } from '@ant-design/charts';

export type CardProps = {
    cardWidth?: number,
    cardHeight: number,
}

const BarChart: React.FC = (props) => {
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

let dataSet=[]

    if (props.data) {
        dataSet = props.data
      }

    var config2 = {
        data: dataSet,
        xField: 'type',
        yField: 'value',
        height: props.height ?? 200,
        width: props.width ?? undefined,
        color: '#7def80',
        label: {
            //   position: 'middle',
            // content: function content(item: any) {
            //     return item.department;
            // },
            // style: { fill: '#7def80' },
            style: {
              fill: '#1030e6',
              opacity: 1,
            },
        },
        // interactions: [{ type: 'element-highlight-by-color' }, { type: 'element-link' }],
    };
    return < Column {...config2} />;
};

export default BarChart;