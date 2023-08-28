import TerminationReasons from "./fragments/TerminationReason";
import PromotionTypes from './fragments/PromotionTypes';
import TransferTypes from './fragments/TransferTypes';
import ResignationTypes from './fragments/ResignationTypes';
import ConfirmationReasons from './fragments/ConfirmationReasons';
import ResignationProcessConfig from "@/pages/ResignationProcessConfig";

const routes = async (hasPermitted: (permission: string) => boolean) => {
    const staticMasterData = [
        {
            name: 'Promotion Type',
            component: <PromotionTypes />,
            key: 'promotion-type'
        },
        {
            name: 'Confirmation Reason',
            component: <ConfirmationReasons />,
            key: 'confirmation-reason'
        },
        {
            name: 'Transfer Type',
            component: <TransferTypes />,
            key: 'transfer-type'
        },
        {
            name: 'Resignation Type',
            component: <ResignationTypes />,
            key: 'resignation-type'
        },
        {
            name: 'Resignation Reason',
            component: <TerminationReasons />,
            key: 'resignation-reason'
        }
    ];

    if (hasPermitted('config-resignation-process-read-write')) {
        staticMasterData.push({
            name: 'Resignation Process Configuration',
            component: <ResignationProcessConfig hidePageContainer={true} />,
            key: 'config-resignation-process'
        });
    }

    return staticMasterData;
}

export default routes
