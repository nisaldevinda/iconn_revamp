import React from 'react';
import { Card, Space, Row, Col, Divider, Badge, Tooltip } from 'antd';
import { DeleteOutlined, EditFilled, PlusCircleFilled } from '@ant-design/icons';
import _ from 'lodash';
import styles from './styles.less';

interface OrgNodeProps {
  nodeData: NodeData;
  showModal: (type: string, data: any) => void;
  employeeList: Array<any>;
  entityWiseEmpData: Array<any>;
  getIsolatedOrgTreeData: (entityId: number) => void;
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

const OrgNode: React.FC<OrgNodeProps> = ({
  nodeData,
  showModal,
  employeeList,
  entityWiseEmpData,
  getIsolatedOrgTreeData,
}) => {
  const { name, entityLevelLabel, parentEntityId, headOfEntityId } = nodeData;
  let empData = null;

  if (entityWiseEmpData.length > 0) {
    const index = entityWiseEmpData.findIndex((item) => nodeData.id == item.entityId);
    empData = index === -1 ? null : entityWiseEmpData[index];
  } else {
    empData = null;
  }

  const empDetail = empData;

  const addNode = () => {
    showModal('add', nodeData);
  };

  const editNode = () => {
    showModal('edit', nodeData);
  };

  const deleteNode = () => {
    showModal('delete', nodeData);
  };

  const getFormattedNumber = async (value) => {
    return value < 10 ? '0' + value : value;
  };

  const getEmployeeName = (id: Number): String => {
    const employeeObject = _.find(employeeList, { value: id });
    return employeeObject ? employeeObject.label : '';
  };

  return (
    <Card
      type="inner"
      className={'managerOrgNode'}
      headStyle={{
        background: '#18aeef',
        borderTopLeftRadius: 6,
        borderTopRightRadius: 6,
      }}
      bodyStyle={{
        border: '1px solid #18aeef',
        borderBottomRightRadius: 6,
        borderBottomLeftRadius: 6,
        height: 110,
      }}
      title={
        <>
          <span style={{ color: 'white', fontWeight: 600, fontSize: 16 }}>{entityLevelLabel}</span>
          <h2>{name}</h2>
        </>
      }
    >
      <>
        <Row>
          <Col span={6}>
            <Row
              align="middle"
              style={{ fontSize: 10, color: 'grey', fontWeight: 550, width: '100%' }}
            >
              <Col span={24}>Headcount</Col>
            </Row>
            <Row>
              <Col style={{ color: '#00468b' }} span={24}>
                {empDetail ? empDetail.headCount : '00'}
              </Col>
            </Row>
          </Col>
          <Col span={6}>
            <Row
              align="middle"
              style={{ fontSize: 10, color: 'grey', fontWeight: 550, width: '100%' }}
            >
              <Col span={24}>Male</Col>
            </Row>
            <Row>
              <Col style={{ color: '#00468b' }} span={24}>
                {empDetail ? empDetail.maleCount : '00'}
              </Col>
            </Row>
          </Col>
          <Col span={6}>
            <Row
              align="middle"
              style={{ fontSize: 10, color: 'grey', fontWeight: 550, width: '100%' }}
            >
              <Col span={24}>Female</Col>
            </Row>
            <Row>
              <Col style={{ color: '#00468b' }} span={24}>
                {empDetail ? empDetail.femaleCount : '00'}
              </Col>
            </Row>
          </Col>
          <Col span={6}>
            <Row
              align="middle"
              style={{ fontSize: 10, color: 'grey', fontWeight: 550, width: '100%' }}
            >
              <Col span={24}>Resigned</Col>
            </Row>
            <Row>
              <Col style={{ color: '#00468b' }} span={24}>
                {empDetail ? empDetail.resignCount : '00'}
              </Col>
            </Row>
          </Col>
        </Row>
        <Divider />
        <Row>
          <Col span={24}>
            <Row
              align="middle"
              style={{ fontSize: 10, color: 'grey', fontWeight: 550, width: '100%' }}
            >
              <Col span={24}>New Recruits</Col>
            </Row>
            <Row>
              <Col style={{ color: '#00468b' }} span={24}>
                {empDetail ? empDetail.newRecruitsCount : '00'}
              </Col>
            </Row>
          </Col>
        </Row>
        <Row style={{ marginTop: -25 }}>
          <Col span={24}>
            <Row align="middle">
              <Col span={1} offset={23}>
                <Tooltip title={'Isolate'}>
                  <span
                    onClick={() => {
                      getIsolatedOrgTreeData(nodeData.id);
                    }}
                  >
                    <Badge color="#44A4ED" />
                  </span>
                </Tooltip>
              </Col>
            </Row>
          </Col>
        </Row>
      </>
    </Card>
  );
};

export default OrgNode;
