/**
 * @author Stéphane (slafleche) LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import React from "react";
import * as PropTypes from "prop-types";
import Quill from "quill/quill";
import EditorEmojiMenu from "../components/EditorEmojiMenu";
import * as Icons from "./Icons";

export default class EditorEmojiPicker extends React.Component {

    /** @type {number} */
    static count;

    static propTypes = {
        quill: PropTypes.instanceOf(Quill).isRequired,
    };

    /**
     * @inheritDoc
     */
    constructor(props) {
        super(props);

        if (!this.count) {
            this.count = 1;
        } else {
            this.count++;
        }

        this.editorID = this.count;
        this.menuID = "emojiMenu-menu-" + this.editorID;
        this.buttonID = "emojiMenu-button-" + this.editorID;
        this.menuTitleID = "emojiMenu-title-" + this.editorID;

        // Quill can directly on the class as it won't ever change in a single instance.
        this.quill = props.quill;

        this.state = {
            isVisible: false,
        };

        this.escFunction = this.escFunction.bind(this);

    }

    escFunction(event){
        if(event.keyCode === 27) {
            this.closeMenu(event);
        }
    }

    /**
     * Toggle Menu menu
     * @param {SyntheticEvent} e
     */

    toggleEmojiMenu = (e) => {
        this.setState(prevState => ({
            isVisible: !prevState.isVisible,
        }));
    }

    componentDidMount(){
        document.addEventListener("keydown", this.escFunction, false);
    }

    componentWillUnmount(){
        document.removeEventListener("keydown", this.escFunction, false);
    }

    /**
     * Closes menu
     * @param {SyntheticEvent} e
     */

    closeMenu = (e) => {
        this.setState({
            isVisible: false,
        });

        e.preventDefault();
        e.stopPropagation();
    }

    /**
     * @inheritDoc
     */
    render() {
        return <div className="emojiPicker">
            <EditorEmojiMenu {...this.state} menuTitleID={this.menuTitleID} quill={this.quill} closeMenu={this.closeMenu}/>
            <button onClick={this.toggleEmojiMenu} className="richEditor-button" type="button" id={this.buttonID} aria-controls={this.menuID} aria-expanded={this.state.isVisible} aria-haspopup="menu">
                {Icons.emoji()}
            </button>
        </div>;
    }
}
