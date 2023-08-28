import { Avatar, Card, Row } from "antd";
import { FormattedMessage, Link, useAccess } from "umi";
import MenuLinkIcon from '../../assets/menu-link.svg';

const GeneralSettingsCard: React.FC = () => {
    const access = useAccess();
    const { hasPermitted } = access;

    return <Card
        style={{ width: '105%', marginBottom: '7%' }}
        title={
            <>
                <Avatar src={MenuLinkIcon} size={30} />
                <span style={{ paddingLeft: '5%' }}>
                    <FormattedMessage
                        id="settings.GeneralSettings"
                        defaultMessage="General Settings"
                    />
                </span>
            </>
        }
    >
        {hasPermitted('bulk-upload-read-write') ? (
            <Row>
                <Link data-key="bulkUpload" to="/settings/bulk-upload">
                    <FormattedMessage id="settings.BulkUpload" defaultMessage="Bulk Upload" />
                </Link>
            </Row>
        ) : (
            <></>
        )}

        {hasPermitted('company-info-read-write') ? (
            <Row>
                <Link data-key="companyInformation" to="/settings/edit-company-info">
                    <FormattedMessage
                        id="settings.CompanyInformation "
                        defaultMessage="Company Information"
                    />
                </Link>
            </Row>
        ) : (
            <></>
        )}

        {hasPermitted('document-template-read-write') ? (
            <Row>
                <Link data-key="documentTemplates" to="/settings/document-templates">
                    <FormattedMessage
                        id="settings.DocumentTemplates "
                        defaultMessage="Letter Template Builder"
                    />
                </Link>
            </Row>
        ) : (
            <></>
        )}

        {hasPermitted('manual-process') ? (
            <Row>
                <Link data-key="manualProcesses" to="/settings/manual-processes">
                    <FormattedMessage
                        id="settings.ManualProcesses "
                        defaultMessage="Manual Processes"
                    />
                </Link>
            </Row>
        ) : (
            <></>
        )}
        {hasPermitted('workflow-management-read-write') ? (
            <Row>
                <Link to="/settings/workflow-engine">
                    <FormattedMessage
                        id="settings.WorkflowManagement"
                        defaultMessage="Workflow Management"
                    />
                </Link>
            </Row>
        ) : (
            <></>
        )}
        {hasPermitted('scheduled-jobs-log') ? (
            <Row>
                <Link to="/settings/scheduled-jobs-log">
                    <FormattedMessage
                        id="settings.ScheduledJobsLog"
                        defaultMessage="Scheduled Jobs Log"
                    />
                </Link>
            </Row>
        ) : (
            <></>
        )}
        {hasPermitted('azure-active-directory') ? (
            <Row>
                <Link
                    data-key="importUsersFromAzureActiveDirectory"
                    to="/settings/azure-active-directory"
                >
                    <FormattedMessage
                        id="settings.AzureActiveDirectory"
                        defaultMessage="Azure Active Directory"
                    />
                </Link>
            </Row>
        ) : (
            <></>
        )}

        {hasPermitted('email-template-read-write') ? (
            <Row>
                <Link to="/settings/email-notifications">
                    <FormattedMessage
                        id="settings.emailNotifications"
                        defaultMessage="Email Notifications"
                    />
                </Link>
            </Row>
        ) : (
            <></>
        )}
        {hasPermitted('form-builder') ? (
            <Row>
                <Link to="/settings/form-builder">
                    <FormattedMessage id="settings.FormBuilder" defaultMessage="Form Builder" />
                </Link>
            </Row>
        ) : (
            <></>
        )}
        {hasPermitted('template-builder') ? (
            <Row>
                <Link to="/settings/template-builder">
                    <FormattedMessage
                        id="settings.TemplateBuilder"
                        defaultMessage="Template Builder"
                    />
                </Link>
            </Row>
        ) : (
            <></>
        )}
        {hasPermitted('template-builder') ? (
            <Row>
                <Link to="/settings/workflow-builder">
                    <FormattedMessage id="settings.WorkflowBuilder" defaultMessage="Workflow Builder" />
                </Link>
            </Row>
        ) : (
            <></>
        )}
        {hasPermitted('self-service-lock') ? (
            <Row>
                <Link to="/settings/self-service-lock">
                    <FormattedMessage id="settings.SelfServiceLock" defaultMessage="Self Service Lock" />
                </Link>
            </Row>
        ) : (
            <></>
        )}
        {/* {hasPermitted('config-resignation-process-read-write') ? (
            <Row>
                <Link to="/settings/config-resignation-process">
                    <FormattedMessage
                        id="settings.ConfigResignationProcess"
                        defaultMessage="Resignation Process"
                    />
                </Link>
            </Row>
        ) : (
            <></>
        )} */}
        {/* {hasPermitted('config-confirmation-process-read-write') ? (
            <Row>
                <Link to="/settings/config-confirmation-process">
                    <FormattedMessage
                        id="settings.ConfigConfirmationProcess"
                        defaultMessage="Confirmation Process"
                    />
                </Link>
            </Row>
        ) : (
            <></>
        )} */}
    </Card>
}

export default GeneralSettingsCard;
