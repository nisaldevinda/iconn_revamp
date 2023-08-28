import { Card, Col, Row } from 'antd';
import React from 'react'
import { useDrop } from 'react-dnd';
import { Field } from './Field';
import fieldTypes from './fieldTypes';

export const Backet: React.FC<any> = ({ model, fields, onClick, onChange, onDelete }) => {
    const [{ isOver }, dropRef] = useDrop({
        accept: ['fieldType'],
        drop: (item) => onChange(item),
        collect: (monitor) => ({
            isOver: monitor.isOver()
        })
    })

    return (
        <React.Fragment>
            <div ref={dropRef} style={{ minHeight: 10 }}>
                <Row gutter={{ xs: 8, sm: 16, md: 24, lg: 32 }}>
                    {fields.map(fieldname => <Col span={12}>
                        {Object.keys(fieldTypes).includes(model.fields[fieldname]?.type)
                            ? <a onClick={() => onClick(model.fields[fieldname])}>
                                <Field onDelete={onDelete} field={model.fields[fieldname]} />
                            </a>
                            : <Card style={{ margin: 4 }}>Custom Field</Card>
                        }
                    </Col>)}
                    {isOver && <div>Drop Here!</div>}
                </Row>
            </div>
        </React.Fragment>
    )
}