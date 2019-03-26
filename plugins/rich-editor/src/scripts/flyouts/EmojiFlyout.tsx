/**
 * @author Stéphane (slafleche) LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { getRequiredID, IOptionalComponentID, IRequiredComponentID } from "@library/utility/idUtils";
import FlyoutToggle, { IFlyoutToggleChildParameters } from "@library/flyouts/FlyoutToggle";
import { t } from "@library/utility/appUtils";
import { emoji } from "@library/icons/editorIcons";
import classNames from "classnames";
import EmojiPicker from "@rich-editor/flyouts/pieces/EmojiPicker";
import { richEditorClasses } from "@rich-editor/editor/richEditorClasses";
import { forceSelectionUpdate } from "@rich-editor/quill/utility";
import { ButtonTypes } from "@library/forms/buttonStyles";

interface IProps extends IOptionalComponentID {
    disabled?: boolean;
    renderAbove?: boolean;
    renderLeft?: boolean;
    legacyMode: boolean;
}

export default class EmojiFlyout extends React.Component<IProps, IRequiredComponentID> {
    public constructor(props) {
        super(props);
        this.state = {
            id: getRequiredID(props, "emojiPopover"),
        };
    }

    /**
     * @inheritDoc
     */
    public render() {
        const icon = emoji();
        const classesRichEditor = richEditorClasses(this.props.legacyMode);

        return (
            <FlyoutToggle
                id={this.state.id}
                className="emojiPicker"
                buttonClassName={classNames("richEditor-button", "richEditor-embedButton", classesRichEditor.button)}
                onVisibilityChange={forceSelectionUpdate}
                disabled={this.props.disabled}
                name={t("Emoji Picker")}
                buttonContents={icon}
                buttonBaseClass={ButtonTypes.ICON}
                renderAbove={this.props.renderAbove}
                renderLeft={this.props.renderLeft}
                openAsModal={false}
            >
                {(options: IFlyoutToggleChildParameters) => {
                    return (
                        <EmojiPicker
                            {...options}
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
