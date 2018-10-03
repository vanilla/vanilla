/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import ReactDOM from "react-dom";
import { disableBodyScroll, enableBodyScroll } from "body-scroll-lock";
import TabHandler from "@library/TabHandler";
import { logError } from "@library/utility";
import { getRequiredID } from "@library/componentIDs";
import classNames from "classnames";
import { ModalSizes } from "./ModalSizes";

interface IProps {
    className?: string;
    exitHandler?: () => void;
    appContainer?: Element;
    container?: Element;
    children: React.ReactNode;
    initialFocus?: HTMLElement;
    description?: string; //For Accessibility
    size: ModalSizes;
}

interface IState {
    id: string;
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
export default class Modal extends React.Component<IProps, IState> {
    public static defaultProps = {
        appContainer: document.getElementById("app"),
        container: document.getElementById("modals"),
    };

    public static focusHistory: HTMLElement[] = [];

    private selfRef: React.RefObject<HTMLDivElement> = React.createRef();

    private get modalID() {
        return this.state.id + "-modal";
    }

    private get descriptionID() {
        return this.state.id + "-description";
    }

    public constructor(props) {
        super(props);
        this.state = {
            id: getRequiredID(props, "modal"),
        };
    }

    /**
     * Render the contents into a portal.
     */
    public render() {
        return ReactDOM.createPortal(
            <div className="overlay" onKeyDown={this.handleEscapeKeyPress} onClick={this.handleScrimClick}>
                <div
                    id={this.modalID}
                    role="dialog"
                    aria-modal={true}
                    className={classNames(
                        "modal",
                        {
                            isFullScreen: this.props.size === ModalSizes.FULL_SCREEN,
                            inheritHeight: this.props.size === ModalSizes.FULL_SCREEN,
                            isLarge: this.props.size === ModalSizes.LARGE,
                            isMedium: this.props.size === ModalSizes.MEDIUM,
                            isSmall: this.props.size === ModalSizes.SMALL,
                        },
                        this.props.className,
                    )}
                    ref={this.selfRef}
                    onKeyDown={this.handleTabbing}
                    onClick={this.handleModalClick}
                    aria-describedby={this.descriptionID}
                >
                    <div id={this.descriptionID} className="sr-only">
                        {this.props.description}
                    </div>
                    {this.props.children}
                </div>
            </div>,
            this.props.container!,
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
        Modal.focusHistory.push(document.activeElement as HTMLElement);
        this.focusInitialElement();
        this.props.appContainer!.setAttribute("aria-hidden", true);
        disableBodyScroll(this.selfRef.current!);
    }

    /**
     * Tear down setup from componentDidMount
     */
    public componentWillUnmount() {
        this.props.appContainer!.removeAttribute("aria-hidden");
        enableBodyScroll(this.selfRef.current!);
        const prevFocussedElement = Modal.focusHistory.pop() || document.body;
        prevFocussedElement.focus();
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
    private handleEscapeKeyPress = (event: React.KeyboardEvent) => {
        const escKey = 27;

        if (event.keyCode === escKey) {
            event.preventDefault();
            event.stopPropagation();
            if (this.props.exitHandler) {
                this.props.exitHandler();
            }
        }
    };

    /**
     * Stop propagation of events at the top of the modal so they don't make it
     * to the scrim click handler.
     */
    private handleModalClick = (event: React.MouseEvent) => {
        event.stopPropagation();
    };

    /**
     * Call the exit handler when the scrim is clicked directly.
     */
    private handleScrimClick = (event: React.MouseEvent) => {
        event.preventDefault();
        if (this.props.exitHandler) {
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
