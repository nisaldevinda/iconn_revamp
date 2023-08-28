import React, { useEffect, useState } from 'react';
import { FooterToolbar, PageContainer } from '@ant-design/pro-layout';
import { Modal, Button, Card, message } from 'antd';
import { DrawerForm, ModalForm, ProFormDependency, ProFormSelect, ProFormText } from '@ant-design/pro-form';
import { CloseOutlined, ExclamationCircleFilled, ExclamationCircleOutlined } from '@ant-design/icons';
import { updateDynamicForm } from '@/services/dynamicForm';
import { useIntl } from 'react-intl';
import { DndProvider } from 'react-dnd';
import { HTML5Backend } from 'react-dnd-html5-backend';
import { FieldType } from './FieldType';
import { Backet } from './Backet';
import { StickyContainer, Sticky } from 'react-sticky';
import FieldTypes from './fieldTypes'
import _ from 'lodash';

const { confirm } = Modal;

interface SingleTabBuilderProps {
    modelName: any,
    model: any,
    layout: any,
    onFinish: (model: any, layout: any, otherAlternativeLayout?: any) => Promise<any>,
    otherAlternativeLayout?: []
}

const SingleTabBuilder: React.FC<SingleTabBuilderProps> = (props) => {
    const intl = useIntl();

    const [loading, setLoading] = useState(false);
    const [modelName, setModelName] = useState<String>();
    const [model, setModel] = useState({});
    const [layout, setLayout] = useState([]);
    const [otherAlternativeLayout, setOtherAlternativeLayout] = useState([]);

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

    const addSection = async (data: {
        key: string,
        title: string,
        addAfter?: string
    }) => {
        try {
            let newTabs = [...layout];

            const newSection = {
                key: _.camelCase(data.title),
                defaultLabel: data.title,
                labelKey: modelName?.concat('.').concat(_.camelCase(data.title)),
                content: [],
                closable: true,
            };

            if (data.addAfter) {
                const sectionIndex = newTabs.findIndex(section =>
                    typeof section === 'object'
                    && section != null
                    && section.key === data.addAfter
                );
                newTabs.splice(sectionIndex + 1, 0, newSection);
            } else {
                newTabs = [];
                newTabs.push(newSection);
            }

            setLayout(newTabs);
            setIsAddSectionModalVisible(false);
        } catch (error) {
            console.log(error);
        }
    };

    const removeSection = (sectionKey: string) => {
        let newTabs = [...layout];

        const sectionIndex = newTabs.findIndex(section =>
            typeof section === 'object'
            && section != null
            && section.key === sectionKey
        );

        if (sectionIndex < 0)
            return;

        newTabs.splice(sectionIndex, 1);

        setLayout(newTabs);
    };

    const addField = (sectionKey: string, fieldType: any) => {
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

        fields[fieldBlock.name] = fieldBlock;
        newModel.fields = fields;

        setModel(newModel);

        let newTabs = [...layout];

        const sectionIndex = newTabs.findIndex(section => section.key === sectionKey);
        if (sectionIndex < 0)
            return;

        if (_.isArray(newTabs[sectionIndex].content)) {
            newTabs[sectionIndex].content.push(fieldBlock.name);
        } else {
            newTabs[sectionIndex].content = [];
            newTabs[sectionIndex].content.push(fieldBlock.name);
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

    const removeField = (sectionKey: string, fieldKey: string) => {
        setLoading(true);

        let newModel = { ...model };
        let fields = { ...newModel.fields };
        delete fields[fieldKey];
        newModel.fields = fields;
        setModel(newModel);

        const newTabs = [...layout];

        const sectionIndex = newTabs.findIndex(section =>
            typeof section === 'object'
            && section != null
            && section.key === sectionKey
        );
        if (sectionIndex < 0)
            return;

        const fieldIndex = newTabs[sectionIndex].content.findIndex(field => field === fieldKey);
        if (fieldIndex < 0)
            return;

        newTabs[sectionIndex].content.splice(fieldIndex, 1);
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
                                <>
                                    {layout.map(section => (
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
                                                        removeSection(section.key);
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
                                                onDelete={(fieldName) => removeField(section.key, fieldName)}
                                                onChange={(fieldType) => fieldType.type == 'fieldType' && addField(section.key, fieldType.fieldType)}
                                            />
                                        </Card>
                                    ))}
                                    <Button type="dashed" block onClick={() => setIsAddSectionModalVisible(true)}>
                                        Add Section
                                    </Button>
                                </>
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
                {layout.length > 0 ? <ProFormSelect
                    name="addAfter"
                    label="Add new section after"
                    placeholder="Please select a section"
                    rules={[{ required: true, message: 'Please select a section!' }]}
                    request={async () => {
                        return layout.map((section) => {
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
                    const _otherAlternativeLayout = otherAlternativeLayout.map((tab: any) => {
                        if (tab.key === value.tabName) {
                            return {
                                ...tab,
                                content: tab.content.map((section: any) => {
                                    if (section.key === value.sectionName) {
                                        const content = [...section.content];
                                        content.push(recentlyAddedField);
                                        return {
                                            ...section,
                                            content
                                        };
                                    }
                                    return section;
                                })
                            };
                        }
                        return tab;
                    });

                    setOtherAlternativeLayout(_otherAlternativeLayout);
                    setIsAddOtherAlternativeModalVisible(false);
                }}
                onVisibleChange={setIsAddOtherAlternativeModalVisible}
                modalProps={{
                    destroyOnClose: true
                }}>
                <ProFormSelect
                    name="tabName"
                    label="Tab"
                    placeholder="Please select a tab"
                    rules={[{ required: true, message: 'Please select a tab!' }]}
                    options={otherAlternativeLayout.map((tab: any) => {
                        return {
                            value: tab.key,
                            label: tab.defaultLabel,
                        };
                    })}
                />
                <ProFormDependency name={['tabName']}>
                    {({ tabName }) => {
                        return tabName && <ProFormSelect
                            name="sectionName"
                            label="Section"
                            placeholder="Please select a section"
                            rules={[{ required: true, message: 'Please select a section!' }]}
                            options={otherAlternativeLayout.find((tab: any) => tab.key == tabName)
                                ?.content.map((section: any) => {
                                    return {
                                        value: section.key,
                                        label: section.defaultLabel,
                                    };
                                })}
                        />;
                    }}
                </ProFormDependency>
            </ModalForm>
        </PageContainer>
    );
};

export default SingleTabBuilder;
