/**
 * @author Stéphane (slafleche) LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import React from "react";
import EmojiPicker from "./EmojiPicker";
import PopoverController, { IPopoverControllerChildParameters } from "./Generic/PopoverController";
import * as Icons from "./Icons";
import { getRequiredID, IRequiredComponentID } from "@core/Interfaces/componentIDs";

export default class EmojiPopover extends React.Component<IRequiredComponentID, IRequiredComponentID> {
    public constructor(props) {
        super(props);
        this.state = {
            id: getRequiredID(props, "emojiPopover"),
        };
    }

    get contentID(): string {
        return this.state.id + "-contents";
    }

    /**
     * @inheritDoc
     */
    public render() {
        const icon = Icons.emoji();

        return (
            <PopoverController id={this.state.id} contentID={this.contentID} classNameRoot="emojiPicker" icon={icon}>
                {(options: IPopoverControllerChildParameters) => {
                    return <EmojiPicker {...options} id={this.contentID} />;
                }}
            </PopoverController>
        );
    }
}
