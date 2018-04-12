/**
 * @author Stéphane (slafleche) LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import React from "react";
import PopoverController from "./Generic/PopoverController";
import EmojiPopover from "./EmojiPopover";
import * as Icons from "./Icons";

export default class EmojiPicker extends React.Component {

    /**
     * @inheritDoc
     */
    render() {
        const icon = Icons.emoji();

        return <PopoverController PopoverComponentClass={EmojiPopover} targetTitleOnOpen={true} icon={icon} classNameRoot="emojiPicker"/>;
    }
}
