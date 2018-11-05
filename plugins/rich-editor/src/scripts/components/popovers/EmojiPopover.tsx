/**
 * @author Stéphane (slafleche) LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { getRequiredID, IOptionalComponentID, IRequiredComponentID } from "@library/componentIDs";
import { forceSelectionUpdate } from "@rich-editor/quill/utility";
import EmojiPicker from "@rich-editor/components/popovers/pieces/EmojiPicker";
import PopoverController, { IPopoverControllerChildParameters } from "@library/components/PopoverController";
import { t } from "@library/application";
import { ButtonBaseClass } from "@library/components/forms/Button";
import { emoji } from "@library/components/icons/editor";

interface IProps extends IOptionalComponentID {
    disabled?: boolean;
}

export default class EmojiPopover extends React.Component<IProps, IRequiredComponentID> {
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

        return (
            <PopoverController
                id={this.state.id}
                classNameRoot="emojiPicker"
                buttonClassName="richEditor-button richEditor-embedButton"
                onVisibilityChange={forceSelectionUpdate}
                disabled={this.props.disabled}
                name={t("Emoji Picker")}
                buttonContents={icon}
                buttonBaseClass={ButtonBaseClass.ICON}
            >
                {(options: IPopoverControllerChildParameters) => {
                    return <EmojiPicker {...options} contentID={options.id} />;
                }}
            </PopoverController>
        );
    }
}
