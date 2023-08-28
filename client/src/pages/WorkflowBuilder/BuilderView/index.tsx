import React, { useEffect, useState } from 'react';
import OrganizationChart from '@dabeng/react-orgchart';
import { Modal, Form, Input, Select, message, Radio, Checkbox, Button, Row, Col } from 'antd';
import type { CheckboxValueType } from 'antd/es/checkbox/Group';
import { ExclamationCircleOutlined, ZoomInOutlined, ZoomOutOutlined } from '@ant-design/icons';
import { useParams, history, Access, useAccess, useModel ,useIntl } from 'umi';
import WorkflowNode from './workflowNode';
import styles from './styles.less';

const { confirm } = Modal;
interface OrgChartProps {
  data: any;
  hierarchyConfig: Object;
  employeeList: Array<any>;
  poolList: Array<any>;
  approvalTypeCategoryList: Array<any>;
  commonApprovalTypeList: Array<any>;
  approverUserRolesList: Array<any>;
  jobCategoryList: Array<any>;
  designationList: Array<any>;
  addNodeHandler: (values: NodeData) => void;
  addWorkflowNodeHandler: (values: NodeData) => void;
  addApproverLevelNodeHandler: (nodeLevel: any, data: any) => void;
  deleteWorkflowNodeHandler: (values: NodeData) => void;
  deleteLevelNodeHandler: (values: NodeData) => void;
  editNodeHandler: (values: NodeData) => void;
  changeWorkflowLevelConfigs: (params: any) => void;
  deleteNodeHandler: (values: NodeData) => void;
}

interface NodeData {
  id?: Number;
  name: String;
  entityLevel: String;
  headOfEntityId: Number | null;
  parentEntityId: Number | null;
}

