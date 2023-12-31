import React from 'react';
import { Card, Space, Row, Col, Divider, Avatar } from 'antd';
import { DeleteOutlined, EditFilled, PlusCircleFilled, UserOutlined } from '@ant-design/icons';
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
        background: '#2D68FE',
        borderTopLeftRadius: 10,
        borderTopRightRadius: 10,
        height:'22px',
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
      style={{ width: 1000, backgroundColor: '#CDE7FF', borderRadius: '10px' }}
      // className={styles.card}
    >
      {/* <Row>
        <div style={{display:"flex",gap:"10px"}}>
          <Avatar size={64} style={{ backgroundColor: '#18aeef' }} />
          <div style={{display:'flex',flexDirection:"column",alignItems:"start",justifyContent:"start"}}>
            <div style={{ color: 'gray' }}>{entityLevelLabel}</div>
            <div style={{ fontSize: 22, fontWeight: 600, color: '#18aeef' }}>{name}</div>
          </div>
        </div>
      </Row>
      <Row></Row>
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
      </Row> */}
      <div
        style={{
          display: 'flex',
          gap: '10px',
          alignItems: 'center',
          justifyContent: 'space-between',
          width: '100%',
        }}
      >
        <div style={{ width: '' }}>
          <Avatar size={64} icon={<UserOutlined color='black'/>} />
        </div>

        <div
          style={{
            display: 'flex',
            flexDirection: 'column',
            gap: '5px',
            alignItems: 'start',
            justifyItems: 'start',
            width: '100%',
            color: 'black',
          }}
        >
          <div style={{ color: 'gray', fontSize: '12px' }}>{entityLevelLabel}</div>
          <div
            style={{
              fontSize: 18,
              fontWeight: 600,
              color: 'black',
              alignItems: 'start',
              justifyItems: 'start',
              textAlign: 'left',
            }}
          >
            {name}
          </div>
          <Divider />
          <div style={{ fontSize: '14px' }}> {'Head'}</div>
          <div style={{ textAlign: 'left', fontSize: '12px' }}>
            {headOfEntityId ? getEmployeeName(headOfEntityId) : '---'}
          </div>
        </div>
      </div>
    </Card>
  );
};

export default OrgNode;
