import Icon from "@ant-design/icons";
import { Breadcrumb, Card, Divider ,Button ,Form ,message as Message} from "antd";
import _ from "lodash";
import React, { Dispatch, SetStateAction, useEffect, useState } from "react";
import FileList from "./FileList";
import FolderList from "./FolderList";
import Folder from '@/assets/FileExplorer/folder.svg';
import { Link, useIntl ,useLocation } from "umi";

export type FileExplorerProps = {
  folders: Array<DocumentFolderType>,
  baseRoute?: string; 
  path: Array<string>,
  setPath?: Dispatch<SetStateAction<Array<string>>>,
  getFiles: (data: any) => Promise<DocumentFileType>;
  uploadFile: (data: any) => Promise<DocumentFileType>;
  downloadFile: (id: number) => Promise<FileType>;
  deleteFile: (id: number) => Promise<FileType>;
  updateDocumentFile: (id: number,data: any) => Promise<DocumentFileType>;
  documentAcknowledge : (id: number,data:any) =>  Promise<DocumentFileType>;
  folderPath: Array<DocumentFileType>
};

export type RouteType = {
  key: string,
  title: string,
  route?: string
}

export type DocumentFolderType = {
  id?: number,
  folderId?: number,
  employeeId?: number,
  name: string,
  type?: 'COMPANY' | 'EMPLOYEE' | 'OTHER',
  parentId: number,
  slug: string
}

export type DocumentFileType = {
  id: number,
  documentName: string,
  documentDescription: string,
  folderId: number,
  employeeId: number,
  fileId: number,
}

export type FileType = {
  id: number,
  name: string,
  size: number,
  type: string,
  data: any
}

export type BreadcrumbItemType = {
  path: string | Array<string>,
  name: string,
  icon?: any
}

const FileExplorer: React.FC<FileExplorerProps> = (props) => {
  const intl = useIntl();
  const location = useLocation();
 
  const [type, setType] = useState<'FileList' | 'FolderList'>('FolderList');
  const [listData, setListData] = useState<Array<DocumentFolderType | DocumentFileType>>([]);
  const [breadcrumbItems, setBreadcrumbItems] = useState<Array<BreadcrumbItemType>>([]);

  useEffect(() => {
      init();
  }, [props.path]);

  useEffect(()=>{
    if(location.search === '?documents') {
      props.setPath(['employee-acknowledge document'])
    }
  },[]);

  const init = async () => {
    if (_.isEmpty(props.path)) {
      const folders = props.folders.filter(folder => folder.parentId == 0);
      setType('FolderList');
      setListData(folders);
      setBreadcrumbItems([]);
    } else {
      const currentFolder = props.folders.find(folder => folder.slug == props.path.slice(-1)[0]);
      if (!currentFolder) return;

      const folders = props.folders.filter(folder => folder.parentId == currentFolder.folderId);

      if (!_.isEmpty(folders)) {
        setType('FolderList');
        setListData(folders);
      } else {
        setType('FileList');
        setListData([]);
      }

      if (props.setPath) {
        let previousPath: Array<string> = [];
        let _breadcrumbItems:Array<BreadcrumbItemType> = [{
          path: [...previousPath],
          name: intl.formatMessage({ id: 'folders', defaultMessage: 'Folders' }),
          icon: folderIcon
        }];

        _breadcrumbItems = _breadcrumbItems.concat(props.path.map(slug => {
          const folder = props.folders.find(folder => folder.slug == slug);
          previousPath.push(slug);
          return {
            path: [...previousPath],
            name: folder?.name ?? slug
          };
        }));

        setBreadcrumbItems(_breadcrumbItems);
      } else if (props.baseRoute) {
        let previousPath = props.baseRoute;
        let _breadcrumbItems:Array<BreadcrumbItemType> = [{
          path: previousPath,
          name: intl.formatMessage({ id: 'folders', defaultMessage: 'Folders' }),
          icon: folderIcon
        }];

        _breadcrumbItems = _breadcrumbItems.concat(props.path.map(slug => {
          const folder = props.folders.find(folder => folder.slug == slug);
          previousPath = previousPath.concat('/').concat(slug);
          return {
            path: previousPath,
            name: folder?.name ?? slug
          };
        }));

        setBreadcrumbItems(_breadcrumbItems);
      }
    }
  }

  const listView = () => {
    switch (type) {
      case 'FileList':
        return <FileList
          getFiles={props.getFiles}
          uploadFile={props.uploadFile}
          deleteFile={props.deleteFile}
          downloadFile={props.downloadFile}
          source = 'documentManager'
          updateDocumentFile={props.updateDocumentFile}
          documentAcknowledge={props.documentAcknowledge}
          folderPath={props.path}
        />
      default:
        return <FolderList
          data={listData}
          path={props.path}
          setPath={props.setPath}
        />
    }
  };

  const folderIcon = <Icon component={() => <img src={Folder} height={24} width={24} style={{marginBottom: 6, marginRight: 10}} />} />;

  const breadcrumb = () => {
    return <Breadcrumb>
      {breadcrumbItems.map(item => {
        if (_.isArray(item.path)) {
          return <Breadcrumb.Item><a onClick={() => {props.setPath(item.path)}}>{item.icon ?? null}{item.name}</a></Breadcrumb.Item>
        } else {
          return <Breadcrumb.Item><Link to={`/${item.path}`}>{item.icon ?? null}{item.name}</Link></Breadcrumb.Item>
        }
      })}
    </Breadcrumb>
  }
  
  return( 
    _.isEmpty(props.path) ?(
        <Card> 
          {listView()}
        </Card> ) : (
      <Card
        title={breadcrumb()}
      >
        {listView()}
      </Card>
      )
  )

};

export default FileExplorer;
