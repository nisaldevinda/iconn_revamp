import { deleteFile, getFile, getFileList, getFolderHierarchy,getMyFile, getMyFileList, getMyFolderHierarchy, uploadFile ,documentAcknowledge , getFilesInEmployeeFolders } from '@/services/documentManager';
import { Col, Spin } from 'antd';
import _ from 'lodash';
import React, { useEffect, useState } from 'react';
import FileExplorer, { DocumentFolderType } from '../FileExplorer';
import { useAccess  } from "umi";

export type EditEmployeeRouteParams = {
    values: string
};

const DocumentView: React.FC<{ values: any }> = (props) => {
    const [loading, setLoading] = useState(false);
    const [folders, setFolders] = useState<Array<DocumentFolderType>>([]);
    const [path, setPath] = useState<Array<string>>([]);
    const access = useAccess();
    const { hasPermitted } = access;

    useEffect(() => {
        init();
    }, []);

    const init = async () => {
        setLoading(true);

        if (_.isEmpty(folders)) {
            let _folders: Array<DocumentFolderType> = [];
            let documentFolders ;
            if (hasPermitted('document-manager-read-write')) {
                documentFolders = await getFolderHierarchy();

            } else {
                documentFolders = await getMyFolderHierarchy();
            }
           
            if (documentFolders.data && !_.isEmpty(documentFolders.data)) {
                _folders = _folders.concat(documentFolders.data
                    .filter((documentFolder: DocumentFolderType) => {
                        return documentFolder.parentId == documentFolders.data
                            .find((folder: DocumentFolderType) => folder?.type == 'EMPLOYEE')?.id;
                    })
                    .map((documentFolder: DocumentFolderType) => {
                        return {
                            folderId: documentFolder.id,
                            name: documentFolder.name,
                            parentId: 0,
                            slug: documentFolder.slug
                        };
                    }));
            }
             // In employee My info display only folders which has documents for the given employeeId 
            if (!(hasPermitted('document-manager-read-write'))) {
               
                let folderIds = ['1']; // Document Acknowledge Process folder Id
                _folders.map((folder) => {
                    let folders = folderIds.push(folder.folderId);
                    return folders
                });
                const requestData = {
                    folderId: folderIds.toString(),
                    employeeId: props.values.id
                }
                const employeeDocumentFiles = await getFilesInEmployeeFolders(requestData)
                // all the acknowledge documents are stored in folderId [1] 
                // but In My info page , need to display the acknowledge documents in folderID [3]
                let folderData = employeeDocumentFiles.data.find((folder) => folder === 1);
                let index = employeeDocumentFiles.data.indexOf(folderData)
                employeeDocumentFiles.data[index] = 3;
                
                const filterdFolders = [];
                _folders.map((folder) => {
                    let filteredData = employeeDocumentFiles.data.includes(folder.folderId);
                    if (filteredData) {
                        return filterdFolders.push(folder);
                    }
                })

                setFolders(filterdFolders);
            } else {
                setFolders(_folders);
            }
        }
        setLoading(false);
    }


    const extractCurrentFolderEmployee = (): {folder?: DocumentFolderType, employee?: DocumentFolderType} => {
        if (!_.isEmpty(path)) {
            let _path = [...path];
            let folder = undefined;
            let employee = undefined;
            
            folder = folders.find(folder => folder.slug == _path[_path.length -1]);
            employee = {
                employeeId: props.values.id,
                name: props.values.fullName,
                parentId: 0,
                slug: props.values.employeeNumber
            }

            return {folder, employee};
        } else {
            return {};
        }
    }

    const _getFiles = async () => {
        const current = extractCurrentFolderEmployee();
        const folderId = current.folder?.folderId != 3 ? current.folder?.folderId : 1 ;
       
        if (hasPermitted('document-manager-read-write')) {
           const files = await getFileList(folderId, current.employee?.employeeId);
           return files.data ?? [];
        } else {
            const files = await getMyFileList(folderId , current.employee?.employeeId);
            return files.data ?? [];
        }
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

    const _deleteFile = async (id: number) => {
        const response = await deleteFile(id);
        return response.data ?? {};
    }

    const _downloadFile = async (id: number) => {
        if (hasPermitted('document-manager-read-write')) {
           const response = await getFile(id);
           return response.data ?? {};
        } else {
            const response = await getMyFile(id);
            return response.data ?? {}; 
        }
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
    return !loading && folders
        ? <Col span={24}>
            <FileExplorer
                folders={folders}
                path={path}
                setPath={setPath}
                getFiles={_getFiles}
                uploadFile={_uploadFile}
                downloadFile={_downloadFile}
                deleteFile={_deleteFile}
                documentAcknowledge={_documentAcknowledge}
            />
        </Col>
        : <Spin/>
};

export default DocumentView;
