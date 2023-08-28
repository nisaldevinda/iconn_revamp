import Icon from '@ant-design/icons';
import ConfirmationDueDate from '../../assets/customIcon/confirmation-due-date.svg';
import ResignationNoticePeriod from '../../assets/customIcon/resignation-notice-period.svg';
import RetirementDate from '../../assets/customIcon/retirement-date.svg';
import Status from '../../assets/customIcon/status.svg';

export type CustomIconProps = {
    icon: string,
    height?: number,
    width?: number
};

const CustomIcon: React.FC<CustomIconProps> = (props) => {
    switch (props.icon) {
        case 'ConfirmationDueDate': return <Icon component={() => <img src={ConfirmationDueDate} height={props.height ?? 24} width={props.width ?? 24} />} />
        case 'ResignationNoticePeriod': return <Icon component={() => <img src={ResignationNoticePeriod} height={props.height ?? 24} width={props.width ?? 24} />} />
        case 'RetirementDate': return <Icon component={() => <img src={RetirementDate} height={props.height ?? 24} width={props.width ?? 24} />} />
        case 'Status': return <Icon component={() => <img src={Status} height={props.height ?? 24} width={props.width ?? 24} />} />
        default: return <></>
    }
};

export default CustomIcon;
