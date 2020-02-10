/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useState, useEffect } from "react";
import Modal from "@vanilla/library/src/scripts/modal/Modal";
import ModalSizes from "@vanilla/library/src/scripts/modal/ModalSizes";
import Frame from "@vanilla/library/src/scripts/layout/frame/Frame";
import FrameHeader from "@vanilla/library/src/scripts/layout/frame/FrameHeader";
import FrameBody from "@vanilla/library/src/scripts/layout/frame/FrameBody";
import { useSwaggerUI, ISwaggerHeading } from "@openapi-embed/embed/swagger/useSwaggerUI";
import Loader from "@vanilla/library/src/scripts/loaders/Loader";
import FrameFooter from "@vanilla/library/src/scripts/layout/frame/FrameFooter";
import Button from "@vanilla/library/src/scripts/forms/Button";
import { frameFooterClasses } from "@vanilla/library/src/scripts/layout/frame/frameFooterStyles";
import { ButtonTypes } from "@vanilla/library/src/scripts/forms/buttonStyles";
import { t } from "@vanilla/i18n";

interface IProps {
    isVisible: boolean;
    spec?: object;
    url?: string;
    onDismiss: () => void;
    onConfirm: () => void;
    onLoadHeadings?: (headings: ISwaggerHeading[]) => void;
}

export function OpenApiPreview(props: IProps) {
    const { onLoadHeadings, url, spec } = props;
    const { swaggerRef, isLoading, headings } = useSwaggerUI({ url, spec });
    const { actionButton } = frameFooterClasses();

    useEffect(() => {
        if (!isLoading) {
            onLoadHeadings?.(headings);
        }
    }, [headings, isLoading, onLoadHeadings]);

    return (
        <Modal isVisible={props.isVisible} size={ModalSizes.LARGE} titleID="">
            <Frame
                header={
                    <FrameHeader onBackClick={props.onDismiss} closeFrame={props.onDismiss} title={t("API Preview")} />
                }
                body={
                    <FrameBody hasVerticalPadding>
                        <div ref={swaggerRef} style={{ display: isLoading ? "none" : "initial" }}></div>
                        {isLoading && <Loader padding={200} />}
                    </FrameBody>
                }
                footer={
                    <FrameFooter justifyRight>
                        <Button className={actionButton} baseClass={ButtonTypes.TEXT} onClick={props.onDismiss}>
                            {t("Cancel")}
                        </Button>
                        <Button
                            disabled={isLoading}
                            className={actionButton}
                            baseClass={ButtonTypes.TEXT_PRIMARY}
                            onClick={props.onConfirm}
                        >
                            {t("Save")}
                        </Button>
                    </FrameFooter>
                }
            />
        </Modal>
    );
}
