import React, { useEffect, useState } from 'react';
import { Col, Form, Row, Select } from 'antd';
import { getAllEntities } from '@/services/department';
import _ from 'lodash';

interface OrgSelectorProps {
  value?: number;
  setValue?: (values: number) => void;
  span?: number;
  readOnly?: boolean;
  className?: string;
  orgEntities?: {
    entities: any;
    orgHierarchyConfig: any;
  };
  colStyle?: any;
  allowClear?: boolean;
}

interface Entity {
  id: number;
  name: string;
  parentEntityId: number;
  entityLevel: string;
  headOfEntityId: number;
}

const OrgSelector: React.FC<OrgSelectorProps> = ({
  value,
  setValue,
  span,
  readOnly,
  className,
  orgEntities,
  colStyle,
  allowClear = false,
}) => {
  const defaultLevelState = {
    level1: null,
    level2: null,
    level3: null,
    level4: null,
    level5: null,
    level6: null,
    level7: null,
    level8: null,
    level9: null,
    level10: null,
  };

  const [entities, setEntities] = useState<Array<Entity>>([]);
  const [hierarchyConfig, setHierarchyConfig] = useState<Object>({});
  const [entityObject, setEntityObject] = useState<Object>({});
  const [levelState, setLevelState] = useState<Object>(defaultLevelState);

  useEffect(() => {
    loadData();
  }, []);

  useEffect(() => {
    if (value) {
      const initLevels = setInitialValue(value);
      setLevelState({ ...levelState, ...initLevels });
    }
    const treeData = generateTreeData();
    setEntityObject(treeData);
  }, [entities, hierarchyConfig]);

  useEffect(() => {
    if (value == 1) {
      setLevelState({ ...defaultLevelState, level1: 1 });
    }
  }, [value]);

  const loadData = async () => {
    try {
      if (orgEntities) {
        setEntities(orgEntities.entities);
        setHierarchyConfig(orgEntities.orgHierarchyConfig);
      } else {
        const entities = await getAllEntities();
        setEntities(entities.data.entities);
        setHierarchyConfig(entities.data.orgHierarchyConfig);
      }
    } catch (error) {
      console.error(error);
    }
  };

  const generateTreeData = () => {
    const groupedNodes = _.groupBy(entities, 'entityLevel');
    return _.mapValues(groupedNodes, function (group, key) {
      return {
        level: key,
        levelLabel: hierarchyConfig[key],
        nodes: group,
      };
    });
  };

  const getOptions = (level: string) => {
    const parentLevel = getParentLevel(level);
    const parentSelectedValue = levelState[parentLevel];
    const obj = entityObject[level];
    const nodeData = obj.nodes;

    const options = nodeData
      .filter((node: Entity) => {
        return parentSelectedValue ? node.parentEntityId === parentSelectedValue : node;
      })
      .map(({ id, name }: Entity) => {
        return {
          value: id,
          label: name,
        };
      });

    return options;
  };

  const onChangeHandler = (level: string, value: number) => {
    const levelStatueObj = { ...levelState };
    levelStatueObj[level] = value;

    const childLevels = getChildLevels(level, []);
    const obj = {};
    childLevels.forEach((childLevel: string) => {
      obj[childLevel] = null;
    });

    setLevelState({ ...levelStatueObj, ...obj });
  };

  const getParentLevel = (level: string) => {
    const levelNumber = parseInt(level.substring(5, 7));
    return `level${levelNumber - 1}`;
  };

  const getChildLevels = (level: string, levelsArray: Array<string>) => {
    const levelNumber = parseInt(level.substring(5, 7));
    const childLevel = `level${levelNumber + 1}`;
    if (levelNumber < 10) {
      levelsArray.push(childLevel);
      getChildLevels(childLevel, levelsArray);
    }

    return levelsArray;
  };

  const setInitialValue = (entityId: number) => {
    const object = {};
    getParentNode(entityId, object);
    return object;
  };

  const getParentNode = (entityId: number, obj: Object) => {
    const entity = _.find(entities, { id: entityId });
    if (entity) {
      const { id, entityLevel, parentEntityId } = entity;
      obj[entityLevel] = id;
      if (parentEntityId) {
        getParentNode(parentEntityId, obj);
      }
    }
  };

  const getFields = () => {
    return Object.keys(entityObject).map((key, index) => {
      const obj = entityObject[key];

      return (
        <Col
          span={span ?? 12}
          key={key}
          hidden={key !== 'level1' && levelState[getParentLevel(key)] === null}
          style={colStyle ?? {}}
        >
          <Form.Item>
            <label
              style={{ color: '#324054', fontSize: '13px', fontWeight: 500}}
            >
              {obj.levelLabel} :
            </label>
            <Select
            style={{marginTop:"10px"}}
              className={className}
              onChange={(value: number) => {
                onChangeHandler(key, value);
                if (setValue) {
                  setValue(value);
                }
              }}
              options={getOptions(key)}
              value={levelState[key]}
              disabled={readOnly}
              allowClear={allowClear && index !== 0}
              placeholder={index === 0 ? 'Select' : ''}
            />
          </Form.Item>
        </Col>
      );
    });
  };

  // return (
  //   <Form layout="vertical">
  //     <Row gutter={24}>{getFields()}</Row>
  //   </Form>
  // );

  return getFields();
};

export default OrgSelector;
