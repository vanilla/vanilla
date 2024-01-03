/**
 * @author Mihran Abrahamian <mabrahamian@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import { DashboardFormGroup } from "@dashboard/forms/DashboardFormGroup";
import UserPreferencesClasses from "@dashboard/userPreferences/UserPreferences.classes";
import { dashboardClasses } from "@dashboard/forms/dashboardStyles";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import Frame from "@library/layout/frame/Frame";
import FrameBody from "@library/layout/frame/FrameBody";
import FrameFooter from "@library/layout/frame/FrameFooter";
import FrameHeader from "@library/layout/frame/FrameHeader";
import ButtonLoader from "@library/loaders/ButtonLoader";
import Modal from "@library/modal/Modal";
import ModalSizes from "@library/modal/ModalSizes";
import {
    NotificationPreferencesContextProvider,
    api,
    useNotificationPreferencesContext,
} from "@library/notificationPreferences";
import { ITabData, Tabs } from "@library/sectioning/Tabs";
import { TabsTypes } from "@library/sectioning/TabsTypes";
import { t } from "@vanilla/i18n";
import React, { ComponentProps, useState } from "react";

import { DashboardFormControl } from "@dashboard/forms/DashboardFormControl";
import { DashboardCheckGroup } from "@dashboard/forms/DashboardRadioGroups";
import { useToast } from "@library/features/toaster/ToastContext";
import Heading from "@library/layout/Heading";
import ModalConfirm from "@library/modal/ModalConfirm";
import { INotificationPreferences, utils } from "@library/notificationPreferences";
import { ISectionProps, JsonSchema, JsonSchemaForm } from "@vanilla/json-schema-forms";
import { useFormik } from "formik";
import { defaultNotificationPreferencesFormClasses } from "@dashboard/userPreferences/DefaultNotificationPreferences/DefaultNotificationPreferences.classes";

export default function DefaultNotificationPreferences() {
    const [modalVisible, setModalVisible] = useState(false);

    function openModal() {
        setModalVisible(true);
    }

    function closeModal() {
        setModalVisible(false);
    }

    return (
        <>
            <div className={dashboardClasses().buttonRow}>
                <div className="label-wrap">
                    <Heading depth={3}>{t("Default Notification Preferences")}</Heading>
                    <p>
                        {t(
                            "When new users register, they will be subscribed to the following notification preferences by default. Users can customize their preferences in their profile settings.",
                        )}
                    </p>
                </div>
                <Button
                    onClick={() => {
                        openModal();
                    }}
                >
                    {t("Edit Default Notifications")}
                </Button>
            </div>

            <NotificationPreferencesContextProvider userID={"defaults"} api={api}>
                <DefaultNotificationPreferencesModal isVisible={modalVisible} exitHandler={() => closeModal()} />
            </NotificationPreferencesContextProvider>
        </>
    );
}

export function DefaultNotificationPreferencesModal(
    props: Pick<ComponentProps<typeof Modal>, "isVisible" | "exitHandler">,
) {
    const classes = defaultNotificationPreferencesFormClasses();

    const { isVisible } = props;
    const toast = useToast();

    const [confirmDialogVisible, setConfirmDialogVisible] = useState(false);

    function openConfirmDialog() {
        setConfirmDialogVisible(true);
    }

    function closeConfirmDialog() {
        setConfirmDialogVisible(false);
    }

    const { schema, preferences, editPreferences } = useNotificationPreferencesContext();

    const dataIsReady = !!schema?.data && !!preferences?.data;

    const notificationsSchema = schema?.data?.properties?.notifications;

    const { values, submitForm, setValues, isSubmitting, dirty, resetForm } = useFormik<INotificationPreferences>({
        initialValues: preferences?.data ?? {},
        onSubmit: async function (values, { resetForm }) {
            try {
                await editPreferences(values, {
                    onSuccess: () => {
                        toast.addToast({
                            autoDismiss: true,
                            body: <>{t("Success! Your changes were saved.")}</>,
                        });
                        props.exitHandler?.();
                        resetForm();
                    },
                    onError: (e) => {
                        toast.addToast({
                            dismissible: true,
                            body: <>{t(e.message)}</>,
                        });
                    },
                });
            } catch (e) {
                resetForm();
            }
        },
        enableReinitialize: true,
    });

    function handleClose() {
        closeConfirmDialog();
        props.exitHandler?.();
        resetForm();
    }

    const groups: ITabData[] = Object.values(notificationsSchema?.properties ?? {}).map((groupSchema: JsonSchema) => {
        return {
            label: groupSchema["x-control"].label,
            contents: (
                <JsonSchemaForm
                    instance={utils.mapNotificationPreferencesToSchemaLikeStructure(groupSchema, values)}
                    schema={groupSchema}
                    FormControl={DashboardFormControl}
                    FormSection={NotificationPreferencesFormSection}
                    onChange={(newValues) => {
                        setValues({
                            ...values,
                            ...newValues,
                        });
                    }}
                />
            ),
        };
    });

    return dataIsReady ? (
        <>
            <Modal isVisible={isVisible} exitHandler={dirty ? openConfirmDialog : handleClose} size={ModalSizes.LARGE}>
                <form
                    onSubmit={(e) => {
                        e.preventDefault();
                        submitForm();
                    }}
                    aria-label={t("Default Notification Preferences")}
                >
                    <Frame
                        header={
                            <FrameHeader
                                closeFrame={() => {
                                    dirty ? openConfirmDialog() : handleClose();
                                }}
                                title={t("Edit Default Notification Preferences")}
                            />
                        }
                        body={
                            <FrameBody className={UserPreferencesClasses().frameBody}>
                                <Tabs
                                    extendContainer
                                    largeTabs
                                    tabClass={classes.tab}
                                    tabType={TabsTypes.BROWSE}
                                    data={groups}
                                    includeVerticalPadding={false}
                                />
                            </FrameBody>
                        }
                        footer={
                            <FrameFooter justifyRight>
                                <Button
                                    buttonType={ButtonTypes.TEXT}
                                    onClick={() => {
                                        dirty ? openConfirmDialog() : handleClose();
                                    }}
                                >
                                    {t("Cancel")}
                                </Button>
                                <Button submit buttonType={ButtonTypes.TEXT_PRIMARY}>
                                    {isSubmitting ? <ButtonLoader /> : t("Save")}
                                </Button>
                            </FrameFooter>
                        }
                    />
                </form>
            </Modal>
            <ModalConfirm
                isVisible={confirmDialogVisible}
                title={t("Unsaved Changes")}
                onCancel={() => {
                    closeConfirmDialog();
                }}
                onConfirm={() => {
                    handleClose();
                }}
                confirmTitle={t("Exit")}
            >
                {t("You have unsaved changes. Are you sure you want to exit without saving?")}
            </ModalConfirm>
        </>
    ) : (
        <></>
    );
}

const NotificationPreferencesFormSection: React.ComponentType<ISectionProps> = (props) => {
    const classes = defaultNotificationPreferencesFormClasses();

    return props.pathString === "/" && !!props.title && !!props.description ? (
        <>
            <div className="form-group">
                <div className="label-wrap-wide">
                    <Heading depth={5} className={classes.sectionHeading}>
                        {props.title}
                    </Heading>
                    <p dangerouslySetInnerHTML={{ __html: props.description }} />
                </div>
            </div>
            {props.children}
        </>
    ) : (
        <>
            {props.description && !props.title ? (
                <DashboardFormGroup fieldset label={props.description}>
                    <DashboardCheckGroup>{props.children}</DashboardCheckGroup>
                </DashboardFormGroup>
            ) : (
                <>{props.children}</>
            )}
        </>
    );
};
