import React, { useState, useEffect } from "react";
import { Modal, Badge, Card, FormInstance, Row, Tabs, Space, Button } from 'antd';
import { ModelType } from '@/services/model';
import FormInput from "../FormInput";
import { useIntl, useAccess } from 'umi';
import _, { concat } from "lodash";
import { StickyContainer, Sticky } from 'react-sticky';
import { ExclamationCircleOutlined } from "@ant-design/icons";

const { TabPane } = Tabs;

export type DynamicFieldSetProps = {
  form: FormInstance;
  fieldNamePrefix?: string;
  initialValues: {};
  values: {};
  setValues: (values: any) => void;
  errors: {};
  setErrors: (values: any) => void;
  formSubmit: (values: any) => void;
  tabActiveKey: string;
  setTabActiveKey: (values: any) => void;
  tabSubmitterList: [];
  cardSubmitterList: [];
  tabularDataCreator?: (parentId: string, multirecordAttribute: string, data: any) => Promise<boolean | void>;
  tabularDataUpdater?: (parentId: string, multirecordAttribute: string, multirecordId: number, data: any) => Promise<boolean | void>;
  tabularDataDeleter?: (parentId: string, multirecordAttribute: string, multirecordId: number) => Promise<boolean | void>;
  hasViewPermission: (fieldName: string) => boolean;
  hasWritePermission: (fieldName: string) => boolean;
  model: ModelType;
  permission: any;
  recentlyChangedValue: any;
  defaultActiveKey?: string;
  scope?: string;
  refreshMasterData?: () => Promise<void>;
};

