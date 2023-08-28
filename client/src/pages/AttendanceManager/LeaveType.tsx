import { getCountries } from '@/services/countryService';
import { addLeaveType, deleteLeaveType, editLeaveType, getLeaveTypes } from '@/services/leave';
import { DeleteOutlined, PlusOutlined, UpOutlined } from '@ant-design/icons';
import { PageContainer } from '@ant-design/pro-layout';
import ProTable, { ActionType } from '@ant-design/pro-table';
import { Button, Form, message, Modal, Popconfirm, Space, Tooltip } from 'antd';
import { country } from 'currency-codes';
import React, { useEffect, useRef, useState } from 'react';
import { Access, FormattedMessage, useAccess, useIntl ,history} from 'umi';
import PermissionDeniedPage from '../403';
import { ReactComponent as LeavetypeSettings } from '../../assets/attendance/leaveTypeSettings.svg';
import { ReactComponent as LeaveTypeEdit } from '../../assets/attendance/leaveTypeEdit.svg';
import { ReactComponent as Delete } from '../../assets/attendance/delete.svg';
import { APIResponse } from '@/utils/request';
import { DrawerForm, ModalForm, ProFormSelect, ProFormText, ProFormTextArea } from '@ant-design/pro-form';
import _ from 'lodash';



const LeaveType: React.FC = (props) => {

    const access = useAccess();
    const { hasPermitted } = access;
    const [countryEnum, setCountryEnum] = useState()
    const [addModalVisible, setAddModalVisible] = useState(false)
    const [drawerVisible, setDrawerVisible] = useState(false)
    const [formInitialValues,setFormInitialValues]=useState({})
    const [options,setOptions]=useState([])
    const [currentRecordId,setCurrentRecordId]=useState()
    const intl = useIntl();
    const actionRef = useRef<ActionType>();
    const [modalForm] = Form.useForm();
    const [drawerForm] = Form.useForm();
    const [searchText, setSearchText] = useState('');

    useEffect(() => {
        fetchCountries()

    }, [])

    const fetchCountries = async () => {
        try {
            const enumArr = {}
            const optionsArr=[]
            const countries = await getCountries();
            if (countries.data) {
                countries.data.forEach((country) => {
                    enumArr[country.id] = {
                        text: country.name
                    }
                    optionsArr.push({
                        value:country.id,
                        label:country.name

                    })
                })
            }
            setCountryEnum(enumArr)
            setOptions(optionsArr)
        }
        catch (e) {
            console.error(e)
        }

    }

    const formOnFinish = async (data, type) => {
        try {
            if (type === "add") {
                const request = await  addLeaveType(data);
                message.success(request.message);
                setAddModalVisible(false)
               
            }
            else if(type==="update"){
                const request = await editLeaveType(data);
                message.success(request.message);
                setDrawerVisible(false)

            }
            actionRef.current?.reload()
        }
        catch (error) {
            if (!_.isEmpty(error.data) && _.isObject(error.data)) {
                for (const fieldName in error.data) {
                    if(addModalVisible){
                        modalForm.setFields([
                            {
                              name: fieldName,
                              errors: error.data[fieldName] 
                            }
                          ]);
                    }
                    if(drawerVisible){
                        drawerForm.setFields([
                            {
                              name: fieldName,
                              errors: error.data[fieldName] 
                            }
                          ]);
                    }

                }
              }
        }

    }

    const formFields = () => {
        return <>
            <ProFormText
                name="name"
                label= {intl.formatMessage({
                    id: 'leaveType.name',
                    defaultMessage: 'Leave Type',
                })}
                width="md"
                rules={[
                    {
                        required: true,
                        message: (
                            <FormattedMessage
                                id="leaveType.required"
                                defaultMessage="Required"
                            />
                        ),
                    },
                    { 
                        max: 100, 
                        message: (
                          <FormattedMessage
                            id="leaveType.max"
                            defaultMessage="Maximum length is 100 characters"
                          />
                        )
                    }
                ]}
            />
            <ProFormSelect
                name="applicableCountryId"
                label={intl.formatMessage({
                    id: 'leaveType.ApplicableCountry',
                    defaultMessage: 'Applicable Country',
                })}
                width="md"
                options={options}
                showSearch
                rules={[
                    {
                        required: true,
                        message: (
                            <FormattedMessage
                                id="applicableCountry.required"
                                defaultMessage="Required"
                            />
                        ),
                    },
                ]}
            />
            <ProFormTextArea
                name="leaveTypeComment"
                label={intl.formatMessage({
                    id: 'leaveType.comment',
                    defaultMessage: 'Comment',
                })}
                width="lg"
                rules={[
                    { 
                        max: 250, 
                        message: (
                          <FormattedMessage
                            id="comment.max"
                            defaultMessage="Maximum length is 250 characters"
                          />
                        )
                    }
                ]}
            />
        </>
    }
    const handleSearch = () => {
        return {
          className: 'basic-container-search',
          placeholder:`${intl.formatMessage({
            id: 'search',
            defaultMessage: 'Search by Leave Type Name',
        })}`,
          onChange: (value: any) => {
            setSearchText(value.target.value);
            if (_.isEmpty(value.target.value)) {
                actionRef.current?.reset();
                actionRef.current?.reload();
            }
          },
          value:searchText
        };
    };
    const columns = [

        {
            title: 'Leave Type',
            dataIndex: 'name',
        },
        {
            title: 'Applicable Country',
            dataIndex: 'applicableCountryId',
            valueEnum: countryEnum
        },
        {
            title: 'Actions',
            dataIndex: 'actions',
            render: (text, record, index) => {

                return <>

                    <Space>
                        <Tooltip
                            placement={'bottom'}
                            key="config"
                            title={intl.formatMessage({
                                id: 'config',
                                defaultMessage: 'Config',
                            })}
                        >
                            <a onClick={()=>{
                                      history.push(`/settings/leave-types/config/${record?.id}`);

                            }}><LeavetypeSettings height={16} /></a>
                        </Tooltip>
                        <Tooltip
                            placement={'bottom'}
                            key="editrecord"
                            title={intl.formatMessage({
                                id: 'edit',
                                defaultMessage: 'Edit',
                            })}
                        >
                            <a onClick={() => {
                                setCurrentRecordId(record.id)
                                setFormInitialValues({
                                    id:record.Id,
                                    name:record.name,
                                    applicableCountryId:record.applicableCountryId,
                                    leaveTypeComment:record.leaveTypeComment

                                })
                                setDrawerVisible(true)}}><LeaveTypeEdit height={16} /></a>
                        </Tooltip>
                        <div onClick={(e) => e.stopPropagation()}>
                            <Popconfirm
                                key="deleteRecordConfirm"
                                title={intl.formatMessage({
                                    id: 'are_you_sure',
                                    defaultMessage: 'Are you sure?',
                                })}
                                onConfirm={async () => {
                                    const key = 'deleting';
                                    message.loading({
                                        content: intl.formatMessage({
                                            id: 'deleting',
                                            defaultMessage: 'Deleting...',
                                        }),
                                        key,
                                    });
                                    deleteLeaveType(record.id)
                                        .then((response: APIResponse) => {
                                            if (response.error) {
                                                message.error({
                                                    content:
                                                        response.message ??
                                                        intl.formatMessage({
                                                            id: 'failedToDelete',
                                                            defaultMessage: 'Failed to delete',
                                                        }),
                                                    key,
                                                });
                                                return;
                                            }

                                            message.success({
                                                content:
                                                    response.message ??
                                                    intl.formatMessage({
                                                        id: 'successfullyDeleted',
                                                        defaultMessage: 'Successfully deleted',
                                                    }),
                                                key,
                                            });

                                            actionRef?.current?.reload();
                                        })

                                        .catch((error: APIResponse) => {
                                            message.error({
                                              content:
                                                error.message ?
                                                <>
                                                    {error.message}
                                                </>
                                                : intl.formatMessage({
                                                    id: 'failedToDelete',
                                                    defaultMessage: 'Failed to delete',
                                                }),
                                              key,
                                            });
                                        });
                                }}
                                okText="Yes"
                                cancelText="No"
                            >
                                <Tooltip
                                    placement={'bottom'}
                                    key="deleteRecordTooltip"
                                    title={intl.formatMessage({
                                        id: 'delete',
                                        defaultMessage: 'Delete',
                                    })}
                                >
                                    <a key="deleteRecordButton">
                                        <Delete height={16} />
                                    </a>
                                </Tooltip>
                            </Popconfirm>
                        </div>



                    </Space></>
            }
        },

    ]
    return (
        <>
             <Access
                accessible={hasPermitted('leave-type-config')}
                fallback={<PermissionDeniedPage />}
            >
                <div>
                    <PageContainer
                        extra={[
                            <Button
                                onClick={(e) => {
                                    setAddModalVisible(true)
                                }}
                                style={{
                                    background: '#86C129',
                                    border: '1px solid #7DC014',
                                    color: '#FFFFFF',
                                }}
                            >
                                {' '}
                                <PlusOutlined /> Add Leave Type
                            </Button>,
                        ]}
                    >
                        <ProTable
                            actionRef={actionRef}
                            search={false}
                            toolbar={{
                                search: handleSearch(),
                            }}
                            options={{
                                search: true,
                                reload:  () => {
                                    actionRef.current?.reset();
                                    actionRef.current?.reload();
                                    setSearchText("");
                                  }
                            }}
                            columns={columns}
                            request={async (params, filter) => {
                                let sorter = { name: 'name', order: 'ASC' };
                                const response = await getLeaveTypes({ ...params ,searchText ,sorter});

                                return {
                                    data: response.data.data,
                                    success: true,
                                    total: response.data.total
                                }
                            }}
                        />
                    </PageContainer>


                    <ModalForm
                        width={550}
                        title={intl.formatMessage({
                            id: 'add.leaveType',
                            defaultMessage: 'Add Leave Type',
                        })}
                        modalProps={{
                            destroyOnClose: true,
                            onCancel: () => setAddModalVisible(false),
                        }}
                        form={modalForm}
                        visible={addModalVisible}
                        onFinish={async (values,props) => {
                            await formOnFinish({
                                name:values.name,
                                applicableCountryId:values.applicableCountryId,
                                leavePeriod:"STANDARD",
                                leaveTypeComment:values.leaveTypeComment
                            },"add")
                        }
                      
                    }
                        submitter={{
                            render: (props, defaultDoms) => {
                                return [

                                    <Button
                                        key="Reset"
                                        onClick={() => {
                                            
                                            setAddModalVisible(false)
                                        }}
                                    >
                                        Cancel
                                    </Button>,

                                    <Button
                                        key="ok"
                                        onClick={() => {
                                            props.submit();
                                        }}
                                        type={"primary"}
                                    >
                                        Save
                                    </Button>,
                                ];
                            },
                        }}
                    >
                        <div
                            style={{ paddingLeft: 16 }}
                        >
                            {formFields()}
                        </div>

                    </ModalForm>
                    <DrawerForm
                        width={550}
                        title={intl.formatMessage({
                            id: 'edit.leaveType',
                            defaultMessage: 'Edit Leave Type',
                        })}
                        onVisibleChange={setDrawerVisible}
                        form={drawerForm}
                        drawerProps={{
                            destroyOnClose: true,
                        }}
                        visible={drawerVisible}
                        onFinish={async (values) => {
                            
                           await formOnFinish({
                               id:currentRecordId,
                               name:values.name,
                               applicableCountryId:values.applicableCountryId,
                               leaveTypeComment:values.leaveTypeComment
                           },"update")
                        }}
                        initialValues={formInitialValues}
                        submitter={{
                            render: (props, defaultDoms) => {
                                return [

                                    <Button
                                        key="Reset"
                                        onClick={() => {
                                            
                                            setDrawerVisible(false)
                                        }}
                                    >
                                        Cancel
                                    </Button>,

                                    <Button
                                        key="ok"
                                        onClick={() => {
                                            props.submit();
                                        }}
                                        type={"primary"}
                                    >
                                        Update
                                    </Button>,
                                ];
                            },
                        }}
                    >
                        {formFields()}
                    </DrawerForm>
                </div>
            </Access>

        </>
    );
}

export default LeaveType;