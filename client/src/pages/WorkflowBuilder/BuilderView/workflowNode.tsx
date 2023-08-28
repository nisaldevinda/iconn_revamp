import React from 'react';
import { Card, Space, Tooltip, Button } from 'antd';
import {
  DeleteOutlined,
  EditFilled,
  PlusCircleFilled,
  PlusOutlined,
  SettingOutlined,
} from '@ant-design/icons';
import _ from 'lodash';
import styles from './styles.less';
interface OrgNodeProps {
  nodeData: NodeData;
  showModal: (type: string, data: any) => void;
  deleteConfirmModal: (type: string, data: any) => void;
  addWorkflowNode: (data: any) => void;
  addFirstApproverLevelNode: (nodeLevel: any, data: any) => void;
  addApproverLevelNode: (nodeLevel: any, data: any) => void;
  employeeList: Array<any>;
}

interface NodeData {
  id: Number;
  name: String;
  entityLevel: String;
  entityLevelLabel: String;
  nodeType: String;
  headOfEntityId: Number;
  parentEntityId: Number;
  children: Array<Object>;
}

const WorkflowNode: React.FC<OrgNodeProps> = ({
  nodeData,
  showModal,
  employeeList,
  addWorkflowNode,
  deleteConfirmModal,
  addFirstApproverLevelNode,
  addApproverLevelNode,
}) => {
  const { name, nodeType, parentEntityId, headOfEntityId } = nodeData;

  const customizeApprovalLevel = () => {
    showModal('add', nodeData);
  };

  const addWorkflow = () => {
    addWorkflowNode(nodeData);
  };

  const addFirstApproverLevel = () => {
    let nodeLevel = 1;
    addFirstApproverLevelNode(nodeLevel, nodeData);
  };

  const addApproverLevel = () => {
    let nodeLevel = nodeData.levelSequence + 1;

    addApproverLevelNode(nodeLevel, nodeData);
  };

  const editNode = () => {
    showModal('edit', nodeData);
  };

  const deleteNode = () => {
    // showModal('delete', nodeData);
    deleteConfirmModal('deleteApprovalLevelNode', nodeData);
  };

  const deleteProcedureNode = () => {
    deleteConfirmModal('deleteWorkflowNode', nodeData);
  };

  const getEmployeeName = (id: Number): String => {
    const employeeObject = _.find(employeeList, { value: id });
    return employeeObject ? employeeObject.label : '';
  };

  return (
    <>
      {nodeType == 'mainNode' ? (
        <div style={{ width: '100%' }}>
          <Card
            type="inner"
            style={{ width: 250 }}
            headStyle={{
              background: '#7e57c2',
              borderTopLeftRadius: 6,
              borderTopRightRadius: 6,
            }}
            bodyStyle={{
              border: '1px solid #7e57c2',
              borderBottomRightRadius: 6,
              borderBottomLeftRadius: 6,
            }}
            title={
              <div>
                {name.length <= 25 ? (
                  <div style={{ fontSize: 16 }}>{name}</div>
                ) : name !== null && name.length > 25 ? (
                  <Tooltip title={name}>
                    <div style={{ fontSize: 16 }}>{name.substring(0, 25 - 3) + '...'} </div>
                  </Tooltip>
                ) : (
                  <></>
                )}
              </div>
            }
            className={styles.card}
          >
            <Button onClick={addWorkflow} style={{ width: '100%' }}>
              {'+ New Workflow Procedure'}
            </Button>
          </Card>
        </div>
      ) : nodeType == 'workflowNode' ? (
        <div style={{ width: '100%' }}>
          <Card
            type="inner"
            style={{ width: 250 }}
            headStyle={{
              background: '#4588fa',
              borderTopLeftRadius: 6,
              borderTopRightRadius: 6,
            }}
            bodyStyle={{
              border: '1px solid #4588fa',
              borderBottomRightRadius: 6,
              borderBottomLeftRadius: 6,
            }}
            title={
              <div>
                {name.length <= 25 ? (
                  <div style={{ fontSize: 16 }}>{name}</div>
                ) : name !== null && name.length > 25 ? (
                  <Tooltip title={name}>
                    <div style={{ fontSize: 16 }}>{name.substring(0, 25 - 3) + '...'} </div>
                  </Tooltip>
                ) : (
                  <></>
                )}
              </div>
            }
            extra={
              <Space>
                {parentEntityId ? (
                  <DeleteOutlined className={styles.cardIcon} onClick={deleteProcedureNode} />
                ) : null}
              </Space>
            }
            className={styles.card}
          >
            <Button style={{ width: 150 }} onClick={addFirstApproverLevel} type="text" block>
              {'+ First Approver'}
            </Button>
          </Card>
        </div>
      ) : nodeType == 'sucessActionNode' ? (
        <div style={{ width: '100%', paddingLeft: 30 }}>
          <div
            style={{
              border: '1px solid #60cd72',
              borderRadius: '50%',
              width: 120,
              height: 120,
              backgroundColor: '#ffffff',
            }}
          >
            <div className="sucessHalfCircle">
              <div style={{ paddingTop: '30%', fontSize: 16 }}>{'Approve'}</div>
              <div style={{ paddingTop: '2%', fontSize: 12 }}>{'Next Approver'}</div>
              <div style={{ paddingTop: 5 }}>
                <a onClick={addApproverLevel}>{<PlusOutlined style={{ fontSize: 18 }} />}</a>
              </div>
            </div>
          </div>
        </div>
      ) : nodeType == 'failierActionNode' ? (
        <div style={{ width: '100%' }}>
          <div
            style={{
              border: '1px solid #f24e51',
              borderRadius: '50%',
              width: 120,
              height: 120,
              backgroundColor: '#ffffff',
            }}
          >
            <div className="failierHalfCircle">
              <div style={{ paddingTop: '30%', fontSize: 16 }}>{'Reject'}</div>
            </div>
            {/* <hr/> */}
            <div>
              <div></div>
            </div>
          </div>
        </div>
      ) : nodeType == 'approverLevelNode' ? (
        <div style={{ width: '100%', paddingRight: 50 }}>
          <Card
            type="inner"
            style={{ width: 200, marginLeft: 50 }}
            headStyle={{
              background: '#38acc1',
              borderTopLeftRadius: 6,
              borderTopRightRadius: 6,
            }}
            bodyStyle={{
              border: '1px solid #38acc1',
              borderBottomRightRadius: 6,
              borderBottomLeftRadius: 6,
              height: 50,
              padding: 0,
              paddingTop: 10,
            }}
            title={
              <div style={{ marginRight: 20 }}>
                {name.length <= 18 ? (
                  <div style={{ fontSize: 15 }}>{name}</div>
                ) : name !== null && name.length > 18 ? (
                  <Tooltip title={name}>
                    <div style={{ fontSize: 15 }}>{name.substring(0, 18 - 3) + '...'} </div>
                  </Tooltip>
                ) : (
                  <></>
                )}
              </div>
            }
            extra={
              <Space>
                <DeleteOutlined style={{ fontSize: 16, paddingLeft: 10 }} onClick={deleteNode} />
              </Space>
            }
            className={styles.card}
          >
            {/* <Button style={{width: '100%'}}>{'+ First Approver'}</Button> */}
            <div style={{ width: '100%' }}>
              <Button style={{ width: 150 }} onClick={customizeApprovalLevel} type="text" block>
                <span>{<SettingOutlined></SettingOutlined>}</span>
                <span style={{ marginLeft: 10 }}>{'Customize'}</span>
              </Button>
            </div>
          </Card>
        </div>
      ) : (
        <></>
      )}
    </>
  );
};

export default WorkflowNode;