const DynamicFieldSet: React.FC<DynamicFieldSetProps> = (props) => {
  const intl = useIntl();
  const { confirm } = Modal;
  const access = useAccess();
  const { hasPermitted } = access;

  useEffect(() => {
    if (props.model.frontEndDefinition
      && props.model.frontEndDefinition.topLevelComponent === 'tab') {
        // const primaryTabKey = props.model.frontEndDefinition.structure?.[0]?.key;
        // props.setTabActiveKey(props.defaultActiveKey ?? primaryTabKey);

        let permittedTabs = [];
        props.model.frontEndDefinition.structure.map((tab: any) => {
          if (hasViewPermissionByKey(tab) || hasTabViewPermission(tab.content)) {
            permittedTabs.push(tab.key);
          }
        });
        if (props.defaultActiveKey) {
          props.setTabActiveKey(props.defaultActiveKey);
        } else {
          props.setTabActiveKey(permittedTabs[0] ?? permittedTabs[0]);
        }

    }
  }, [props.model])

  const tabErrorBadgeVisibility = (tabContent: Array<any>) => {
    const fieldsError = props.form.getFieldsError() ?? [];
    const errors = fieldsError
      .filter((error) => {
        return !_.isEmpty(error.errors);
      }).map((error) => {
        return error.name[0];
      });

    let hasError = false;
    tabContent.forEach((content: {content: Array<string>}) => {
      const error = content.content.filter(field => errors.includes(field));
      if (!_.isEmpty(error)) {
        hasError = true;
        return;
      }

      const otherError = content.content.filter(field => Object.keys(props.errors).includes(field));
      if (!_.isEmpty(otherError)) {
        hasError = true;
        return;
      }
    });

    return hasError;
  }

  const renderTabBar = (props: any, DefaultTabBar: any) => (
    <Sticky bottomOffset={80} topOffset={-48}>
      {({ style }) => {
        if (style.position == 'fixed') {
          style.left = 'auto';
          style.top = 48;
          style.zIndex = 3;
          style.background = 'white';
        }

        return <DefaultTabBar {...props} className="site-custom-tab-bar" style={{ ...style }} />
      }}
    </Sticky>
  );

  const tabsOnChange = (key: string) => {
    if (props.model.frontEndDefinition && props.model.frontEndDefinition.topLevelComponent === 'tab') {
      const structure = props?.model?.frontEndDefinition?.structure.find(tab => tab.key === props.tabActiveKey);

      let hasChange = false;
      structure.content.map((card: any) => {
        card.content.map((field: string) => {
          if (props.initialValues[field] != props.values[field]) {
            hasChange = true;
            return;
          }
        })
      });

      if (hasChange) {
        confirm({
          title: 'Unsaved Changes',
          icon: <ExclamationCircleOutlined />,
          content: 'This tab contains unsaved changes. Do you still wish to leave the tab?',
          onOk() {
            props.setValues(props.initialValues);
            props.form.setFieldsValue(props.initialValues);
            props.setTabActiveKey(key);
          }
        });
        return;
      }
    }

    props.setTabActiveKey(key);
  }

  const showCardSubmitter = (tabKey: string, cardKey: string): boolean => {
    let key:string = tabKey + '.' + cardKey;
    return props.cardSubmitterList.includes(key);
  }

  const saveCardFields = (tabKey: string, cardKey: string) => {
    if (props.model.frontEndDefinition
      && props.model.frontEndDefinition.topLevelComponent === 'tab') {
        const card = props.model.frontEndDefinition
          .structure.find(tab => tab.key === tabKey)
          .content.find(card => card.key === cardKey);

        let _values = {'id': props.values['id']};
        card?.content.map(field => {
          const type = props.model.modelDataDefinition?.fields[field]?.type;
          const relation = props.model.modelDataDefinition?.relations[field];

          if (type == 'model' && relation == 'HAS_ONE') {
            field = field.concat('Id');
          }

          _values[field] = props.values[field]
        });

        props.formSubmit(_values);
    }
  }

  const resetCardFields = (tabKey: string, cardKey: string) => {
    if (props.model.frontEndDefinition
      && props.model.frontEndDefinition.topLevelComponent === 'tab') {
        const card = props.model.frontEndDefinition
          .structure.find(tab => tab.key === tabKey)
          .content.find(card => card.key === cardKey);

        let _values = {...props.values};
        card?.content.map((field:any) => {
          const type = props.model.modelDataDefinition?.fields[field]?.type;
          if (type == 'model') {
            field = field+'Id';
          }
          _values[field] = props.initialValues[field]
        });

        props.setValues(_values);
        props.form.setFieldsValue(_values);
    }
  }

  const hasTabViewPermission = (tabContent: Array<string>) => {
    let fields = [];
    tabContent.map(card => {
      card.content.map(field => fields.push(field));
    });

    return !_.isEmpty(fields.filter(field => props.hasViewPermission(field)));
  }

  const hasCardViewPermission = (fields: Array<string>) => {
    return !_.isEmpty(fields.filter(field => props.hasViewPermission(field)));
  }

  const hasViewPermissionByKey = (data: any) => {

    if ( Array.isArray(data.viewPermission) ) {
      let fields = [];
      data.viewPermission.forEach((permission : string) =>{
        fields.push((permission && hasPermitted(permission)))
      });
      let hasViewPermission = fields.includes(true);
      return hasViewPermission;
    }
    return (data.viewPermission && hasPermitted(data.viewPermission))
      || (data.editablePermission && hasPermitted(data.editablePermission))
  }

  const hasEditPermissionByKey = (data: any) => {
    if ( Array.isArray(data.editablePermission) ) {
      let fields = [];
      data.editablePermission.forEach((permission : string) =>{
        fields.push((permission && hasPermitted(permission)))
      });
      let hasEditPermission = fields.includes(true);
      return hasEditPermission;
    }
    return (data.editablePermission && hasPermitted(data.editablePermission))
  }

  if (props.model.frontEndDefinition
    && props.model.frontEndDefinition.topLevelComponent === 'section') {
      return props.model.frontEndDefinition.structure.map(section =>
        hasViewPermissionByKey(section) || hasCardViewPermission(section.content) ?
        <Card
          data-key={section.key}
          key={section.key}
          title={intl.formatMessage({
            id: section.labelKey,
            defaultMessage: section.defaultLabel
          })}
          style={{ marginTop: 16 }}
        >
          <Row gutter={{ xs: 8, sm: 16, md: 24, lg: 32 }}>
            {section.content.map(inputField =>
              hasViewPermissionByKey(section) || props.hasViewPermission(inputField)
              ? <FormInput
                key={inputField}
                fieldNamePrefix={props.fieldNamePrefix}
                fieldName={inputField}
                model={props.model}
                readOnly={!hasEditPermissionByKey(section) && !props.hasWritePermission(inputField)}
                permission={props.permission}
                form={props.form}
                values={props.values}
                setValues={props.setValues}
                errors={props.errors}
                setErrors={props.setErrors}
                formSubmit={props.formSubmit}
                tabularDataCreator={props.tabularDataCreator}
                tabularDataUpdater={props.tabularDataUpdater}
                tabularDataDeleter={props.tabularDataDeleter}
                recentlyChangedValue={props.recentlyChangedValue}
                scope={props.scope}
                refreshMasterData={props.refreshMasterData}
              />
              : <></>
            )}
          </Row>
        </Card> : <></>);
  } else if (props.model.frontEndDefinition) {
    return (
      <StickyContainer>
        <Tabs renderTabBar={renderTabBar} defaultActiveKey={props.defaultActiveKey} activeKey={props.tabActiveKey} onChange={tabsOnChange}>
          {props.model.frontEndDefinition.structure.map(tab =>
            hasViewPermissionByKey(tab) || hasTabViewPermission(tab.content) ?
            <TabPane
              className={`${tab.key}-tab`}
              forceRender={true}
              key={tab.key}
              tab={<Badge dot={tabErrorBadgeVisibility(tab.content)}>{tab.defaultLabel}</Badge>}
            >
              {tab.content.map(child =>
                typeof child === 'object' ?
                  hasViewPermissionByKey(tab) || hasViewPermissionByKey(child) || hasCardViewPermission(child.content)
                    ? <Card
                      data-key={child.key}
                      key={child.key}
                      title={intl.formatMessage({
                        id: child.labelKey,
                        defaultMessage: child.defaultLabel
                      })}
                      style={{ marginTop: 16 }}
                    >
                      <Row gutter={{ xs: 8, sm: 16, md: 24, lg: 32 }}>
                        {child.content.map(inputField =>
                          hasViewPermissionByKey(tab) || hasViewPermissionByKey(child) || props.hasViewPermission(inputField)
                          ?
                           <FormInput
                            key={inputField}
                            fieldNamePrefix={props.fieldNamePrefix}
                            fieldName={inputField}
                            model={props.model}
                            readOnly={!hasEditPermissionByKey(tab) && !hasEditPermissionByKey(child) && !props.hasWritePermission(inputField)}
                            permission={props.permission}
                            form={props.form}
                            values={props.values}
                            setValues={props.setValues}
                            errors={props.errors}
                            setErrors={props.setErrors}
                            formSubmit={props.formSubmit}
                            tabularDataCreator={props.tabularDataCreator}
                            tabularDataUpdater={props.tabularDataUpdater}
                            tabularDataDeleter={props.tabularDataDeleter}
                            recentlyChangedValue={props.recentlyChangedValue}
                            scope={props.scope}
                            refreshMasterData={props.refreshMasterData}
                          />
                          : <></>
                        )}
                      </Row>
                      { showCardSubmitter(tab.key, child.key)
                        ? <Space style={{float: 'right'}}>
                          <Button data-key={`${tab.key}.${child.key}.reset`} onClick={() => resetCardFields(tab.key, child.key)}>Reset</Button>
                          <Button data-key={`${tab.key}.${child.key}.save`} type='primary' onClick={() => saveCardFields(tab.key, child.key)}>Save</Button>
                        </Space>
                        : <></> }
                    </Card> : <></>
                  : props.hasViewPermission(child)
                    ? <FormInput
                      key={child}
                      fieldNamePrefix={props.fieldNamePrefix}
                      fieldName={child}
                      model={props.model}
                      readOnly={!props.hasWritePermission(child)}
                      permission={props.permission}
                      form={props.form}
                      values={props.values}
                      setValues={props.setValues}
                      errors={props.errors}
                      setErrors={props.setErrors}
                      formSubmit={props.formSubmit}
                      tabularDataCreator={props.tabularDataCreator}
                      tabularDataUpdater={props.tabularDataUpdater}
                      tabularDataDeleter={props.tabularDataDeleter}
                      recentlyChangedValue={props.recentlyChangedValue}
                      scope={props.scope}
                      refreshMasterData={props.refreshMasterData}
                    />
                    : <></>
              )}
            </TabPane> : <></>
          )}
        </Tabs>
      </StickyContainer>
    );
  } else if (props.model.modelDataDefinition
    && props.model.modelDataDefinition.fields) {
      const fieldSet:Array<any> = Object.values(props.model.modelDataDefinition.fields)
        .filter((field:any) => {
          if (field.isSystemValue) {
            return false;
          }
          return true;
        })
        .map(field => field.name);
      return (
        <Row gutter={{ xs: 8, sm: 16, md: 24, lg: 32 }}>
          {fieldSet.map(inputField =>
            props.hasViewPermission(inputField)
            ? <FormInput
              key={inputField}
              fieldNamePrefix={props.fieldNamePrefix}
              fieldName={inputField}
              model={props.model}
              readOnly={!props.hasWritePermission(inputField)}
              permission={props.permission}
              form={props.form}
              values={props.values}
              setValues={props.setValues}
              errors={props.errors}
              setErrors={props.setErrors}
              formSubmit={props.formSubmit}
              tabularDataCreator={props.tabularDataCreator}
              tabularDataUpdater={props.tabularDataUpdater}
              tabularDataDeleter={props.tabularDataDeleter}
              recentlyChangedValue={props.recentlyChangedValue}
              scope={props.scope}
              refreshMasterData={props.refreshMasterData}
            />
            : <></>
          )}
        </Row>
      );
  }
};

export default DynamicFieldSet;
