/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonStyles";
import InputTextBlock from "@library/forms/InputTextBlock";
import FrameBody from "@library/layout/frame/FrameBody";
import FrameFooter from "@library/layout/frame/FrameFooter";
import { t } from "@library/utility/appUtils";
import { EditorEventWall } from "@rich-editor/editor/pieces/EditorEventWall";
import { embedMenuClasses } from "@rich-editor/editor/pieces/embedMenuStyles";
import Frame from "@vanilla/library/src/scripts/layout/frame/Frame";
import FrameHeader from "@vanilla/library/src/scripts/layout/frame/FrameHeader";
import Modal from "@vanilla/library/src/scripts/modal/Modal";
import ModalSizes from "@vanilla/library/src/scripts/modal/ModalSizes";
import { useUniqueID } from "@vanilla/library/src/scripts/utility/idUtils";
import { useLastValue } from "@vanilla/react-utils";
import React, { useCallback, useEffect, useRef, useState } from "react";

interface IProps {
    onSave: (meta: IImageMeta) => void;
    className?: string;
    initialAlt: string;
    onClose: () => void;
    isVisible: boolean;
}

export interface IImageMeta {
    alt: string;
}

/**
 * A class for rendering Giphy embeds.
 */
export function ImageEmbedModal(props: IProps) {
    const classes = embedMenuClasses();
    const [alt, setAlt] = useState(props.initialAlt);
    const inputRef = useRef<HTMLInputElement>(null);

    const handleTextChange = useCallback(event => {
        if (event) {
            event.stopPropagation();
            event.preventDefault();
            setAlt(event.target.value || "");
        }
    }, []);

    // Focus the input when opened.
    useEffect(() => {
        inputRef.current?.focus();
    }, []);

    const saveAndClose = () => {
        setAlt(alt);
        props.onSave({
            alt,
        });
        props.onClose();
    };

    const titleID = useUniqueID("modalTitle");

    return (
        <Modal isVisible={props.isVisible} size={ModalSizes.SMALL} titleID={titleID} exitHandler={props.onClose}>
            <EditorEventWall>
                <form className={classes.form}>
                    <Frame
                        header={<FrameHeader title={t("Alt Text")} titleID={titleID} closeFrame={saveAndClose} />}
                        body={
                            <FrameBody className={classes.verticalPadding}>
                                <InputTextBlock
                                    label={t(
                                        "Alternative text helps users with accessibility concerns and improves SEO.",
                                    )}
                                    labelClassName={classes.paragraph}
                                    inputProps={{
                                        required: true,
                                        value: alt,
                                        onChange: handleTextChange,
                                        inputRef,
                                        placeholder: t("Example: Image of a Coffee"),
                                        onKeyPress: (event: React.KeyboardEvent) => {
                                            if (event.key === "Enter") {
                                                event.preventDefault();
                                                event.stopPropagation();
                                                saveAndClose();
                                            }
                                        },
                                    }}
                                />
                            </FrameBody>
                        }
                        footer={
                            <FrameFooter justifyRight={true}>
                                <Button
                                    baseClass={ButtonTypes.TEXT_PRIMARY}
                                    onClick={e => {
                                        e.preventDefault();
                                        e.stopPropagation();
                                        saveAndClose();
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
