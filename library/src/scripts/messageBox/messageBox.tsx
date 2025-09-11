/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { cx } from "@emotion/css";
import DropDown, { DropDownOpenDirection } from "@library/flyouts/DropDown";
import { Icon, IconType } from "@vanilla/icons";
import { useEffect } from "react";
import { useSizeAnimator } from "@vanilla/react-utils";
import { messageBoxClasses as classes } from "@library/messageBox/messageBox.classes";

export type DisplayState =
    | {
          type: "closed" | "root" | "messageInbox" | "transitioningToMessageInbox";
      }
    | {
          type: "messageDetails";
          productMessageID: string;
      };

interface IMessageBoxProps {
    displayState: DisplayState;
    changeDisplayState: (newState: DisplayState) => void;
    rootContents?: React.ReactNode;
    messageInboxContents?: React.ReactNode;
    messageDetailsContents?: React.ReactNode;
    dropDownTargetState: "root" | "transitioningToMessageInbox";
    animator: ReturnType<typeof useSizeAnimator>;
    icon?: IconType;

    dropdownContentsClassName?: string;
}

export function MessageBox(props: IMessageBoxProps) {
    const {
        displayState,
        rootContents,
        messageInboxContents,
        messageDetailsContents,
        dropDownTargetState,
        changeDisplayState,
        animator,
        icon,
        dropdownContentsClassName,
    } = props;

    useEffect(() => {
        if (displayState.type === "transitioningToMessageInbox") {
            changeDisplayState({ type: "messageInbox" });
        }
    }, [displayState.type]);

    const contents = (() => {
        switch (displayState.type) {
            case "closed":
                return null;
            case "transitioningToMessageInbox":
                return (
                    // We need an element with width and height to be able to animate from collapsed to expanded
                    <div
                        style={{
                            width: "1px",
                            height: "1px",
                            transform: "translate3d(0, 0, 0)",
                        }}
                    ></div>
                );
            case "root":
                return rootContents ?? null;
            case "messageInbox":
                return messageInboxContents ?? null;
            case "messageDetails":
                return messageDetailsContents ?? null;
        }
    })();

    return (
        <DropDown
            contentRef={animator.measureRef}
            isVisible={displayState.type !== "closed"}
            onVisibilityChange={(newVisibility) => {
                changeDisplayState({ type: newVisibility ? dropDownTargetState : "closed" } as DisplayState);
            }}
            openDirection={DropDownOpenDirection.ABOVE_LEFT}
            className={classes.root}
            buttonContents={<Icon icon={icon ?? "admin-assistant"} />}
            flyoutType={
                displayState.type === "messageDetails" || displayState.type === "messageInbox" ? "frame" : "list"
            }
            buttonType={"custom"}
            buttonClassName={classes.rootButton}
            contentsClassName={cx(
                classes.dropdownRoot,
                displayState.type === "messageInbox" && classes.messagesDropdownContent,
                displayState.type === "messageDetails" && classes.detailDropdownContent,
                dropdownContentsClassName,
            )}
        >
            {contents}
        </DropDown>
    );
}
