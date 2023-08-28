import React, { useState } from 'react';
import _ from "lodash";
import { Form, Col, FormInstance, } from "antd";
import { useIntl } from "react-intl";
import CountryPhoneInput, { CountryPhoneInputValue, ConfigProvider, } from 'antd-country-phone-input';
import en from 'world_countries_lists/data/en/world.json';
import 'flagpack/dist/flagpack.css';
import './styles.css';

export type MobileInputProps = {
    modelName: string,
    fieldName: string,
    fieldNamePrefix?: string;
    fieldDefinition: {
        labelKey: string,
        defaultLabel: string,
        type: string,
        isEditable: string,
        isSystemValue: string,
        validations: {
            isRequired: boolean,
            min: number,
            max: number
        },
        placeholderKey: string,
        defaultPlaceholder: string,
        defaultValue: string,
    },
    readOnly: boolean;
    values: {},
    setValues: (values: any) => void,
    recentlyChangedValue: any,
    form: FormInstance
};

const MobileInput: React.FC<MobileInputProps> = (props) => {
    const intl = useIntl();
    const fieldName = props.fieldNamePrefix
        ? props.fieldNamePrefix.concat(props.fieldName)
        : props.fieldName;
    const label = intl.formatMessage({
        id: `model.${props.modelName}.${props.fieldDefinition.labelKey}`,
        defaultMessage: props.fieldDefinition.defaultLabel,
    });

    const currentValues = { ...props.values };
    const phoneValue = currentValues[fieldName]?.split("-");
    const defaultPhoneCode = phoneValue && phoneValue[0] ? parseInt(phoneValue[0]) : 0;
    const defaultPhoneNumber = phoneValue && phoneValue[1] ? phoneValue[1] : '';
    const defaultValue = { code: defaultPhoneCode, phone: defaultPhoneNumber };
    const [validatedStatus, setValidateStatus] = useState<"" | "error">("");
    const [help, setHelp] = useState(' ');

    const regex = new RegExp(/^\d{10}$/);
    const isRequired = props.fieldDefinition.validations?.isRequired;

    const onChange = (content: CountryPhoneInputValue) => {
        const contentCode = content.code ?? 0;
        const phoneNo = contentCode + "-" + content.phone;

        const currentValue = { ...props.values };
        currentValue[fieldName] = phoneNo;
        props.setValues(currentValue);

        props.form.setFieldsValue(currentValue);

        mobileValidate(content);
    }

    const mobileValidate = (content: CountryPhoneInputValue) => {
        const validCountryCode = content.code && content.code !== 0 ? true : false;
        const validMobile = content.phone && content.phone.length === 10 && regex.test(content.phone) ? true : false;

        if (!validCountryCode && !validMobile) {
            if (isRequired) {
                setValidateStatus('error');
                setHelp("Enter valid country code and mobile no.");
                return true;
            } else if (content.phone && content.phone.length !== 0) {
                setValidateStatus('error');
                setHelp("Number need to be 10 numbers.");
                return true;
            }
        }
        else if (!validCountryCode) {
            setValidateStatus('error');
            setHelp("Enter valid country code.");
            return true;
        }
        else if (!validMobile) {
            if (isRequired || content.phone && content.phone.length !== 0) {
                setValidateStatus('error');
                setHelp("Number need to be 10 numbers.");
                return true;
            }
        }
        setValidateStatus('');
        setHelp('');
        return false;
    }



    const anyValidation = (rule, value, callback) => {
        const currentValue = { ...props.values };
        const mobile = currentValue[fieldName];
        const content: CountryPhoneInputValue = {
            code: parseInt(mobile?.split("-")[0] ?? 0),
            phone: mobile?.split("-")[1] ?? null
        }
        const isInvalid = mobileValidate(content);
        if (isInvalid) {
            callback('Required');
        } else {
            callback();
        }
    };

    return (
        <Col data-key={fieldName} span={12}>
            <Form.Item
                className="pro-field pro-field-md"
                validateStatus={validatedStatus}
                help={help}
                name={fieldName}
                label={label}
                required={isRequired}
                rules={[
                    { validator: anyValidation }
                ]}
            >
                <ConfigProvider
                    locale={en}
                    areaMapper={(area) => {
                        return {
                            ...area,
                            emoji: <span className={`fp ${area.short.toLowerCase()}`} />,
                        };
                    }}
                >
                    <CountryPhoneInput inline
                        type={'number'}
                        width="lg"
                        className="mobile_input"
                        style={{width:'100%'}}
                        value={defaultValue}
                        onChange={(content) => {
                            onChange(content);
                        }}
                        inputMode={'numeric'}
                        autoComplete='none'
                        disabled={props.readOnly}
                        selectProps={{
                            disabled: props.readOnly
                        }}
                        onKeyDown={ (evt) => (((evt.key === 'e') || (evt.key === '.') ) && evt.preventDefault() )}
                    />
                </ConfigProvider>
            </Form.Item>
        </Col>
    );
};

export default MobileInput;
