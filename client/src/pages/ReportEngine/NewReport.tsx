import React, { useRef, useState, useEffect } from 'react';
import { PageContainer } from '@ant-design/pro-layout';
import {
  Form,
  Row,
  Col,
  Input,
  Select,
  Button,
  Card,
  Modal,
  Space,
  Divider,
  Typography,
  message as Message,
  Transfer,
  Tree,
  DatePicker,
  AutoComplete,
  Popover,
  Dropdown,
  Menu,
  Spin,
  Checkbox,
} from 'antd';
import { useParams, history, Access, useAccess, useModel, useIntl } from 'umi';
import {
  addReport,
  getReportDataById,
  queryFilterDefinitions,
  updateReport,
} from '@/services/reportService';
import { getModel } from '@/services/model';
import PermissionDeniedPage from '../403';
import { ConsoleSqlOutlined, DownCircleOutlined, MinusOutlined, MoreOutlined, PlusOutlined, UpCircleOutlined, DragOutlined } from '@ant-design/icons';
import { getAllEmployee } from '@/services/employee';
import _, { orderBy } from 'lodash';
import request from '@/utils/request';
import ProForm, { ModalForm } from '@ant-design/pro-form';
import { DndProvider, useDrop, useDrag } from 'react-dnd';
import { HTML5Backend } from 'react-dnd-html5-backend';
import './reportViewTableStyles.css';


