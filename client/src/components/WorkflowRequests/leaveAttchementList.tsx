import React, { useEffect, useState } from 'react';
import { Col, Row, List } from 'antd';
import { FormattedMessage } from 'react-intl';

import {  DownloadOutlined, PaperClipOutlined } from '@ant-design/icons';
import './index.less';

type LeaveAttachemtnProps = {
    attachementList: any,
    
};

const LeaveAttachmnetList: React.FC<LeaveAttachemtnProps> = (props) => {
    const [attachementSet, setAttachementSet] = useState<any>(props.attachementList);


    return (
        <>
        <Row style={{width: 1200}}>
            <Col span={16} style={{ paddingBottom: 20 }}>
                <Row><Col style={{ fontWeight :800, fontSize: 16, color: 'rgb(57, 66, 65)'}} ><FormattedMessage id="attachedDocuments" defaultMessage="Attachments:" /></Col></Row>
                <Row><Col style={{ width: '100%', }}>
                
                <List
                    itemLayout="horizontal"
                    dataSource={attachementSet}
                    size = {'small'}
                    split = {false}
                    renderItem={item => (
                    <List.Item style={{color: 'gray'}} >
                        <List.Item.Meta 
                        style={{color: 'gray'}}
                        avatar={<PaperClipOutlined/>}
                        title={<a href={item.data} download={item.name} >{item.name}<DownloadOutlined style={{marginLeft: 10, fontSize: 14, color: '#86C129'}}></DownloadOutlined></a>}    
                        />  
                    </List.Item>
                    )}
                />
                </Col></Row>
            </Col>
        </Row>
        </>
    );
};

export default LeaveAttachmnetList;
