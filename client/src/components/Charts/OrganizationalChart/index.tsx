import React, { useState, useEffect } from 'react';
import _ from 'lodash';
import clsx from 'clsx';
import { Tree, TreeNode } from 'react-organizational-chart';
import { Card, Button, Avatar, Col, Row, Divider, message as Message } from 'antd';
import { DndProvider } from 'react-dnd';
import { HTML5Backend } from 'react-dnd-html5-backend';
import { useDrop } from 'react-dnd';
import { DownOutlined, UserOutlined, PlusOutlined, MinusOutlined } from '@ant-design/icons';
import { TransformWrapper, TransformComponent } from 'react-zoom-pan-pinch';
import styles from './styles.less';

interface OrganizationalChartProps {
  data: any;
  onNodeClick?: (d: any) => void;
}

interface DetailCardProps {
  data: any;
  onCollapse: () => void;
  collapsed: boolean;
}

interface TreeNodeProps {
  parent: any;
  nodeData: any;
}

const DetailCard: React.FC<DetailCardProps> = (props) => {
  const { Meta } = Card;
  let childrenCount;
  if (props.data.organizationChildRelationship) {
    childrenCount = props.data.organizationChildRelationship.length;
  }

  const [{ canDrop, isOver }, drop] = useDrop({
    accept: 'account',
    drop: () => ({ name: props.data.employeeName }),
    collect: (monitor: any) => ({
      isOver: monitor.isOver(),
      canDrop: monitor.canDrop(),
    }),
  });
  const fullName = `${props.data.employeeFirstName} ${props.data.employeeLastName}`;
  return (
    <div ref={drop}>
      <Card className={styles.nodeCard}>
        <div className={styles.cardTop}></div>
        <Row style={{ marginBottom: '10px' }} justify="center">
          {props.data.imageUrl ? (
            <Avatar className={styles.popoverAvatarContent} src={props.data.imageUrl} size={38} />
          ) : (
            <Avatar
              className={styles.popoverAvatarContent}
              style={{ backgroundColor: props.data.color }}
              size={38}
            >
              {fullName
                .split(' ')
                .map((x) => x[0])
                .join('')}
            </Avatar>
          )}
        </Row>
        <Row justify="center" style={{ marginBottom: '4px' }}>
          <div className={styles.nameText}>
            {`${props.data.employeeFirstName}  ${props.data.employeeLastName}`}{' '}
          </div>
        </Row>
        <Row justify="center" style={{ marginBottom: '16px' }}>
          <Meta
            description={props.data.employeeDesignation ? props.data.employeeDesignation : '---'}
          />
        </Row>
        <Row justify="center">
          {childrenCount > 0 ? (
            <>
              <Col span={5} style={{ paddingRight: '0px' }}>
                <UserOutlined className={styles.countIcon} />
              </Col>
              <Col span={4}>
                <div className={styles.childrenCount}>{childrenCount}</div>
              </Col>
              <Col span={4}>
                <Button
                  className={clsx(styles.cardExpand, {
                    [styles.cardOpen]: !props.collapsed,
                  })}
                  onClick={props.onCollapse}
                  shape="circle"
                  icon={<DownOutlined />}
                  size="small"
                />
              </Col>
            </>
          ) : (
            <>
              <Col span={24}>
                <Button
                  className={clsx(styles.cardExpand, {
                    [styles.cardOpen]: !props.collapsed,
                  })}
                  onClick={props.onCollapse}
                  shape="circle"
                  icon={<DownOutlined />}
                  size="small"
                />
              </Col>
            </>
          )}
        </Row>
      </Card>
    </div>
  );
};

const Node: React.FC<TreeNodeProps> = (props) => {
  const [collapsed, setCollapsed] = useState(props.nodeData.collapsed);
  const handleCollapse = () => {
    setCollapsed(!collapsed);
  };

  useEffect(() => {
    props.nodeData.collapsed = collapsed;
  }, [collapsed]);

  const T = props.parent
    ? TreeNode
    : (treeData: any) => (
        <Tree {...treeData} lineWidth="2px" lineColor="#bbc" lineBorderRadius="12px">
          {treeData.children}
        </Tree>
      );

  return collapsed ? (
    <T
      label={<DetailCard data={props.nodeData} onCollapse={handleCollapse} collapsed={collapsed} />}
    />
  ) : (
    <T
      label={<DetailCard data={props.nodeData} onCollapse={handleCollapse} collapsed={collapsed} />}
    >
      {_.map(props.nodeData.organizationChildRelationship, (c) => (
        <Node nodeData={c} parent={props.nodeData} />
      ))}
    </T>
  );
};

const OrganizationalChart: React.FC<OrganizationalChartProps> = (props) => {
  return (
    <div className="chart-controls">
      <TransformWrapper initialScale={0.7} centerOnInit={true} minScale={0.1} maxScale={4}>
        {({ zoomIn, zoomOut, resetTransform, centerView, ...rest }) => (
          <>
            <Row justify="end">
              <Button
                className={styles.panelButtons}
                icon={<PlusOutlined />}
                onClick={() => zoomIn()}
              />
              <Button
                className={styles.panelButtons}
                icon={<MinusOutlined />}
                onClick={() => zoomOut()}
              />
              <Button className={styles.panelButtons} onClick={() => resetTransform()}>
                Reset
              </Button>
              <Button className={styles.panelButtons} onClick={() => centerView()}>
                Center
              </Button>
            </Row>
            <br></br>
            <TransformComponent wrapperStyle={{ width: '100%', height: '86vh' }}>
              <div>
                <DndProvider backend={HTML5Backend}>
                  <div style={{ marginBottom: 50, marginTop: 50 }}>
                    <Node nodeData={props.data} />
                  </div>
                  <Divider style={{ width: 1000 }} />
                </DndProvider>
              </div>
            </TransformComponent>
          </>
        )}
      </TransformWrapper>
    </div>
  );
};

export default OrganizationalChart;
