import React, { useEffect, useState } from 'react';
import { FooterToolbar, PageContainer } from '@ant-design/pro-layout';
import { Tabs, Modal, Button, Card, message } from 'antd';
import { DrawerForm, ModalForm, ProFormSelect, ProFormText } from '@ant-design/pro-form';
import { CloseOutlined, ExclamationCircleFilled, ExclamationCircleOutlined } from '@ant-design/icons';
import { useIntl } from 'react-intl';
import { DndProvider } from 'react-dnd';
import { HTML5Backend } from 'react-dnd-html5-backend';
import { FieldType } from './FieldType';
import { Backet } from './Backet';
import { StickyContainer, Sticky } from 'react-sticky';
import FieldTypes from './fieldTypes'
import _ from 'lodash';

const { TabPane } = Tabs;
const { confirm } = Modal;

interface MultiTabsBuilderProps {
    modelName: any,
    model: any,
    layout: any,
    onFinish: (model: any, layout: any, otherAlternativeLayout?: any) => Promise<any>,
    otherAlternativeLayout?: []
}

const MultiTabsBuilder: React.FC<MultiTabsBuilderProps> = (props) => {
    const intl = useIntl();

    const [loading, setLoading] = useState(false);
    const [modelName, setModelName] = useState<String>();
    const [model, setModel] = useState({});
    const [layout, setLayout] = useState([]);
    const [otherAlternativeLayout, setOtherAlternativeLayout] = useState([]);

    const [activeKey, setActiveKey] = useState();
    const [isAddTabModalVisible, setIsAddTabModalVisible] = useState(false);
    const [isAddSectionModalVisible, setIsAddSectionModalVisible] = useState(false);
    const [isFieldConfigCardVisible, setIsFieldConfigCardVisible] = useState(false);
    const [activeConfigDrawerData, setActiveConfigDrawerData] = useState();
    const [activeConfigDrawerType, setActiveConfigDrawerType] = useState();
    const [isAddOtherAlternativeModalVisible, setIsAddOtherAlternativeModalVisible] = useState(false);
    const [recentlyAddedField, setRecentlyAddedField] = useState();

    const [updating, setUpdating] = useState(false);

    useEffect(() => {
        init();
    }, [])

    const init = async () => {
        setLoading(true);
        setModelName(props.modelName);
        setModel(props.model);
        setLayout(props.layout);
        setOtherAlternativeLayout(props.otherAlternativeLayout ?? []);
        setLoading(false);
    }

    const onTabAction = (targetKey: any, action: 'add' | 'remove') => {
        switch (action) {
            case 'add':
                setIsAddTabModalVisible(true);
                break;
            case 'remove':
                confirm({
                    title: 'Are you sure remove this tab?',
                    icon: <ExclamationCircleOutlined />,
                    okText: 'Remove',
                    okType: 'danger',
                    cancelText: 'No',
                    onOk() {
                        removeTab(targetKey);
                    }
                });
                break;
        }
    }

    const addTab = async (data: {
        key: string,
        title: string,
        addAfter?: string
    }) => {
        const tabKey = _.camelCase(data.title);
        const newTabs = [...layout];
        const newTab = {
            key: tabKey,
            defaultLabel: data.title,
            labelKey: modelName?.concat('.').concat(_.camelCase(data.title)),
            content: [],
            closable: true,
        };

        if (data.addAfter) {
            const afterItemIndex = newTabs.findIndex(tab => tab.key === data.addAfter);
            newTabs.splice(afterItemIndex + 1, 0, newTab);
        } else {
            newTabs.push(newTab)
        }

        setLayout(newTabs);
        setActiveKey(tabKey);
        setIsAddTabModalVisible(false);
    };

    const removeTab = (key: string) => {
        const newTabs = [...layout];
        const index = newTabs.findIndex(tab => tab.key === key);

        if (index !== -1) {
            newTabs.splice(index, 1);
        }

        setLayout(newTabs);
    };

    const addSection = async (data: {
        key: string,
        title: string,
        addAfter?: string
    }) => {
        const newTabs = [...layout];
        const tabIndex = newTabs.findIndex(tab => tab.key === activeKey);

        if (tabIndex < 0)
            return;

        const newSection = {
            key: _.camelCase(data.title),
            defaultLabel: data.title,
            labelKey: modelName?.concat('.').concat(_.camelCase(data.title)),
            content: [],
            closable: true,
        };

        if (data.addAfter) {
            const sectionIndex = newTabs[tabIndex].content.findIndex(section =>
                typeof section === 'object'
                && section != null
                && section.key === data.addAfter
            );
            newTabs[tabIndex].content.splice(sectionIndex + 1, 0, newSection);
        } else {
            newTabs[tabIndex].content = [];
            newTabs[tabIndex].content.push(newSection);
        }

        setLayout(newTabs);
        setIsAddSectionModalVisible(false);
    };

    const removeSection = (tabKey: string, sectionKey: string) => {
        const newTabs = [...layout];
        const tabIndex = newTabs.findIndex(tab => tab.key === tabKey);

        if (tabIndex < 0)
            return;

        const sectionIndex = newTabs[tabIndex].content.findIndex(section =>
            typeof section === 'object'
            && section != null
            && section.key === sectionKey
        );

        if (sectionIndex < 0)
            return;

        newTabs[tabIndex].content.splice(sectionIndex, 1);

        setLayout(newTabs);
    };

    const addField = (tabKey: string, sectionKey: string, fieldType: any) => {
        setLoading(true);

        let newModel = { ...model };
        let fields = { ...newModel.fields };
        let fieldBlock = { ...fieldType.config };

        const currentFieldNumber = model.fields
            ? _.max(Object.keys(model.fields)
                .filter(field => field.includes(fieldBlock.name))
                .map(field => parseInt(field.replace(fieldBlock.name, ''))))
            : null;

        fieldBlock.name = fieldBlock.name.concat(currentFieldNumber ? currentFieldNumber + 1 : 1);
        fieldBlock.defaultLabel = fieldBlock.defaultLabel.concat(currentFieldNumber ? currentFieldNumber + 1 : 1);
        fieldBlock.labelKey = modelName?.concat('.').concat(_.camelCase(fieldBlock.defaultLabel));

        if (fieldBlock.type == 'model') {
            let relations = { ...newModel.relations };
            relations[fieldBlock.name] = 'HAS_ONE';
        }

        fields[fieldBlock.name] = fieldBlock;
        newModel.fields = fields;

        setModel(newModel);
        console.log('fieldBlock >>> ', fieldBlock);

        const newTabs = [...layout];
        const tabIndex = newTabs.findIndex(tab => tab.key === tabKey);
        if (tabIndex < 0)
            return;

        const sectionIndex = newTabs[tabIndex].content.findIndex(section => section.key === sectionKey);
        if (sectionIndex < 0)
            return;

        if (_.isArray(newTabs[tabIndex].content[sectionIndex].content)) {
            newTabs[tabIndex].content[sectionIndex].content.push(fieldBlock.name);
        } else {
            newTabs[tabIndex].content[sectionIndex].content = [];
            newTabs[tabIndex].content[sectionIndex].content.push(fieldBlock.name);
        }

        setLayout(newTabs);

        if (!_.isEmpty(props.otherAlternativeLayout)) {
            setRecentlyAddedField(fieldBlock.name);
            confirm({
                icon: <ExclamationCircleFilled />,
                title: 'Do you Want to add this field to the Add From?',
                okText: 'Yes, Add',
                onOk() {
                    setIsAddOtherAlternativeModalVisible(true);
                },
                onCancel() {
                    setIsAddOtherAlternativeModalVisible(false);
                },
            });
        }

        setLoading(false);
    }

    const updateField = (values) => {
        let updatedData = { ...activeConfigDrawerData };

        const fieldTypeConfig = FieldTypes[updatedData.type].config;
        const ignoredConfig = ['name', 'type', 'labelKey', 'values', 'validations'];
        Object.keys(fieldTypeConfig).forEach(property => {
            if (ignoredConfig.includes(property))
                return;

            updatedData[property] = values[property];
        });

        Object.keys(fieldTypeConfig.validations).forEach(validation => {
            updatedData.validations[validation] = values.validations[validation];
        });

        updatedData.labelKey = modelName?.concat('.').concat(_.camelCase(updatedData.defaultLabel));

        console.log('updatedData', updatedData, values);

        if (updatedData.hasOwnProperty('values')) {
            let updatedValues = values.values.map(value => {
                if (!value.value)
                    value.value = 'option_'.concat(new Date().getTime().toString());

                value.labelKey = updatedData.labelKey.concat('.').concat(_.camelCase(value.defaultLabel));
                return value;
            });

            updatedData['values'] = updatedValues;
        }

        let _model = { ...model };
        _model.fields[updatedData.name] = updatedData;

        setModel(_model);
        return true;
    }

    const removeField = (tabKey: string, sectionKey: string, fieldKey: string) => {
        setLoading(true);

        let newModel = { ...model };
        let fields = { ...newModel.fields };
        delete fields[fieldKey];
        newModel.fields = fields;
        setModel(newModel);

        const newTabs = [...layout];
        const tabIndex = newTabs.findIndex(tab => tab.key === tabKey);
        if (tabIndex < 0)
            return;

        const sectionIndex = newTabs[tabIndex].content.findIndex(section =>
            typeof section === 'object'
            && section != null
            && section.key === sectionKey
        );
        if (sectionIndex < 0)
            return;

        const fieldIndex = newTabs[tabIndex].content[sectionIndex].content.findIndex(field => field === fieldKey);
        if (fieldIndex < 0)
            return;

        newTabs[tabIndex].content[sectionIndex].content.splice(fieldIndex, 1);
        setLayout(newTabs);

        setLoading(false);
    }

    const onUpdate = async () => {
        setUpdating(true);
        await props.onFinish(model, layout, otherAlternativeLayout);
        setUpdating(false);
    }

    return (
        <PageContainer loading={loading}>
            {model &&
                <DndProvider backend={HTML5Backend}>
                    <StickyContainer>
                        <div style={{ display: 'grid', gridTemplateColumns: '300px calc(100% - 300px)' }}>
                            <Sticky topOffset={-56}>
                                {({ style }) => {
                                    if (style.position == 'fixed') style.top = 56;
                                    let cardBodyHieght = style.position != 'fixed' ? 'calc(100vh - 296px)' : 'calc(100vh - 164px)';
                                    return <div style={{ ...style }}>
                                        <Card
                                            title="Add Field"
                                            bordered={false}
                                            style={{ marginRight: 24 }}
                                            bodyStyle={{ height: cardBodyHieght, overflowY: 'scroll' }}
                                        >
                                            {Object.values(FieldTypes).map(fieldType =>
                                                <FieldType draggable fieldType={fieldType} />
                                            )}
                                        </Card>
                                    </div>
                                }}
                            </Sticky>
                            <div>
                                <Card>
                                    <Tabs
                                        type="editable-card"
                                        onChange={setActiveKey}
                                        activeKey={activeKey}
                                        onEdit={onTabAction}
                                    >
                                        {layout.map(tab => (
                                            <TabPane
                                                tab={intl.formatMessage({
                                                    id: tab.labelKey,
                                                    defaultMessage: tab.defaultLabel,
                                                })}
                                                key={tab.key}
                                                closable={tab.closable}
                                            >
                                                {tab.content.map(section => (
                                                    <Card
                                                        key={section.key}
                                                        title={intl.formatMessage({
                                                            id: section.labelKey,
                                                            defaultMessage: section.defaultLabel,
                                                        })}
                                                        style={{ marginBottom: 24 }}
                                                        extra={[<a onClick={() => {
                                                            confirm({
                                                                title: 'Are you sure remove this section?',
                                                                icon: <ExclamationCircleOutlined />,
                                                                okText: 'Remove',
                                                                okType: 'danger',
                                                                cancelText: 'No',
                                                                onOk() {
                                                                    removeSection(tab.key, section.key);
                                                                }
                                                            });
                                                        }}><CloseOutlined /></a>]}
                                                    >
                                                        <Backet
                                                            model={model}
                                                            fields={section.content}
                                                            onClick={(fieldConfig) => {
                                                                const type = fieldConfig.type;
                                                                setIsFieldConfigCardVisible(true);
                                                                setActiveConfigDrawerData(fieldConfig);
                                                                setActiveConfigDrawerType(FieldTypes[type])
                                                            }}
                                                            onDelete={(fieldName) => removeField(tab.key, section.key, fieldName)}
                                                            onChange={(fieldType) => fieldType.type == 'fieldType' && addField(tab.key, section.key, fieldType.fieldType)}
                                                        />
                                                    </Card>
                                                ))}
                                                <Button type="dashed" block onClick={() => setIsAddSectionModalVisible(true)}>
                                                    Add Section
                                                </Button>
                                            </TabPane>
                                        ))}
                                    </Tabs>
                                </Card>
                            </div>
                        </div>
                    </StickyContainer>

                    <FooterToolbar>
                        <Button
                            type="primary"
                            key="submit"
                            loading={updating}
                            onClick={onUpdate}
                        >
                            {
                                props.submitbuttonLabel ?? intl.formatMessage({
                                    id: 'UPDATE',
                                    defaultMessage: 'Update'
                                })
                            }
                        </Button>
                    </FooterToolbar>
                </DndProvider>
            }
            <DrawerForm
                title="Field Configuration"
                visible={isFieldConfigCardVisible}
                onVisibleChange={setIsFieldConfigCardVisible}
                initialValues={activeConfigDrawerData}
                drawerProps={{
                    destroyOnClose: true,
                }}
                submitter={{
                    searchConfig: {
                        submitText: intl.formatMessage({
                            id: 'change',
                            defaultMessage: 'Change',
                        }),
                        resetText: intl.formatMessage({
                            id: 'cancel',
                            defaultMessage: 'Cancel',
                        }),
                    },
                }}
                width="40vw"
                onFinish={async (values) => updateField(values)}
            >
                {activeConfigDrawerType && activeConfigDrawerType.configForm}
            </DrawerForm>

            <ModalForm
                title="Add Tab"
                visible={isAddTabModalVisible}
                onFinish={addTab}
                onVisibleChange={setIsAddTabModalVisible}
                modalProps={{
                    destroyOnClose: true
                }}>
                <ProFormText
                    name="title"
                    label="Tab title"
                    rules={[
                        { required: true, message: 'Please enter new tab title!' },
                        () => ({
                            validator(rule, value) {
                                if (value && layout.some(tab => tab.title === value)) {
                                    return Promise.reject(
                                        new Error(
                                            'Tab title must be unique.',
                                        ),
                                    );
                                }
                                return Promise.resolve();
                            },
                        }),
                    ]}
                />
                {layout.length > 0 ? <ProFormSelect
                    name="addAfter"
                    label="Add new tab after"
                    placeholder="Please select a tab"
                    rules={[{ required: true, message: 'Please select a tab!' }]}
                    request={async () => layout.map((tab) => {
                        return {
                            value: tab.key,
                            label: tab.defaultLabel,
                        };
                    })}
                /> : <></>}
            </ModalForm>

            <ModalForm
                title="Add Section"
                visible={isAddSectionModalVisible}
                onFinish={addSection}
                onVisibleChange={setIsAddSectionModalVisible}
                modalProps={{
                    destroyOnClose: true
                }}>
                <ProFormText
                    name="title"
                    label="Section title"
                    rules={[
                        { required: true, message: 'Please enter new section title!' },
                        () => ({
                            validator(rule, value) {
                                if (value && layout.some(tab => tab.title === value)) {
                                    return Promise.reject(
                                        new Error(
                                            'Section title must be unique.',
                                        ),
                                    );
                                }
                                return Promise.resolve();
                            },
                        }),
                    ]}
                />
                {layout.find(tab => tab.key == activeKey)?.content.length > 0 ? <ProFormSelect
                    name="addAfter"
                    label="Add new section after"
                    placeholder="Please select a section"
                    rules={[{ required: true, message: 'Please select a section!' }]}
                    request={async () => {
                        let tab = layout.find((tab) => {
                            return tab.key == activeKey
                        })
                        let tabContent = tab ? tab.content : [];

                        return tabContent.map((section) => {
                            return {
                                value: section.key,
                                label: section.defaultLabel,
                            };
                        })
                    }}
                /> : <></>}
            </ModalForm>

            <ModalForm
                title="Select the section you want to add this field in Add Form"
                visible={isAddOtherAlternativeModalVisible}
                onFinish={async (value: any) => {
                    const _otherAlternativeLayout = otherAlternativeLayout.map((section: any) => {
                        if (section.key === value.sectionName) {
                            const content = [...section.content];
                            content.push(recentlyAddedField);
                            return {
                                ...section,
                                content
                            };
                        }
                        return section;
                    });

                    setOtherAlternativeLayout(_otherAlternativeLayout);
                    setIsAddOtherAlternativeModalVisible(false);
                }}
                onVisibleChange={setIsAddOtherAlternativeModalVisible}
                modalProps={{
                    destroyOnClose: true
                }}>
                <ProFormSelect
                    name="sectionName"
                    label="Section"
                    placeholder="Please select a section"
                    rules={[{ required: true, message: 'Please select a section!' }]}
                    options={otherAlternativeLayout.map((section: any) => {
                        return {
                            value: section.key,
                            label: section.defaultLabel,
                        };
                    })}
                />
            </ModalForm>
        </PageContainer>
    );
};

export default MultiTabsBuilder;