const WorkflowFlowChart: React.FC<OrgChartProps> = ({
  data,
  hierarchyConfig,
  employeeList,
  poolList,
  approvalTypeCategoryList,
  commonApprovalTypeList,
  approverUserRolesList,
  jobCategoryList,
  designationList,
  addNodeHandler,
  addWorkflowNodeHandler,
  addApproverLevelNodeHandler,
  deleteWorkflowNodeHandler,
  deleteLevelNodeHandler,
  changeWorkflowLevelConfigs,
  editNodeHandler,
  deleteNodeHandler,
}) => {
  const [isModalOpen, setIsModalOpen] = useState<Boolean>(false);
  const [modalTitle, setModalTitle] = useState<String>('Org Structure Setup');
  const [levels, setLevels] = useState<Array<Object>>([]);
  const [isWorkflowReadOnly, setIsWorkflowReadOnly] = useState<boolean>(false);
  const [selectedLevel, setSelectedLevel] = useState<any>({});
  const [levelType, setlevelType] = useState<any>(null);
  const [zoomLevel, setZoomLevel] = useState<number | null>(1);
  const [classNme, setClassNme] = useState<any>(styles.orgChart1);
  const [dynamicApprovalTypeCategory, setDynamicApprovalTypeCategory] = useState<any>(null);
  const [approverJobCategories, setApproverJobCategories] = useState<any>([]);
  const [approverDesignation, setApproverDesignation] = useState<any>([]);
  const [approverUserRoles, setApproverUserRoles] = useState<any>([]);
  const [commonApprovalType, setCommonApprovalType] = useState<any>(null);
  const [actionType, setActionType] = useState<String | null>(null);
  const [selectedNode, setSelectedNode] = useState<NodeData>({
    name: '',
    entityLevel: '',
    headOfEntityId: null,
    parentEntityId: null,
  });
  const [form] = Form.useForm();
  const initailFormValue: NodeData = {
    name: '',
    entityLevel: '',
    headOfEntityId: null,
    parentEntityId: null,
  };

  const showModal = (type: string, nodeData: any) => {
    setActionType(type);
    setSelectedNode(nodeData);

    setlevelType(null);
    setDynamicApprovalTypeCategory(null);
    setApproverJobCategories([]);
    setApproverDesignation([]);
    setApproverUserRoles([]);
    setCommonApprovalType(null);

    switch (type) {
      case 'add':
        setModalTitle('Customize Approver Level');

        let selectedLevelData = JSON.parse(nodeData.levelData);
        console.log(selectedLevelData);
        setSelectedLevel({ ...selectedLevelData });

        setIsWorkflowReadOnly(nodeData.isReadOnly);

        if (selectedLevelData.levelType) {
          setlevelType(selectedLevelData.levelType);
        }

        if (selectedLevelData.dynamicApprovalTypeCategory) {
          setDynamicApprovalTypeCategory(selectedLevelData.dynamicApprovalTypeCategory);
        }

        if (selectedLevelData.approverJobCategories) {
          setApproverJobCategories(selectedLevelData.approverJobCategories);
        }

        if (selectedLevelData.approverDesignation) {
          setApproverDesignation(selectedLevelData.approverDesignation);
        }

        if (selectedLevelData.approverUserRoles) {
          setApproverUserRoles(selectedLevelData.approverUserRoles);
        }
        if (selectedLevelData.commonApprovalType) {
          setCommonApprovalType(selectedLevelData.commonApprovalType);
        }

        form.setFieldsValue({
          levelName: selectedLevelData.levelName,
          levelType: selectedLevelData.levelType ? selectedLevelData.levelType : null,
          staticApproverEmployeeId: selectedLevelData.staticApproverEmployeeId
            ? selectedLevelData.staticApproverEmployeeId
            : null,
          dynamicApprovalTypeCategory: selectedLevelData.dynamicApprovalTypeCategory
            ? selectedLevelData.dynamicApprovalTypeCategory
            : null,
          commonApprovalType: selectedLevelData.commonApprovalType
            ? selectedLevelData.commonApprovalType
            : null,
          approverUserRoles: selectedLevelData.approverUserRoles
            ? JSON.parse(selectedLevelData.approverUserRoles)
            : [],
          approverJobCategories: selectedLevelData.approverJobCategories
            ? JSON.parse(selectedLevelData.approverJobCategories)
            : [],
          approverDesignation: selectedLevelData.approverDesignation
            ? JSON.parse(selectedLevelData.approverDesignation)
            : [],
          approverPoolId: selectedLevelData.approverPoolId
            ? selectedLevelData.approverPoolId
            : null,
          approvalLevelActions: [2, 3],
        });
        setIsModalOpen(true);
        break;
      default:
        break;
    }
  };

  const deleteConfirmModal = (type: string, nodeData: any) => {
    if (nodeData.isReadOnly) {
      message.error('Can not change this is a deafult workflow');
      return;
    }
    switch (type) {
      case 'deleteWorkflowNode':
        confirm({
          title: 'Are you sure you want to delete this workflow node ?',
          icon: <ExclamationCircleOutlined />,
          content: '',
          okText: 'Yes',
          okType: 'danger',
          cancelText: 'No',
          onOk() {
            deleteWorkflowNodeHandler(nodeData);
          },
        });
        break;
      case 'deleteApprovalLevelNode':
        if (nodeData.children) {
          if (nodeData.children[0].children) {
            message.error('Can not delete, this level node has children levels');
            return;
          }
        }
        confirm({
          title: 'Are you sure you want to delete this level node ?',
          icon: <ExclamationCircleOutlined />,
          content: '',
          okText: 'Yes',
          okType: 'danger',
          cancelText: 'No',
          onOk() {
            deleteLevelNodeHandler(nodeData);
          },
        });
        break;
      default:
        break;
    }
  };
  const addWorkflowNode = (nodeData: any) => {
    if (nodeData.isReadOnly) {
      message.error('Can not change this is a deafult workflow');
      return;
    }
    setSelectedNode(nodeData);

    if (nodeData.hasOwnProperty('children') && nodeData.children.length > 0) {
      message.error('Have already added workflow node');
      return;
    }
    console.log(nodeData);
    addWorkflowNodeHandler(nodeData);
  };

  const addFirstApproverLevelNode = (level: any, nodeData: any) => {
    if (nodeData.isReadOnly) {
      message.error('Can not change this is a deafult workflow');
      return;
    }
    setSelectedNode(nodeData);

    if (nodeData.hasOwnProperty('children') && nodeData.children.length > 0) {
      message.error('First approver node is already added');
      return;
    }
    addApproverLevelNodeHandler(level, nodeData);
  };

  const addApproverLevelNode = (level: any, nodeData: any) => {
    // setSelectedNode(nodeData);
    if (nodeData.isReadOnly) {
      message.error('Can not change this is a deafult workflow');
      return;
    }

    if (nodeData.hasOwnProperty('children') && nodeData.children.length > 0) {
      message.error('Node connection already in use');
      return;
    }
    addApproverLevelNodeHandler(level, nodeData);
  };

  const changeLevelConfigs = (values: any) => {
    if (isWorkflowReadOnly) {
      message.error('Can not change this is a deafult workflow');
      return;
    }

    let params = {
      id: selectedLevel.id,
      workflowId: selectedLevel.workflowId,
      levelName: form.getFieldValue('levelName'),
      levelType: form.getFieldValue('levelType'),
      staticApproverEmployeeId: form.getFieldValue('staticApproverEmployeeId'),
      dynamicApprovalTypeCategory: form.getFieldValue('dynamicApprovalTypeCategory'),
      commonApprovalType: form.getFieldValue('commonApprovalType'),
      approverUserRoles: JSON.stringify(form.getFieldValue('approverUserRoles')),
      approverJobCategories: JSON.stringify(form.getFieldValue('approverJobCategories')),
      approverDesignation: JSON.stringify(form.getFieldValue('approverDesignation')),
      approverPoolId: form.getFieldValue('approverPoolId'),
      approvalLevelActions: JSON.stringify([2, 3]),
    };

    changeWorkflowLevelConfigs(params);
    setIsModalOpen(false);
  };

  const handleCancel = () => {
    setIsModalOpen(false);
  };

  const getChildNodeEntityLevel = (parentEntityLevel: string) => {
    const childEntityLevel = parseInt(parentEntityLevel.substring(5, 6)) + 1;
    const entityLevel = `level${childEntityLevel}`;
    return { entityLevel, entityLevelLabel: hierarchyConfig[entityLevel] };
  };

  return (
    <>
      <Row>
        <Col offset={20} span={4}>
          <div style={{ float: 'right', marginRight: 15 }}>
            <Button disabled={zoomLevel == 1} onClick={() => {
              let currentZoomLevel = zoomLevel;
              if (currentZoomLevel && currentZoomLevel > 1) {
                currentZoomLevel --;
              }
              let styleClass = styles.orgChart1;
              switch (currentZoomLevel) {
                case 2:
                  styleClass = styles.orgChart2;
                  break;
                case 3:
                  styleClass = styles.orgChart3;
                  break;
                case 4:
                  styleClass = styles.orgChart4;
                  break;
                case 5:
                  styleClass = styles.orgChart5;
                  break;
                case 6:
                  styleClass = styles.orgChart6;
                  break;
                case 7:
                  styleClass = styles.orgChart7;
                  break;
              
                default:
                  break;
              }

              console.log(currentZoomLevel);
              setClassNme(styleClass);
              setZoomLevel(currentZoomLevel);
            }} style={{marginRight: 5}}><ZoomInOutlined></ZoomInOutlined></Button>
            <Button style={{marginRight: 5}} onClick={() => {
              let currentZoomLevel = zoomLevel;
              if (currentZoomLevel && currentZoomLevel < 7) {
                currentZoomLevel ++;
              }
              let styleClass = styles.orgChart1;
              switch (currentZoomLevel) {
                case 2:
                  styleClass = styles.orgChart2;
                  break;
                case 3:
                  styleClass = styles.orgChart3;
                  break;
                case 4:
                  styleClass = styles.orgChart4;
                  break;
                case 5:
                  styleClass = styles.orgChart5;
                  break;
                case 6:
                  styleClass = styles.orgChart6;
                  break;
                case 7:
                  styleClass = styles.orgChart7;
                  break;
              
                default:
                  break;
              }

              console.log(currentZoomLevel);
              setClassNme(styleClass);
              setZoomLevel(currentZoomLevel);
            }} > <ZoomOutOutlined></ZoomOutOutlined> </Button>
            <Button onClick={() => {
              setZoomLevel(1);
              setClassNme(styles.orgChart1);
            }} style={{marginRight: 5}}>Reset</Button>
          </div>
        </Col>
      </Row>
      <OrganizationChart
        chartClass={classNme}
        datasource={data}
        collapsible={false}
        NodeTemplate={(nodeData: any) => (
          <WorkflowNode
            nodeData={nodeData.nodeData}
            showModal={showModal}
            deleteConfirmModal={deleteConfirmModal}
            addWorkflowNode={addWorkflowNode}
            addFirstApproverLevelNode={addFirstApproverLevelNode}
            addApproverLevelNode={addApproverLevelNode}
            employeeList={employeeList}
          />
        )}
      />
      <div style={{float:'right', paddingRight: 8}}>
        <Button
          htmlType="button"
          onClick={() => {
            history.push(`/settings/workflow-builder`);
          }}
        >
          Back
        </Button>
      </div>
      <Modal
        title={modalTitle}
        className={'workflowAddLevelModal'}
        visible={isModalOpen}
        footer={!isWorkflowReadOnly ? [
          <Button
            key="submit"
            type="primary"
            disabled = {isWorkflowReadOnly}
            onClick={() => {
              form
                .validateFields()
                .then((values) => {
                  // form.resetFields();
                  changeLevelConfigs(values);
                })
                .catch((info) => {
                  console.log('Validate Failed:', info);
                });
            }}
          >
            Save
          </Button>,
          <Button type="default" onClick={handleCancel}>
            Cancel
          </Button>,
        ] : [<Button type="default" onClick={handleCancel}>
        Cancel
      </Button>]}
        onCancel={handleCancel}
      >
        <Form layout="vertical" form={form} initialValues={initailFormValue}>
          <Form.Item label="Name" name="levelName" rules={[{ required: true }]}>
            <Input disabled = {isWorkflowReadOnly} style={{ borderRadius: 6 }} placeholder="Name" />
          </Form.Item>
          <Form.Item label="Level Type" rules={[{ required: true }]} name="levelType">
            <Radio.Group
              onChange={(val) => {
                setlevelType(val.target.value);
                switch (val.target.value) {
                  case 'STATIC':
                    setApproverDesignation([]);
                    setApproverJobCategories([]);
                    setApproverUserRoles([]);
                    setDynamicApprovalTypeCategory(null);
                    setCommonApprovalType(null);

                    form.setFieldsValue({
                      dynamicApprovalTypeCategory: null,
                      commonApprovalType: null,
                      approverUserRoles: [],
                      approverJobCategories: [],
                      approverDesignation: [],
                      approverPoolId: null
                    });
                    break;
                  case 'DYNAMIC':
                    form.setFieldsValue({
                      staticApproverEmployeeId: null,
                      approverPoolId: null
                    });
                    break;
                  case 'POOL':
                    setApproverDesignation([]);
                    setApproverJobCategories([]);
                    setApproverUserRoles([]);
                    setDynamicApprovalTypeCategory(null);
                    setCommonApprovalType(null);

                    form.setFieldsValue({
                      dynamicApprovalTypeCategory: null,
                      commonApprovalType: null,
                      approverUserRoles: [],
                      approverJobCategories: [],
                      approverDesignation: [],
                      staticApproverEmployeeId:null
                    });
                    break;

                  default:
                    break;
                }
              }}
              value={levelType}
            >
              <Radio disabled = {isWorkflowReadOnly} value={'STATIC'}>Static</Radio>
              <Radio disabled = {isWorkflowReadOnly} value={'DYNAMIC'}>Dynamic</Radio>
              <Radio disabled = {isWorkflowReadOnly} value={'POOL'}>Pool</Radio>
            </Radio.Group>
          </Form.Item>
          {levelType == 'STATIC' ? (
            <Form.Item
              label="Employee"
              rules={[{ required: true }]}
              name="staticApproverEmployeeId"
            >
              <Select 
                disabled = {isWorkflowReadOnly} 
                showSearch 
                optionFilterProp="children"
                filterOption={(input, option) =>
                  (option?.label ?? '').toLowerCase().includes(input.toLowerCase())
                } 
                options={employeeList} 
                placeholder="Select Employe" 
              />
            </Form.Item>
          ) : levelType == 'DYNAMIC' ? (
            <>
              <Form.Item
                label="Approver Type Category"
                rules={[{ required: true }]}
                name="dynamicApprovalTypeCategory"
              >
                <Select
                  value={dynamicApprovalTypeCategory}
                  disabled = {isWorkflowReadOnly}
                  onChange={(val) => {
                    console.log(val);
                    setDynamicApprovalTypeCategory(val);
                  }}
                  options={approvalTypeCategoryList}
                  placeholder="--Select--"
                />
              </Form.Item>
              {dynamicApprovalTypeCategory == 'COMMON' ? (
                <Form.Item label="Approver" rules={[{ required: true }]} name="commonApprovalType">
                  <Select
                    value={commonApprovalType}
                    options={commonApprovalTypeList}
                    disabled = {isWorkflowReadOnly}
                    onChange={(val) => {
                      setCommonApprovalType(val);
                      setApproverDesignation([]);
                      setApproverJobCategories([]);
                      setApproverUserRoles([]);

                      form.setFieldsValue({
                        approverUserRoles: [],
                        staticApproverEmployeeId: null,
                        approverJobCategories: [],
                        approverDesignation: [],
                      });
                    }}
                    placeholder="--Select--"
                  />
                </Form.Item>
              ) : dynamicApprovalTypeCategory == 'JOB_CATEGORY' ? (
                <Form.Item
                  label="Approver"
                  rules={[{ required: true }]}
                  name="approverJobCategories"
                >
                  <Select
                    mode="multiple"
                    disabled = {isWorkflowReadOnly}
                    value={approverJobCategories}
                    options={jobCategoryList}
                    onChange={(val) => {
                      console.log(val);
                      setApproverJobCategories(val);

                      setCommonApprovalType(null);
                      setApproverDesignation([]);
                      setApproverUserRoles([]);

                      form.setFieldsValue({
                        approverUserRoles: [],
                        approverDesignation: [],
                        staticApproverEmployeeId: null,
                        commonApprovalType: null,
                      });
                    }}
                    placeholder="--Select--"
                  />
                </Form.Item>
              ) : dynamicApprovalTypeCategory == 'DESIGNATION' ? (
                <Form.Item label="Approver" rules={[{ required: true }]} name="approverDesignation">
                  <Select
                    mode="multiple"
                    disabled = {isWorkflowReadOnly}
                    value={approverDesignation}
                    options={designationList}
                    onChange={(val) => {
                      console.log(val);
                      setApproverDesignation(val);

                      setCommonApprovalType(null);
                      setApproverUserRoles([]);
                      setApproverJobCategories([]);

                      form.setFieldsValue({
                        approverUserRoles: [],
                        staticApproverEmployeeId: null,
                        approverJobCategories: [],
                        commonApprovalType: null,
                      });
                    }}
                    placeholder="--Select--"
                  />
                </Form.Item>
              ) : dynamicApprovalTypeCategory == 'USER_ROLE' ? (
                <Form.Item label="Approver" rules={[{ required: true }]} name="approverUserRoles">
                  <Select
                    mode="multiple"
                    value={approverUserRoles}
                    disabled = {isWorkflowReadOnly}
                    options={approverUserRolesList}
                    onChange={(val) => {
                      console.log(val);
                      setApproverUserRoles(val);

                      setCommonApprovalType(null);
                      setApproverJobCategories([]);
                      setApproverDesignation([]);

                      form.setFieldsValue({
                        staticApproverEmployeeId: null,
                        approverJobCategories: [],
                        commonApprovalType: null,
                        approverDesignation: [],
                      });
                    }}
                    placeholder="--Select--"
                  />
                </Form.Item>
              ) : (
                <></>
              )}
            </>
          ) : levelType == 'POOL' ? (
            <Form.Item label="Pool" rules={[{ required: true }]} name="approverPoolId">
              <Select disabled = {isWorkflowReadOnly} options={poolList} placeholder="Select Employe" />
            </Form.Item>
          ) : (
            <></>
          )}
          <div className="workflowBuilderCheckbox">
            <Form.Item label="Approver Level Actions" name="approvalLevelActions">
              <Checkbox.Group
                options={[
                  { label: 'Approve', value: 2 },
                  { label: 'Reject', value: 3 },
                ]}
                disabled
                // defaultValue={[2, 3]}
              />
            </Form.Item>
          </div>
        </Form>
      </Modal>
    </>
  );
};

export default WorkflowFlowChart;
