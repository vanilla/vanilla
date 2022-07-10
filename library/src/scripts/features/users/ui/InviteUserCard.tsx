/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useEffect, useState } from "react";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import Frame from "@library/layout/frame/Frame";
import FrameHeader from "@library/layout/frame/FrameHeader";
import FrameBody from "@library/layout/frame/FrameBody";
import LazyModal from "@library/modal/LazyModal";
import ModalSizes from "@library/modal/ModalSizes";
import { t } from "@vanilla/i18n";
import { IComboBoxOption } from "@library/features/search/SearchBar";
import MultiUserInput from "@library/features/users/MultiUserInput";
import InputTextBlock, { InputTextBlockBaseClass } from "@library/forms/InputTextBlock";
import Checkbox from "@library/forms/Checkbox";
import { userCardClasses } from "@library/features/users/ui/inviteUserCardStyles";
import Permission, { hasPermission, PermissionMode } from "@library/features/users/Permission";
import { IApiError } from "@library/@types/api/core";
import ErrorMessages from "@library/forms/ErrorMessages";

interface IProps {
    defaultUsers: IComboBoxOption[];
    updateStoreInvitees: (invitees: IComboBoxOption[]) => void;
    inputEmails: string;
    updateStoreEmails: (emails: string) => void;
    sentInvitations: () => void;
    visible: boolean;
    closeModal: () => void;
    errors?: IApiError | undefined;
}

export default function InviteUserCard(props: IProps) {
    const {
        defaultUsers,
        inputEmails,
        updateStoreEmails,
        updateStoreInvitees,
        sentInvitations,
        visible,
        closeModal,
        errors,
    } = props;

    const hasEmailInvitePermission = hasPermission("emailInvitations.add");
    const [boxChecked, setBoxChecked] = useState(false);

    const classes = userCardClasses();

    return (
        <LazyModal
            isVisible={visible}
            size={ModalSizes.MEDIUM}
            exitHandler={() => {
                closeModal;
                setBoxChecked(false);
            }}
        >
            <Frame
                header={
                    <FrameHeader
                        title={t("Invite Members")}
                        closeFrame={() => {
                            closeModal();
                            setBoxChecked(false);
                        }}
                    />
                }
                body={
                    <FrameBody className={classes.body}>
                        <p className={classes.message}>{t("Invite one or more people to join this group.")}</p>

                        <MultiUserInput
                            label={"Usernames"}
                            onChange={(invitees) => {
                                updateStoreInvitees(invitees);
                            }}
                            value={defaultUsers}
                            /** FIXME: This maxHeight value prevents blur by stopping
                             * dropdowns from overflowing with the modal
                             * Remove with https://github.com/vanilla/vanilla-cloud/issues/3155
                             */
                            maxHeight={200}
                        />
                        <Permission permission={"emailInvitations.add"} mode={PermissionMode.GLOBAL}>
                            {hasEmailInvitePermission && (
                                <InputTextBlock
                                    baseClass={InputTextBlockBaseClass.CUSTOM}
                                    className={classes.textbox}
                                    label={t("Emails")}
                                    inputProps={{
                                        value: inputEmails,
                                        onChange: (e) => {
                                            updateStoreEmails(e.target.value);
                                        },

                                        multiline: true,
                                        placeholder: t("Type or paste emails separated by commas."),
                                    }}
                                    multiLineProps={{
                                        overflow: "scroll",
                                        rows: 5,
                                        maxRows: 5,
                                    }}
                                    errors={errors?.response.data?.errors?.emails}
                                />
                            )}
                            {inputEmails.trim() && (
                                <Checkbox
                                    labelBold={false}
                                    checked={boxChecked}
                                    label={t("I confirm that I have permission to use the email addresses provided.")}
                                    onChange={() => setBoxChecked(!boxChecked)}
                                />
                            )}
                        </Permission>
                        {!errors?.response.data?.errors && errors?.response.data?.message && (
                            <ErrorMessages padded errors={[{ message: errors?.response.data?.message }]} />
                        )}
                        <div className={classes.buttonGroup}>
                            <Button
                                className={classes.button}
                                buttonType={ButtonTypes.STANDARD}
                                onClick={() => {
                                    setBoxChecked(false);
                                    closeModal();
                                }}
                            >
                                {t("Cancel")}
                            </Button>
                            <Button
                                disabled={hasEmailInvitePermission && !!inputEmails.trim() && !boxChecked}
                                className={classes.button}
                                buttonType={ButtonTypes.PRIMARY}
                                onClick={() => {
                                    setBoxChecked(false);
                                    sentInvitations();
                                }}
                            >
                                {t("Invite")}
                            </Button>
                        </div>
                    </FrameBody>
                }
            />
        </LazyModal>
    );
}
