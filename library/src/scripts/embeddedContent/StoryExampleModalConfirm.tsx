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

interface IProps extends Omit<IStoryTileAndTextProps, "children"> {}

/**
 * Separator, for react storybook.
 */
export function StoryExampleModalConfirm(props: IProps) {
    const [open, setOpen] = useState(false);

    const openButtonRef = useRef<HTMLButtonElement>(null);
    const toggleButton = useUniqueID("exampleConfirmModal_toggleButton");

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
                <ModalConfirm
                    title={"Do you agree?"}
                    onCancel={() => {
                        /* do something before closing */
                        setOpen(false);
                    }}
                    onConfirm={() => {
                        /* do something on confirm */
                        setOpen(false);
                    }}
                    confirmTitle={"I Concur!"}
                    elementToFocusOnExit={
                        openButtonRef.current ? (openButtonRef.current as HTMLButtonElement) : undefined
                    }
                >
                    {"Do you agree with this statement?"}
                </ModalConfirm>
            )}
        </>
    );
}
