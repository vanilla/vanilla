/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useState } from "react";
import Modal from "@vanilla/library/src/scripts/modal/Modal";
import ModalSizes from "@vanilla/library/src/scripts/modal/ModalSizes";
import Frame from "@vanilla/library/src/scripts/layout/frame/Frame";
import FrameHeader from "@vanilla/library/src/scripts/layout/frame/FrameHeader";
import FrameBody from "@vanilla/library/src/scripts/layout/frame/FrameBody";
import FrameFooter from "@vanilla/library/src/scripts/layout/frame/FrameFooter";
import Button from "@vanilla/library/src/scripts/forms/Button";
import { ButtonTypes } from "@vanilla/library/src/scripts/forms/buttonStyles";
import InputTextBlock from "@vanilla/library/src/scripts/forms/InputTextBlock";
import { useSwaggerUI } from "@openapi-embed/embed/useSwaggerUI";
import Loader from "@vanilla/library/src/scripts/loaders/Loader";

interface IProps {
    previewUrl: string;
    onDismiss: () => void;
}

export function OpenApiPreview(props: IProps) {
    const { swaggerRef, isLoading } = useSwaggerUI(props.previewUrl);

    return (
        <Modal size={ModalSizes.LARGE} titleID="">
            <Frame
                header={<FrameHeader closeFrame={props.onDismiss} title={"OpenApi Preview"} />}
                body={
                    <FrameBody hasVerticalPadding>
                        {/* <Loader padding={200} /> */}
                        <div ref={swaggerRef} style={{ display: isLoading ? "none" : "initial" }}></div>
                        {isLoading && <Loader padding={200} />}
                    </FrameBody>
                }
            />
        </Modal>
    );
}
