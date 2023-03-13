/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import { cx } from "@emotion/css";
import { AccountSettingType } from "@library/accountSettings/AccountSettingsDetail";
import { EditEmail } from "@library/accountSettings/forms/EditEmail";
import { EditUsername } from "@library/accountSettings/forms/EditUsername";
import { EditPassword } from "@library/accountSettings/forms/EditPassword";
import { ErrorBoundary } from "@library/errorPages/ErrorBoundary";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import Frame from "@library/layout/frame/Frame";
import FrameBody from "@library/layout/frame/FrameBody";
import { frameBodyClasses } from "@library/layout/frame/frameBodyStyles";
import FrameFooter from "@library/layout/frame/FrameFooter";
import { frameFooterClasses } from "@library/layout/frame/frameFooterStyles";
import FrameHeader from "@library/layout/frame/FrameHeader";
import ButtonLoader from "@library/loaders/ButtonLoader";
import Modal from "@library/modal/Modal";
import ModalConfirm from "@library/modal/ModalConfirm";
import ModalSizes from "@library/modal/ModalSizes";
import { useUniqueID } from "@library/utility/idUtils";
import { t } from "@vanilla/i18n";
import React, { useEffect, useMemo, useRef, useState } from "react";

export interface IAccountSettingFormHandle {
    onSave(): void;
}

interface IProps {
    /** The externally controlled visibility of the modal */
    visibility: boolean;
    /** Function to update parent component of visibility state changes */
    onVisibilityChange(visibility: boolean): void;
    /** The kind of bulk action that needs to be performed */
    editType: AccountSettingType | null;
}

export interface IAccountModalForm {
    setIsSaving(isSaving: boolean): void;
    setIsFormDirty(isDirty: boolean): void;
    setIsSuccess(isSuccess: boolean): void;
}

/**
 * A generic modal for all Account Setting edit workflows
 */
export function AccountSettingsModal(props: IProps) {
    const { editType, visibility, onVisibilityChange } = props;

    const [confirmExit, setConfirmExit] = useState(false);
    const [saveState, setSaveState] = useState(false);
    const [formDirtyState, setFormDirty] = useState(false);
    const [isSaveSuccessful, setSaveIsSuccessful] = useState<boolean | null>(null);

    const contentFormHandleRef = useRef<IAccountSettingFormHandle>(null);

    // Boilerplate for the modal
    const titleID = useUniqueID("editUserProfile_modal");
    const classFrameFooter = frameFooterClasses();
    const classesFrameBody = frameBodyClasses();

    const Content = useMemo(() => {
        if (editType) {
            return (
                {
                    [AccountSettingType.USERNAME]: EditUsername,
                    [AccountSettingType.EMAIL]: EditEmail,
                    [AccountSettingType.PASSWORD]: EditPassword,
                }[editType] ?? null
            );
        }
        return null;
    }, [editType]);

    const modalTitle = useMemo<string>(() => {
        switch (editType) {
            case AccountSettingType.USERNAME: {
                return t("Edit username");
            }
            case AccountSettingType.EMAIL: {
                return t("Edit email");
            }
            case AccountSettingType.PASSWORD: {
                return t("Change password");
            }
            default: {
                return t("Edit");
            }
        }
    }, [editType]);

    useEffect(() => {
        if (isSaveSuccessful) {
            closeModal();
        }
    }, [isSaveSuccessful, onVisibilityChange]);

    const closeModal = () => {
        onVisibilityChange && onVisibilityChange(false);
        setConfirmExit(false);
        setSaveState(false);
        setFormDirty(false);
        setSaveIsSuccessful(null);
    };

    return (
        <div>
            <Modal
                isVisible={visibility}
                size={ModalSizes.SMALL}
                exitHandler={() => {
                    if (formDirtyState) {
                        setConfirmExit(true);
                    } else {
                        closeModal();
                    }
                }}
                titleID={titleID}
            >
                <form
                    onSubmit={(e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        contentFormHandleRef.current?.onSave();
                    }}
                >
                    <Frame
                        header={<FrameHeader titleID={titleID} closeFrame={closeModal} title={modalTitle} />}
                        body={
                            <FrameBody>
                                <div className={cx("frameBody-contents", classesFrameBody.contents)}>
                                    <ErrorBoundary>
                                        {Content && (
                                            <Content
                                                setIsSaving={setSaveState}
                                                setIsFormDirty={setFormDirty}
                                                setIsSuccess={setSaveIsSuccessful}
                                                ref={contentFormHandleRef}
                                            />
                                        )}
                                    </ErrorBoundary>
                                </div>
                            </FrameBody>
                        }
                        footer={
                            <FrameFooter justifyRight>
                                <Button
                                    className={classFrameFooter.actionButton}
                                    buttonType={ButtonTypes.TEXT}
                                    onClick={closeModal}
                                >
                                    {t("Cancel")}
                                </Button>
                                <Button
                                    type="submit"
                                    className={classFrameFooter.actionButton}
                                    buttonType={ButtonTypes.TEXT_PRIMARY}
                                >
                                    {saveState ? <ButtonLoader /> : t("Save")}
                                </Button>
                            </FrameFooter>
                        }
                    />
                </form>
            </Modal>
            <ModalConfirm
                isVisible={confirmExit}
                title={t("Unsaved Changes")}
                onCancel={() => {
                    setConfirmExit(false);
                }}
                onConfirm={() => {
                    setConfirmExit(false);
                    closeModal();
                }}
                confirmTitle={t("Exit")}
            >
                {t(
                    "You have unsaved changes and your work will be lost. Are you sure you want to continue without saving?",
                )}
            </ModalConfirm>
        </div>
    );
}
