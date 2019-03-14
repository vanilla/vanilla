/**
 * @author Stéphane (slafleche) LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { getRequiredID, IOptionalComponentID, IRequiredComponentID } from "../../../../../library/src/scripts/utility/idUtils";
import { forceSelectionUpdate } from "../quill/utility";
import EmojiPicker from "pieces/EmojiPicker";
import FlyoutToggle, { IFlyoutToggleChildParameters } from "../../../../../library/src/scripts/flyouts/FlyoutToggle";
import { t } from "../../../../../library/src/scripts/dom/appUtils";

import { emoji } from "../../../../../library/src/scripts/icons/editorIcons";
import { richEditorClasses } from "../editor/richEditorClasses";
import classNames from "classnames";
import { ButtonTypes } from "@library/styles/buttonStyles";

interface IProps extends IOptionalComponentID {
    disabled?: boolean;
    renderAbove?: boolean;
    renderLeft?: boolean;
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
        const classesRichEditor = richEditorClasses();

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
