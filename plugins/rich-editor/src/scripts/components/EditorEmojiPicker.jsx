/**
 * @author Stéphane (slafleche) LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import React from "react";
import EmojiPopover from "./EmojiPopover";
import * as Icons from "./Icons";
import UniqueID from "react-html-id";
import { closeEditorFlyouts, CLOSE_FLYOUT_EVENT } from "../quill-utilities";
import { withEditor, editorContextTypes } from "./EditorProvider";

export class EditorEmojiPicker extends React.Component {

    static propTypes = {
        ...editorContextTypes,
    };

    /**
     * @inheritDoc
     */
    constructor(props) {
        super(props);

        UniqueID.enableUniqueIds(this);
        const prefix = 'emojiPicker-';
        this.ID = this.nextUniqueId();
        this.pickerID = prefix + this.ID;
        this.menuID = prefix + 'menu-' + this.ID;
        this.buttonID = prefix + 'button-' + this.ID;
        this.menuTitleID = prefix + 'title-' + this.ID;
        this.menuDescriptionID = prefix + 'description-' + this.ID;
        this.emojiCategoriesID = prefix + 'categories-' + this.ID;

        // Quill can directly on the class as it won't ever change in a single instance.
        this.quill = props.quill;

        this.state = {
            isVisible: false,
        };

        this.escFunction = this.escFunction.bind(this);

    }

    /**
     * Handle the escape key.
     *
     * @param {React.KeyboardEvent} event - A synthetic keyboard event.
     */
    escFunction(event){
        if(event.keyCode === 27) {
            this.closeMenu(event);
        }
    }

    /**
     * Close if we lose focus on the component
     * @param {React.FocusEvent} event - A synthetic event.
     */
    checkForExternalFocus = (event) => {
        setImmediate(() => {
            const activeElement = document.activeElement;
            const emojiPickerElement = document.getElementById(this.pickerID);
            if(activeElement.id !== this.pickerID && !emojiPickerElement.contains(activeElement)) {
                this.closeMenu(event);
            }
        });
    };

    /**
     * Toggle Menu menu
     */
    toggleEmojiMenu = () => {
        closeEditorFlyouts(this.constructor.name);

        this.setState({
            isVisible: !this.state.isVisible,
        });
    };

    componentDidMount(){
        document.addEventListener("keydown", this.escFunction, false);
        document.addEventListener(CLOSE_FLYOUT_EVENT, this.closeMenu);
    }

    componentWillUnmount(){
        document.removeEventListener("keydown", this.escFunction, false);
        document.removeEventListener(CLOSE_FLYOUT_EVENT, this.closeMenu);
    }

    /**
     * Closes menu
     * @param {SyntheticEvent} event - The fired event. This could be a custom event.
     */
    closeMenu = (event) => {
        if (event.detail && event.detail.firingKey && event.detail.firingKey === this.constructor.name) {
            return;
        }

        const activeElement = document.activeElement;
        const parentElement = document.getElementById(this.pickerID);

        this.setState({
            isVisible: false,
        });

        if (parentElement.contains(activeElement)) {
            document.getElementById(this.buttonID).focus();
        }
    };

    /**
     * @inheritDoc
     */
    render() {
        return <div id={this.pickerID} className="emojiPicker">
            <button onClick={this.toggleEmojiMenu} onBlur={this.checkForExternalFocus} className="richEditor-button" type="button" id={this.buttonID} aria-controls={this.menuID} aria-expanded={this.state.isVisible} aria-haspopup="true">
                {Icons.emoji()}
            </button>
            <EmojiPopover {...this.state} checkForExternalFocus={this.checkForExternalFocus} pickerID={this.pickerID} menuID={this.menuID} menuDescriptionID={this.menuDescriptionID} emojiCategoriesID={this.emojiCategoriesID} menuTitleID={this.menuTitleID} closeMenu={this.closeMenu}/>
        </div>;
    }
}

export default withEditor(EditorEmojiPicker);
