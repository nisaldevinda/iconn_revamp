import FileList, { DocumentFolderType } from "@/components/FileExplorer/FileList";
import { deleteFile, getFile, getFileList, getFolderHierarchy, uploadFile , updateDocumentFile ,documentAcknowledge , addFolder} from "@/services/documentManager";
import { getAllEmployee } from "@/services/employee";
import { PageContainer } from "@ant-design/pro-layout";
import { Spin ,Button ,message as Message ,Form} from "antd";
import _ from "lodash";
import React, { useEffect, useState } from "react";
import { useParams ,useIntl } from "umi";


const DocumentAcknowledgeProcess: React.FC = (props) => {
    const { slugs } = useParams<{slugs: string}>();

    const [loading, setLoading] = useState(false);
    const [folders, setFolders] = useState<Array<DocumentFolderType>>([]);
    const [path, setPath] = useState<Array<string>>([]);
    
    const intl = useIntl();

    useEffect(() => {
        init();
    }, []);

    useEffect(() => {
        setPath(slugs ? slugs.split('/') : []);
    }, [slugs]);

    const init = async () => {
        setLoading(true);

        if (_.isEmpty(folders)) {
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
                _folders = _folders.filter((folder) => folder.slug === 'company');
              
            }   
            setFolders(_folders);
        }
        
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
        const folderId =  1 ;
        const files = await getFileList( folderId, current.employee?.employeeId ,data);
        return files.data ?? [];
    }

    const _uploadFile = async (data: any) => {
        const current = extractCurrentFolderEmployee();
        const response = await uploadFile({
            ...data,
            folderId: 1,
            employeeId: current.employee?.employeeId,
        });

        return response.data ?? {};
    }
  
    const _updateDocumentFile = async (id: number,data: any) => {
        const current = extractCurrentFolderEmployee();
        const response = await updateDocumentFile(id,{
            ...data,
            folderId: 1,
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
    
   
    return !loading && folders ? 
        <PageContainer title="Document Manager">
            
            <FileList
              getFiles={_getFiles}
              uploadFile={_uploadFile}
              downloadFile={_downloadFile}
              source = 'documentAcknowledge'
              deleteFile={_deleteFile}
              updateDocumentFile={_updateDocumentFile}
              documentAcknowledge={_documentAcknowledge}
              folderPath={path}
            />
        </PageContainer>
    : <Spin/>;
};

export default DocumentAcknowledgeProcess;
