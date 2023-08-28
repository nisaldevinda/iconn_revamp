import { Col, Row, Skeleton as AntSkeleton, Card } from 'antd';

const Skeleton = () => {
    return (<>
        <Row>
            <Col span={24}>
                <Card style={{ marginTop: 24 }}>
                    <AntSkeleton active avatar paragraph={{ rows: 3 }} />
                </Card>
            </Col>
        </Row>
        <Row gutter={10}>
            <Col span={12}>
                <Card style={{ marginTop: 24 }}>
                    <AntSkeleton active paragraph={{ rows: 3 }} />
                </Card>
            </Col>
            <Col span={12}>
                <Card style={{ marginTop: 24 }}>
                    <AntSkeleton active paragraph={{ rows: 3 }} />
                </Card>
            </Col>
        </Row>
    </>);
};

export default Skeleton;
