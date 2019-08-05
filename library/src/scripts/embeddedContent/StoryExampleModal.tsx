/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useEffect, useRef, useState } from "react";
import { IStoryTileAndTextProps } from "@library/storybook/StoryTileAndText";
import Button from "@library/forms/Button";
import classNames from "classnames";
import { useUniqueID } from "@library/utility/idUtils";
import ModalConfirm from "@library/modal/ModalConfirm";
import { storyBookClasses } from "@library/storybook/StoryBookStyles";
import { StoryTile } from "@library/storybook/StoryTile";
import { Omit } from "@library/@types/utils";
import Modal from "@library/modal/Modal";

interface IProps extends Omit<IStoryTileAndTextProps, "children"> {}

/**
 * Separator, for react storybook.
 */
export function StoryExampleModalConfirm(props: IProps) {
    const [open, setOpen] = useState(false);

    const openButtonRef = useRef<HTMLButtonElement>(null);
    const toggleButton = useUniqueID("exampleConfirmModal_toggleButton");

    useEffect(() => {}, []);

    return (
        <>
            <li className={classNames(storyBookClasses().tilesAndText, storyBookClasses().compactTilesAndText)}>
                <StoryTile
                    tag={"div"}
                    mouseOverText={props.mouseOverText}
                    type={props.type}
                    scaleContents={props.scaleContents}
                >
                    <Button
                        id={toggleButton}
                        onClick={() => {
                            setOpen(true);
                        }}
                        buttonRef={openButtonRef}
                    >
                        Confirm Modal
                    </Button>
                </StoryTile>
            </li>
            {open && (
                {/*<Modal*/}
                {/*    size={ModalSizes.SMALL}*/}
                {/*    elementToFocus={*/}
                {/*        openButtonRef ? ((openButtonRef.current as unknown) as HTMLButtonElement) : undefined*/}
                {/*    }*/}
                {/*    exitHandler={() => {*/}
                {/*        setOpen(false);*/}
                {/*    }}*/}
                {/*    titleID={titleID}*/}
                {/*    elementToFocusOnExit={(openButtonRef.current as unknown) as HTMLButtonElement}*/}
                {/*>*/}
                {/*    <Frame*/}
                {/*        header={*/}
                {/*            <FrameHeader*/}
                {/*                titleID={titleID}*/}
                {/*                closeFrame={() => {*/}
                {/*                    setOpen(false);*/}
                {/*                }}*/}
                {/*                title={t("Example Modal")}*/}
                {/*            />*/}
                {/*        }*/}
                {/*        body={*/}
                {/*            <FrameBody>*/}
                {/*                <SmartAlign className={classNames("frameBody-contents", classesFrameBody.contents)}>*/}
                {/*                    {}*/}
                {/*                </SmartAlign>*/}
                {/*            </FrameBody>*/}
                {/*        }*/}
                {/*        footer={*/}
                {/*            <FrameFooter justifyRight={true}>*/}
                {/*                <Button*/}
                {/*                    className={classFrameFooter.actionButton}*/}
                {/*                    baseClass={ButtonTypes.TEXT}*/}
                {/*                    buttonRef={this.cancelRef}*/}
                {/*                    onClick={onCancel}*/}
                {/*                >*/}
                {/*                    {t("Cancel")}*/}
                {/*                </Button>*/}
                {/*                <Button*/}
                {/*                    className={classFrameFooter.actionButton}*/}
                {/*                    onClick={onConfirm}*/}
                {/*                    baseClass={ButtonTypes.TEXT_PRIMARY}*/}
                {/*                    disabled={isConfirmLoading}*/}
                {/*                >*/}
                {/*                    {isConfirmLoading ? <ButtonLoader /> : this.props.confirmTitle}*/}
                {/*                </Button>*/}
                {/*            </FrameFooter>*/}
                {/*        }*/}
                    // />
                </Modal>
            )}
        </>
    );
}
