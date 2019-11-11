/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useRef, useState } from "react";
import { IStoryTileAndTextProps } from "@library/storybook/StoryTileAndText";
import Button from "@library/forms/Button";
import classNames from "classnames";
import { useUniqueID } from "@library/utility/idUtils";
import Modal from "@library/modal/Modal";
import ModalSizes from "@library/modal/ModalSizes";
import FrameHeader from "@library/layout/frame/FrameHeader";
import FrameBody from "@library/layout/frame/FrameBody";
import FrameFooter from "@library/layout/frame/FrameFooter";
import { ButtonTypes } from "@library/forms/buttonStyles";
import { t } from "@library/utility/appUtils";
import { frameFooterClasses } from "@library/layout/frame/frameFooterStyles";
import { frameBodyClasses } from "@library/layout/frame/frameBodyStyles";
import Frame from "@library/layout/frame/Frame";
import InputTextBlock from "@library/forms/InputTextBlock";
import { inputBlockClasses } from "@library/forms/InputBlockStyles";

interface IProps extends Omit<IStoryTileAndTextProps, "children"> {}

/**
 * Separator, for react storybook.
 */
export function StoryExampleModal(props: IProps) {
    const [open, setOpen] = useState(false);

    const openButtonRef = useRef<HTMLButtonElement>(null);
    const toggleButton = useUniqueID("exampleConfirmModal_toggleButton");
    const classFrameFooter = frameFooterClasses();
    const classesFrameBody = frameBodyClasses();
    const titleID = useUniqueID("exampleModal_title");
    const cancelRef = useRef(null);
    const classesInputBlock = inputBlockClasses();

    return (
        <>
            <Button
                id={toggleButton}
                onClick={() => {
                    setOpen(true);
                }}
                buttonRef={openButtonRef}
            >
                Modal with text input
            </Button>
            {open && (
                <Modal
                    size={ModalSizes.SMALL}
                    elementToFocus={
                        openButtonRef ? ((openButtonRef.current as unknown) as HTMLButtonElement) : undefined
                    }
                    exitHandler={() => {
                        setOpen(false);
                    }}
                    titleID={titleID}
                    elementToFocusOnExit={(openButtonRef.current as unknown) as HTMLButtonElement}
                >
                    <Frame
                        header={
                            <FrameHeader
                                titleID={titleID}
                                closeFrame={() => {
                                    setOpen(false);
                                }}
                                title={t("Example Modal")}
                            />
                        }
                        body={
                            <FrameBody>
                                <div className={classNames("frameBody-contents", classesFrameBody.contents)}>
                                    <InputTextBlock inputProps={{}} />
                                </div>
                            </FrameBody>
                        }
                        footer={
                            <FrameFooter justifyRight={true}>
                                <Button
                                    className={classFrameFooter.actionButton}
                                    baseClass={ButtonTypes.TEXT}
                                    buttonRef={cancelRef}
                                    onClick={() => {
                                        /* do something before closing */
                                        setOpen(false);
                                    }}
                                >
                                    {t("Cancel")}
                                </Button>
                                <Button
                                    className={classFrameFooter.actionButton}
                                    onClick={() => {
                                        /* do something before closing */
                                        setOpen(false);
                                    }}
                                    baseClass={ButtonTypes.TEXT_PRIMARY}
                                >
                                    {"Save"}
                                </Button>
                            </FrameFooter>
                        }
                    />
                </Modal>
            )}
        </>
    );
}
