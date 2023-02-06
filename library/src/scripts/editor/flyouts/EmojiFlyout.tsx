/**
 * @author Stéphane (slafleche) LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import EmojiPicker from "@library/editor/flyouts/pieces/EmojiPicker";
import FlyoutToggle, { IFlyoutToggleChildParameters } from "@library/flyouts/FlyoutToggle";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { EmojiIcon } from "@library/icons/editorIcons";
import ScreenReaderContent from "@library/layout/ScreenReaderContent";
import { t } from "@library/utility/appUtils";
import { useUniqueID } from "@library/utility/idUtils";
import React, { useRef } from "react";

interface IProps {
    disabled?: boolean;
    renderAbove?: boolean;
    renderLeft?: boolean;
    legacyMode?: boolean;
    onVisibilityChange?: React.ComponentProps<typeof FlyoutToggle>["onVisibilityChange"];
    onInsertEmoji: React.ComponentProps<typeof EmojiPicker>["onInsertEmoji"];
}

export default function EmojiFlyout(props: IProps) {
    const titleRef = useRef<HTMLDivElement>(null);
    const id = useUniqueID("emojiFlyout");

    /**
     * @inheritDoc
     */

    const label = t("Emoji Picker");
    const handleID = id + "-handle";
    const contentID = id + "-content";
    return (
        <FlyoutToggle
            id={handleID}
            className="emojiPicker"
            onVisibilityChange={props.onVisibilityChange}
            disabled={props.disabled}
            name={label}
            buttonContents={
                <>
                    <ScreenReaderContent>{label}</ScreenReaderContent>
                    <EmojiIcon />
                </>
            }
            buttonType={ButtonTypes.ICON_MENUBAR}
            renderAbove={props.renderAbove}
            renderLeft={props.renderLeft}
            openAsModal={false}
            initialFocusElement={titleRef.current}
            contentID={contentID}
        >
            {(options: IFlyoutToggleChildParameters) => {
                return (
                    <EmojiPicker
                        {...options}
                        id={contentID}
                        handleID={handleID}
                        titleRef={titleRef}
                        renderAbove={props.renderAbove}
                        renderLeft={props.renderLeft}
                        legacyMode={props.legacyMode ?? false}
                        onInsertEmoji={props.onInsertEmoji}
                    />
                );
            }}
        </FlyoutToggle>
    );
}
