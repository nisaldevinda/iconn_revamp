import React from 'react';
import { Card, Space, Row, Col, Divider } from 'antd';
import { DeleteOutlined, EditFilled, PlusCircleFilled } from '@ant-design/icons';
import _ from 'lodash';
import styles from './styles.less';

interface OrgNodeProps {
  nodeData: NodeData;
  showModal: (type: string, data: any) => void;
  employeeList: Array<any>;
}

interface NodeData {
  id: Number;
  name: String;
  entityLevel: String;
  entityLevelLabel: String;
  headOfEntityId: Number;
  parentEntityId: Number;
  children: Array<Object>;
}

const OrgNode: React.FC<OrgNodeProps> = ({ nodeData, showModal, employeeList }) => {
  const { name, entityLevelLabel, parentEntityId, headOfEntityId } = nodeData;

  const addNode = () => {
    showModal('add', nodeData);
  };

  const editNode = () => {
    showModal('edit', nodeData);
  };

  const deleteNode = () => {
    showModal('delete', nodeData);
  };

  const getEmployeeName = (id: Number): String => {
    const employeeObject = _.find(employeeList, { value: id });
    return employeeObject ? employeeObject.label : '';
  };

  return (
    <Card
      type="inner"
      title=""
      className={'orgStructureNode'}
      headStyle={{
        background: '#002B98',
        borderTopLeftRadius: 6,
        borderTopRightRadius: 6,
      }}
      bodyStyle={{
        border: '1px solid #002B98',
        borderBottomRightRadius: 6,
        borderBottomLeftRadius: 6,
        backgroundColor: '#D9E4FF',
        color: '#324054',
      }}
      extra={
        <Space>
          <PlusCircleFilled className={styles.cardIcon} onClick={addNode} />
          <EditFilled className={styles.cardIcon} onClick={editNode} />
          {parentEntityId ? (
            <DeleteOutlined className={styles.cardIcon} onClick={deleteNode} />
          ) : null}
        </Space>
      }
      // className={styles.card}
    >
      <Row>
        <Col span={24} style={{ color: 'gray' }}>
          {entityLevelLabel}
        </Col>
      </Row>
      <Row>
        <Col span={24} style={{ fontSize: 22, fontWeight: 600, color: '#18aeef' }}>
          {name}
        </Col>
      </Row>
      <Divider />
      <Row>
        <Col span={24} style={{ color: 'gray' }}>
          {'Head'}
        </Col>
      </Row>
      <Row>
        <Col span={24} style={{ fontWeight: 550, color: '#18aeef' }}>
          {headOfEntityId ? getEmployeeName(headOfEntityId) : '---'}
        </Col>
      </Row>
    </Card>
  );
};

export default OrgNode;
