/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { cx } from "@emotion/css";
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
import ModalSizes from "@library/modal/ModalSizes";
import { t } from "@vanilla/i18n";
import React from "react";
import dashboardAddEditUserClasses from "./DashboardAddEditUser.classes";

interface IDashboardAddEditUserModalProps {
    title?: string;
    handleSubmit: () => Promise<void>;
    isSubmitting: boolean;
    isVisible: boolean;
    handleClose: () => void;
}

export default function DashboardAddEditUserModal(props: React.PropsWithChildren<IDashboardAddEditUserModalProps>) {
    const { isSubmitting, isVisible, handleClose, title = "addEditUser" } = props;

    const titleID = `${title}_modal`;
    const classes = dashboardAddEditUserClasses();

    return (
        <Modal isVisible={isVisible} size={ModalSizes.XL} exitHandler={() => handleClose()} titleID={titleID}>
            <form
                onSubmit={(e) => {
                    e.preventDefault();
                    props.handleSubmit();
                }}
                noValidate
                className={classes.form}
                aria-label={t(title ?? "Add/Edit User")}
            >
                <Frame
                    header={
                        <FrameHeader
                            closeFrame={() => {
                                handleClose();
                            }}
                            title={t(title ?? "Add/Edit User")}
                        />
                    }
                    body={<FrameBody>{props.children}</FrameBody>}
                    footer={
                        <FrameFooter justifyRight={true}>
                            <Button
                                className={frameFooterClasses().actionButton}
                                buttonType={ButtonTypes.TEXT}
                                onClick={() => {
                                    handleClose();
                                }}
                                disabled={isSubmitting}
                            >
                                {t("Cancel")}
                            </Button>
                            <Button
                                disabled={isSubmitting}
                                className={frameFooterClasses().actionButton}
                                buttonType={ButtonTypes.TEXT_PRIMARY}
                                submit
                            >
                                {isSubmitting ? <ButtonLoader /> : t("Save")}
                            </Button>
                        </FrameFooter>
                    }
                />
            </form>
        </Modal>
    );
}
