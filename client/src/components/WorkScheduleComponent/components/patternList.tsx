import React, { useEffect, useState } from "react";
import { Select, Space,DatePicker,Form,Col,Badge ,Row ,Spin} from 'antd';
import moment from 'moment';
import { useIntl } from 'umi';

const { Option } = Select;

interface IProps {
    fieldsVal: any[],
    workPattern: any[], 
    refresh : any
    
}
const PatternList: React.FC<IProps> = ({fieldsVal,workPattern,onChange, refresh}) => { 
  const intl = useIntl();
  const [loading , setLoading] = useState(false);
  const [currentData , setCurrentData] = useState([]);
  useEffect(()=>{
   setLoading(true);
   setTimeout(loadingWeek, 500);
   
  },[refresh]);

  const loadingWeek =() =>{
   setCurrentData(fieldsVal);
   setLoading(false);

  }
  
      const data= !loading ? currentData.slice(0).reverse().map((item) => {
        return (
            <Space  style={{ display: 'flex', marginBottom: 8 }} align="baseline">
                {item.effectiveDate <= moment().format('YYYY-MM-DD')  ?  item.currentRecord  ? <Badge status="success" />:<Badge> &nbsp;&nbsp;&nbsp;</Badge> :<Badge status="warning" />}
               <Col span={12}>
                  <Form.Item
                    name='date'
                    label={intl.formatMessage({
                       id: 'effectiveDate',
                       defaultMessage: ' Effective Date',
                    })}
                   >
                     <DatePicker  
                       defaultValue={moment(item.effectiveDate)} 
                       style={{ width: 190 }} 
                       disabled={item.locationId}
                       onChange ={(value => {
                          onChange(value,item.id)
                         }) 
                        }
                       format={'DD-MM-YYYY'}
                      />
                  </Form.Item>
               </Col>
               <Col span={12}>
                  <Form.Item
                     name='patternId'
                     label={intl.formatMessage({
                        id: 'workpattern',
                        defaultMessage: ' Work Pattern',
                     })}
                  >
                     <Select
                        showSearch
                        style={{ width: 235 }}
                        placeholder="Select Pattern"
                        optionFilterProp="children"
                        defaultValue={item.workpatternId}
                        disabled={item.locationId}
                        onChange ={(value => {
                           onChange(value,item.id)
                           })
                        }
                     >
                        {workPattern.map((pattern) => {
                           return (
                              <Option key={pattern.id} value={pattern.id}>
                                {pattern.name}
                              </Option>
                            );
                           })
                        }
                     </Select>
                  </Form.Item>
               </Col>
               <Col span={2}>
                   <Form.Item hidden>
                     
                   </Form.Item>
                </Col>
            </Space>
         )
      }) : <Spin size='default' spinning={loading} />;
    
   return data;
    
};

export default PatternList;