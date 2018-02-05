/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import React from "react";
import * as PropTypes from "prop-types";
import Quill from "quill/quill";

/**
 * Component for a single item in a EditorToolbar.
 */
export default class EditorEmojiButton extends React.Component {

    static propTypes = {
        quill: PropTypes.instanceOf(Quill).isRequired,
        emoji: PropTypes.object.isRequired,
        closeMenu: PropTypes.func.isRequired,
    };

    /**
     * @inheritDoc
     */
    constructor(props) {
        super(props);
        this.quill = props.quill;
        this.emojiChar = props.emoji.emoji;
        this.closeMenu = props.closeMenu;
    }

    insertEmojiBlot = () => {
        const range = this.quill.getSelection(true);
        this.quill.insertEmbed(range.index, 'emoji', {
            emojiChar: this.emojiChar,
        }, Quill.sources.USER);
        this.quill.setSelection(range.index + 1, Quill.sources.SILENT);
        this.closeMenu();
    }

    render() {
        return <button className="richEditor-button richEditor-insertEmoji" type="button" onClick={this.insertEmojiBlot}>
            {this.emojiChar}
        </button>;
    }
}
