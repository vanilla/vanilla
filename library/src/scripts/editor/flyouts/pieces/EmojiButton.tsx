/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import classNames from "classnames";
import { convertToSafeEmojiCharacters } from "@vanilla/dom-utils";
import { insertEmojiClasses } from "@library/editor/flyouts/pieces/insertEmojiClasses";

interface IProps {
    emojiChar: string;
    style: React.CSSProperties;
    index: number;
    activeIndex: number;
    onKeyUp: () => void;
    onKeyDown: () => void;
    onKeyRight: () => void;
    onKeyLeft: () => void;
    onClick(event: React.MouseEvent<HTMLButtonElement>): void;
}

/**
 * Component for a single item in a EditorToolbar.
 */
export class EmojiButton extends React.Component<IProps> {
    private domButton: HTMLButtonElement;

    public render() {
        const classesEmoji = insertEmojiClasses();
        const componentClasses = classNames(classesEmoji.root, "emojiChar-" + this.props.emojiChar);
        return (
            <button
                ref={(elButton) => {
                    this.domButton = elButton as HTMLButtonElement;
                }}
                onKeyDown={this.handleKeyPress}
                style={this.props.style}
                className={componentClasses}
                type="button"
                onClick={this.props.onClick}
            >
                <span
                    className="safeEmoji"
                    dangerouslySetInnerHTML={{ __html: convertToSafeEmojiCharacters(this.props.emojiChar) }}
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

export default EmojiButton;
