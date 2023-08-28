import React from 'react';
import { DecompositionTreeGraph, IGraph } from '@ant-design/charts';
import _ from '@umijs/deps/compiled/lodash';
import { Button, Row } from 'antd';
import { MinusOutlined, PlusOutlined } from '@ant-design/icons';
interface TreeDiagramProps {
  data: any;
  behaviors: string[];
  onReady: (ready: IGraph) => void;
}

const TreeDiagram: React.FC<TreeDiagramProps> = ({ data, behaviors, onReady }) => {

  return (
    <>
      <DecompositionTreeGraph
        adjustLayout={true}
        autoFit={true}
        style={{ backgroundColor: '#f0f2f5', height: '80vh' }}
        toolbarCfg={{
          show: true,
          renderIcon: (zoomIn, zoomOut) => (
            <>
              <Button icon={<PlusOutlined />} onClick={() => zoomIn()} />
              <Button icon={<MinusOutlined />} onClick={() => zoomOut()} />
            </>
          ),
        }}
        edgeCfg={{
          style: {
            position: 'right',

            stroke: '#88a838',
          },

          edgeStateStyles: {
            hover: {
              stroke: '#88a838',
            },
          },
        }}
        nodeCfg={{
          size: [160, 30],

          style: {
            position: 'right',

            stroke: '#88a838',
          },

          title: {
            containerStyle: {
              fill: '#88a838',
            },
          },

          nodeStateStyles: {
            hover: {
              shadowColor: '#1890ff',

              lineWidth: 2,
            },
          },
        }}
        data={data}
        behaviors={behaviors}
        markerCfg={(cfg) => {
          const { children } = cfg;

          return {
            show: children.length,

            collapsed: true,

            style: {
              stroke: '#88a838',
            },
          };
        }}
        onReady={onReady}
      />
    </>
  );
};

export default TreeDiagram;
