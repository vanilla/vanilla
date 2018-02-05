/**
 * @author Stéphane (slafleche) LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import React from "react";
import * as PropTypes from "prop-types";
import Quill from "quill";
import { t } from "@core/utility";
// import Events from "@core/events";
// import EditorToolbar from "./EditorToolbar";
// import Emitter from "quill/core/emitter";
// import { Range } from "quill/core/selection";
import EditorEmojiButton from "../components/EditorEmojiButton";

export default class EditorEmojiMenu extends React.Component {


    static propTypes = {
        quill: PropTypes.instanceOf(Quill).isRequired,
        isVisible: PropTypes.bool.isRequired,
    };

    /** @type {Quill} */
    quill;

    /**
     * @type {Object}
     * @property {RangeStatic} - The current quill selected text range..
     */
    state;

    /** @type {HTMLElement} */
    menu;

    /** @type {number} */
    resizeListener;

    /**
     * @inheritDoc
     */
    constructor(props) {
        super(props);

        // Quill can directly on the class as it won't ever change in a single instance.
        this.quill = props.quill;
    }


    /**
     * @inheritDoc
     */
    render() {
        if(this.props.isVisible) {
            return <div className="richEditor-menu FlyoutMenu insertEmoji insertPopover" role="dialog" aria-labeledby="{props.menuTitleID">
                <div className="insertPopover-header">
                    <h2 id="tempId-insertMediaMenu-title" className="H insertMedia-title">
                        {t('Smileys & Faces')}
                    </h2>
                    <a href="#" aria-label="<?php echo t('Close'); ?>" className="Close richEditor-close">
                        <span>×</span>
                    </a>
                </div>
                <div className="insertPopover-body">
                    <div className="richEditor-emojis">
                        <button className="richEditor-button richEditor-insertEmoji" data-emoji="😀">😀</button>

                        <button className="richEditor-button richEditor-insertEmoji" data-emoji="😁">😁</button>
                        <button className="richEditor-button richEditor-insertEmoji" data-emoji="😂">😂</button>
                        <button className="richEditor-button richEditor-insertEmoji" data-emoji="😀">😀</button>
                        <button className="richEditor-button richEditor-insertEmoji" data-emoji="😁">😁</button>
                        <button className="richEditor-button richEditor-insertEmoji" data-emoji="😂">😂</button>
                        <button className="richEditor-button richEditor-insertEmoji" data-emoji="😁">😁</button>
                        <button className="richEditor-button richEditor-insertEmoji" data-emoji="😂">😂</button>
                        <button className="richEditor-button richEditor-insertEmoji" data-emoji="😀">😀</button>
                        <button className="richEditor-button richEditor-insertEmoji" data-emoji="😁">😁</button>
                        <button className="richEditor-button richEditor-insertEmoji" data-emoji="😂">😂</button>
                        <button className="richEditor-button richEditor-insertEmoji" data-emoji="😁">😁</button>
                        <button className="richEditor-button richEditor-insertEmoji" data-emoji="😂">😂</button>
                        <button className="richEditor-button richEditor-insertEmoji" data-emoji="😀">😀</button>
                        <button className="richEditor-button richEditor-insertEmoji" data-emoji="😁">😁</button>
                        <button className="richEditor-button richEditor-insertEmoji" data-emoji="😂">😂</button>
                        <button className="richEditor-button richEditor-insertEmoji" data-emoji="😁">😁</button>
                        <button className="richEditor-button richEditor-insertEmoji" data-emoji="😂">😂</button>
                        <button className="richEditor-button richEditor-insertEmoji" data-emoji="😀">😀</button>
                        <button className="richEditor-button richEditor-insertEmoji" data-emoji="😁">😁</button>
                        <button className="richEditor-button richEditor-insertEmoji" data-emoji="😂">😂</button>
                        <button className="richEditor-button richEditor-insertEmoji" data-emoji="😁">😁</button>
                        <button className="richEditor-button richEditor-insertEmoji" data-emoji="😂">😂</button>
                        <button className="richEditor-button richEditor-insertEmoji" data-emoji="😀">😀</button>
                        <button className="richEditor-button richEditor-insertEmoji" data-emoji="😁">😁</button>
                        <button className="richEditor-button richEditor-insertEmoji" data-emoji="😂">😂</button>
                        <button className="richEditor-button richEditor-insertEmoji" data-emoji="😁">😁</button>
                        <button className="richEditor-button richEditor-insertEmoji" data-emoji="😂">😂</button>
                        <button className="richEditor-button richEditor-insertEmoji" data-emoji="😀">😀</button>
                        <button className="richEditor-button richEditor-insertEmoji" data-emoji="😁">😁</button>
                        <button className="richEditor-button richEditor-insertEmoji" data-emoji="😂">😂</button>
                        <button className="richEditor-button richEditor-insertEmoji" data-emoji="😁">😁</button>
                        <button className="richEditor-button richEditor-insertEmoji" data-emoji="😂">😂</button>
                        <button className="richEditor-button richEditor-insertEmoji" data-emoji="😀">😀</button>
                        <button className="richEditor-button richEditor-insertEmoji" data-emoji="😁">😁</button>
                        <button className="richEditor-button richEditor-insertEmoji" data-emoji="😂">😂</button>
                        <button className="richEditor-button richEditor-insertEmoji" data-emoji="😁">😁</button>
                        <button className="richEditor-button richEditor-insertEmoji" data-emoji="😂">😂</button>
                        <button className="richEditor-button richEditor-insertEmoji" data-emoji="😀">😀</button>
                        <button className="richEditor-button richEditor-insertEmoji" data-emoji="😁">😁</button>
                        <button className="richEditor-button richEditor-insertEmoji" data-emoji="😂">😂</button>
                        <button className="richEditor-button richEditor-insertEmoji" data-emoji="😁">😁</button>
                        <button className="richEditor-button richEditor-insertEmoji" data-emoji="😂">😂</button>
                        <button className="richEditor-button richEditor-insertEmoji" data-emoji="😀">😀</button>
                        <button className="richEditor-button richEditor-insertEmoji" data-emoji="😁">😁</button>
                        <button className="richEditor-button richEditor-insertEmoji" data-emoji="😂">😂</button>
                        <button className="richEditor-button richEditor-insertEmoji" data-emoji="😁">😁</button>
                        <button className="richEditor-button richEditor-insertEmoji" data-emoji="😂">😂</button>
                        <button className="richEditor-button richEditor-insertEmoji" data-emoji="😀">😀</button>
                        <button className="richEditor-button richEditor-insertEmoji" data-emoji="😁">😁</button>
                        <button className="richEditor-button richEditor-insertEmoji" data-emoji="😂">😂</button>
                    </div>
                </div>
            </div>
        } else {
            return null;
        }
    }
}
