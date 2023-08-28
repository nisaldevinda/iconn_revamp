import { Card } from 'antd'
import { useDrag } from 'react-dnd'
import Icon from '@ant-design/icons';

export const FieldType: React.FC<any> = ({fieldType}) => {
    const [{ isDragging }, dragRef] = useDrag({
        type: 'fieldType',
        item: { type: 'fieldType', fieldType },
        collect: (monitor) => ({
            isDragging: monitor.isDragging()
        })
    })
    return (
        <div ref={dragRef}>
            <Card style={{ margin: 4 }}>
                <Icon component={fieldType.icon} style={{marginRight: 4}} />
                {fieldType.title}
            </Card>
        </div>
    )
}