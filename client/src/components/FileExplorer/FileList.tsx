import React, { useEffect } from 'react';
import { getBase64 } from "@/utils/fileStore";
import { humanReadableFileSize } from "@/utils/utils";
import { DeleteOutlined, PlusOutlined } from "@ant-design/icons";
import ProForm, { ModalForm, DrawerForm, ProFormText, ProFormTextArea, ProFormUploadButton, ProFormRadio, ProFormSelect, ProFormDatePicker } from "@ant-design/pro-form";
import ProTable, { ProColumns } from "@ant-design/pro-table";
import { Button, message, Popconfirm, Space, Tooltip, Form, Col, Row, Transfer, Checkbox, Typography, Modal, Avatar } from "antd";
import { useRef, useState } from "react";
import { FormattedMessage, useIntl, useAccess, history } from "umi";
import File from '@/assets/FileExplorer/file.svg';
import Icon from "@ant-design/icons";
import { DocumentFileType, FileType } from ".";
import { getEmployeeList, getManagerList, getSubordinatesList } from '@/services/dropdown';
import { getAllLocations } from '@/services/location';
import { ReactComponent as WarningOutlined } from '../../assets/documents/warningOutlined.svg';
import { ReactComponent as LikeOutlined } from '../../assets/documents/acknowledged.svg';
import { ReactComponent as Pending } from '../../assets/documents/pendingAcknowledge.svg';
import { ReactComponent as DownloadOutlined } from '../../assets/documents/download.svg';
import { EyeOutlined } from '@ant-design/icons';

import { ReactComponent as Edit } from '../../assets/attendance/leaveTypeEdit.svg';
import moment from "moment";
import _ from "lodash";
import DocViewer, { DocViewerRenderers } from "react-doc-viewer";
import styles from './styles.less';



export type FileListProps = {
  getFiles: (data: any) => Promise<Array<DocumentFileType>>;
  uploadFile: (data: any) => Promise<DocumentFileType>;
  source?: any,
  downloadFile: (id: number) => Promise<FileType>;
  deleteFile: (id: number) => Promise<FileType>;
  updateDocumentFile: (id: number, data: any) => Promise<DocumentFileType>;
  documentAcknowledge: (id: number, data: any) => Promise<DocumentFileType>;
  folderPath: Array<DocumentFileType>;
};

