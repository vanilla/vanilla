/**
 * @author Stéphane (slafleche) LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import * as Icons from "@rich-editor/components/icons";
import { getRequiredID, IRequiredComponentID, IOptionalComponentID } from "@library/componentIDs";
import PopoverController, {
    IPopoverControllerChildParameters,
} from "@rich-editor/components/popovers/pieces/PopoverController";
import EmojiPicker from "@rich-editor/components/popovers/pieces/EmojiPicker";
import { ILegacyMode } from "@rich-editor/components/editor/editor";

interface IProps extends IOptionalComponentID, ILegacyMode {}

export default class EmojiPopover extends React.Component<IProps, IRequiredComponentID> {
    public static defaultProps = {
        legacyMode: false,
    };

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
        const icon = Icons.emoji();

        return (
            <PopoverController id={this.state.id} classNameRoot="emojiPicker" icon={icon}>
                {(options: IPopoverControllerChildParameters) => {
                    return <EmojiPicker {...options} contentID={options.id} legacyMode={this.props.legacyMode} />;
                }}
            </PopoverController>
        );
    }
}
