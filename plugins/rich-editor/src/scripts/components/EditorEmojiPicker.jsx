/**
 * @author Stéphane (slafleche) LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import React from "react";
import * as PropTypes from "prop-types";
import Quill from "quill/core";
import EditorEmojiMenu from "../components/EditorEmojiMenu";
import * as Icons from "./Icons";
import UniqueID from "react-html-id";

export default class EditorEmojiPicker extends React.Component {

    static propTypes = {
        quill: PropTypes.instanceOf(Quill).isRequired,
    };

    /**
     * @inheritDoc
     */
    constructor(props) {
        super(props);

        UniqueID.enableUniqueIds(this);
        this.ID = this.nextUniqueId();
        this.menuID = "emojiMenu-menu-" + this.ID;
        this.buttonID = "emojiMenu-button-" + this.ID;
        this.menuTitleID = "emojiMenu-title-" + this.ID;

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
            <button onClick={this.toggleEmojiMenu} className="richEditor-button" type="button" id={this.buttonID} aria-controls={this.menuID} aria-expanded={this.state.isVisible} aria-haspopup="menu">
                {Icons.emoji()}
            </button>
            <EditorEmojiMenu {...this.state} menuID={this.menuID} menuTitleID={this.menuTitleID} quill={this.quill} closeMenu={this.closeMenu}/>
        </div>;
    }
}
