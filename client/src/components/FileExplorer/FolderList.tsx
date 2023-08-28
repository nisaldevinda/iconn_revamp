import { List } from 'antd';
import { Dispatch, SetStateAction } from 'react';
import { Link } from 'umi';
import Folder from '@/assets/FileExplorer/folder.svg';
import Icon from '@ant-design/icons';
import { useLocation } from 'react-router-dom';

export type FolderListProps = {
  data: any;
  path?: Array<string>;
  setPath?: Dispatch<SetStateAction<string[]>>;
};

const FolderList: React.FC<FolderListProps> = (props) => {
  const location = useLocation();
  return (
    <List
      dataSource={props.data}
      renderItem={(item) => (
        <List.Item key={item.id}>
          <List.Item.Meta
            avatar={
              <Icon
                component={() => (
                  <img src={Folder} height={24} width={24} style={{ marginBottom: 6 }} />
                )}
              />
            }
            title={
              props.path && props.setPath ? (
                <a onClick={() => props.setPath([...props.path, item.slug])}> {item.name} </a>
              ) : (
                <Link to={`${location.pathname}/${item.slug}`}> {item.name} </Link>
              )
            }
          />
        </List.Item>
      )}
    />
  );
};

export default FolderList;
