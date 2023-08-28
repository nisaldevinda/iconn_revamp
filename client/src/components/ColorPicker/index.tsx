import React, { useState } from 'react';
import { Button, Modal, Col, Row } from 'antd';
import { ChromePicker } from 'react-color';
import { BgColorsOutlined } from '@ant-design/icons';
import { ProFormText } from '@ant-design/pro-form';

interface ColorPickerProps {
  fieldName: string;
  label: string;
  readOnly: boolean;
  value: string;
  onChange: (value: any) => void;
};

const ColorPicker = (props: ColorPickerProps) => {

  const { fieldName, label, onChange, value, readOnly } = props;

  const [selectedColor, setSelectedColor] = useState(value);
  const [visible, setVisible] = useState(false);

  const handleColorChange = (color) => {
    setSelectedColor(color.hex);
    onChange(color.hex);
  };

  return (
    <Col data-key={fieldName} span={12}>
      <Row style={{ borderWidth: 5, borderColor: 'red' }}>
        <ProFormText
          style={{ backgroundColor: selectedColor }}
          width="md"
          name={fieldName}
          label={label}
          key={fieldName}
          disabled={readOnly}
          fieldProps={{
            value: selectedColor,
          }}
        />
        <Button type="default" onClick={() => setVisible(true)} style={{ alignSelf: 'center', marginTop: 5 }}>
          <BgColorsOutlined />
        </Button>
      </Row>
      <Modal
        title="Select Color"
        visible={visible}
        onOk={() => setVisible(false)}
        onCancel={() => setVisible(false)}
      >
        <ChromePicker
          color={selectedColor}
          onChange={handleColorChange}
        />
      </Modal>
    </Col>
  );
};

export default ColorPicker;