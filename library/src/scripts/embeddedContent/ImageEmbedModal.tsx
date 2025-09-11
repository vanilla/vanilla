/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import InputTextBlock from "@library/forms/InputTextBlock";
import FrameBody from "@library/layout/frame/FrameBody";
import FrameFooter from "@library/layout/frame/FrameFooter";
import { t } from "@library/utility/appUtils";
import Frame from "@library/layout/frame/Frame";
import FrameHeader from "@library/layout/frame/FrameHeader";
import Modal from "@library/modal/Modal";
import ModalSizes from "@library/modal/ModalSizes";
import { useUniqueID } from "@library/utility/idUtils";
import React, { useCallback, useEffect, useRef, useState } from "react";
import { EditorEventWall } from "@library/editor/pieces/EditorEventWall";
import { embedMenuClasses } from "@library/editor/pieces/embedMenuStyles";

interface IProps {
    onSave: (meta: IImageMeta) => void;
    className?: string;
    initialAlt?: string;
    initialTargetUrl?: string;
    onClose: () => void;
    isVisible: boolean;
}

export interface IImageMeta {
    alt?: string;
    targetUrl?: string;
}

/**
 * A class for rendering Giphy embeds.
 */
export function ImageEmbedModal(props: IProps) {
    const classes = embedMenuClasses();
    const [alt, setAlt] = useState(props.initialAlt);
    const [targetUrl, setTargetUrl] = useState(props.initialTargetUrl);
    const inputRef = useRef<HTMLInputElement>(null);

    const isImageTargetUrl = !!props.initialTargetUrl;

    // Reset alt state when initialAlt changes
    useEffect(() => {
        setAlt(props.initialAlt);
    }, [props.initialAlt]);

    // Reset targetUrl state when initialTargetUrl changes
    useEffect(() => {
        setTargetUrl(props.initialTargetUrl);
    }, [props.initialTargetUrl]);

    const handleTextChange = useCallback((event) => {
        if (event) {
            const newValue = event.target.value || "";
            event.stopPropagation();
            event.preventDefault();
            isImageTargetUrl ? setTargetUrl(newValue) : setAlt(newValue);
        }
    }, []);

    // Focus the input when opened.
    useEffect(() => {
        inputRef.current?.focus();
    }, []);

    const saveAndClose = (value: IImageMeta) => {
        props.onSave(value);
        props.onClose();
    };

    const titleID = useUniqueID("modalTitle");

    return (
        <Modal isVisible={props.isVisible} size={ModalSizes.SMALL} titleID={titleID} exitHandler={props.onClose}>
            <EditorEventWall>
                <form className={classes.form}>
                    <Frame
                        header={
                            <FrameHeader
                                title={isImageTargetUrl ? t("Link Image to URL") : t("Alt Text")}
                                titleID={titleID}
                                closeFrame={() => {
                                    props.onClose();
                                }}
                            />
                        }
                        body={
                            <FrameBody className={classes.verticalPadding}>
                                <InputTextBlock
                                    label={
                                        isImageTargetUrl
                                            ? t("Enter the URL you want this image to link to.")
                                            : t(
                                                  "Alternative text helps users with accessibility concerns and improves SEO.",
                                              )
                                    }
                                    labelClassName={classes.paragraph}
                                    inputProps={{
                                        required: true,
                                        value: isImageTargetUrl ? targetUrl : alt,
                                        onChange: handleTextChange,
                                        inputRef,
                                        placeholder: isImageTargetUrl
                                            ? t("Example: https://example.com")
                                            : t("Example: https://example.com/image.jpg"),
                                        onKeyPress: (event: React.KeyboardEvent) => {
                                            if (event.key === "Enter") {
                                                event.preventDefault();
                                                event.stopPropagation();
                                                saveAndClose(isImageTargetUrl ? { targetUrl } : { alt: alt || "" });
                                            }
                                        },
                                    }}
                                />
                            </FrameBody>
                        }
                        footer={
                            <FrameFooter justifyRight={true}>
                                <Button
                                    buttonType={ButtonTypes.TEXT_PRIMARY}
                                    onClick={(e) => {
                                        e.preventDefault();
                                        e.stopPropagation();
                                        saveAndClose(isImageTargetUrl ? { targetUrl } : { alt: alt || "" });
                                    }}
                                >
                                    {t("Save")}
                                </Button>
                            </FrameFooter>
                        }
                    />
                </form>
            </EditorEventWall>
        </Modal>
    );
}
