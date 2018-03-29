/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import React from "react";
import * as PropTypes from "prop-types";
import { closeEditorFlyouts, CLOSE_FLYOUT_EVENT } from "../../Quill/utility";
import { withEditor, editorContextTypes } from "../ContextProvider";

export class PopoverController extends React.Component {

    static propTypes = {
        ...editorContextTypes,
        PopoverComponentClass: PropTypes.func.isRequired,
        classNameRoot: PropTypes.string.isRequired,
        icon: PropTypes.element.isRequired,
        targetTitleOnOpen: PropTypes.bool,
    };

    /**
     * @inheritDoc
     */
    constructor(props) {
        super(props);

        this.quill = props.quill;

        this.state = {
            isVisible: false,
        };

        this.controllerID = this.props.classNameRoot + "-" + this.props.editorID;
        this.popoverID = this.props.classNameRoot + "-popover-" + this.props.editorID;
        this.buttonID = this.props.classNameRoot + "-button-" + this.props.editorID;
        this.popoverTitleID =  this.props.classNameRoot + "-popoverTitle-" + this.props.editorID;
        this.popoverDescriptionID =  this.props.classNameRoot + "-popoverDescription-" + this.props.editorID;
        this.targetTitleOnOpen = !!this.props.targetTitleOnOpen;
    }

    componentDidMount(){
        document.addEventListener("keydown", this.handleEscapeKey, false);
        document.addEventListener(CLOSE_FLYOUT_EVENT, this.closeMenu);
    }

    componentWillUnmount(){
        document.removeEventListener("keydown", this.handleEscapeKey, false);
        document.removeEventListener(CLOSE_FLYOUT_EVENT, this.closeMenu);
    }

    /**
     * Handle the escape key.
     *
     * @param {React.KeyboardEvent} event - A synthetic keyboard event.
     */
    handleEscapeKey = (event) => {
        if(event.keyCode === 27 && this.state.isVisible) {
            this.closeMenu(event);
        }
    };

    /**
     * Close if we lose focus on the component.
     *
     * @param {React.FocusEvent} event - A synthetic event.
     */
    checkForExternalFocus = (event) => {
        // https://reactjs.org/docs/events.html#event-pooling
        event.persist();

        setImmediate(() => {
            const activeElement = document.activeElement;
            const emojiPickerElement = document.getElementById(this.popoverID);
            if(activeElement.id !== this.controllerID && !emojiPickerElement.contains(activeElement)) {
                this.closeMenu(event);
            }
        });
    };

    /**
     * Toggle Menu menu
     */
    togglePopover = () => {
        closeEditorFlyouts(this.constructor.name);
        const titleID = this.popoverTitleID;

        this.setState({
            isVisible: !this.state.isVisible,
        }, () => {
            if (this.targetTitleOnOpen && this.state.isVisible) {
                setImmediate(() => {
                    document.getElementById(titleID).focus();
                });
            }
        });
    };

    /**
     * Closes menu
     * @param {SyntheticEvent} event - The fired event. This could be a custom event.
     */
    closeMenu = (event) => {
        if (event.detail && event.detail.firingKey && event.detail.firingKey === this.constructor.name) {
            return;
        }

        const activeElement = document.activeElement;
        const parentElement = document.getElementById(this.controllerID);

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
        return <div id={this.controllerID} className={this.props.classNameRoot}>
            <button
                id={this.buttonID}
                onClick={this.togglePopover}
                onBlur={this.checkForExternalFocus}
                className="richEditor-button"
                type="button"
                aria-controls={this.editorID}
                aria-expanded={this.state.isVisible}
                aria-haspopup="true"
            >
                {this.props.icon}
            </button>
            <this.props.PopoverComponentClass
                id={this.popoverID}
                isVisible={this.state.isVisible}
                blurHandler={this.checkForExternalFocus}
                closeMenu={this.closeMenu}
                popoverTitleID={this.popoverTitleID}
                popoverDescriptionID={this.popoverDescriptionID}
                targetTitleOnOpen={this.props.targetTitleOnOpen}
            />
        </div>;
    }
}

export default withEditor(PopoverController);
