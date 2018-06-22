/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import React from "react";
import Quill from "quill/core";
import classNames from "classnames";
import { convertToSafeEmojiCharacters } from "@dashboard/dom";
import { withEditor, IEditorContextProps } from "@rich-editor/components/context";

interface IProps extends IEditorContextProps {
    emojiData: {
        emoji: string;
    };
    style: React.CSSProperties;
    index: number;
    rowIndex: number;
    isSelectedButton: boolean;
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
        const componentClasses = classNames(
            "richEditor-button",
            "richEditor-insertEmoji",
            "emojiChar-" + this.emojiChar,
        );
        return (
            <button
                ref={elButton => {
                    this.domButton = elButton as HTMLButtonElement;
                }}
                onKeyDown={this.handleKeyPress}
                style={this.props.style}
                className={componentClasses}
                data-index={this.props.index}
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
    public componentDidUpdate(prevProps) {
        if (this.domButton && prevProps.isSelectedButton) {
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
            case "ArrowRight":
            case "ArrowDown":
                event.stopPropagation();
                event.preventDefault();
                const nextSibling = this.domButton.nextSibling;
                if (nextSibling instanceof HTMLElement) {
                    nextSibling.focus();
                }
                break;
            case "ArrowUp":
            case "ArrowLeft":
                event.stopPropagation();
                event.preventDefault();
                const previousSibling = this.domButton.previousSibling;
                if (previousSibling instanceof HTMLElement) {
                    previousSibling.focus();
                }
                break;
        }
    };
}

export default withEditor<IProps>(EmojiButton);
