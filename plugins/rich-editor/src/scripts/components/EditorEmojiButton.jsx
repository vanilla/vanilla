/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import React from "react";
import * as PropTypes from "prop-types";
import Quill from "quill/core";
import { parseEmoji } from "@core/emoji-utility";
import classNames from 'classnames';

/**
 * Component for a single item in a EditorToolbar.
 */
export default class EditorEmojiButton extends React.Component {

    static propTypes = {
        quill: PropTypes.instanceOf(Quill).isRequired,
        emojiData: PropTypes.object.isRequired,
        closeMenu: PropTypes.func.isRequired,
        style: PropTypes.object.isRequired,
        index: PropTypes.number.isRequired,
        rowIndex: PropTypes.number.isRequired,
        selectedButton: PropTypes.bool.isRequired,
    };

    /**
     * @inheritDoc
     */
    constructor(props) {
        super(props);
        this.emojiChar = props.emojiData.emoji;
    }

    /**
     * Insert Emoji
     * @param {SyntheticEvent} e
     */
    insertEmojiBlot = (e) => {
        const range = this.props.quill.getSelection(true);
        this.props.quill.insertEmbed(range.index, 'emoji', {
            emojiChar: this.emojiChar,
        }, Quill.sources.USER);
        this.props.quill.setSelection(range.index + 1, Quill.sources.SILENT);
        this.props.closeMenu(e);
    }

    componentDidUpdate = (prevProps) => {
        if (this.domButton && prevProps.selectedButton) {
            this.domButton.focus();
        }
    }

    render() {
        const componentClasses = classNames(
            'richEditor-button',
            'richEditor-insertEmoji',
            'emojiChar-' + this.emojiChar,
        );
        return <button ref={(button) => { this.domButton = button; }}  style={this.props.style} className={componentClasses} data-index={this.props.position} type="button" onClick={this.insertEmojiBlot}>
            <span className="safeEmoji" dangerouslySetInnerHTML={{__html: parseEmoji(this.emojiChar)}} />
        </button>;
    }
}
