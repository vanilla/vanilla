/**
 * @author Stéphane (slafleche) LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { uniqueIDFromPrefix } from "@library/utility/idUtils";
import FlyoutToggle, { IFlyoutToggleChildParameters } from "@library/flyouts/FlyoutToggle";
import { t } from "@library/utility/appUtils";
import classNames from "classnames";
import EmojiPicker from "@rich-editor/flyouts/pieces/EmojiPicker";
import { richEditorClasses } from "@rich-editor/editor/richEditorClasses";
import { forceSelectionUpdate } from "@rich-editor/quill/utility";
import { ButtonTypes } from "@library/forms/buttonStyles";
import { IconForButtonWrap } from "@rich-editor/editor/pieces/IconForButtonWrap";
import { EmojiIcon } from "@library/icons/editorIcons";

interface IProps {
    disabled?: boolean;
    renderAbove?: boolean;
    renderLeft?: boolean;
    legacyMode: boolean;
}

export default class EmojiFlyout extends React.Component<IProps> {
    private titleRef = React.createRef<HTMLElement>();
    private id = uniqueIDFromPrefix("emojiPopover");

    /**
     * @inheritDoc
     */
    public render() {
        const classesRichEditor = richEditorClasses(this.props.legacyMode);

        return (
            <FlyoutToggle
                id={this.id}
                className="emojiPicker"
                buttonClassName={classNames("richEditor-button", "richEditor-embedButton", classesRichEditor.button)}
                onVisibilityChange={forceSelectionUpdate}
                disabled={this.props.disabled}
                name={t("Emoji Picker")}
                buttonContents={<IconForButtonWrap icon={<EmojiIcon />} />}
                buttonBaseClass={ButtonTypes.ICON}
                renderAbove={this.props.renderAbove}
                renderLeft={this.props.renderLeft}
                openAsModal={false}
                initialFocusElement={this.titleRef.current}
            >
                {(options: IFlyoutToggleChildParameters) => {
                    return (
                        <EmojiPicker
                            {...options}
                            titleRef={this.titleRef}
                            renderAbove={this.props.renderAbove}
                            renderLeft={this.props.renderLeft}
                            contentID={options.id}
                        />
                    );
                }}
            </FlyoutToggle>
        );
    }
}
