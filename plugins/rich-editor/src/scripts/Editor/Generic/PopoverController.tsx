/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import React from "react";
import Quill from "quill/core";
import * as PropTypes from "prop-types";
import { closeEditorFlyouts, CLOSE_FLYOUT_EVENT } from "../../Quill/utility";
import { withEditor, IEditorContextProps } from "../ContextProvider";
import { IPopoverProps } from "./Popover";

interface IProps extends IEditorContextProps {
    PopoverComponentClass: React.ComponentClass<IPopoverProps>;
    classNameRoot: string;
    icon: JSX.Element;
    targetTitleOnOpen?: boolean;
}

interface IState {
    isVisible: boolean;
}

export class PopoverController extends React.PureComponent<IProps, IState> {
    private quill: Quill;
    private controllerID: string;
    private popoverID: string;
    private buttonID: string;
    private popoverTitleID: string;
    private popoverDescriptionID: string;
    private targetTitleOnOpen: boolean;

    constructor(props) {
        super(props);

        this.quill = props.quill;

        this.state = {
            isVisible: false,
        };

        this.controllerID = props.classNameRoot + "-" + props.editorID;
        this.popoverID = props.classNameRoot + "-popover-" + props.editorID;
        this.buttonID = props.classNameRoot + "-button-" + props.editorID;
        this.popoverTitleID = props.classNameRoot + "-popoverTitle-" + props.editorID;
        this.popoverDescriptionID = props.classNameRoot + "-popoverDescription-" + props.editorID;
        this.targetTitleOnOpen = !!props.targetTitleOnOpen;
    }

    public render() {
        return (
            <div id={this.controllerID} className={this.props.classNameRoot}>
                <button
                    id={this.buttonID}
                    onClick={this.togglePopover}
                    onBlur={this.checkForExternalFocus}
                    className="richEditor-button"
                    type="button"
                    aria-controls={this.props.editorID}
                    aria-expanded={this.state.isVisible}
                    aria-haspopup="true"
                >
                    {this.props.icon}
                </button>
                <this.props.PopoverComponentClass
                    id={this.popoverID}
                    isVisible={this.state.isVisible}
                    blurHandler={this.checkForExternalFocus}
                    closeMenuHandler={this.closeMenu}
                    popoverTitleID={this.popoverTitleID}
                    popoverDescriptionID={this.popoverDescriptionID}
                />
            </div>
        );
    }

    public componentDidMount() {
        document.addEventListener("keydown", this.handleEscapeKey, false);
        document.addEventListener(CLOSE_FLYOUT_EVENT, this.closeMenu);
    }

    public componentWillUnmount() {
        document.removeEventListener("keydown", this.handleEscapeKey, false);
        document.removeEventListener(CLOSE_FLYOUT_EVENT, this.closeMenu);
    }

    /**
     * Handle the escape key.
     *
     * @param {React.KeyboardEvent} event - A synthetic keyboard event.
     */
    private handleEscapeKey = event => {
        if (this.state.isVisible) {
            if (event.code === "Escape") {
                this.closeMenu(event);
            }
        }
    };

    /**
     * Close if we lose focus on the component.
     *
     * @param {React.FocusEvent} event - A synthetic event.
     */
    private checkForExternalFocus = event => {
        // https://reactjs.org/docs/events.html#event-pooling
        event.persist();

        setImmediate(() => {
            const activeElement = document.activeElement;
            const emojiPickerElement = document.getElementById(this.popoverID);
            if (
                activeElement.id !== this.controllerID &&
                emojiPickerElement &&
                !emojiPickerElement.contains(activeElement)
            ) {
                this.closeMenu(event);
            }
        });
    };

    /**
     * Toggle Menu menu
     */
    private togglePopover = () => {
        closeEditorFlyouts(this.constructor.name);
        const titleID = this.popoverTitleID;

        this.setState(
            {
                isVisible: !this.state.isVisible,
            },
            () => {
                if (this.targetTitleOnOpen && this.state.isVisible) {
                    setImmediate(() => {
                        const title = document.getElementById(titleID);
                        title && title.focus();
                    });
                }
            },
        );
    };

    /**
     * Closes menu
     * @param {SyntheticEvent} event - The fired event. This could be a custom event.
     */
    private closeMenu = event => {
        if (event.detail && event.detail.firingKey && event.detail.firingKey === this.constructor.name) {
            return;
        }

        const activeElement = document.activeElement;
        const parentElement = document.getElementById(this.controllerID);

        this.setState({
            isVisible: false,
        });

        if (parentElement && parentElement.contains(activeElement)) {
            const button = document.getElementById(this.buttonID);
            button && button.focus();
        }
    };
}

export default withEditor<IProps>(PopoverController);
