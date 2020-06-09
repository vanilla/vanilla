/**
 * @author Stéphane (slafleche) LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useRef } from "react";
import FlyoutToggle, { IFlyoutToggleChildParameters } from "@library/flyouts/FlyoutToggle";
import { t } from "@library/utility/appUtils";
import classNames from "classnames";
import EmojiPicker from "@rich-editor/flyouts/pieces/EmojiPicker";
import { richEditorClasses } from "@rich-editor/editor/richEditorStyles";
import { forceSelectionUpdate } from "@rich-editor/quill/utility";
import { IconForButtonWrap } from "@rich-editor/editor/pieces/IconForButtonWrap";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { EmojiIcon } from "@library/icons/editorIcons";
import ScreenReaderContent from "@library/layout/ScreenReaderContent";
import { useUniqueID } from "@library/utility/idUtils";

interface IProps {
    disabled?: boolean;
    renderAbove?: boolean;
    renderLeft?: boolean;
    legacyMode: boolean;
}

export function EmojiFlyout(props: IProps) {
    const titleRef = useRef<HTMLDivElement>(null);
    const id = useUniqueID("emojiFlyout");

    /**
     * @inheritDoc
     */

    const classesRichEditor = richEditorClasses(props.legacyMode);
    const label = t("Emoji Picker");
    const handleID = id + "-handle";
    const contentID = id + "-content";
    return (
        <FlyoutToggle
            id={handleID}
            className="emojiPicker"
            buttonClassName={classNames("richEditor-button", "richEditor-embedButton", classesRichEditor.button)}
            onVisibilityChange={forceSelectionUpdate}
            disabled={props.disabled}
            name={label}
            buttonContents={
                <>
                    <ScreenReaderContent>{label}</ScreenReaderContent>
                    <IconForButtonWrap icon={<EmojiIcon />} />
                </>
            }
            buttonBaseClass={ButtonTypes.ICON}
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
                    />
                );
            }}
        </FlyoutToggle>
    );
}