export default (): React.ReactNode => {
  const { Text, Link } = Typography;
  const { id } = useParams<IParams>();
  const access = useAccess();
  const { Option } = Select;
  const { Search } = Input;
  const intl = useIntl();
  const { hasPermitted } = access;
  const [form] = Form.useForm();
  const [modalForm] = Form.useForm();

  const [loading, setLoading] = useState<boolean>(false);
  const [treeData, setTreeData] = useState([])
  const [filters, setFilters] = useState(
    [{
      field: "",
      condition: "",
      value: "",
      type: "",
      dateType: ""
    }])
  const [userFields, setUserFields] = useState({});
  const [isFilterOn, setIsFilterOn] = useState("all");
  const [isSystemReport, setIsSystemReport] = useState(false);
  const [followedByValue, setFolowedByValue] = useState("AND")
  const [inputCriterias, setInputCriteria] = useState({})
  const [fieldsAvailable, setFieldsAvailable] = useState([])
  const [targetKeys, setTargetKeys] = useState([]);
  const [modelDataSet, setModelDataSet] = useState([])
  const [employeeModel, setEmployeeModel] = useState([])
  const [submitButtonText, setSubmitButtonText] = useState("Save")
  const [treeDataCopy, setTreeDataCopy] = useState([])
  const [optionsVisible, setOptionsVisible] = useState(false)
  const [optionsModalData, setOptionsModalData] = useState({})
  const [currentDisplayNameArr, serCurrentDisplayNameArr] = useState([])
  const [existingReportData, setExixtingReportData] = useState([])
  const treeStructure = []
  const [sortBy, setSortBy] = useState(
    [])
  const [filterConditionValue, setFilterConditionValue] = useState("all")
  //const [treeSearchVal, setTreeSearchVal] = useState("")
  let treeSearchVal = ""
  //fetch data
  const [showHistoryKey, setShowHistoryKey] = useState('');
  const [aggregateField, setAggregateField] = useState('');
  useEffect(() => {
    const fetchData = async () => {

      await setLoading(true);

      await generateChildrenNodes();

      setTimeout(timed, 1000)

    };

    try {
      fetchData();

    } catch (error) {
    }
  }, [id]);

  const timed = () => {
    setLoading(false)

  }
  const generateChildrenNodes = async () => {

    const allfields = []
    let tables = []
    const filterDefinition = await queryFilterDefinitions()
    await setInputCriteria(filterDefinition.data)

    const employeeModelData = await getModel("employee", 'edit')
    const userModel = await generateReportEmployeeModel(employeeModelData);

    await setEmployeeModel(userModel.data)
    await setUserFields(userModel.data.modelDataDefinition.fields)
    if (id !== undefined) {
      setSubmitButtonText("Update")
      const reportData = await getReportDataById(id)
      if (reportData) {
        tables = reportData.data.selectedTables
      }
      setTimeout(timed, 500)
      form.setFieldsValue({ reportName: reportData.data.reportName });
      setTargetKeys(reportData.data.targetKeys)

      const isSystemReportValue = reportData.data.isSystemReport ? true : false;
      setIsSystemReport(isSystemReportValue);


    }
    else {
      setSubmitButtonText("Save")
    }
    // eslint-disable-next-line no-restricted-syntax
    for await (const element of userModel.data.frontEndDefinition.structure) {
      if (element.key !== "documentTemplates" && element.key !== "documents" && element.key !== "workSchedule") {

        const children = [];
        element.content.forEach(async (child) => {
          const childArray = child.content;

          if (child.key === "basicInformation") {
            childArray.push("employeeName")
          }
          await childArray.forEach(async (subChild) => {

            if (subChild !== 'profilePicture' && userModel.data.modelDataDefinition.relations[subChild] === "HAS_MANY") {

              const endChild = []

              //get multirecord Data

              const childModel = await getModel(userModel.data.modelDataDefinition.fields[subChild].modelName)

              if (childModel && childModel.data) {
                const objectKeys = await Object.keys(childModel.data.modelDataDefinition.fields)
                // eslint-disable-next-line no-restricted-syntax
                for await (const nodeChild of objectKeys) {
                  if (!childModel.data.modelDataDefinition.fields[nodeChild].isSystemValue) {
                    if (childModel.data.modelDataDefinition.fields[nodeChild].name == "calendar") {
                      childModel.data.modelDataDefinition.fields[nodeChild].modelName = "workCalendar";
                      childModel.data.modelDataDefinition.fields[nodeChild].enumValueKey = "id";
                      childModel.data.modelDataDefinition.fields[nodeChild].enumLabelKey = "name";
                    }

                    await endChild.push({
                      key: `${subChild}.${childModel.data.modelDataDefinition.fields[nodeChild].name}`,
                      title: childModel.data.modelDataDefinition.fields[nodeChild].defaultLabel,
                    })
                    let currentKey = `${subChild}.${childModel.data.modelDataDefinition.fields[nodeChild].name}`
                    let newTitle = `${userModel.data.modelDataDefinition.fields[subChild].defaultLabel} - ${childModel.data.modelDataDefinition.fields[nodeChild].defaultLabel}`
                    let showHistoryVal = false
                    if (tables.length > 0) {
                      const newLabel = _.find(tables, o => o.key === currentKey)

                      if (newLabel) {
                        showHistoryVal = newLabel.showHistory
                        newTitle = newLabel.displayName
                      }
                    }
                    await allfields.push({
                      key: `${subChild}.${childModel.data.modelDataDefinition.fields[nodeChild].name}`,
                      title: newTitle,
                      type: "multirecord",
                      columnName: `${childModel.data.modelDataDefinition.fields[nodeChild].name}`,
                      modelName: childModel.data.modelDataDefinition.name,
                      multirecordModelName: childModel.data.modelDataDefinition.fields[nodeChild].modelName,
                      enumLabelKey: childModel.data.modelDataDefinition.fields[nodeChild].enumLabelKey,
                      enumValueKey: childModel.data.modelDataDefinition.fields[nodeChild].enumValueKey,
                      columnType: childModel.data.modelDataDefinition.fields[nodeChild].type,
                      alias: `${userModel.data.modelDataDefinition.fields[subChild].name}${childModel.data.modelDataDefinition.fields[nodeChild].name}`,
                      displayName: newTitle,
                      parentKey: `${subChild}`,
                      isEffectiveDateConsiderable: userModel.data.modelDataDefinition.fields[subChild].isEffectiveDateConsiderable,
                      showHistory: !!showHistoryVal,
                      filterModelName: childModel.data.modelDataDefinition.fields[nodeChild].modelName,
                      filterColumnType: childModel.data.modelDataDefinition.fields[nodeChild].type,
                      filterEnumLabelKey: childModel.data.modelDataDefinition.fields[nodeChild].enumLabelKey,
                      filterEnumValueKey: childModel.data.modelDataDefinition.fields[nodeChild].enumValueKey,
                      filterValues: childModel.data.modelDataDefinition.fields[nodeChild].values,
                      isEncripted: childModel.data.modelDataDefinition.fields[nodeChild].isEncripted,
                      concatFields: (childModel.data.modelDataDefinition.fields[nodeChild].enumLabelKey == 'employeeName') ? userModel.data.modelDataDefinition.fields['employeeName'].concatFields : null
                    })
                  }
                }
              }

              await children.push({
                key: `${subChild}`,
                title: userModel.data.modelDataDefinition.fields[subChild].defaultLabel,
                children: endChild

              })

              await allfields.push({
                key: `${subChild}`,
                title: userModel.data.modelDataDefinition.fields[subChild].defaultLabel,
                isSystemValue: true,
                parentKey: `${element.key}Id`,
                isEffectiveDateConsiderable: userModel.data.modelDataDefinition.fields[subChild].isEffectiveDateConsiderable,
                showHistory: false,
                isEncripted: userModel.data.modelDataDefinition.fields[subChild].isEncripted
              })
            }

            else if (subChild !== 'profilePicture') {
              let currentKey = subChild
              let newTitle = userModel.data.modelDataDefinition.fields[subChild].defaultLabel
              if (subChild.includes("permanentAddress")) {
                newTitle = `Permanent Address - ${userModel.data.modelDataDefinition.fields[subChild].defaultLabel}`
              }
              else if (subChild.includes("residentialAddress")) {
                newTitle = `Residential Address - ${userModel.data.modelDataDefinition.fields[subChild].defaultLabel}`
              }

              if (tables.length > 0) {
                const newLabel = _.find(tables, o => o.key === currentKey)
                if (newLabel) {
                  newTitle = newLabel.displayName
                }
              }
              await allfields.push({
                key: subChild,
                title: newTitle,
                type: "normalField",
                columnType: userModel.data.modelDataDefinition.fields[subChild].type,
                alias: `${element.key}${subChild}`,
                displayName: newTitle,
                parentKey: `${element.key}Id`,
                isEffectiveDateConsiderable: userModel.data.modelDataDefinition.fields[subChild].isEffectiveDateConsiderable,
                showHistory: false,
                filterModelName: userModel.data.modelDataDefinition.fields[subChild].modelName,
                filterColumnType: userModel.data.modelDataDefinition.fields[subChild].type,
                filterEnumLabelKey: userModel.data.modelDataDefinition.fields[subChild].enumLabelKey,
                filterEnumValueKey: userModel.data.modelDataDefinition.fields[subChild].enumValueKey,
                filterValues: userModel.data.modelDataDefinition.fields[subChild].values,
                isComputed: userModel.data.modelDataDefinition.fields[subChild].isComputedProperty,
                isEncripted: userModel.data.modelDataDefinition.fields[subChild].isEncripted,
                concatFields: (userModel.data.modelDataDefinition.fields[subChild].name == 'employeeName') ? userModel.data.modelDataDefinition.fields['employeeName'].concatFields : null
              })
              await children.push({
                key: subChild,
                title: newTitle,

              })
            }
          })

        })
        await treeStructure.push(
          {
            key: `${element.key}Id`,
            title: element.defaultLabel,
            children: children,


          }
        )
        await allfields.push({
          key: `${element.key}Id`,
          title: element.defaultLabel,
          isSystemValue: true
        })
      }


    }
    await setTreeData(treeStructure)
    await setFieldsAvailable(allfields)



    if (id !== undefined) {
      await setSubmitButtonText("Update")
      const reportData = await getReportDataById(id)
      form.setFieldsValue({ reportName: reportData.data.reportName });
      await setTargetKeys(reportData.data.targetKeys)
      if (reportData.data.filterValues) {
        await setFilters(reportData.data.filterValues)


        reportData.data.filterValues.forEach((ele, index) => {
          form.setFieldsValue({ [`field${index}`]: ele.field });
          form.setFieldsValue({ [`condition${index}`]: ele.condition });
        })
      }

      if (reportData.data.sortByValues) {

        await setSortBy(reportData.data.sortByValues)

        reportData.data.sortByValues.forEach((ele, index) => {
          form.setFieldsValue({ [`sort${index}`]: ele.field });
          form.setFieldsValue({ [`sortcondition${index}`]: ele.condition });
        })
      }

      await setFolowedByValue(reportData.data.filterCondition)
      await setFilterConditionValue(reportData.data.filterCriterias.length > 0 ? 'only' : 'all')
      await setIsFilterOn(reportData.data.filterCriterias.length > 0 ? 'only' : 'all')
    }
    else {
      setSubmitButtonText("Save")
    }

  }

  const generateReportEmployeeModel = async (reportData: any) => {
    const fields = reportData?.data?.modelDataDefinition?.fields ?? {};
    const replacements = Object.values(fields)
      .filter((field: any) => _.has(field, 'reportField'))
      .map((field: any) => {
        return {
          origin: field.name,
          replacement: field.reportField
        };
      });

    let frontEndDefinitionString = JSON.stringify(reportData?.data?.frontEndDefinition ?? {});
    replacements.forEach((el: { origin: string, replacement: string }) => {
      frontEndDefinitionString = frontEndDefinitionString.replace(new RegExp(el.origin, 'g'), el.replacement);
    });

    const frontEndDefinition = JSON.parse(frontEndDefinitionString);
    reportData.data.frontEndDefinition = frontEndDefinition;
    return reportData;
  }

  const handleChange = t => {
    if (t.length > 0) {
      form.setFields([{ name: 'reportFields', errors: [] }])
    }



    const targetKeyArray = []
    t.forEach(element => {
      const selectedChildren = _.filter(fieldsAvailable, o => o.parentKey === element)

      if (selectedChildren.length > 0) {
        selectedChildren.forEach(ele => {

          if (!ele.isSystemValue) {
            targetKeyArray.push(ele.key)
          }
          else {
            _.filter(fieldsAvailable, o => o.parentKey === ele.key).forEach(data => {
              targetKeyArray.push(data.key)

            })
          }

        })
      }

      else {
        targetKeyArray.push(element)
      }

    });


    setTargetKeys(targetKeyArray);

  };



  const isChecked = (selectedKeys, eventKey) => {
    return selectedKeys.indexOf(eventKey) !== -1;
  }

  const generateTree = (treeNodes = [], checkedKeys = [], searchT) => {

    let finalArray = [];
    const searched = treeNodes.filter((obj) =>
      JSON.stringify(obj).toLowerCase().includes(searchT.toLowerCase())
    )

    //   filteredItems.forEach(ele=>{
    //     treeNodes.forEach(childEle=>{
    //       if(ele.key===childEle.key){
    //         finalArray.push(childEle)
    //       }
    //     })
    // })


    return searched.map(({ children, ...props }) => ({
      ...props,
      disabled: checkedKeys.includes(props.key),
      children: generateTree(children, checkedKeys, searchT),
    }))
  }


  const handleRenameModal = (e) => {
    setOptionsModalData(e)
    const index = _.findIndex(fieldsAvailable, (o) => { return o.key == e.key });
    modalForm.setFieldsValue({ fieldName: e.key, columnName: fieldsAvailable[index]["displayName"] });
    setOptionsVisible(true)
  }

  const onModalOk = () => {
    const newFieldsArray = [...fieldsAvailable]
    const index = _.findIndex(newFieldsArray, (o) => { return o.key == optionsModalData.key });
    newFieldsArray[index]["displayName"] = modalForm.getFieldValue("columnName")
    newFieldsArray[index]["title"] = modalForm.getFieldValue("columnName")

    setFieldsAvailable(newFieldsArray)

    setOptionsVisible(false)

  }
  const handleCancel = () => {
    setOptionsVisible(false)

  }
  const validateDisplayName = () => {
    const modalFormVal = modalForm.getFieldsValue()
    const element = _.find(fieldsAvailable, (o) => { return o.displayName === modalFormVal.columnName; });


    if (element && element.key !== modalFormVal.fieldName) {
      if (element.displayName !== "") {
        //modalForm.setFields([{name:'columnName',errors:["error"]}])
        return Promise.reject(new Error('This is an unique field.'));
      }

      return Promise.resolve();
    }

    return Promise.resolve();



    // if(!element){
    //   serCurrentDisplayNameArr(prev => [
    //     ...prev,
    //     modalFormVal
    //   ])
    // }

    // if(element.columnName === modalFormVal.columnName){

    //   }

  }

  const validatetargetKeys = () => {
    if (targetKeys.length === 0) {
      //modalForm.setFields([{name:'columnName',errors:["error"]}])
      return Promise.reject(new Error('At least one field must be selected'));
    }

    return Promise.resolve();
  }

  const handleDragAndDrop = (index: number, newIndex: number) => {
    const targetAray = [...targetKeys];
    const currentelement = targetAray[index];
    targetAray.splice(index, 1);
    targetAray.splice(newIndex, 0, currentelement);
    setTargetKeys([...targetAray]);
  }

  const handleReorderUp = (val) => {
    const targetAray = [...targetKeys]
    const index = _.findIndex(targetAray, (o) => { return o == val.key });
    const currentelement = targetAray[index]
    if (index > 0) {
      targetAray.splice(index, 1);
      targetAray.splice(index - 1, 0, currentelement)
    }
    setTargetKeys([...targetAray])
  }

  const handleReorderDown = (val) => {
    const targetAray = [...targetKeys]
    const index = _.findIndex(targetAray, (o) => { return o == val.key });
    const currentelement = targetAray[index]
    if (index < targetAray.length) {
      targetAray.splice(index, 1);
      targetAray.splice(index + 1, 0, currentelement)
    }
    setTargetKeys([...targetAray])
  }
  const handleShowHistory = (checked, key) => {
    const temFieldsAvailable = fieldsAvailable
    const keysWithEffectiveDate = _.filter(temFieldsAvailable, o => o.parentKey === key)
    keysWithEffectiveDate.forEach(eachKey => {
      const index = _.findIndex(temFieldsAvailable, o => o.key === eachKey.key)
      temFieldsAvailable[index]["showHistory"] = checked;

      if (checked) {
        setShowHistoryKey(key);
      } else {
        setShowHistoryKey("");
      }
    })
    setFieldsAvailable(temFieldsAvailable)
    setTargetKeys([...targetKeys])
  }
  const renderOptions = (data) => {


    let disabled = false;
    if (showHistoryKey === " ") {
      disabled = false;
    } else {
      if (showHistoryKey !== "") {
        if (showHistoryKey !== data.parentKey) {
          disabled = true;
        } else {
          disabled = false;
        }
      }
    }
    const currentValue = { ...data }
    const menu = (
      <Menu>
        <Menu.Item key={`${data.key}0`} >
          <div onClick={e => { e.preventDefault(); handleRenameModal(currentValue) }}> Rename </div>
        </Menu.Item>

        {data.isEffectiveDateConsiderable ? <><Menu.Divider />
          <Menu.Item key={`${data.key}1`} >
            <Space onClick={e => { e.stopPropagation() }} >Show History <Checkbox checked={data.showHistory} onChange={e => { handleShowHistory(e.target.checked, data.parentKey) }} disabled={disabled} /></Space>
          </Menu.Item></> : <></>}
      </Menu>)

    return (
      <>
        <Row>
          <Col span={18}>
            <div style={{
              wordWrap: "break-word"
            }}>
              {data.title}
            </div>

          </Col>
          <Col span={4}>
            <Space>
              <UpCircleOutlined onClick={e => { e.stopPropagation(); handleReorderUp(currentValue) }} />
              <DownCircleOutlined onClick={e => { e.stopPropagation(); handleReorderDown(currentValue) }} />
            </Space>
          </Col>
          <Col >
            <Dropdown overlay={menu} trigger={['click']}>
              < div onClick={e => { e.preventDefault(); e.stopPropagation() }}>
                <MoreOutlined />
              </div>
            </Dropdown>
          </Col>
        </Row>

      </>

    )

  }

  const handleLeftTreeSearch = (e, val) => {
    treeSearchVal = val;

  }
  const TreeTransfer = () => {
    //const transferDataSource = [];
    function flatten(list = []) {
      list.forEach(item => {

        flatten(item.children);
      });
    }
    flatten(treeData);
    return (
      <DndProvider backend={HTML5Backend}>
        <Transfer
          targetKeys={targetKeys}
          dataSource={fieldsAvailable}
          render={(item) => (
            <DraggableItem
              index={targetKeys.findIndex((key) => key === item.key)}
              data={item}
              moveRow={handleDragAndDrop}
            />
          )}
          // render={item=>item.title}
          listStyle={{
            height: 415,
          }}
          showSelectAll={false}
          onChange={handleChange}
          showSearch
          filterOption={(search, item) => {
            return item.title.toLowerCase().indexOf(search.toLowerCase()) >= 0;
          }}
          onSearch={(e, val) => handleLeftTreeSearch(e, val)}
        >
          {({ direction, onItemSelect, selectedKeys, onItemSelectAll }) => {
            const changeKeys = async (eventKey, selectedKeys) => {
              const index = _.findIndex(fieldsAvailable, (o) => {
                return o.key == eventKey;
              });
              let isParentFieldInSelectedList = false;
              let parentKey = fieldsAvailable[index]['parentKey'];

              if (parentKey != undefined) {
                // isParentFieldInSelectedList = (selectedKeys.indexOf(parentKey) !== -1) ? true : false;
                if (selectedKeys.indexOf(parentKey) !== -1) {
                  let childKeys = [];
                  fieldsAvailable.map((element, index) => {
                    if (element.parentKey == parentKey && element.key !== eventKey) {
                      childKeys.push(element.key);
                    }
                  });
                  await onItemSelect(parentKey, false);

                  const parentKeyIndex = _.findIndex(selectedKeys, (o) => {
                    return o == parentKey;
                  });
                  selectedKeys.splice(parentKeyIndex, 1);

                  let newKeys = [];

                  childKeys.map((el, index) => {
                    let keyIndex = _.findIndex(targetKeys, (o) => {
                      return o == el;
                    });
                    if (keyIndex === -1) {
                      newKeys.push(el);
                    }
                  });

                  await onItemSelectAll(newKeys, true);
                } else {
                  onItemSelect(eventKey, !isChecked(selectedKeys, eventKey));
                }
              } else {
                onItemSelect(eventKey, !isChecked(selectedKeys, eventKey));
              }
            };
            if (direction === 'left') {
              const checkedKeys = [...selectedKeys, ...targetKeys];
              return (
                <div style={{ overflowY: 'scroll', height: 315 }}>
                  {/* <Input style={{ marginBottom: 8,width: ""}} placeholder="Search" onClick={event => event.preventDefault()}  onChange={e=>{handleLeftTreeSearch(e);}  } className='test'/> */}

                  <Tree
                    blockNode
                    checkable
                    draggable={true}
                    checkedKeys={checkedKeys}
                    treeData={generateTree(treeData, targetKeys, treeSearchVal)}
                    onCheck={(e, { node: { key } }) => {
                      changeKeys(key, checkedKeys);
                      // onItemSelect(key, !isChecked(checkedKeys, key));
                    }}
                    onSelect={(e, { node: { key } }) => {
                      onItemSelect(key, !isChecked(checkedKeys, key));
                    }}
                  />
                </div>
              );
            }
          }}
        </Transfer>
      </DndProvider>
    );
  };

  const type = 'DraggableItem';
  const DraggableItem = ({ index, data, moveRow }) => {
    const ref = useRef();
    const [{ isOver, dropClassName }, drop] = useDrop({
      accept: type,
      collect: (monitor) => {
        const { index: dragIndex } = monitor.getItem() || {};
        if (dragIndex === index) {
          return {};
        }
        return {
          isOver: monitor.isOver(),
          dropClassName: dragIndex < index ? ` drop-over-downward` : ` drop-over-upward`,
        };
      },
      drop: (item) => {
        console.log('drop > ', item);
        console.log('index > ', index);
        moveRow(item.index, index);
        // handleDragAndDrop(item.index, index);
      },
    });

    const [{ isDragging }, drag, preview] = useDrag({
      type,
      item: { index },
      collect: (monitor) => ({
        isDragging: monitor.isDragging(),
      }),
    });

    preview(drop(ref));

    let disabled = false;
    if (showHistoryKey === ' ') {
      disabled = false;
    } else {
      if (showHistoryKey !== '') {
        if (showHistoryKey !== data.parentKey) {
          disabled = true;
        } else {
          disabled = false;
        }
      }
    }
    const currentValue = { ...data };
    const menu = (
      <Menu>
        <Menu.Item key={`${data.key}0`}>
          <div
            onClick={(e) => {
              e.preventDefault();
              handleRenameModal(currentValue);
            }}
          >
            {' '}
            Rename{' '}
          </div>
        </Menu.Item>

        {data.isEffectiveDateConsiderable ? (
          <>
            <Menu.Divider />
            <Menu.Item key={`${data.key}1`}>
              <Space
                onClick={(e) => {
                  e.stopPropagation();
                }}
              >
                Show History{' '}
                <Checkbox
                  checked={data.showHistory}
                  onChange={(e) => {
                    handleShowHistory(e.target.checked, data.parentKey);
                  }}
                  disabled={disabled}
                />
              </Space>
            </Menu.Item>
          </>
        ) : (
          <></>
        )}
      </Menu>
    );

    return (
      <div key={data.key} ref={ref} className={`${isOver ? dropClassName : ''}`}>
        <Row>
          <Col span={16}>
            <div
              style={{
                wordWrap: 'break-word',
              }}
            >
              {data.title}
            </div>
          </Col>
          <Col span={4}>
            <Space>
              <UpCircleOutlined
                onClick={(e) => {
                  e.stopPropagation();
                  console.log('currentValue > ', currentValue);
                  handleReorderUp(currentValue);
                }}
              />
              <DownCircleOutlined
                onClick={(e) => {
                  e.stopPropagation();
                  handleReorderDown(currentValue);
                }}
              />
            </Space>
          </Col>
          <Col span={2}>
            <Dropdown overlay={menu} trigger={['click']}>
              <div
                onClick={(e) => {
                  e.preventDefault();
                  e.stopPropagation();
                }}
              >
                <MoreOutlined />
              </div>
            </Dropdown>
          </Col>
          <Col span={2}>
            <Space>
              <DragOutlined
                ref={drag}
                onClick={(e) => {
                  e.stopPropagation();
                }}
              />
            </Space>
          </Col>
        </Row>
      </div>
    );
  };

  const makeid = (length) => {
    let result = '';
    let characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    let charactersLength = characters.length;
    for (var i = 0; i < length; i++) {
      result += characters.charAt(Math.floor(Math.random() *
        charactersLength));
    }
    return result;
  }

  //form handling
  const onFinish = async (formData: IReportForm) => {
    const { reportName } = formData;
    let filterCritiras = []



    const newFormatFields = []
    const selectedFieldsArray = [];
    const joinCriteriasValues = []
    const selectedFieldsMulti = []
    const SelectedTablesFromMultiRecord = []
    let aliasName;
    targetKeys.forEach((element, index) => {
      let columnValue = element;
      const currentField = _.find(fieldsAvailable, (o) => { return o.key === element; });

      if (currentField.type == "normalField" && !currentField.isSystemValue) {
        if (currentField.columnType === "model") {
          columnValue = `${element}Id`
          joinCriteriasValues.push(
            {
              tableOneName: employeeModel.modelDataDefinition.name,
              tableOneAlias: employeeModel.modelDataDefinition.name,
              tableOneOperandOne: columnValue,
              operator: "=",
              tableTwoName: employeeModel.modelDataDefinition.fields[element].modelName,
              tableTwoOperandOne: employeeModel.modelDataDefinition.fields[element].enumValueKey,
              tableTwoAlias: currentField.alias

            }
          )


          selectedFieldsArray.push({
            key: currentField.key,
            isDerived: false,
            originalTableName: "employee",
            tableName: currentField.alias,
            parentColumnName: `${employeeModel.modelDataDefinition.fields[element].modelName}Id`,
            columnName: employeeModel.modelDataDefinition.fields[element].enumLabelKey,
            displayName: currentField.displayName === "" ? employeeModel.modelDataDefinition.fields[element].enumLabelKey : currentField.displayName,
            columnIndex: index,
            valueType: currentField.columnType,
            dataIndex: `${currentField.alias}${employeeModel.modelDataDefinition.fields[element].enumLabelKey}`,
            isEncripted: currentField.isEncripted,
          })
        }
        else {

          console.log(currentField);
          selectedFieldsArray.push({
            key: currentField.key,
            isDerived: currentField.isComputed || false,
            tableName: "employee",
            originalTableName: "employee",
            parentColumnName: columnValue,
            columnName: columnValue,
            displayName: currentField.displayName === "" ? `${currentField.modelName} ${currentField.columnName}` : currentField.displayName,
            columnIndex: index,
            valueType: currentField.columnType,
            dataIndex: `employee${columnValue}`,
            isEncripted: currentField.isEncripted,
            concatFields: (currentField.concatFields) ? currentField.concatFields : null
          })
        }
      }
      else if (currentField.type === "multirecord" && !currentField.isSystemValue) {

        let selectedField = {
          key: currentField.key,
          isDerived: false,
          tableName: currentField.modelName,
          originalTableName: currentField.modelName,
          parentColumnName: currentField.columnName,
          columnName: currentField.columnName,
          displayName: currentField.displayName === "" ? `${currentField.modelName} ${currentField.columnName}` : currentField.displayName,
          columnIndex: index,
          valueType: currentField.columnType,
          dataIndex: `${currentField.modelName}${currentField.columnName}`,
          showHistory: !!currentField.showHistory,
          isEncripted: currentField.isEncripted
        }

        let joinElement
        if (currentField.isEffectiveDateConsiderable) {
          const currentValueobject = _.find(employeeModel.modelDataDefinition.fields, o => o.modelName === currentField.modelName && o.isSystemValue)
          joinElement = {
            tableOneName: employeeModel.modelDataDefinition.name,
            tableOneAlias: employeeModel.modelDataDefinition.name,
            tableOneOperandOne: "id",
            tableOneOperandTwo: `${currentValueobject.name}Id`,
            operator: "=",
            tableTwoName: currentField.modelName,
            tableTwoAlias: currentField.modelName,
            tableTwoOperandOne: "employeeId",
            tableTwoOperandTwo: "id"
          }
          if (currentField.showHistory) {
            joinElement = _.omit(joinElement, ['tableOneOperandTwo', 'tableTwoOperandTwo'])
          }

        } else {
          joinElement = {
            tableOneName: employeeModel.modelDataDefinition.name,
            tableOneAlias: employeeModel.modelDataDefinition.name,
            tableOneOperandOne: "id",
            operator: "=",
            tableTwoName: currentField.modelName,
            tableTwoAlias: currentField.modelName,
            tableTwoOperandOne: "employeeId",
          }
        }

        const currentIndex = _.findIndex(joinCriteriasValues, o => o.tableTwoName === currentField.modelName)
        if (currentIndex < 0) {
          joinCriteriasValues.push(joinElement)
        }
        else {
          joinCriteriasValues[currentIndex] = joinElement
        }


        if (currentField.columnType === "model") {

          selectedField = {
            key: currentField.key,
            isDerived: currentField.enumLabelKey === "employeeName" ? true : false,
            tableName: currentField.alias,
            originalTableName: currentField.modelName,
            parentColumnName: currentField.columnName,
            columnName: currentField.enumLabelKey,
            displayName: currentField.displayName === "" ? `${currentField.modelName} ${currentField.columnName}` : currentField.displayName,
            columnIndex: index,
            valueType: currentField.columnType,
            dataIndex: `${currentField.alias}${currentField.enumLabelKey}`,
            showHistory: !!currentField.showHistory,
            isEncripted: currentField.isEncripted,
            concatFields: currentField.concatFields
          }
          if (currentField.enumLabelKey === "employeeName") {
            const multiRecordModelJoin = {
              tableOneName: currentField.modelName,
              tableOneAlias: currentField.modelName,
              tableOneOperandOne: `${currentField.columnName}Id`,
              operator: "=",
              tableTwoName: currentField.multirecordModelName,
              tableTwoAlias: currentField.alias,
              tableTwoOperandOne: "id",
            }
            joinCriteriasValues.push(multiRecordModelJoin)
          }
          else {
            const multiRecordModelJoin = {
              tableOneName: currentField.modelName,
              tableOneAlias: currentField.modelName,
              tableOneOperandOne: `${currentField.columnName}Id`,
              operator: "=",
              tableTwoName: currentField.multirecordModelName,
              tableTwoAlias: currentField.alias,
              tableTwoOperandOne: "id",
            }
            joinCriteriasValues.push(multiRecordModelJoin)
          }

        }
        selectedFieldsArray.push(selectedField)






      }

    })
    if (isFilterOn == "only") {
      filters.forEach((element) => {
        // const val = `employee.${element.field} ${} ${element.value}`
        let criteriaValue = `employee.${element.field}`
        // if (element.type.columnType == "model") {
        //   criteriaValue = `employee.${element.field}Id`


        // }
        let filterTableName = `employee.${element.type.key}`

        if (element.type.columnType == "model") {
          if (element.type.modelName) {

            const aliasForTable = `${element.type.modelName}${makeid(5)}`
            filterTableName = `${aliasForTable}.${element.type.filterModelName}Id`
            joinCriteriasValues.push(
              {
                tableOneName: "employee",
                tableOneAlias: "employee",
                tableOneOperandOne: "id",
                operator: "=",
                tableTwoName: element.type.modelName,
                tableTwoOperandOne: "employeeId",
                tableTwoAlias: aliasForTable,

              }
            )

          }
          else {
            filterTableName = `employee.${element.type.filterModelName}Id`


          }
        }
        else {

          if (element.type.modelName) {

            const aliasForTable = `${element.type.columnType}${makeid(5)}`
            filterTableName = `${aliasForTable}.${element.type.columnName}`
            joinCriteriasValues.push(
              {
                tableOneName: "employee",
                tableOneAlias: "employee",
                tableOneOperandOne: "id",
                operator: "=",
                tableTwoName: element.type.modelName,
                tableTwoOperandOne: "employeeId",
                tableTwoAlias: aliasForTable,

              }
            )

          }
          else {
            filterTableName = `employee.${element.type.key}`


          }

        }
        filterCritiras.push(
          {
            criteria: filterTableName,
            condition: element.condition,
            value: element.value,
            followedBy: followedByValue,
            type: element.type.columnType,
            dateType: element.dateType
          })
      })
    }
    else {
      filterCritiras = []
    }
    const orderByArray = []
    sortBy.forEach(element => {
      const fieldData = _.find(selectedFieldsArray, o => o.key === element.type.key)
      orderByArray.push({
        columnName: fieldData.dataIndex,
        order: element.condition
      })
    });
    const requestData = {
      reportName,
      outputMethod: "pdf",
      selectedTables: selectedFieldsArray,
      filterCriterias: filterCritiras,
      joinCriterias: joinCriteriasValues,
      groupBy: [],
      orderBy: orderByArray,
      pageSize: 20,
      targetKeys: targetKeys,
      filterValues: filters,
      sortByValues: sortBy,
      filterCondition: followedByValue
    };

    if (id == undefined) {
      try {
        const { message, data } = await addReport(requestData);
        const { id: reportId } = data;

        history.push(`/report-engine/report-wizard/${reportId}`);
        Message.success(message);
      } catch (err) {
        console.log(err)
        const errorArray = []
        for (const i in err.data) {
          errorArray.push({ name: i, errors: err.data[i] })
        }
        form.setFields([...errorArray]);
      }
    } else {
      try {

        requestData["id"] = id;
        const { message, data } = await updateReport(id, requestData);
        Message.success(message);
      } catch (err) {
        console.log(err);
        const errorArray = []
        for (const i in err.data) {
          errorArray.push({ name: i, errors: err.data[i] })
        }
        form.setFields([...errorArray]);
      }
    }
  };




  //handle dynamic filters
  const addFilterField = () => {
    const tempFilters = filters;
    setFilters([...filters, {
      field: "",
      condition: "",
      value: "",
      type: "",
      dateType: ""
    }])
  }
  const addSortField = () => {
    const tempSortBy = sortBy;
    setSortBy([...sortBy, {
      field: "",
      condition: "",
      type: "",

    }])
  }
  const removeFilterField = (e) => {
    const removedFilters = filters
    removedFilters.splice(e, 1)
    setFilters([...removedFilters])

  }

  const removeSortField = (e) => {
    const removedSorts = sortBy
    removedSorts.splice(e, 1)
    setSortBy([...removedSorts])

  }
  const setFieldValue = (field, value, index,) => {
    const filterVal = filters;
    filterVal[index][field] = value;
    setFilters([...filterVal])
  }

  const setSortFieldValue = (field, value, index,) => {
    const sortByVal = sortBy;
    sortByVal[index][field] = value;
    setSortBy([...sortByVal])
  }

  const fetchModelData = async (model) => {
    const filterModel = await getModel(model.type.filterModelName)
    const path: string = `/api${filterModel.data.modelDataDefinition.path}`;
    const response = await request(path, {})
    if (response && response.data && Array.isArray(response.data)) {
      const dataSet = []
      response.data?.forEach(data => {
        dataSet.push(
          {
            label: data[model.type.filterEnumLabelKey],
            value: data[model.type.filterEnumValueKey]
          })
      });

      //  setValuesSet(dataSet);

      setModelDataSet(dataSet)
    }

  }


  const renderSortOptions = (index) => {
    const elementType = sortBy[index].type.columnType
    switch (elementType) {
      case "string":
        return (<>
          <Option value={"ASC"}>A to Z</Option>
          <Option value={"DESC"}>Z to A</Option>

        </>)
      case "number":
        return (<>
          <Option value={"ASC"}>Ascending</Option>
          <Option value={"DESC"}>Descending</Option>
        </>)
      case "timestamp":
        return (<>
          <Option value={"ASC"}>Oldest First</Option>
          <Option value={"DESC"}>Newest First</Option>
        </>)


      default:
        return (<>
          <Option value={"ASC"}>A to Z</Option>
          <Option value={"DESC"}>Z to A</Option>
        </>)
    }

  }
  const renderDynamicFilters = (model, element, index) => {
    if (model.type.columnType === 'timestamp') {
      return (

        <>
          <Space>
            <Select
              showSearch
              defaultValue={"date"}
              style={{ width: 100 }}
              value={element.dateType}
              onChange={(e) => { setFieldValue("dateType", e, index) }}
            >
              <Option value="date">Date</Option>
              <Option value="month">Month</Option>
              <Option value="year">Year</Option>
            </Select>

            <DatePicker onChange={(e, dateString) => { setFieldValue("value", dateString, index) }} picker={filters[index].dateType} />
          </Space>
        </>
      )
    }
    if (model.type.columnType === "model") {

      return (<>
        <Select
          showSearch
          defaultValue={"date"}
          style={{ width: 160 }}
          onClick={(e) => { fetchModelData(model) }}
          value={element.value}
          onChange={(e) => { setFieldValue("value", e, index) }}
          options={modelDataSet}
        />

      </>)

    }

    if (model.type.columnType === "enum") {
      return (<>
        <Select
          defaultValue={"date"}
          style={{ width: 160 }}
          value={element.value}
          onChange={(e) => { setFieldValue("value", e, index) }}
          options={model.type.filterValues}
        />

      </>)
    }
    if (model.type.columnType === "string") {
      return (<>
        <Input
          style={{ width: 160 }}
          value={element.value}
          onChange={(e) => { setFieldValue("value", e.target.value, index) }}
        />



        {/* <AutoComplete
          style={{ width: 200 }}
          value={element.value}
          onSelect={(e) => setFieldValue("value", e, index)}
          onSearch={(e) => loadFieldValue(e, element.field)}
          onChange={(e) => { setFieldValue("value", e, index) }}

        >
          {searchVal.map((e) => {
            return (<Option value={e[element.field]}>{e[element.field]}</Option>)

          })}
        </AutoComplete> */}
      </>)

    }

    if (model.type.columnType === "employeeNumber") {
      return (<>
        <Input
          style={{ width: 160 }}
          value={element.value}
          onChange={(e) => { setFieldValue("value", e.target.value, index) }}
        />
      </>)
    }

    if (model.type.columnType === "boolean") {
      return (<>
        <Select
          defaultValue='1'
          style={{ width: 160 }}
          value={element.value}
          onChange={(e) => { setFieldValue("value", e, index) }}
          options={[
            {value: 1, label: 'Yes'},
            {value: 0, label: 'No'}
          ]}
          
        />
      </>)
    }

    return (
      <Input
        type={"number"}
        style={{ width: 160 }}
        value={element.value}
        onChange={(e) => { setFieldValue("value", e.target.value, index) }}
      />
    )
  }


  return (

    <Access
      accessible={hasPermitted('reports-read-write')}
      fallback={<PermissionDeniedPage />}
    >
      {isSystemReport === false ?
        (
          <PageContainer loading={loading}>

            <Card>
              <Col offset={1} span={24}>
                <ProForm
                  form={form}
                  layout="vertical"
                  initialValues={{
                  }}
                  name="control-hooks"
                  onFinish={onFinish}
                  submitter={false}
                >
                  <Col span={10}>
                    <Form.Item name="reportName" label="Report Name" rules={[
                      {
                        required: true,
                        message: intl.formatMessage({
                          id: 'reportName',
                          defaultMessage: 'Required',
                        }),
                      },
                      {
                        max: 50,
                        message: intl.formatMessage({
                          id: 'reportName.max',
                          defaultMessage: 'Maximum length is 50 characters',
                        }),
                      }
                    ]}>
                      <Input />
                    </Form.Item>
                  </Col>
                  <Col span={24}>
                    <Form.Item name="reportFields" label="Choose Fields" validateTrigger={"onBlur"} rules={[{ validator: validatetargetKeys }]}>
                      <Text type="secondary">Select the fields you like to report on</Text>
                      <Row>
                        <Col span={10}>
                          {/* <Input style={{ marginBottom: 8,width: ""}} placeholder="Search"  onChange={e=>{handleLeftTreeSearch(e);}  }/> */}
                        </Col>
                        <Col span={2}></Col>
                        <Col span={10}>
                          {/* <Input style={{ marginBottom: 8 }} placeholder="Search"  onChange={e=>{handonChange(e);}  }/> */}

                        </Col>
                      </Row>
                      <Row>
                        <Col span={24} xxl={18}>
                          <TreeTransfer />

                        </Col>

                      </Row>

                    </Form.Item>

                  </Col>

                  <Form.Item name="createFilters" label="Create filters">
                    <Text type="secondary">Select conditions to filter the results of your report</Text>
                    <Row style={{ marginBottom: 8 }} gutter={[32, 16]}>
                      <Col>
                        <Text >Show </Text>
                        <Select style={{ width: 160 }} value={filterConditionValue} onChange={(e) => { setIsFilterOn(e); setFilterConditionValue(e) }} >
                          <Option value="all">All Employees</Option>
                          <Option value="only">Only Employees</Option>
                        </Select>
                      </Col>
                      {isFilterOn === "only" ? <> <Col >
                        <Text >that match  </Text>
                        <Select style={{ width: 160 }} defaultValue="all" value={followedByValue} onChange={(e) => { setFolowedByValue(e) }}>
                          <Option value="AND">All</Option>
                          <Option value="OR">any</Option>

                        </Select>

                      </Col>
                        <Col>
                          <Text >of the following conditions </Text>
                        </Col></> : <></>}


                    </Row>

                  </Form.Item>


                  {isFilterOn === "only" ? <>

                    {filters.map((element, index) => {
                      return (

                        <Row gutter={[32, 16]}>
                          <Col className='horizontal-form-column'  >
                            {/* <Text className="customRequiredFields">Field </Text> */}
                            <Form.Item rules={[{
                              required: true,
                              message: intl.formatMessage({
                                id: 'fieldName',
                                defaultMessage: 'Required',
                              }),
                            }]} name={`field${index}`} label="Field" >
                              <Select showSearch
                                optionFilterProp="children"
                                filterOption={(input, option) =>
                                  option.children.toLowerCase().indexOf(input.toLowerCase()) >= 0
                                }
                                defaultValue={element.field} style={{ width: 260 }} value={element.field}
                                onSelect={(e) => { setFieldValue("field", e, index); setFieldValue("type", fieldsAvailable[e], index) }}  >
                                {/* {Object.keys(userFields).map(index => {
                              if (!userFields[index].isSystemValue)
                                return <Option value={index}>{userFields[index].defaultLabel}</Option>
                            })} */}
                                {fieldsAvailable.map((element, index) => {
                                  if (!element.isSystemValue)
                                    return <Option value={index}>{element.title}</Option>
                                })}

                              </Select>
                            </Form.Item>
                          </Col>
                          <Col className='horizontal-form-column' >
                            {/* <Text className="customRequiredFields">Condition  </Text> */}
                            <Form.Item rules={[{
                              required: true,
                              message: intl.formatMessage({
                                id: 'condition',
                                defaultMessage: 'Required',
                              }),
                            }]} name={`condition${index}`} label="Condition" >

                              <Select
                                defaultValue={element.condition} style={{ width: 160 }} value={element.condition} onChange={(e) => { setFieldValue("condition", e, index) }} >
                                {_.get(inputCriterias, filters[index].type.columnType, []).map((typeOfField) => {
                                  return <Option value={typeOfField.value}>{typeOfField.label}</Option>


                                })}
                              </Select>
                            </Form.Item>
                          </Col>
                          <Col className='horizontal-form-column ant-form-item-label' >
                            <label >Value&nbsp;&nbsp;</label>
                            {renderDynamicFilters(filters[index], element, index)}


                          </Col>
                          <Col >
                            <Button shape="circle" icon={<MinusOutlined />} onClick={() => removeFilterField(index)} />

                          </Col>
                        </Row>)

                    })}


                    <Row gutter={[2, 10]} style={{ marginBottom: 10 }}>


                    </Row>
                    <Row gutter={[3, 20]} style={{ marginBottom: 10 }}>
                      <Col xs={24} sm={24} md={24} lg={24} xl={24} xxl={17}>
                        <Button type="dashed" block onClick={addFilterField} icon={<PlusOutlined />}> Add filters</Button>

                      </Col>


                    </Row>
                  </> : <></>}
                  <Form.Item name="createsort" label="Sort Results">
                    <Text type="secondary">How would you like the results to be sorted?</Text>

                  </Form.Item>
                  {sortBy.map((element, index) => {
                    return (
                      <Row gutter={[32, 16]}>
                        <Col className='horizontal-form-column'  >
                          {/* <Text className="customRequiredFields">Field </Text> */}
                          <Form.Item rules={[{
                            required: true, message: intl.formatMessage({
                              id: 'sort.fieldName',
                              defaultMessage: 'Required',
                            }),
                          }]} name={`sort${index}`} label={index === 0 ? "Sort by" : "Then by"} >
                            <Select showSearch style={{ width: 260 }} value={element.field} onChange={(e) => { setSortFieldValue("field", e, index); setSortFieldValue("type", _.find(fieldsAvailable, o => o.key == e), index) }}  >
                              {/* {Object.keys(userFields).map(index => {
                              if (!userFields[index].isSystemValue)
                                return <Option value={index}>{userFields[index].defaultLabel}</Option>
                            })} */}

                              {targetKeys.map((element, subIndex) => {
                                const field = _.find(fieldsAvailable, o => o.key == element)
                                if (!field.isSystemValue)
                                  return <Option value={field.key}>{field.title}</Option>
                              })}

                            </Select>
                          </Form.Item>
                        </Col>
                        <Col className='horizontal-form-column' >
                          {/* <Text className="customRequiredFields">Condition  </Text> */}
                          <Form.Item rules={[{
                            required: true, message: intl.formatMessage({
                              id: 'sort.condition',
                              defaultMessage: 'Required',
                            }),
                          }]} name={`sortcondition${index}`} label="Order" >

                            <Select defaultValue={element.condition} style={{ width: 260 }} value={element.condition} onChange={(e) => { setSortFieldValue("condition", e, index) }} >
                              {renderSortOptions(index)}




                            </Select>
                          </Form.Item>
                        </Col>
                        <Col >
                          <Button shape="circle" icon={<MinusOutlined />} onClick={() => removeSortField(index)} />

                        </Col>
                      </Row>)

                  })}
                  <Row gutter={[2, 10]} style={{ marginBottom: 10 }}>


                  </Row>
                  <Row gutter={[3, 20]} style={{ marginBottom: 10 }}>
                    <Col xs={24} sm={24} md={24} lg={24} xl={24} xxl={17}>

                      <Button type="dashed" block onClick={addSortField} icon={<PlusOutlined />}> Add Sort</Button>
                    </Col>


                  </Row>
                  <Row>
                    <Col span={24} style={{ textAlign: 'right' }}>
                      <Form.Item>
                        <Space>
                          <Button
                            htmlType="button"
                            onClick={() => {
                              history.push(`/report-engine`);
                            }}
                          >
                            Back
                          </Button>
                          <Button type="primary" htmlType="submit">
                            {submitButtonText}
                          </Button>
                        </Space>
                      </Form.Item>
                    </Col>
                  </Row>
                </ProForm>
              </Col>
            </Card>
            {/* <Modal title="Column Settings" visible={optionsVisible}  onCancel={handleCancel} onOk={onModalOk}

        footer={[<Button type="primary" htmlType="submit">
        Submit
      </Button>]}
        > */}
            <ModalForm
              form={modalForm}
              onFinish={onModalOk}
              title="Column Settings"
              visible={optionsVisible}
              width={'600px'}
              onVisibleChange={setOptionsVisible}
            //onChange={e=>{validateDisplayName(e)}}
            >
              <Row>
                <Col span={12}>
                  <Form.Item name="fieldName" label="Field Name" labelCol={{ span: 24 }}>
                    <Input disabled />
                  </Form.Item>
                </Col>
                <Col span={12}>
                  <Form.Item name="columnName" label="Column Name" labelCol={{ span: 24 }}
                    rules={[{ required: true, message: 'Required' }]}
                  >
                    <Input />
                  </Form.Item>

                </Col>
              </Row>


            </ModalForm>

          </PageContainer>
        )
        : (
          <PermissionDeniedPage />
        )
      }
    </Access>


  );
};