const FileList: React.FC<FileListProps> = (props) => {

  const intl = useIntl();
  const { Text } = Typography;
  const [fileList, setFileList] = useState([]);
  const tableRef = useRef<any>();
  const [addModalVisible, handleAddModalVisible] = useState<boolean>(false);
  const [modalForm] = Form.useForm();
  const [acknowledgeForm] = Form.useForm();
  const [selectedEmployees, setSelectedEmployees] = useState([]);
  const [editModalVisible, setEditModalVisible] = useState<boolean>(false);
  const [formInitialValues, setFormInitialValues] = useState({});
  const [viewDocument, setViewDocument] = useState(false);
  const [docs, setDocs] = useState([]);
  const [fileId, setFileId] = useState('');
  const [initializing, setInitializing] = useState(false);
  const [audienceMethod, setAudienceMethod] = useState([]);
  const [audienceType, setAudienceType] = useState('');
  const [adminEmployees, setAdminEmployees] = useState([]);
  const [managers, setManagers] = useState([]);
  const [locations, setLocations] = useState([]);
  const [targetKeys, setTargetKeys] = useState<string[]>([]);
  const [mainFolder, setMainFolder] = useState('');
  const [subFolder, setSubFolder] = useState('');
  const [modalFormTitle, setModalFormTitle] = useState('');
  const [managerEmployees, setManagerEmployees] = useState([]);
  const [employeesList, setEmployeesList] = useState([]);
  const [hasAcknowledged, setHasAcknowledged] = useState(false);
  const [searchText, setSearchText] = useState('');
  const access = useAccess();
  const { hasPermitted } = access;

  useEffect(() => {
    init();
    if (props.folderPath.length > 0 && props.folderPath.length <= 1) {
      setMainFolder(props.folderPath[0].split("-")[0]);
      setSubFolder(props.folderPath[0].split("-")[1]);
    }

    if (props.folderPath.length > 1 && props.folderPath.length == 3) {
      setMainFolder(props.folderPath[2].split("-")[0]);
      setSubFolder(props.folderPath[2].split("-")[1]);
    }
  }, []);


  const init = async () => {
    setInitializing(true);

    const adminEmployeesRes = await getEmployeeList("ADMIN");
    setAdminEmployees(adminEmployeesRes?.data.map(employee => {
      return {
        title: employee.employeeNumber + ' | ' + employee.employeeName,
        key: employee.id
      };
    }));


    const managerRes = await getManagerList();
    setManagers(managerRes?.data.map(manager => {
      return {
        label: manager.employeeNumber + ' | ' + manager.employeeName,
        value: manager.id
      };
    }));

    const locationRes = await getAllLocations();
    setLocations(Object.values(locationRes?.data.map(location => {
      return {
        label: location.name,
        value: location.id
      };
    })));

    setInitializing(false);
    const _audienceMethod = [];
    _audienceMethod.push({ label: `${intl.formatMessage({ id: 'ALL', defaultMessage: 'All' })}`, value: 'ALL' });
    _audienceMethod.push({ label: `${intl.formatMessage({ id: 'ASSIGN_TO_MANAGER', defaultMessage: 'Assign To Manager' })}`, value: 'REPORT_TO' });
    _audienceMethod.push({ label: `${intl.formatMessage({ id: 'LOCATION', defaultMessage: 'Location' })}`, value: 'QUERY' });
    _audienceMethod.push({ label: `${intl.formatMessage({ id: 'CUSTOM', defaultMessage: 'Custom' })}`, value: 'CUSTOM' });
    setAudienceMethod(_audienceMethod);
  }
  const getManagerSubordinatesList = async (value) => {
    const { data } = await getSubordinatesList(value);
    setManagerEmployees(data.map(employee => {
      return {
        title: employee.employeeName,
        key: employee.id
      }
    }));

    setEmployeesList(data);
  }
  const handleAcknowledgeForm = async () => {
    try {
      const id = fileId;
      await props.documentAcknowledge(Number(id), {
        isAcknowledged: acknowledgeForm.getFieldValue('isAcknowledged'),
      });
      setViewDocument(false);
      tableRef.current?.reset();
      tableRef.current?.reload();

      message.success({
        content:
          intl.formatMessage({
            id: 'pages.document.succ_updated',
            defaultMessage: 'Successfully Acknowledged',
          }),
      });
    } catch (error) {
      console.log(error);
    }
  }
  const toolBarRender = () => [
    <>
      {subFolder !== "acknowledge document" &&
        <Button
          key="uploadFile"
          onClick={() => {
            handleAddModalVisible(true);
            setFileList([]);
            setTargetKeys([]);
            setAudienceType('');
            setManagerEmployees([]);
          }}
          type="primary"
        >
          <PlusOutlined /> <FormattedMessage id="pages.document.new" defaultMessage="New" />
        </Button>
      }
    </>,
    <>
      {mainFolder !== 'employee' && <Button
        key="report"
        onClick={() => {
          history.push(`/document/documentmanagerAcknowledge/reports`);
        }}
        type="primary"
      >
        Report
      </Button>
      }
    </>
  ];

  const actionColumnRender = (record: any) => [
    <div onClick={(e) => e.stopPropagation()}>
      <Tooltip key="download-tool-tip" title={intl.formatMessage({
        id: 'pages.document.download_tooltip',
        defaultMessage: 'Download',
      })}>
        <a
          key="download-btn"
          onClick={async (item) => {
            const messageKey = 'downloading';
            message.loading({
              content: intl.formatMessage({
                id: 'pages.document.downloading',
                defaultMessage: 'Downloading...',
              }),
              key: messageKey,
            });

            try {
              const result = await props.downloadFile(record.id);
              var anchor = document.createElement("a");
              anchor.href = result?.data,
                anchor.download = result?.name;
              anchor.click();

              message.success({
                content: intl.formatMessage({
                  id: 'pages.document.successfullyDownload',
                  defaultMessage: 'Successfully download',
                }),
                key: messageKey,
              });
              return;
            } catch (error) {
              message.error({
                content: intl.formatMessage({
                  id: 'pages.document.failedToDownload',
                  defaultMessage: 'Failed to download',
                }),
                key: messageKey,
              });
            }

          }}
        >
          <DownloadOutlined height={15} />
        </a>
      </Tooltip>
    </div>,
    <div onClick={(e) => e.stopPropagation()}>
      <Tooltip
        key="download-tool-tip"
        title={intl.formatMessage({
          id: 'pages.document.edit_tooltip',
          defaultMessage: 'Edit',
        })}
      >
        <a
          onClick={
            (e) => {
              // for share letter by HR
              if (record.audienceMethod !== null) {
                e.stopPropagation();
                return;
              }
              let type = record.type;
              let fileType = type.substring(0, type.indexOf(';'));
              let format = fileType.substring(fileType.indexOf(":") + 1);
              let fileData = [
                {
                  name: record.name,
                  size: record.size,
                  type: format,
                  existing: true
                }
              ];
              const audienceData = JSON.parse(record.audienceData);

              getManagerSubordinatesList(audienceData?.reportTo);
              setAudienceType(record.audienceMethod)
              setFileList(fileData);
              setFormInitialValues({
                id: record.id,
                documentName: record.documentName,
                documentDescription: record.documentDescription,
                initialValue: !_.isNull(record.deadline) && moment(record.deadline, 'YYYY-MM-DD').format('DD-MM-YYYY'),
                hasRequestAcknowledgement: record.hasRequestAcknowledgement ? true : false,
                hasFilePermission: record.hasFilePermission ? true : false,
                systemAlertNotification: record.systemAlertNotification ? true : false,
                emailNotification: record.emailNotification ? true : false,
                upload: fileData,
                audienceMethod: record.audienceMethod,
                reportToManager: audienceData ? audienceData?.reportTo : null,
                queryLocation: audienceData ? audienceData?.locationId : null,
                targetKeys: setTargetKeys(audienceData?.employeeIds),
                deadline: record.deadline
              });
              if (subFolder !== "acknowledge document") {
                setEditModalVisible(true);
              }

            }
          }
          disabled={subFolder === "acknowledge document" || record.audienceMethod !== null}
        >
          <Edit height={16} />
        </a>
      </Tooltip>
    </div>,
    <div onClick={(e) => e.stopPropagation()}>
      <Popconfirm
        key="delete-pop-confirm"
        placement="topRight"
        title={intl.formatMessage({
          id: 'pages.document.are_you_sure',
          defaultMessage: 'Are you sure?',
        })}
        okText={intl.formatMessage({
          id: 'pages.document.yes',
          defaultMessage: 'Yes',
        })}
        cancelText={intl.formatMessage({
          id: 'pages.document.no',
          defaultMessage: 'No',
        })}
        disabled={subFolder === "acknowledge document"}
        onConfirm={async () => {
          const messageKey = 'deleting';
          message.loading({
            content: intl.formatMessage({
              id: 'pages.document.downloading',
              defaultMessage: 'Downloading...',
            }),
            key: messageKey,
          });

          try {
            await props.deleteFile(record.id);
            tableRef.current?.reset();
            tableRef.current?.reload();

            message.success({
              content: intl.formatMessage({
                id: 'pages.document.successfullyDeleted',
                defaultMessage: 'Successfully Deleted',
              }),
              key: messageKey,
            });
            return;
          } catch (error) {
            message.error({
              content: intl.formatMessage({
                id: 'pages.document.failedToDelete',
                defaultMessage: 'Failed to delete',
              }),
              key: messageKey,
            });
          }
        }}
      >
        <Tooltip key="delete-tool-tip" title={intl.formatMessage({
          id: 'pages.document.delete_tooltip',
          defaultMessage: 'Delete',
        })}>
          <a
            key="delete-btn"
            onClick={(e) => e.stopPropagation()}
            disabled={subFolder === "acknowledge document"}
          >
            <DeleteOutlined />
          </a>
        </Tooltip>
      </Popconfirm>
    </div>,
  ];

  const columns: ProColumns<any>[] = [
    {
      title: <FormattedMessage id="pages.document.document_name" defaultMessage="Document Name" />,
      valueType: 'text',
      render: (dom, _) => {
        let element;
        let title = '';
        if (dom.isAcknowledged) {
          title = 'Acknowledged';
          element = <LikeOutlined height={20} />

        } else {
          title = 'Pending Acknowledgement';
          element = <Pending height={20} />
        }

        return (
          <Space>

            {hasPermitted('document-manager-read-write') && dom.hasRequestAcknowledgement && mainFolder === 'employee' ?
              (
                <Tooltip title={title}>
                  {element}
                </Tooltip>
              ) : (
                <col className={styles.fileName}></col>
              )

            }

            <Icon component={() => <img src={File} height={24} width={24} />} />
            <span>{dom.documentName}</span>
            <span style={{ color: '#FFB400' }}>{dom.isDocumentUpdated ? 'Updated' : ''} </span>
          </Space>
        )
      },
    },
    {
      title: <FormattedMessage id="pages.documentManagerReport.documentDescription" defaultMessage="Document Description" />,
      dataIndex: 'documentDescription',
      valueType: 'text',
    },
    {
      title: <FormattedMessage id="pages.documentManagerReport.fileName" defaultMessage="File Name" />,
      dataIndex: 'name',
      valueType: 'text',
      sorter: true,
    },
    {
      title: <FormattedMessage id="pages.documentManagerReport.fileSize" defaultMessage="File Size" />,
      dataIndex: 'size',
      valueType: 'text',
      render: (_, record) => humanReadableFileSize(record.size)
    },
    {
      title: <FormattedMessage id="pages.document.uploaded_date" defaultMessage="Uploaded Date" />,
      dataIndex: 'createdAt',
      valueType: 'text',
      sorter: true,
      render: (_, record) => {
        return <div
          style={{
            textOverflow: 'ellipsis',
            whiteSpace: 'nowrap'
          }}>
          {moment(record.createdAt).format('DD-MM-YYYY HH:mm:ss')}
        </div>
      }
    },
    {
      title: <FormattedMessage id="pages.document.actions" defaultMessage="Actions" />,
      width: 120,
      valueType: 'option',
      render: (_, record) => {
        let color = '';
        let title = '';
        if (record.isAcknowledged) {
          color = '#86C129';
          title = 'Acknowledged';

        } else {
          color = '#FFFF00';
          title = 'Pending Acknowledgement';
        }
        if (hasPermitted('document-manager-read-write')) {
          return actionColumnRender(record);
        } else {
          if (record.hasRequestAcknowledgement) {
            return <div onClick={(e) => e.stopPropagation()}>
              <Tooltip title={title}>
                <LikeOutlined style={{ color: color }} />
              </Tooltip>
            </div>
          }
        }

      }
    },
  ];

  const employeeColumns: ProColumns<any>[] = [
    {
      valueType: 'text',
      render: (record, _) => {

        return (
          <Space>
            <Icon component={() => <img src={File} height={24} width={24} />} />
            <span>{record.documentName}</span>
            <span style={{ color: '#FFB400' }}>{record.isDocumentUpdated ? 'Updated' : ''} </span>
          </Space>
        )
      },
    },
    {
      valueType: 'text',
      render: (_, record) => {
        return <div
          style={{
            textOverflow: 'ellipsis',
            whiteSpace: 'nowrap'
          }}>
          {moment(record.createdAt).format('DD-MM-YYYY HH:mm:ss')}
        </div>
      }

    },
    {
      width: 120,
      valueType: 'option',
      render: (_, record) => {
        let element;
        let title = '';


        if (record.isAcknowledged) {
          title = intl.formatMessage({
            id: 'documentManager.acknowledge',
            defaultMessage: 'Acknowledged'
          });
          element = <LikeOutlined height={20} />

        } else {
          title = intl.formatMessage({
            id: 'documentManager.pending',
            defaultMessage: 'Pending Acknowledgement'
          });
          element = <Pending height={20} />
        }


        return (
          <Space direction="horizontal" >
            {record.hasRequestAcknowledgement ?
              <div onClick={(e) => e.stopPropagation()}>
                <Tooltip title={title}>
                  {element}
                </Tooltip>
              </div> : (
                <> </>
              )
            }
            {subFolder === 'letters' || record.hasFilePermission ?
              <>
                <div onClick={(e) => e.stopPropagation()}>
                  <Tooltip key="download-tool-tip" title={intl.formatMessage({
                    id: 'pages.document.download_tooltip',
                    defaultMessage: 'Download',
                  })}>
                    <a
                      key="download-btn"
                      onClick={async (item) => {
                        const messageKey = 'downloading';
                        message.loading({
                          content: intl.formatMessage({
                            id: 'pages.document.downloading',
                            defaultMessage: 'Downloading...',
                          }),
                          key: messageKey,
                        });

                        try {
                          const result = await props.downloadFile(record.id);
                          var anchor = document.createElement("a");
                          anchor.href = result?.data,
                            anchor.download = result?.name;
                          anchor.click();

                          message.success({
                            content: intl.formatMessage({
                              id: 'pages.document.successfullyDownload',
                              defaultMessage: 'Successfully download',
                            }),
                            key: messageKey,
                          });
                          return;
                        } catch (error) {
                          message.error({
                            content: intl.formatMessage({
                              id: 'pages.document.failedToDownload',
                              defaultMessage: 'Failed to download',
                            }),
                            key: messageKey,
                          });
                        }

                      }}
                    >
                      <DownloadOutlined height={15} />
                    </a>
                  </Tooltip>
                </div>
                <div onClick={(e) => e.stopPropagation()}>
                  <Tooltip
                    placement={'bottom'}
                    key="viewRecordTooltip"
                    title={intl.formatMessage({
                      id: 'view',
                      defaultMessage: 'View',
                    })}
                  >
                    <a
                      key="viewRecordTooltip"
                      onClick={async () => {
                        setModalFormTitle(record.documentName);
                        const fileData = await props.downloadFile(record.id);
                        setDocs([{ uri: `${fileData.data}` }]);
                        setViewDocument(true);
                        setFileId(record.id);

                        if (record.isAcknowledged) {
                          setHasAcknowledged(true);
                        } else {
                          setHasAcknowledged(false);
                        }
                      }
                      }
                    >
                      <EyeOutlined />
                    </a>
                  </Tooltip>
                </div>
              </> : (
                <></>
              )
            }
          </Space>
        )
      }
    },
  ];
  const handleAdd = async (fields: any) => {
    const messageKey = 'deleting';
    message.loading({
      content: intl.formatMessage({
        id: 'pages.document.uploading',
        defaultMessage: 'Uploading...',
      }),
      key: messageKey,
    });

    try {
      const base64File = await getBase64(fields.upload[0].originFileObj);

      let audience = { ...selectedEmployees };

      switch (audienceType) {
        case 'REPORT_TO':
          audience = {
            reportTo: modalForm.getFieldValue('reportToManager'),
            employeeIds: targetKeys
          };
          break;
        case 'QUERY':
          audience = {
            locationId: modalForm.getFieldValue('queryLocation')
          };
          break;
        case 'CUSTOM':
          audience = {
            employeeIds: targetKeys
          };
          break;
        default:
          audience = {};
          break;
      }
      await props.uploadFile({
        documentName: fields.documentName,
        documentDescription: fields.documentDescription,
        deadline: fields.deadline,
        hasRequestAcknowledgement: modalForm.getFieldValue('hasRequestAcknowledgement'),
        hasFilePermission: modalForm.getFieldValue('hasFilePermission'),
        emailNotification: modalForm.getFieldValue('emailNotification'),
        systemAlertNotification: modalForm.getFieldValue('systemAlertNotification'),
        fileName: fields.upload[0].name,
        fileSize: fields.upload[0].size,
        fileType: fields.upload[0].type,
        data: base64File,
        audienceType: audienceType ? audienceType : null,
        audienceData: audience

      });
      handleAddModalVisible(false);
      tableRef.current?.reset();
      tableRef.current?.reload();

      message.success({
        content:
          intl.formatMessage({
            id: 'pages.document.succ_uploading',
            defaultMessage: 'Successfully uploaded',
          }),
        key: messageKey,
      });

      return true;
    } catch (error) {

      if (!_.isUndefined(error)) {
        modalForm.setFields([
          {
            name: [error.data['fields']],
            errors: [error.message],
          },
        ]);
      }
      message.error({
        content:
          intl.formatMessage({
            id: 'pages.document.error_uploading',
            defaultMessage: 'Failed to upload',
          }),
        key: messageKey,
      });

      return false;
    }
  };

  const update = async (id, fields: any) => {
    try {
      let base64File;
      let fileName;
      let fileSize;
      let fileType;
      if (fields.upload && !fields.upload[0].existing) {
        base64File = await getBase64(fields.upload[0].originFileObj);
        fileName = fields.upload[0].name;
        fileSize = fields.upload[0].size;
        fileType = fields.upload[0].type;
      }

      let audience = { ...selectedEmployees };
      switch (audienceType) {
        case 'REPORT_TO':
          audience = {
            reportTo: modalForm.getFieldValue('reportToManager'),
            employeeIds: targetKeys
          };
          break;
        case 'QUERY':
          audience = {
            locationId: modalForm.getFieldValue('queryLocation') ?? formInitialValues.queryLocation
          };
          break;
        case 'CUSTOM':
          audience = {
            employeeIds: targetKeys
          };
          break;
        default:
          audience = {};
          break;
      }

      await props.updateDocumentFile(id, {
        documentName: fields.documentName,
        documentDescription: fields.documentDescription,
        deadline: fields.deadline,
        hasRequestAcknowledgement: modalForm.getFieldValue('hasRequestAcknowledgement') ?? formInitialValues.hasRequestAcknowledgement,
        hasFilePermission: modalForm.getFieldValue('hasFilePermission') ?? formInitialValues.hasFilePermission,
        emailNotification: modalForm.getFieldValue('emailNotification') ?? formInitialValues.emailNotification,
        systemAlertNotification: modalForm.getFieldValue('systemAlertNotification') ?? formInitialValues.systemAlertNotification,
        fileName: fileName,
        fileSize: fileSize,
        fileType: fileType,
        data: base64File,
        audienceType: audienceType ? audienceType : null,
        audienceData: audience

      });
      setEditModalVisible(false);
      tableRef.current?.reset();
      tableRef.current?.reload();

      message.success({
        content:
          intl.formatMessage({
            id: 'pages.document.succ_updated_file',
            defaultMessage: 'Successfully updated',
          }),
      });
    } catch (error) {

      if (!_.isUndefined(error)) {
        modalForm.setFields([
          {
            name: [error.data['fields']],
            errors: [error.message],
          },
        ]);
      }
      message.error({
        content:
          intl.formatMessage({
            id: 'pages.document.error_update',
            defaultMessage: 'Failed to update',
          }),
      });
    }
  }

  const formFields = () => {
    return (
      <ProForm.Group>
        <Row style={{ width: 600 }}>
          <Space>
            <ProFormText
              width="md"
              name="documentName"
              label={intl.formatMessage({
                id: 'pages.document.documentName',
                defaultMessage: 'Document Name',
              })}
              placeholder={intl.formatMessage({
                id: 'pages.document.documentName',
                defaultMessage: 'Enter a Document Name',
              })}
              rules={[
                {
                  required: true,
                  message: 'Required',
                  type: 'string',
                },
                {
                  pattern: /^\w+((?!\s{2}).)*$/,
                  message: intl.formatMessage({
                    id: 'name',
                    defaultMessage: 'Cannot contain more than one space.',
                  }),
                },
              ]}
            />
          </Space>
        </Row>
        <Row style={{ width: 800 }}>
          <Col span={18} className='document-uploader'>
            <ProFormUploadButton
              width="md"
              name="upload"
              label={intl.formatMessage({
                id: 'pages.documents.Attachment',
                defaultMessage: 'Attachment',
              })}
              title={intl.formatMessage({
                id: 'pages.documents.browse',
                defaultMessage: 'Upload Document',
              })}
              max={1}
              fieldProps={{
                name: 'file',
              }}
              fileList={fileList}
              onChange={async (info: any) => {
                let status = info?.file?.status;
                if (status === 'error') {
                  const { fileList, file } = info;
                  const { uid } = file;
                  const index = fileList.findIndex((file: any) => file.uid == uid);
                  const newFile = { ...file };
                  if (index > -1) {
                    newFile.status = 'done';
                    newFile.percent = 100;
                    delete newFile.error;
                    fileList[index] = newFile;
                    setFileList(fileList);
                  }
                } else {
                  setFileList(info.fileList);
                }
              }}
              rules={[
                {
                  required: true,
                  message: 'Required',
                },
                {
                  validator: (_, upload) => {
                    if (upload !== undefined && upload && upload.length !== 0) {
                      //check file size .It should be less than 2MB
                      if (upload[0].size > 2097152) {
                        return Promise.reject(new Error(
                          intl.formatMessage({
                            id: 'pages.documentManager.filesize',
                            defaultMessage: 'File size is too large. Maximum size is 2 MB',
                          })

                        ));
                      }
                      const isValidFormat = [
                        'image/jpeg',
                        'image/png',
                        'application/pdf',
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        'application/zip',
                        'application/msword',
                        'application/vnd.ms-excel'
                      ]
                      //check file format
                      if (!isValidFormat.includes(upload[0].type)) {
                        return Promise.reject(new Error(
                          intl.formatMessage({
                            id: 'pages.documentManager.fileformat',
                            defaultMessage: 'File format should be  jpeg/png/docx/xlsx/pdf/zip/doc/xls',
                          })
                        ));
                      }
                    }
                    return Promise.resolve();
                  },
                },
              ]}
            />
          </Col>
        </Row>
        <Row style={{ width: 800 }}>
          <ProFormTextArea
            width="md"
            name="documentDescription"
            label={intl.formatMessage({
              id: 'pages.documentManagerReport.documentDescription',
              defaultMessage: 'Description',
            })}
            placeholder={intl.formatMessage({
              id: 'pages.document.DocumentFileDescription',
              defaultMessage: 'Enter a Description',
            })}

          />

        </Row>
        {mainFolder !== 'employee' &&
          <>
            <Form.Item
              label={intl.formatMessage({
                id: 'pages.documentManagerReport.audience',
                defaultMessage: 'Audience',
              })}

            >
              <Text type="secondary">
                {intl.formatMessage({
                  id: 'pages.documentManagerReport.secondary.label',
                  defaultMessage: 'Select the employees you want the document to be acknowledged.',
                })}
              </Text>
            </Form.Item>

            <ProFormSelect
              width="lg"
              name="audienceMethod"
              options={audienceMethod}
              onChange={(value) => {
                setTargetKeys([]);
                setManagerEmployees([]);
                setAudienceType(value);
              }}
              placeholder={intl.formatMessage({
                id: 'pages.document.audienceType',
                defaultMessage: 'Select Audience Type',
              })}
            />

            {!initializing && audienceType == 'REPORT_TO' &&
              <ProFormSelect
                width="lg"
                name="reportToManager"
                label={intl.formatMessage({
                  id: 'pages.documentManagerReport.SELECT_A_MANAGER',
                  defaultMessage: 'Select a Manager',
                })}
                options={managers}
                rules={
                  [
                    {
                      required: true,
                      message: intl.formatMessage({
                        id: 'pages.documentManagerReport.topic',
                        defaultMessage: 'Required',
                      })
                    },
                  ]
                }
                onChange={async (value) => {
                  getManagerSubordinatesList(value);
                }}
                placeholder={intl.formatMessage({
                  id: 'pages.document.manager',
                  defaultMessage: 'Select Manager',
                })}
              />
            }

            {!initializing && audienceType == 'QUERY' &&
              <ProFormSelect
                width="lg"
                name="queryLocation"
                label={intl.formatMessage({
                  id: 'pages.documentManagerReport.SELECT_A_LOCATION',
                  defaultMessage: 'Select a Location',
                })}
                options={locations}
                rules={
                  [
                    {
                      required: true,
                      message: intl.formatMessage({
                        id: 'topic',
                        defaultMessage: 'Required',
                      })
                    },
                  ]
                }
                placeholder={intl.formatMessage({
                  id: 'pages.document.location',
                  defaultMessage: 'Select Location',
                })}
              />
            }

            {!initializing && (audienceType == 'CUSTOM' || audienceType == 'REPORT_TO') &&
              <Transfer
                dataSource={audienceType == 'CUSTOM' ? adminEmployees : managerEmployees}
                showSearch
                filterOption={(search, item) => { return item.title.toLowerCase().indexOf(search.toLowerCase()) >= 0; }}
                targetKeys={targetKeys}
                onChange={(newTargetKeys: string[]) => {
                  setTargetKeys(newTargetKeys);
                }}
                render={item => item.title}
                listStyle={{
                  width: 300,
                  height: 300,
                  marginBottom: 20
                }}
              />
            }
          </>
        }
        {addModalVisible &&
          <>
            <Row style={{ width: 870 }}>
              <Space>
                {/* {
                  props.source == 'documentAcknowledge' ? (
                    <>
                      <ProFormDatePicker
                        name="deadline"
                        label={intl.formatMessage({
                          id: 'pages.document.deadline',
                          defaultMessage: 'Deadline',
                        })}
                        width="sm"
                        format={'DD-MM-YYYY'}
                      />
                      <Checkbox
                        name="hasRequestAcknowledgement"
                        style={{ color: '#626D6C' }}
                        onChange={e => modalForm.setFieldsValue({ hasRequestAcknowledgement: e.target.checked })}
                        disabled={fileList.length !== 0 && fileList[0].type === 'application/pdf' ? false : true}
                      >
                        {intl.formatMessage({
                          id: 'pages.document.request',
                          defaultMessage: 'Request Acknowledgement',
                        })}
                      </Checkbox>
                    </>
                  ) : (
                    <></>
                  )
                } */}

                <div className={styles.filePermission}>
                  <Text strong>
                    {intl.formatMessage({
                      id: 'pages.document.file',
                      defaultMessage: 'File Permission',
                    })}
                  </Text>
                  <br />
                  <span className={styles.fileCheckBox}>
                    <Checkbox
                      name="hasFilePermission"
                      style={{ color: '#626D6C', paddingTop: 10 }}
                      onChange={e => modalForm.setFieldsValue({ hasFilePermission: e.target.checked })}
                    >
                      {intl.formatMessage({
                        id: 'pages.document.filePermission',
                        defaultMessage: 'Allow employees to download the document ',
                      })}
                    </Checkbox>
                  </span>
                </div>
              </Space>
            </Row>
          </>
        }
        {
          editModalVisible &&
          <>
            {
              props.source == 'documentAcknowledge' ? (
                <Row style={{ width: 870 }}>
                  <Space>
                    <ProFormDatePicker
                      name="deadline"
                      label={intl.formatMessage({
                        id: 'pages.document.deadline',
                        defaultMessage: 'Deadline',
                      })}
                      width="sm"
                      format={"DD-MM-YYYY"}
                    />
                    <Checkbox
                      name="hasRequestAcknowledgement"
                      style={{ color: '#626D6C', marginTop: 10, paddingLeft: 10 }}
                      onChange={e => modalForm.setFieldsValue({ hasRequestAcknowledgement: e.target.checked })}
                      defaultChecked={formInitialValues.hasRequestAcknowledgement}
                      disabled={fileList.length !== 0 && fileList[0].type === 'application/pdf' ? false : true}
                    >
                      {intl.formatMessage({
                        id: 'pages.document.request',
                        defaultMessage: 'Request Acknowledgement',
                      })}
                    </Checkbox>
                  </Space>
                </Row>
              ) : (
                <></>
              )
            }
            <Row className={styles.editFilePermission}>
              <div style={{ marginBottom: 24 }}>
                <Text strong>
                  {intl.formatMessage({
                    id: 'pages.document.file',
                    defaultMessage: 'File Permission',
                  })}
                </Text>
                <br />
                <Checkbox
                  name="hasFilePermission"
                  style={{ color: '#626D6C' }}
                  onChange={e => modalForm.setFieldsValue({ hasFilePermission: e.target.checked })}
                  defaultChecked={formInitialValues.hasFilePermission}
                >
                  {intl.formatMessage({
                    id: 'pages.document.filePermission',
                    defaultMessage: 'Allow employees to download the document ',
                  })}
                </Checkbox>
              </div>
            </Row>
          </>
        }
        <Row style={{ width: 800 }}>
          <div>
            <Text strong>
              {intl.formatMessage({
                id: 'pages.document.Notification',
                defaultMessage: 'Notification',
              })}
            </Text>
            <br />
            <Text type="secondary">
              Notifiy via
            </Text>
            {/* <Checkbox
                name="systemAlertNotification"
                style={{ color: '#626D6C', paddingLeft: 10, marginTop: 10 }}
                onChange={e => modalForm.setFieldsValue({ systemAlertNotification: e.target.checked })}
                defaultChecked={formInitialValues.systemAlertNotification}
             >
                {intl.formatMessage({
                  id: 'pages.document.systemAlert',
                  defaultMessage: 'System Alert ',
                })}
              </Checkbox> */}
            <Checkbox
              name="emailNotification"
              style={{ color: '#626D6C', paddingLeft: 10 }}
              onChange={e => modalForm.setFieldsValue({ emailNotification: e.target.checked })}
              defaultChecked={formInitialValues.emailNotification}
            >
              {intl.formatMessage({
                id: 'pages.document.email',
                defaultMessage: 'Email ',
              })}
            </Checkbox>

          </div>
        </Row>

      </ProForm.Group>
    )
  }
  const handleSearch = () => {
    return {
      className: 'basic-container-search',
      placeholder: intl.formatMessage({
        id: 'pages.document.search',
        defaultMessage: 'Search by Document Name',
      }),
      onChange: (value: any) => {
        setSearchText(value.target.value);
        if (_.isEmpty(value.target.value)) {
          tableRef.current?.reset();
          tableRef.current?.reload();
        }
      },
      value: searchText
    };
  };
  return (
    <div>
      <ProTable<any>
        columns={hasPermitted('document-manager-read-write') ? columns : employeeColumns}
        rowKey="id"
        options={{
          search: true,
          reload: () => {
            tableRef.current?.reset();
            tableRef.current?.reload();
            setSearchText('');
          },
        }}

        request={async ({ pageSize, current }, sort) => {

          const files = await props.getFiles({ search: searchText, sorter: sort });
          return { data: files };
        }}
        actionRef={tableRef}
        search={false}
        pagination={{
          showSizeChanger: true,
        }}
        toolbar={{
          search: handleSearch()
        }}
        showHeader={hasPermitted('document-manager-read-write') ? true : false}
        dateFormatter="string"
        toolBarRender={hasPermitted('document-manager-read-write') && toolBarRender}
        onRow={(record, rowIndex) => {
          return {
            onClick: async () => {
              if (((!record.isAcknowledged && record.hasRequestAcknowledgement) || subFolder === 'letters') && (hasPermitted('document-manager-employee-access'))) {
                setHasAcknowledged(false);
                const fileData = await props.downloadFile(record.id);

                setDocs([{ uri: `${fileData.data}` }]);
                setModalFormTitle(record.documentName);
                if (record.isDocumentUpdated) {

                  Modal.confirm({
                    title: `${record.documentName} has been updated`,
                    icon: <WarningOutlined />,
                    content: 'Open the updated document and re-acknowledge the document.',
                    okText: intl.formatMessage({
                      id: 'Open',
                      defaultMessage: 'Open',
                    }),
                    cancelText: intl.formatMessage({
                      id: 'Cancel',
                      defaultMessage: 'Cancel',
                    }),
                    onOk: () => {
                      setViewDocument(true);
                    }
                  });
                } else {
                  setViewDocument(true);
                }
                setFileId(record.id);
              }
            },
          };
        }}
      />

      <ModalForm
        width={800}
        form={modalForm}
        title={intl.formatMessage({
          id: 'pages.document.addNewDocument',
          defaultMessage: 'Add New Document',
        })}
        onFinish={async (values: any) => {
          await handleAdd(values as any);
        }}
        visible={addModalVisible}
        onVisibleChange={handleAddModalVisible}
        modalProps={{
          destroyOnClose: true,
        }}
        submitter={{
          searchConfig: {
            submitText: intl.formatMessage({
              id: 'pages.document.save',
              defaultMessage: 'Save',
            }),
            resetText: intl.formatMessage({
              id: 'pages.document.cancel',
              defaultMessage: 'Cancel',
            }),
          },
        }}
      >
        {formFields()}
      </ModalForm>
      {editModalVisible &&
        <DrawerForm
          width={650}
          form={modalForm}
          title={intl.formatMessage({
            id: 'pages.document.editDocument',
            defaultMessage: 'Edit Document',
          })}
          onVisibleChange={setEditModalVisible}
          drawerProps={{
            destroyOnClose: true,
          }}
          visible={editModalVisible}
          onFinish={async (values) => {
            update(formInitialValues.id, values);
          }}
          initialValues={formInitialValues}
          submitter={{
            render: (props, defaultDoms) => {
              return [

                <Button
                  key="cancel"
                  onClick={() => {
                    setEditModalVisible(false)
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
      }
      {viewDocument &&
        <ModalForm
          title={modalFormTitle}
          form={acknowledgeForm}
          visible={viewDocument}
          onVisibleChange={setViewDocument}
          onFinish={async (values: any) => {
            await handleAcknowledgeForm(values as any);
          }}
          submitter={{
            render: (props, defaultDoms) => {
              return [
                <Row >
                  {subFolder !== 'letters' && !hasAcknowledged ?
                    <Col span={10} >
                      <Checkbox
                        name="isAcknowledged"
                        onChange={e => acknowledgeForm.setFieldsValue({ isAcknowledged: e.target.checked })}
                      >
                        {intl.formatMessage({
                          id: 'pages.viewDocument.acknowledge',
                          defaultMessage: 'I acknowledge that I have read this document',
                        })
                        }</Checkbox>
                    </Col> :
                    <Col span={10} ></Col>
                  }
                  {subFolder !== 'letters' && !hasAcknowledged &&
                    <Col span={12}>
                      <Button
                        key="cancel"
                        onClick={() => {
                          setViewDocument(false)
                        }}
                      >
                        {intl.formatMessage({
                          id: 'pages.document.cancel',
                          defaultMessage: 'Cancel',
                        })}
                      </Button>

                      <Button
                        key="ok"
                        onClick={() => {
                          props.submit();
                        }}
                        type={"primary"}
                      >
                        {intl.formatMessage({
                          id: 'pages.document.save',
                          defaultMessage: 'Ok',
                        })}
                      </Button>
                    </Col>
                  }
                </Row>

              ];
            },
          }}
          style={{ width: 600 }}
        >
          <DocViewer
            documents={docs}
            pluginRenderers={DocViewerRenderers}
            config={{
              header: {
                disableFileName: true,
              }
            }}
            style={{
              // overflowY: 'scroll',
              height: '50%',
              backgroundColor: '#FAF9F6'
            }}
          />
        </ModalForm>
      }

    </div>
  );
}



export default FileList;
