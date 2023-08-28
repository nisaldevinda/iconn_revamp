import React, { useState, useEffect } from 'react';
import { Card, Modal } from 'antd';
import { Editor } from '@tinymce/tinymce-react';
import ReactHtmlParser from "react-html-parser";
import { getNotice } from '@/services/notice';

export type TinyModelProps = {
  data?: any,
}

const TinyModelLayout: React.FC<TinyModelProps> = (props) => {
  const [visible, setVisible] = useState(false);
  const [attachment, setAttachment] = useState({});

  useEffect(() => {
    init();
  }, []);

  const init = async () => {
    if (props.data.attachmentId) {
      const response = await getNotice(props.data.id);
      console.log(response.data);
      console.log(response.data.attachment);
      setAttachment(response.data.attachment);
    }
  }

  return (
    <div>
      <Card
        onClick={() => setVisible(true)}
        style={{ backgroundColor: '#f2fdf3', borderColor: '#07b542', borderRadius: 10, marginBottom: 6 }}
        bodyStyle={{ padding: 6 }}>
        {props.data.topic}
      </Card>
      <Modal
        title={props.data.topic}
        centered
        visible={visible}
        footer={[]}
        onCancel={() => setVisible(false)}
        width={750}
      >
        {/* <Editor
          apiKey={TINY_API_KEY}
          initialValue={props.data.description}
          value={props.data.description}
          init={{
            height: 500,
            menubar: false,
            toolbar: false,
            branding: false,
            readonly: true,
            setup(editor) {
              editor.setMode('readonly');
            },
          }}
        /> */}
        <div className="wysiwyg" style={{
          display: 'block',
          height: 500,
          width: "100%",
          overflowY: 'auto',
        }}>
          {props.data.attachmentId && attachment
            ? <embed src={attachment.data} type="application/pdf" width="100%" height="100%"/>
            : <div className="preview">
              {props.data.description && ReactHtmlParser(props.data.description)}
            </div>
          }
        </div>
      </Modal>
    </div>
  );
};

export default TinyModelLayout;
