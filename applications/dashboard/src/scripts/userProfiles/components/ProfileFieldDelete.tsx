/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums, Inc.
 * @license Proprietary
 */

import React, { useEffect, useMemo } from "react";
import { StackingContextProvider } from "@vanilla/react-utils";
import ModalConfirm from "@library/modal/ModalConfirm";
import ModalSizes from "@library/modal/ModalSizes";
import { LoadStatus } from "@library/@types/api/core";
import Message from "@library/messages/Message";
import { messagesClasses } from "@library/messages/messageStyles";
import { ProfileField } from "@dashboard/userProfiles/types/UserProfiles.types";
import { IUserProfilesStoreState } from "@dashboard/userProfiles/state/UserProfiles.slice";
import { t } from "@vanilla/i18n";
import { Icon } from "@vanilla/icons";
import Translate from "@library/content/Translate";
import { frameVariables } from "@library/layout/frame/frameStyles";
import { Mixins } from "@library/styles/Mixins";
import { css } from "@emotion/css";
import { useSelector } from "react-redux";
import { useToast } from "@library/features/toaster/ToastContext";
import { useDeleteProfileField } from "@dashboard/userProfiles/state/UserProfiles.hooks";

interface IProps {
    field: ProfileField | null;
    close: () => void;
}

const confirmTextStyle = css({
    ...Mixins.padding({ top: frameVariables().spacing.padding }),
});

export function ProfileFieldDelete(props: IProps) {
    const { field, close } = props;
    const toast = useToast();
    const classesMessages = messagesClasses();
    const deleteProfileField = useDeleteProfileField();

    // toggle the visibility of the ModalConfirm
    const isVisible = useMemo<boolean>(() => {
        return Boolean(field);
    }, [field]);

    // process the deletion of the profile field
    const handleDeleteConfirm = () => {
        if (field) {
            deleteProfileField(field.apiName);
        }
    };

    // get the current status of the deletion status
    const deleteStatus = useSelector((storeState: IUserProfilesStoreState) => {
        if (!field) return LoadStatus.PENDING;

        return storeState.userProfiles.deleteStatusByApiName[field.apiName]?.status ?? LoadStatus.PENDING;
    });

    // display toast based on deletion status and close the modal
    useEffect(() => {
        if (field) {
            if (deleteStatus === LoadStatus.SUCCESS) {
                toast.addToast({
                    autoDismiss: true,
                    dismissible: true,
                    body: <Translate source={`The profile field "<0 />" was successfully deleted.`} c0={field.label} />,
                });
                close();
            } else if (deleteStatus === LoadStatus.ERROR) {
                toast.addToast({
                    autoDismiss: false,
                    dismissible: true,
                    body: (
                        <Translate source={`An error occurred in deleting profile field "<0 />".`} c0={field.label} />
                    ),
                });
                close();
            }
        }
    }, [field, deleteStatus]);

    return (
        <StackingContextProvider>
            <ModalConfirm
                isVisible={isVisible}
                size={ModalSizes.MEDIUM}
                title={t(`Delete Profile Field: "${field?.label}"`)}
                onCancel={close}
                onConfirm={handleDeleteConfirm}
                isConfirmLoading={deleteStatus === LoadStatus.LOADING}
                confirmTitle={t("Delete")}
                fullWidthContent
            >
                <Message
                    stringContents={t("This action cannot be undone")}
                    contents={<div className={classesMessages.content}>{t("This action cannot be undone")}</div>}
                    icon={<Icon className={classesMessages.icon} icon={"status-warning"} size={"compact"} />}
                />
                <p className={confirmTextStyle}>
                    <Translate
                        source={`Are you sure you want to delete the "<0 />" profile field?`}
                        c0={field?.label}
                    />
                </p>
                <p className={confirmTextStyle}>
                    {t(
                        "No new data for this field will be collected or displayed. Any data previously collected through this field will continue to be associated with community members. If this field was active, it will no longer display on user profiles.",
                    )}
                </p>
            </ModalConfirm>
        </StackingContextProvider>
    );
}

export default ProfileFieldDelete;
