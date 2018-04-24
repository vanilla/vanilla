/**
 * @author Stéphane (slafleche) LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import React from "react";
import EmojiPicker from "./EmojiPicker";
import PopoverController, { IPopoverControllerChildParameters } from "./Generic/PopoverController";
import * as Icons from "./Icons";

export default class EmojiPopover extends React.Component {
    /**
     * @inheritDoc
     */
    public render() {
        const icon = Icons.emoji();

        return (
            <PopoverController classNameRoot="emojiPicker" icon={icon}>
                {(options: IPopoverControllerChildParameters) => {
                    return <EmojiPicker {...options} />;
                }}
            </PopoverController>
        );
    }
}
