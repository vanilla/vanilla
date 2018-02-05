/**
 * @author Stéphane (slafleche) LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import React from "react";
import * as PropTypes from "prop-types";
import Quill from "quill/quill";
import { t } from "@core/utility";
// import Events from "@core/events";
// import EditorToolbar from "./EditorToolbar";
// import Emitter from "quill/core/emitter";
// import { Range } from "quill/core/selection";
import EditorEmojiButton from "../components/EditorEmojiButton";
import emojis from 'emojibase-data/en/data.json';

export default class EditorEmojiMenu extends React.Component {


    static propTypes = {
        quill: PropTypes.instanceOf(Quill).isRequired,
        menuTitleID: PropTypes.string.isRequired,
        isVisible: PropTypes.bool.isRequired,
        closeMenu: PropTypes.func.isRequired,
    };

    /**
     * @inheritDoc
     */
    constructor(props) {
        super(props);

        // Quill can directly on the class as it won't ever change in a single instance.
        this.quill = props.quill;
        this.menuTitleID = props.menuTitleID;
        this.emojiList = emojis;
        this.closeMenu = props.closeMenu;
    }

    /**
     * @inheritDoc
     */
    render() {
        if(this.props.isVisible) {
            return <div className="richEditor-menu insertEmoji FlyoutMenu insertPopover" role="dialog" aria-labelledby="{this.menuTitleID">
                <div className="insertPopover-header">
                    <h2 id="{props.menuTitleID}" className="H insertMedia-title">
                        {t('Smileys & Faces')}
                    </h2>
                    <a href="#" aria-label="{t('Close');}" onClick={this.closeMenu} className="Close richEditor-close">
                        <span>×</span>
                    </a>
                </div>
                <div className="insertPopover-body">
                    <div className="richEditor-emojis">
                        {this.emojiList.map((emoji, i) => {
                            return <EditorEmojiButton key={i} quill={this.quill} emoji={emoji} closeMenu={this.closeMenu}/>;
                        })}
                    </div>
                </div>
            </div>;
        } else {
            return null;
        }
    }
}
