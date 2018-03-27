/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import React from "react";
import PopoverController from "./PopoverController";
import EmbedPopover from "./EmbedPopover";
import * as Icons from "./Icons";

export default class EmbedDialogue extends React.Component {

    /**
     * @inheritDoc
     */
    render() {
        const icon = Icons.embed();

        return <PopoverController PopoverComponentClass={EmbedPopover} icon={icon} classNameRoot="embedDialogue"/>;
    }
}
