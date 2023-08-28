import React, { useEffect, useState, useRef } from 'react';
import OrganizationChart from '@dabeng/react-orgchart';
import { Modal, Form, Input, Select, message, Button, Row, Col } from 'antd';
import {
  ExclamationCircleOutlined,
  PlusOutlined,
  MinusOutlined,
  ZoomInOutlined,
  ZoomOutOutlined,
  SyncOutlined,
} from '@ant-design/icons';
import OrgNode from './OrgNode';
import { ProFormSelect } from '@ant-design/pro-form';
import styles from './styles.less';
import { TransformWrapper, TransformComponent } from 'react-zoom-pan-pinch';

const { confirm } = Modal;

interface OrgChartProps {
  data: any;
  hierarchyConfig: Object;
  employeeList: Array<any>;
  entityWiseEmpData: Array<any>;
  addNodeHandler: (values: NodeData) => void;
  editNodeHandler: (values: NodeData) => void;
  deleteNodeHandler: (values: NodeData) => void;
  getIsolatedOrgTreeData: (entityId: number) => void;
  getOrgTreeData: (entityId: number) => void;
  refresh: any;
}

interface NodeData {
  id?: Number;
  name: String;
  entityLevel: String;
  headOfEntityId: Number | null;
  parentEntityId: Number | null;
}

