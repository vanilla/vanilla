/**
 * @author Dominic Lacaille <dominic.lacaille@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license Proprietary
 */

import React, { useState } from "react";
import { DashboardHeaderBlock } from "@dashboard/components/DashboardHeaderBlock";
import { t } from "@vanilla/i18n";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { DashboardPagerArea } from "@dashboard/components/DashboardToolbar";
import { DashboardPager } from "@dashboard/components/DashboardPager";
import { AuthenticatorTableRow } from "@oauth2/components/AuthenticatorTableRow";
import { DashboardTable } from "@dashboard/tables/DashboardTable";
import { TableColumnSize } from "@dashboard/tables/DashboardTableHeadItem";
import { LoadStatus } from "@library/@types/api/core";
import { AuthenticatorDeleteModal } from "@oauth2/components/AuthenticatorDeleteModal";
import Loader from "@library/loaders/Loader";
import { EmptyAuthenticatorResults } from "@oauth2/components/EmptyAuthenticatorResults";
import { IAuthenticator } from "@oauth2/AuthenticatorTypes";
import OAuth2AddEdit from "@oauth2/pages/AuthenticatorAddEdit";
import Modal from "@library/modal/Modal";
import ModalSizes from "@library/modal/ModalSizes";
import Button from "@library/forms/Button";
import { useAuthenticators, useSetAuthenticatorActive } from "@oauth2/AuthenticatorHooks";

export default function ConnectionsIndexPage() {
    const { HeadItem } = DashboardTable;
    const setActive = useSetAuthenticatorActive();
    const [page, setPage] = useState<number>(1);
    const [isToggleDisabled, setIsToggleDisabled] = useState<boolean>(false);
    const [deleteID, setDeletingID] = useState<number | undefined>();
    const [editingID, setEditingID] = useState<number | undefined>();
    const [isFormVisible, setFormVisible] = useState<boolean>(false);

    const authenticators = useAuthenticators({ page, limit: 10, type: "oauth2" });
    const pagination = authenticators.data?.pagination;

    const makeOnChangeActive = (authenticatorID: number) => async (newValue: boolean) => {
        setIsToggleDisabled(true);
        await setActive(authenticatorID, newValue);
        setIsToggleDisabled(false);
    };

    const closeFormModal = () => setFormVisible(false);

    return (
        <>
            <DashboardHeaderBlock
                title={t("OAuth Connections")}
                actionButtons={
                    <>
                        <Button
                            style={{ marginLeft: "auto" }}
                            buttonType={ButtonTypes.DASHBOARD_PRIMARY}
                            onClick={() => {
                                setEditingID(0);
                                setFormVisible(true);
                            }}
                        >
                            {t("Add Connection")}
                        </Button>
                        <DashboardPagerArea style={{ marginLeft: 16 }}>
                            <DashboardPager page={page} hasNext={!!pagination?.next} onClick={setPage} />
                        </DashboardPagerArea>
                    </>
                }
            />
            <Modal isVisible={isFormVisible} size={ModalSizes.LARGE} exitHandler={closeFormModal}>
                <OAuth2AddEdit
                    authenticatorID={editingID}
                    onClose={() => {
                        setEditingID(undefined);
                        setFormVisible(false);
                    }}
                />
            </Modal>
            {deleteID !== undefined && (
                <AuthenticatorDeleteModal
                    authenticatorID={deleteID}
                    onDismiss={() => {
                        setDeletingID(undefined);
                    }}
                />
            )}
            <DashboardTable
                head={
                    <tr>
                        <HeadItem>{t("Name")}</HeadItem>
                        <HeadItem size={TableColumnSize.MD}>{t("Client ID")}</HeadItem>
                        <HeadItem size={TableColumnSize.XS}>{t("Actions")}</HeadItem>
                    </tr>
                }
                body={
                    !authenticators.data ? (
                        <tr>
                            <td>
                                <Loader />
                            </td>
                        </tr>
                    ) : (
                        Object.values(authenticators.data?.items).map((authenticator: IAuthenticator) => (
                            <AuthenticatorTableRow
                                key={authenticator.authenticatorID}
                                authenticator={authenticator}
                                disableToggle={isToggleDisabled}
                                onChangeActive={makeOnChangeActive(authenticator.authenticatorID!)}
                                onEditClick={() => {
                                    setEditingID(authenticator.authenticatorID);
                                    setFormVisible(true);
                                }}
                                onDeleteClick={() => {
                                    setDeletingID(authenticator.authenticatorID);
                                }}
                            />
                        ))
                    )
                }
            />
            {authenticators.status === LoadStatus.SUCCESS &&
                authenticators.data !== undefined &&
                authenticators.data.items.length === 0 && <EmptyAuthenticatorResults />}
        </>
    );
}
