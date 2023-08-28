import { Card, Modal } from 'antd'
import { useDrag } from 'react-dnd'
import Icon, { DeleteOutlined, ExclamationCircleOutlined } from '@ant-design/icons';
import fieldTypes from './fieldTypes';

export const Field: React.FC<any> = ({ field, onDelete }) => {
    const { confirm } = Modal;
    const [{ isDragging }, dragRef] = useDrag({
        type: 'field',
        item: { type: 'field', field },
        collect: (monitor) => ({
            isDragging: monitor.isDragging()
        })
    })
    return (
        <div ref={dragRef}>
            <Card style={{ margin: 4 }}>
                <Icon component={fieldTypes[field.type]?.icon} style={{ marginRight: 4 }} />
                {field.defaultLabel}
                <a style={{ float: 'right', marginBottom: 0 }} onClick={(event) => {
                    event.stopPropagation();
                    confirm({
                        title: 'Are you sure remove this field?',
                        icon: <ExclamationCircleOutlined />,
                        okText: 'Remove',
                        okType: 'danger',
                        cancelText: 'No',
                        onOk() {
                            onDelete(field.name);
                        }
                    });
                }}><DeleteOutlined /></a>
            </Card>
        </div>
    )
}