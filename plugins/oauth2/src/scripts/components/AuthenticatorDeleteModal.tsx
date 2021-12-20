/**
 * @author Dominic Lacaille <dominic.lacaille@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

import { LoadStatus } from "@library/@types/api/core";
import ModalConfirm from "@library/modal/ModalConfirm";
import { useDeleteAuthenticator } from "@oauth2/AuthenticatorHooks";
import { IAuthenticator } from "@oauth2/AuthenticatorTypes";
import { t } from "@vanilla/i18n";
import * as React from "react";
import { useEffect } from "react";

interface IProps {
    authenticatorID: NonNullable<IAuthenticator["authenticatorID"]>;
    onDismiss: () => void;
}

export function AuthenticatorDeleteModal(props: IProps) {
    const { authenticatorID, onDismiss } = props;
    const { deleteState, deleteAuthenticator } = useDeleteAuthenticator(authenticatorID);

    const handleConfirm = async () => {
        await deleteAuthenticator();
        onDismiss();
    };

    useEffect(() => {
        if (deleteState.status === LoadStatus.SUCCESS && authenticatorID) {
            onDismiss();
        }
    }, [deleteState.status, onDismiss, authenticatorID]);

    return (
        <ModalConfirm
            isVisible={authenticatorID !== null}
            title={t("Delete authenticator")}
            confirmTitle={t("Delete")}
            onConfirm={handleConfirm}
            onCancel={props.onDismiss}
            isConfirmLoading={deleteState.status === LoadStatus.LOADING}
        >
            {t("Are you sure you want to delete this authenticator?")}
        </ModalConfirm>
    );
}
