/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import Quill from "quill/core";
import classNames from "classnames";
import { convertToSafeEmojiCharacters } from "@vanilla/dom-utils";
import { IWithEditorProps } from "@rich-editor/editor/context";
import { withEditor } from "@rich-editor/editor/withEditor";
import { insertEmojiClasses } from "@rich-editor/flyouts/pieces/insertEmojiClasses";

interface IProps extends IWithEditorProps {
    emojiData: {
        emoji: string;
    };
    style: React.CSSProperties;
    index: number;
    activeIndex: number;
    onKeyUp: () => void;
    onKeyDown: () => void;
    onKeyRight: () => void;
    onKeyLeft: () => void;
    closeMenuHandler(event: React.SyntheticEvent<any>);
}

/**
 * Component for a single item in a EditorToolbar.
 */
export class EmojiButton extends React.Component<IProps> {
    private emojiChar: string;
    private quill: Quill;
    private domButton: HTMLButtonElement;

    constructor(props) {
        super(props);
        this.emojiChar = props.emojiData.emoji;
        this.quill = props.quill;
    }

    public render() {
        const classesEmoji = insertEmojiClasses();
        const componentClasses = classNames("richEditor-insertEmoji", classesEmoji.root, "emojiChar-" + this.emojiChar);
        return (
            <button
                ref={elButton => {
                    this.domButton = elButton as HTMLButtonElement;
                }}
                onKeyDown={this.handleKeyPress}
                style={this.props.style}
                className={componentClasses}
                type="button"
                onClick={this.insertEmojiBlot}
            >
                <span
                    className="safeEmoji"
                    dangerouslySetInnerHTML={{ __html: convertToSafeEmojiCharacters(this.emojiChar) }}
                />
            </button>
        );
    }

    /**
     * Check to see if element should get focus
     */
    public componentDidUpdate() {
        this.checkFocus();
    }

    /**
     * Check to see if element should get focus
     */
    public componentDidMount() {
        this.checkFocus();
    }

    private checkFocus() {
        if (this.domButton && this.props.activeIndex === this.props.index) {
            this.domButton.focus();
        }
    }

    /**
     * Insert Emoji
     */
    private insertEmojiBlot = (event: React.SyntheticEvent<any>) => {
        const range = this.quill.getSelection(true);
        this.quill.insertEmbed(
            range.index,
            "emoji",
            {
                emojiChar: this.emojiChar,
            },
            Quill.sources.USER,
        );
        this.quill.setSelection(range.index + 1, 0, Quill.sources.SILENT);
        this.props.closeMenuHandler(event);
    };

    /**
     * Handle key presses
     */
    private handleKeyPress = (event: React.KeyboardEvent<any>) => {
        switch (event.key) {
            case "ArrowDown":
                event.stopPropagation();
                event.preventDefault();
                this.props.onKeyDown();
                break;
            case "ArrowUp":
                event.stopPropagation();
                event.preventDefault();
                this.props.onKeyUp();
                break;
            case "ArrowRight":
                event.stopPropagation();
                event.preventDefault();
                this.props.onKeyRight();
                break;
            case "ArrowLeft":
                event.stopPropagation();
                event.preventDefault();
                this.props.onKeyLeft();
                break;
        }
    };
}

export default withEditor<IProps>(EmojiButton);
