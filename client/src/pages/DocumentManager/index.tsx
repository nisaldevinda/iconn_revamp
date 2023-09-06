import FileExplorer, { DocumentFolderType } from "@/components/FileExplorer";
import { deleteFile, getFile, getFileList, getFolderHierarchy, uploadFile , updateDocumentFile ,documentAcknowledge , addFolder} from "@/services/documentManager";
import { getAllEmployee } from "@/services/employee";
import { PageContainer } from "@ant-design/pro-layout";
import { Spin ,Button ,message as Message ,Form, Col} from "antd";
import _ from "lodash";
import React, { useEffect, useState } from "react";
import { useParams ,useIntl } from "umi";
import ProForm, { ModalForm, ProFormText } from "@ant-design/pro-form";

const DocumentManager: React.FC = (props) => {
    const { slugs } = useParams<{slugs: string}>();

    const [loading, setLoading] = useState(false);
    const [folders, setFolders] = useState<Array<DocumentFolderType>>([]);
    const [path, setPath] = useState<Array<string>>([]);
    const [modalVisible, setModalVisible] = useState<boolean>(false);
    const [modalForm] = Form.useForm();
    const intl = useIntl();

    useEffect(() => {
        init();
    }, []);

    useEffect(() => {
        setPath(slugs ? slugs.split('/') : []);
    }, [slugs]);

    const init = async () => {
        setLoading(true);

     
            let employeeFolder:DocumentFolderType = undefined;
            let _folders: Array<DocumentFolderType> = [];

            const documentFolders = await getFolderHierarchy();
            if (documentFolders.data && !_.isEmpty(documentFolders.data)) {
                _folders = _folders.concat(documentFolders.data.map((documentFolder: DocumentFolderType) => {
                    return {
                        folderId: documentFolder.id,
                        name: documentFolder.name,
                        parentId: documentFolder.parentId,
                        slug: documentFolder.slug
                    };
                }));

                employeeFolder = documentFolders.data.find((folder: DocumentFolderType) => folder.type === 'EMPLOYEE');
            }

            if (employeeFolder) {
                const employees = await getAllEmployee();
                if (employees.data && !_.isEmpty(employees.data)) {
                    _folders = _folders.map((documentFolder: DocumentFolderType) => {
                        if (documentFolder.parentId == employeeFolder.id) {
                            return {
                                ...documentFolder,
                                parentId: -1
                            };
                        }

                        return documentFolder;
                    });

                    _folders = _folders.concat(employees.data.map((employee: {id: number, employeeName: string, employeeNumber: string}) => {
                        return {
                            folderId: -1,
                            employeeId: employee.id,
                            name: employee.employeeName,
                            parentId: employeeFolder.id,
                            slug: employee.employeeNumber
                        };
                    }));
                }
            }
            _folders = _folders.filter((folder) => folder.slug !== 'company');
            setFolders(_folders);
       
        
        setLoading(false);
    }

    const extractCurrentFolderEmployee = (): {folder?: DocumentFolderType, employee?: DocumentFolderType} => {
        if (!_.isEmpty(path)) {
            let _path = [...path];
            let folder = undefined;
            let employee = undefined;

            const employeeSlugIndex = path.findIndex(slug => {
                const folder = folders.find(folder => folder.slug == slug);
                return folder && folder.employeeId;
            });

            if (employeeSlugIndex > -1) {
                employee = folders.find(folder => folder.slug == path[employeeSlugIndex]);
                _path.splice(employeeSlugIndex, 1);
            }
            
            folder = folders.find(folder => folder.slug == _path[_path.length -1]);

            return {folder, employee};
        } else {
            return {};
        }
    }

    const _getFiles = async (data:any) => {
        const current = extractCurrentFolderEmployee();
        const folderId = current.folder?.folderId != 3 ? current.folder?.folderId : 1 ;
        const files = await getFileList( folderId, current.employee?.employeeId, data);
        return files.data ?? [];
    }

    const _uploadFile = async (data: any) => {
        const current = extractCurrentFolderEmployee();
        const response = await uploadFile({
            ...data,
            folderId: current.folder?.folderId,
            employeeId: current.employee?.employeeId,
        });

        return response.data ?? {};
    }
  
    const _updateDocumentFile = async (id: number,data: any) => {
        const current = extractCurrentFolderEmployee();
        const response = await updateDocumentFile(id,{
            ...data,
            folderId: current.folder?.folderId,
            employeeId: current.employee?.employeeId,
        });

        return response.data ?? {};
    }
    const _deleteFile = async (id: number) => {
        const response = await deleteFile(id);
        return response.data ?? {};
    }

    const _downloadFile = async (id: number) => {
        const response = await getFile(id);
        return response.data ?? {};
    }

    const _documentAcknowledge = async (id: number,data: any) => {
        const current = extractCurrentFolderEmployee();
        const response = await documentAcknowledge(id,{
            ...data,
            folderId: current.folder?.folderId,
            employeeId: current.employee?.employeeId,
        });

        return response.data ?? {};
    }
    
    const handleAdd = async (fields: any) => {
        const { name } = fields;
        try {
            const requestData = {
                name: name,
                type: 'OTHER',
                parentId: 2,
                slug: 'employee-'.concat(name).toLocaleLowerCase()

            }

            const { message } = await addFolder(requestData);
            init();
            Message.success(message);
            setModalVisible(false);
        } catch (erorr) {
            Message.error(message);
        }
    }
     
    return !loading && folders ? (
      <div
        style={{
          backgroundColor: 'white',
          borderTopLeftRadius: '30px',
          paddingLeft: '50px',
          paddingTop: '50px',
          paddingBottom: '50px',
          width: '100%',
          paddingRight: '0px',
        }}
      >
        <PageContainer
          title="Document Manager"
          extra={
            _.isUndefined(slugs) && (
              <Button type="primary" onClick={() => setModalVisible(true)}>
                Add Folder
              </Button>
            )
          }
        >
          <FileExplorer
            folders={folders}
            baseRoute="documentmanager"
            path={path}
            getFiles={_getFiles}
            uploadFile={_uploadFile}
            downloadFile={_downloadFile}
            deleteFile={_deleteFile}
            updateDocumentFile={_updateDocumentFile}
            documentAcknowledge={_documentAcknowledge}
          />
          <ModalForm
            width={500}
            form={modalForm}
            title={intl.formatMessage({
              id: 'pages.document.addNewFolder',
              defaultMessage: 'Add New Folder',
            })}
            onFinish={async (values: any) => {
              await handleAdd(values as any);
            }}
            visible={modalVisible}
            onVisibleChange={setModalVisible}
            modalProps={{
              destroyOnClose: true,
            }}
            submitter={{
              searchConfig: {
                submitText: intl.formatMessage({
                  id: 'save',
                  defaultMessage: 'Save',
                }),
                resetText: intl.formatMessage({
                  id: 'cancel',
                  defaultMessage: 'Cancel',
                }),
              },
            }}
          >
            <ProForm.Group>
              <Col style={{ paddingLeft: 20 }}>
                <ProFormText
                  width="md"
                  name="name"
                  label={intl.formatMessage({
                    id: 'pages.document.folderName',
                    defaultMessage: 'Folder Name',
                  })}
                  placeholder={intl.formatMessage({
                    id: 'pages.document.name',
                    defaultMessage: 'Enter a Folder Name',
                  })}
                  rules={[
                    {
                      required: true,
                      message: 'Required',
                      type: 'string',
                    },
                  ]}
                />
              </Col>
            </ProForm.Group>
          </ModalForm>
        </PageContainer>
      </div>
    ) : (
      <Spin />
    );
};

export default DocumentManager;
