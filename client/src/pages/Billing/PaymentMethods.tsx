import { FC } from 'react';
import visa from '../../assets/icons/paymentmethodsicons/visa.svg';
import master from '../../assets/icons/paymentmethodsicons/mastercard.svg';
import american from '../../assets/icons/paymentmethodsicons/americanexpress.svg';

interface Icons {
  [key: string]: FC;
}

const VisaIcon: FC = () => <img src={visa} alt="" className="me-4" />;
const MasterIcon: FC = () => <img src={master} alt="" className="me-4" />;
const AmericanExpressIcon: FC = () => <img src={american} alt="" className="me-4" />;

const icons: Icons = {
  visa: VisaIcon,
  mastercard: MasterIcon,
  amex: AmericanExpressIcon,
};

const PaymentMethods = (props: { icon: string | undefined }) => {
  if (props?.icon === undefined) {
    return <></>;
  }
  const Icon = icons[props.icon];
  if (!Icon) {
    return <></>;
  }
  return <Icon />;
};

export default PaymentMethods;
