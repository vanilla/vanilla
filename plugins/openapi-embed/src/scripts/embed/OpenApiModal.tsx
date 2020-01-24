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
import { OpenApiPreview } from "@openapi-embed/embed/OpenApiPreview";
import { frameFooterClasses } from "@vanilla/library/src/scripts/layout/frame/frameFooterStyles";
import { isAllowedUrl } from "@vanilla/library/src/scripts/utility/appUtils";

interface IProps {
    currentUrl?: string;
    onDismiss: () => void;
    onSave: (specUrl: string) => void;
}

export function OpenApiModal(props: IProps) {
    const [url, setUrl] = useState(props.currentUrl ?? "");
    const [showPreview, setShowPreview] = useState(false);

    const { actionButton } = frameFooterClasses();

    return (
        <Modal size={ModalSizes.MEDIUM} titleID="">
            <Frame
                header={<FrameHeader closeFrame={props.onDismiss} title={"Configure OpenApi Spec"} />}
                body={
                    <FrameBody hasVerticalPadding>
                        <InputTextBlock
                            label={"Spec URL"}
                            inputProps={{
                                placeholder: "https://petstore.swagger.io/v2/swagger.json",
                                value: url,
                                onChange: e => {
                                    setUrl(e.target.value);
                                },
                            }}
                        />
                    </FrameBody>
                }
                footer={
                    <FrameFooter justifyRight>
                        <Button
                            disabled={!isAllowedUrl(url)}
                            className={actionButton}
                            baseClass={ButtonTypes.TEXT}
                            onClick={() => setShowPreview(true)}
                        >
                            Preview
                        </Button>
                        <Button
                            className={actionButton}
                            baseClass={ButtonTypes.TEXT_PRIMARY}
                            onClick={() => props.onSave(url)}
                        >
                            Save
                        </Button>
                    </FrameFooter>
                }
            />
            {showPreview && (
                <OpenApiPreview
                    previewUrl={url}
                    onDismiss={() => {
                        setShowPreview(false);
                    }}
                />
            )}
        </Modal>
    );
}
