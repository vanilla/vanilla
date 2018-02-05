/**
 * @author Stéphane (slafleche) LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import React from "react";
import * as PropTypes from "prop-types";
import Quill from "quill";
import EditorEmojiMenu from "../components/EditorEmojiMenu";
import { t } from "@core/utility";


export default class EditorEmojiPicker extends React.Component {

    /** @type {number} */
    static count;

    static propTypes = {
        quill: PropTypes.instanceOf(Quill).isRequired,
    };

    /** @type {Quill} */
    quill;

    /**
     * @type {Object}
     * @property {RangeStatic} - The current quill selected text range..
     */
    state;

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
            this.closeMenu();
        }
    }

    toggleEmojiMenu = () => {
        this.setState(prevState => ({
            isVisible: !prevState.isVisible
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
                <svg className="richEditorInline-icon" viewBox="0 0 24 24">
                    <title>{t('Emoji')}</title>
                    <path fill="currentColor" d="M12,4 C7.58168889,4 4,7.58168889 4,12 C4,16.4181333 7.58168889,20 12,20 C16.4183111,20 20,16.4181333 20,12 C20,7.58168889 16.4183111,4 12,4 Z M12,18.6444444 C8.33631816,18.6444444 5.35555556,15.6636818 5.35555556,12 C5.35555556,8.33631816 8.33631816,5.35555556 12,5.35555556 C15.6636818,5.35555556 18.6444444,8.33631816 18.6444444,12 C18.6444444,15.6636818 15.6636818,18.6444444 12,18.6444444 Z M10.7059556,10.2024889 C10.7059556,9.51253333 10.1466667,8.95324444 9.45671111,8.95324444 C8.76675556,8.95324444 8.20746667,9.51253333 8.20746667,10.2024889 C8.20746667,10.8924444 8.76675556,11.4517333 9.45671111,11.4517333 C10.1466667,11.4517333 10.7059556,10.8924444 10.7059556,10.2024889 Z M14.5432889,8.95306667 C13.8533333,8.95306667 13.2940444,9.51235556 13.2940444,10.2023111 C13.2940444,10.8922667 13.8533333,11.4515556 14.5432889,11.4515556 C15.2332444,11.4515556 15.7925333,10.8922667 15.7925333,10.2023111 C15.7925333,9.51235556 15.2332444,8.95306667 14.5432889,8.95306667 Z M14.7397333,14.1898667 C14.5767111,14.0812444 14.3564444,14.1256889 14.2471111,14.2883556 C14.2165333,14.3336889 13.4823111,15.4012444 11.9998222,15.4012444 C10.5198222,15.4012444 9.7856,14.3374222 9.75271111,14.2885333 C9.64444444,14.1256889 9.42471111,14.0803556 9.2608,14.1884444 C9.09688889,14.2963556 9.05155556,14.5169778 9.15964444,14.6810667 C9.19804444,14.7393778 10.1242667,16.1125333 11.9998222,16.1125333 C13.8752,16.1125333 14.8014222,14.7395556 14.84,14.6810667 C14.9477333,14.5173333 14.9027556,14.2983111 14.7397333,14.1898667 Z"/>
                </svg>
            </button>
        </div>;
    }
}