const ManagerOrgChart: React.FC<OrgChartProps> = ({
  data,
  hierarchyConfig,
  employeeList,
  entityWiseEmpData,
  addNodeHandler,
  editNodeHandler,
  deleteNodeHandler,
  getIsolatedOrgTreeData,
  getOrgTreeData,
  refresh,
}) => {
  const [isModalOpen, setIsModalOpen] = useState<Boolean>(false);
  const [modalTitle, setModalTitle] = useState<String>('Org Structure Setup');
  const [levels, setLevels] = useState<Array<Object>>([]);
  const [actionType, setActionType] = useState<String | null>(null);
  const [zoomLevel, setZoomLevel] = useState<number | null>(1);
  const [classNme, setClassNme] = useState<any>(styles.orgChart1);
  const [height, setHeight] = useState(0);
  const elementRef = useRef(null);
  const transformComponentRef = useRef();
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

  useEffect(() => {
    setZoomLevel(1);
    setClassNme(styles.orgChart1);
    // transformComponentRef?.current?.centerView();
    setHeight(elementRef.current.clientHeight);
  }, [refresh]);

  useEffect(() => {
    setHeight(elementRef.current.clientHeight);
  }, []); 

  useEffect(() => {
    if (height > 0) {
        if (height < 300) {
            transformComponentRef?.current?.resetTransform();
            setTimeout(() => {
                transformComponentRef?.current?.zoomIn(1.3);
                setTimeout(() => {
                    transformComponentRef?.current?.centerView();
                }, 250);
            }, 250);
        } else if (height > 300 && height < 800 ) {
            transformComponentRef?.current?.resetTransform();
            setTimeout(() => {
                transformComponentRef?.current?.zoomIn(0.4);
                setTimeout(() => {
                    transformComponentRef?.current?.centerView();
                }, 250);
            }, 250);
        } else if (height > 800 && height < 1000 ) {
            transformComponentRef?.current?.resetTransform();
            setTimeout(() => {
                transformComponentRef?.current?.zoomIn(0.2);
                setTimeout(() => {
                    transformComponentRef?.current?.centerView();
                }, 250);
            }, 250);
        } else {
            transformComponentRef?.current?.resetTransform();
            setTimeout(() => {
                transformComponentRef?.current?.centerView();
            }, 250);
        }
    }
  }, [height]); 

  const showModal = (type: string, nodeData: any) => {
    setActionType(type);
    setSelectedNode(nodeData);
    switch (type) {
      case 'add':
        setModalTitle('Org Structure Setup');
        // set child node level
        const childEntityLevel = getChildNodeEntityLevel(nodeData.entityLevel);
        const { entityLevel } = childEntityLevel;

        if (!hierarchyConfig[entityLevel]) {
          message.error('Please configure org hirachy');
          return;
        }

        form.setFieldsValue({
          name: '',
          entityLevel,
          headOfEntityId: null,
        });
        setIsModalOpen(true);
        break;
      case 'edit':
        setModalTitle('Edit Org Structure');
        form.setFieldsValue(nodeData);
        setIsModalOpen(true);
        break;
      case 'delete':
        confirm({
          title: 'Are you sure delete this Entity ?',
          icon: <ExclamationCircleOutlined />,
          content: '',
          okText: 'Yes',
          okType: 'danger',
          cancelText: 'No',
          onOk() {
            deleteNodeHandler(nodeData);
          },
        });
        break;
      default:
        break;
    }
  };

  const handleOk = (values: NodeData) => {
    const { id } = selectedNode;
    switch (actionType) {
      case 'add':
        // set parent node
        values.parentEntityId = id;
        addNodeHandler(values);
        break;
      case 'edit':
        values.id = id;
        editNodeHandler(values);
        break;
      default:
        break;
    }
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

  useEffect(() => {
    const hierarchyLevels = Object.keys(hierarchyConfig).map((key) => {
      return {
        label: hierarchyConfig[key],
        value: key,
      };
    });
    setLevels(hierarchyLevels);
    // scrollToBottom();
  }, [hierarchyConfig]);

  return (
    <>
      <div className="managerOrgChart">
        <TransformWrapper  ref={transformComponentRef} limitToBounds={true} initialScale={0.7} centerZoomedOut={true}  centerOnInit={true} minScale={0.1} maxScale={4}>
          {({ zoomIn, zoomOut, resetTransform, centerView, ...rest }) => (
            <>
              <Row justify="end">
                <Button
                  className={styles.panelButtons}
                  icon={<SyncOutlined />}
                  onClick={() => {
                    getOrgTreeData();
                  }}
                />
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
                <Button className={styles.panelButtons} onClick={() => {
                  resetTransform();
                  setTimeout(() => {
                    centerView();
                  }, 250);
                }}>
                  Reset
                </Button>
                <Button className={styles.panelButtons} onClick={() => centerView()}>
                  Center
                </Button>
              </Row>
              <br></br>
              <TransformComponent  wrapperStyle={(height > 1300 && height < 1600) ? { width: '100%', height: '100vh' } : (height > 1600 && height < 1800) ? { width: '100%', height: '120vh' } : height > 1800 ? { width: '100%', height: '140vh' } :  { width: '100%', height: '86vh' }}>
                <div ref={elementRef}>
                 <OrganizationChart
                    chartClass={classNme}
                    datasource={data}
                    // pan= {true}
                    collapsible={true}
                    NodeTemplate={(nodeData: any) => (
                        <OrgNode
                        getIsolatedOrgTreeData={getIsolatedOrgTreeData}
                        entityWiseEmpData={entityWiseEmpData}
                        nodeData={nodeData.nodeData}
                        showModal={showModal}
                        employeeList={employeeList}
                        />
                    )}
                 />
                </div>
              </TransformComponent>
            </>
          )}
        </TransformWrapper>
      </div>
      <Modal
        title={modalTitle}
        visible={isModalOpen}
        onOk={() => {
          form
            .validateFields()
            .then((values) => {
              form.resetFields();
              handleOk(values);
            })
            .catch((info) => {
              console.log('Validate Failed:', info);
            });
        }}
        onCancel={handleCancel}
      >
        <Form layout="vertical" form={form} initialValues={initailFormValue}>
          <Form.Item label="Entity" name="entityLevel">
            <Select options={levels} disabled />
          </Form.Item>
          <Form.Item label="Name" name="name" rules={[{ required: true }]}>
            <Input placeholder="Name" />
          </Form.Item>
          <Form.Item label="Entity Head" name="headOfEntityId">
            <ProFormSelect showSearch options={employeeList} placeholder="Entity Head" />
          </Form.Item>
        </Form>
      </Modal>
    </>
  );
};

export default ManagerOrgChart;
