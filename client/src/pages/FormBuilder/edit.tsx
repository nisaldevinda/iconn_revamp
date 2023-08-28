import React, { useEffect, useState } from 'react';
import { useIntl, useParams } from 'umi';
import { getModel, updateAlternativeLayout, updateDynamicForm } from '@/services/dynamicForm';
import { Spin, message } from 'antd';
import MultiTabsBuilder from './MultiTabsBuilder';
import SingleTabBuilder from './SingleTabBuilder';
import _ from 'lodash';

const FormBuilderEdit: React.FC<any> = (props) => {
    const intl = useIntl();

    const [loading, setLoading] = useState(false);
    const [modelName, setModelName] = useState<String>();
    const [numberOfTabs, setNumberOfTabs] = useState<'single' | 'multi'>('single');
    const [model, setModel] = useState({});
    const [layout, setLayout] = useState([]);
    const [otherAlternativeLayout, setOtherAlternativeLayout] = useState();
    const [otherAlternativeLayoutStructure, setOtherAlternativeLayoutStructure] = useState();

    const { modelPath } = useParams<{ modelPath: string }>();

    useEffect(() => {
        init();
    }, [])

    const init = async () => {
        setLoading(true);
        const [_modelName, _alternative] = modelPath.split('/');
        const modelRes = await getModel(_modelName, _alternative);
        if (_alternative) {
            //TODO: hard-coded because of our system has only one model which have alternative
            const _otherAlternative = _alternative == 'edit' ? 'add' : 'edit';
            const alternativeModelRes = await getModel(_modelName, _otherAlternative);
            setOtherAlternativeLayout(alternativeModelRes?.data?.frontEndDefinition ?? {});
            setOtherAlternativeLayoutStructure(alternativeModelRes?.data?.frontEndDefinition?.structure ?? []);
        }
        let _model = modelRes?.data?.modelDataDefinition ?? {};
        const _layout = modelRes?.data?.frontEndDefinition?.structure ?? [];
        const _numberOfTabs = modelRes?.data?.frontEndDefinition?.topLevelComponent == 'section' ? 'single' : 'multi';

        for (var key in _model?.fields) {
            if (_model?.fields.hasOwnProperty(key) && _model?.fields[key]?.type == 'model') {
                if (_model?.relations[key] == 'HAS_ONE') {
                    _model.fields[key].type = 'modelHasOne';
                } else if (_model?.relations[key] == 'HAS_MANY') {
                    _model.fields[key].type = 'modelHasMany';
                }
            }
        }

        setModel(_model);
        setModelName(_modelName);
        setNumberOfTabs(_numberOfTabs);
        setLayout(_layout);
        setLoading(false);
    }

    const onUpdate = async (model: any, layout: any, otherAlternativeLayoutStructure?: any) => {
        const messageKey = 'updating';
        message.loading({
            content: intl.formatMessage({
                id: 'pages.formbuilder.updating',
                defaultMessage: 'Updating...',
            }),
            key: messageKey,
        });

        let _model = { ...model };
        for (var key in _model?.fields) {
            if (_model?.fields.hasOwnProperty(key) && _model?.fields[key]?.type == 'modelHasOne') {
                _model.fields[key].type = 'model';
                _model.relations[key] = 'HAS_ONE'
            } else if (_model?.fields.hasOwnProperty(key) && _model?.fields[key]?.type == 'modelHasMany') {
                _model.fields[key].type = 'model';
                _model.relations[key] = 'HAS_MANY'
            }
        }

        await updateDynamicForm({ modelPath, model: _model, layout })
            .then(async (res) => {
                if (res.error) {
                    message.error({
                        content:
                            intl.formatMessage({
                                id: 'pages.formbuilder.updating.fail',
                                defaultMessage: 'Failed to update.',
                            }),
                        key: messageKey,
                    });
                } else {
                    if (otherAlternativeLayout && otherAlternativeLayoutStructure) {
                        console.log('otherAlternativeLayout', otherAlternativeLayout);
                        const path = `${otherAlternativeLayout.modelName}/${otherAlternativeLayout.alternative}`;
                        await updateAlternativeLayout(path, otherAlternativeLayoutStructure)
                            .then(async (res) => {
                                if (res.error) {
                                    message.error({
                                        content:
                                            intl.formatMessage({
                                                id: 'pages.formbuilder.updating.fail',
                                                defaultMessage: 'Failed to update.',
                                            }),
                                        key: messageKey,
                                    });
                                } else {
                                    message.success({
                                        content:
                                            intl.formatMessage({
                                                id: 'pages.formbuilder.updating.succ',
                                                defaultMessage: 'Successfully updated.',
                                            }),
                                        key: messageKey,
                                    });
                                }

                                return res;
                            })
                            .catch(error => {
                                message.error({
                                    content:
                                        intl.formatMessage({
                                            id: 'pages.formbuilder.updating.fail',
                                            defaultMessage: 'Failed to update.',
                                        }),
                                    key: messageKey,
                                });

                                return error;
                            });
                    } else {
                        message.success({
                            content:
                                intl.formatMessage({
                                    id: 'pages.formbuilder.updating.succ',
                                    defaultMessage: 'Successfully updated.',
                                }),
                            key: messageKey,
                        });
                    }
                }

                return res;
            })
            .catch(error => {
                message.error({
                    content:
                        intl.formatMessage({
                            id: 'pages.formbuilder.updating.fail',
                            defaultMessage: 'Failed to update.',
                        }),
                    key: messageKey,
                });

                return error;
            });
    }

    if (loading) {
        return <Spin />
    } else if (numberOfTabs == 'multi') {
        return <MultiTabsBuilder modelName={modelName} otherAlternativeLayout={otherAlternativeLayoutStructure} model={model} layout={layout} onFinish={onUpdate} />
    } else {
        return <SingleTabBuilder modelName={modelName} otherAlternativeLayout={otherAlternativeLayoutStructure} model={model} layout={layout} onFinish={onUpdate} />
    }
}

export default FormBuilderEdit;
