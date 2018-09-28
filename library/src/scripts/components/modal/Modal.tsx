/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import ReactDOM from "react-dom";
import TabHandler from "@library/TabHandler";
import { logError } from "@library/utility";

interface IProps {
    exitHandler: () => void;
    appContainer: Element;
    container: Element;
    children: React.ReactNode;
}

/**
 * An accessible Modal component.
 *
 * - Renders into the `#modals` element with a React portal.
 * - Implements tab trapping.
 * - Closes with the escape key.
 * - Sets aria-hidden on the main application.
 * - Prevents scrolling of the body.
 * - Focuses the first focusable element in the Modal.
 */
export default class Modal extends React.Component<IProps> {
    private selfRef: React.RefObject<HTMLDivElement> = React.createRef();

    /**
     * Render the contents into a portal.
     */
    public render() {
        return ReactDOM.createPortal(
            <div className="modal inheritHeight" ref={this.selfRef} onKeyDown={this.handleTabbing}>
                {this.props.children}
            </div>,
            this.props.container,
        );
    }

    /**
     * Get a fresh instance of the TabHandler.
     *
     * Since the contents of the modal could be changing constantly
     * we are creating a new instance every time we need it.
     */
    private get tabHandler(): TabHandler {
        return new TabHandler(this.selfRef.current!);
    }

    /**
     * Initial component setup.
     *
     * Everything here should be torn down in componentWillUnmount
     */
    public componentDidMount() {
        this.focusInitialElement();
        document.addEventListener("keydown", this.handleEscapeKeyPress);
        this.props.appContainer.setAttribute("aria-hidden", true);
        document.body.style.position = "fixed";
    }

    /**
     * Tear down setup from componentDidMount
     */
    public componentWillUnmount() {
        this.props.appContainer.removeAttribute("aria-hidden");
        document.removeEventListener("keydown", this.handleEscapeKeyPress);
        document.body.style.position = "initial";
    }

    /**
     * Focus the initial element in the Modal.
     */
    private focusInitialElement() {
        const initialElement = this.tabHandler.getInitial();
        if (initialElement) {
            initialElement.focus();
        } else {
            logError("A modal was created without any focusable element");
        }
    }

    /**
     * Handle tab keyboard presses.
     */
    private handleTabbing = (event: React.KeyboardEvent) => {
        const tabKey = 9;

        if (event.shiftKey && event.keyCode === tabKey) {
            this.handleShiftTab(event);
        } else if (!event.shiftKey && event.keyCode === tabKey) {
            this.handleTab(event);
        }
    };

    /**
     * Handle the escape key.
     *
     * This needs to be a global listener or it will not work if something in the component isn't focused.
     */
    private handleEscapeKeyPress = (event: KeyboardEvent) => {
        const escKey = 27;

        if (event.keyCode === escKey) {
            event.preventDefault();
            event.stopImmediatePropagation();
            this.props.exitHandler();
        }
    };

    /**
     * Handle shift tab key presses.
     *
     * - Focuses the previous element in the modal.
     * - Loops if we are at the beginning
     *
     * @param event The react event.
     */
    private handleShiftTab(event: React.KeyboardEvent) {
        const nextElement = this.tabHandler.getNext(undefined, true);
        if (nextElement) {
            event.preventDefault();
            event.stopPropagation();
            nextElement.focus();
        }
    }

    /**
     * Handle tab key presses.
     *
     * - Focuses the next element in the modal.
     * - Loops if we are at the end.
     *
     * @param event The react event.
     */
    private handleTab(event: React.KeyboardEvent) {
        const previousElement = this.tabHandler.getNext();
        if (previousElement) {
            event.preventDefault();
            event.stopPropagation();
            previousElement.focus();
        }
    }
}
