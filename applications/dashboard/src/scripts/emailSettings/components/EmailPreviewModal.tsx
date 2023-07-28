/**
 * @author Taylor Chance <tchance@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import { t } from "@vanilla/i18n";
import React, { useRef, useLayoutEffect } from "react";
import { useQuery } from "@tanstack/react-query";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import Frame from "@library/layout/frame/Frame";
import FrameBody from "@library/layout/frame/FrameBody";
import FrameFooter from "@library/layout/frame/FrameFooter";
import FrameHeader from "@library/layout/frame/FrameHeader";
import Modal from "@library/modal/Modal";
import ModalSizes from "@library/modal/ModalSizes";
import apiv2 from "@library/apiv2";
import { IEmailPreviewPayload, IEmailSettings } from "@dashboard/emailSettings/types";
import Loader from "@library/loaders/Loader";
import { IError } from "@library/errorPages/CoreErrorMessages";
import ErrorMessages from "@library/forms/ErrorMessages";
import { prepareShadowRoot } from "@vanilla/dom-utils";

function Preview(props: { settings: IEmailSettings | {}; isPlainText: boolean }) {
    const { settings, isPlainText } = props;
    const ref = useRef<HTMLElement>(null);

    const previewPayload: IEmailPreviewPayload = {
        emailFormat: isPlainText ? "text" : "html",
        templateStyles: {
            logoUrl: settings["emailStyles.logoUrl"],
            textColor: settings["emailStyles.textColor"],
            backgroundColor: settings["emailStyles.backgroundColor"],
            containerBackgroundColor: settings["emailStyles.containerBackgroundColor"],
            buttonTextColor: settings["emailStyles.buttonTextColor"],
            buttonBackgroundColor: settings["emailStyles.buttonBackgroundColor"],
        },
        footer: JSON.stringify(settings["outgoingEmails.footer"]),
    };

    const previewQuery = useQuery<any, IError, string>({
        queryKey: ["emailPreview", previewPayload],
        queryFn: async () => {
            const response = await apiv2.post("/emails/preview", previewPayload);
            return response.data;
        },
    });

    useLayoutEffect(() => {
        if (!ref.current) {
            return;
        }
        prepareShadowRoot(ref.current, true);
    });

    if (previewQuery.isLoading) {
        return <Loader small />;
    }

    if (previewQuery.error) {
        return <ErrorMessages errors={[previewQuery.error]} />;
    }

    return (
        <div style={{ whiteSpace: isPlainText ? "pre" : "normal" }}>
            <noscript ref={ref} dangerouslySetInnerHTML={{ __html: previewQuery.data }} />
        </div>
    );
}

interface IEmailPreviewModal {
    settings: IEmailSettings | {};
    onCancel(): void;
}

export default function EmailPreviewModal(props: IEmailPreviewModal) {
    const { settings, onCancel } = props;
    const isPlainText = !settings["emailStyles.format"];

    return (
        <Modal
            isVisible={true}
            size={ModalSizes.LARGE}
            exitHandler={() => {
                onCancel();
            }}
        >
            <Frame
                header={
                    <FrameHeader
                        closeFrame={() => {
                            onCancel();
                        }}
                        title={t("Preview Email")}
                    />
                }
                body={
                    <FrameBody selfPadded={!isPlainText} hasVerticalPadding={isPlainText}>
                        <Preview settings={settings} isPlainText={isPlainText} />
                    </FrameBody>
                }
                footer={
                    <FrameFooter justifyRight>
                        <Button
                            buttonType={ButtonTypes.TEXT}
                            onClick={() => {
                                onCancel();
                            }}
                        >
                            {t("Close")}
                        </Button>
                    </FrameFooter>
                }
            />
        </Modal>
    );
}
