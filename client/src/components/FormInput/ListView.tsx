import React, { useState, useEffect } from 'react';
import { List, Avatar, Col } from 'antd';
import { Link, useParams } from 'umi';
import { FileOutlined, EyeOutlined } from '@ant-design/icons';
import request from '@/utils/request';

interface IListItem {
  id: string;
  title: string;
  subTitle: string;
}

interface IProps {
  dataSourcs: string;
  dataMap: IListItem;
  disableLink: boolean;
  linkRoute: string;
  actions: string[];
}

interface IParams {
  id: string;
}

const ListView: React.FC<IProps> = (props: IProps) => {
  const { id } = useParams<IParams>();
  const { dataSourcs, dataMap, disableLink, linkRoute, actions } = props;
  const [loading, setLoading] = useState<boolean>(false);
  const [list, setList] = useState<IListItem[]>([]);

  const getLinkedRoute = (itemId: string) => {
    return `/employees/${id}/${linkRoute}/${itemId}`;
  };

  const getTitle = (itemId: string, itemTitle: string) => {
    if (disableLink) {
      return <a>{itemTitle}</a>;
    } else {
      const url = getLinkedRoute(itemId);
      return <Link to={url}>{itemTitle}</Link>;
    }
  };

  const getActions = (id: string) => {
    if (disableLink) {
      return [];
    } else {
      return actions.map((action: string) => {
        if (action === 'view') {
          return (
            <Link to={getLinkedRoute(id)}>
              <EyeOutlined />
            </Link>
          );
        } else {
          return [];
        }
      });
    }
  };

  useEffect(() => {
    const featchData = async (dataSourcs: string) => {
      try {
        setLoading(true);
        const { data } = await request(dataSourcs);
        const listData = data.map((item: any) => {
          const { id, title, subTitle } = dataMap;
          return {
            id: id ? item[id] : '',
            title: title ? item[title] : '',
            subTitle: subTitle ? item[subTitle] : '',
          };
        });
        setList(listData);
        setLoading(false);
      } catch (error) {
        console.log('error:', error);
      }
    };

    if (dataSourcs) {
      featchData(dataSourcs);
    }
  }, [dataSourcs]);

  return (
    <Col span={24}>
      <List
        loading={loading}
        itemLayout="horizontal"
        dataSource={list}
        renderItem={(item: IListItem) => (
          <List.Item actions={getActions(item.id)}>
            <List.Item.Meta
              avatar={<Avatar size="large" icon={<FileOutlined />} />}
              title={getTitle(item.id, item.title)}
              description={item.subTitle}
            />
          </List.Item>
        )}
      />
    </Col>
  );
};

export default ListView;
