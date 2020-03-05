/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import ModalSizes from "@library/modal/ModalSizes";
import { ModalView } from "@library/modal/ModalView";
import { logWarning, debug } from "@vanilla/utils";
import React, { ReactElement } from "react";
import ReactDOM from "react-dom";
import { TabHandler } from "@vanilla/dom-utils";
import { mountPortal } from "@vanilla/react-utils";

interface IProps {
    className?: string;
    exitHandler?: (event?: React.SyntheticEvent<any>) => void;
    pageContainer?: Element | null;
    container?: Element;
    description?: string;
    children: React.ReactNode;
    elementToFocus?: HTMLElement;
    size: ModalSizes;
    scrollable?: boolean;
    elementToFocusOnExit?: HTMLElement; // Should either be a specific element or use document.activeElement
    isWholePage?: boolean;
    isVisible: boolean;
    afterContent?: React.ReactNode;
    titleID?: string;
    label?: string; // Necessary if there's no proper title
}

interface IState {
    wasDestroyed: boolean;
}

export const MODAL_CONTAINER_ID = "modals";
export const PAGE_CONTAINER_ID = "page";

/**
 * Mount a modal from a top level context.
 *
 * If you are already in a react context, just use `<Modal />`.
 */
export function mountModal(node: ReactElement<any>) {
    return mountPortal(node, MODAL_CONTAINER_ID);
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
    public static stack: Modal[] = [];
    private closeFocusElement: HTMLElement | null = null;
    private selfRef = React.createRef<HTMLDivElement>();

    public state: IState = {
        wasDestroyed: !this.props.isVisible,
    };

    /**
     * Render the contents into a portal.
     */
    public render() {
        if (this.state.wasDestroyed) {
            return null;
        }

        return mountPortal(
            <>
                <ModalView
                    onDestroyed={this.handleDestroyed}
                    scrollable={this.props.scrollable}
                    onKeyDown={this.handleTabbing}
                    onModalClick={this.handleModalClick}
                    onOverlayClick={this.handleScrimClick}
                    description={this.props.description}
                    size={this.props.size}
                    modalRef={this.selfRef}
                    titleID={"titleID" in this.props ? this.props.titleID : undefined}
                    label={"label" in this.props ? this.props.label : undefined}
                    isVisible={this.props.isVisible}
                >
                    {this.props.children}
                </ModalView>
                {this.props.afterContent}
            </>,
            MODAL_CONTAINER_ID,
            true,
        );
    }

    /**
     * Get a fresh instance of the TabHandler.
     *
     * Since the contents of the modal could be changing constantly
     * we are creating a new instance every time we need it.
     */
    private get tabHandler(): TabHandler | null {
        return this.selfRef.current ? new TabHandler(this.selfRef.current) : null;
    }

    /**
     * Initial component setup.
     *
     * Everything here should be torn down in componentWillUnmount
     */
    public componentDidMount() {
        const pageContainer = this.getPageContainer();
        if (!pageContainer) {
            logWarning(`
A modal was mounted, but the page container could not be found.
Please wrap your primary content area with the ID "${PAGE_CONTAINER_ID}" so it can be hidden to screenreaders.
            `);
        }
    }

    public onMountIn = () => {
        const pageContainer = this.getPageContainer();
        this.setCloseFocusElement();
        this.focusInitialElement();
        pageContainer && pageContainer.setAttribute("aria-hidden", true);

        // Add the escape keyboard listener only on the first modal in the stack.
        if (Modal.stack.length === 0) {
            document.addEventListener("keydown", this.handleDocumentEscapePress);
        }
        Modal.stack.push(this);
    };

    public handleDestroyed = () => {
        // Do some quick state updates to bump the modal to the top of the portal stack.
        // When we set this to true we render null once.
        // The in the update we set back to false.
        // The second render will re-create the portal.
        this.setState({ wasDestroyed: true });

        const pageContainer = this.getPageContainer();
        // Set aria-hidden on page and reenable scrolling if we're removing the last modal
        Modal.stack.pop();
        if (Modal.stack.length === 0) {
            pageContainer && pageContainer.removeAttribute("aria-hidden");

            // This event listener is only added once (on the top modal).
            // So we only remove when clearing the last one.
            document.removeEventListener("keydown", this.handleDocumentEscapePress);
        } else {
            pageContainer && pageContainer.setAttribute("aria-hidden", true);
        }

        // We were destroyed so we should focus back to the last element.
        this.closeFocusElement?.focus();
    };

    public componentDidUpdate(prevProps: IProps, prevState: IState) {
        if (this.props.elementToFocusOnExit !== prevProps.elementToFocusOnExit) {
            this.setCloseFocusElement();
        }

        if (prevState.wasDestroyed && !this.state.wasDestroyed) {
            this.onMountIn();
        }

        if (!prevProps.isVisible && this.props.isVisible) {
            this.setState({ wasDestroyed: false });
        }
    }

    private getPageContainer(): HTMLElement | null {
        return document.getElementById(PAGE_CONTAINER_ID);
    }

    /**
     * Focus the initial element in the Modal.
     */
    private focusInitialElement() {
        const focusElement = this.props.elementToFocus ? this.props.elementToFocus : this.tabHandler?.getInitial();
        if (focusElement) {
            focusElement!.focus();
        }
    }

    /**
     * Set focus on element to target when we close the modal
     */
    private setCloseFocusElement() {
        // if we need to rerender the component, we don't want to include a bad value in the focus history
        if (this.props.elementToFocusOnExit) {
            this.closeFocusElement = this.props.elementToFocusOnExit;
        } else {
            // Get the last focused element
            this.closeFocusElement = document.activeElement as HTMLElement;
        }

        if (debug() && (!this.closeFocusElement || this.closeFocusElement === document.documentElement)) {
            const message = `
Dev Mode Error: Could not detect an element to focus on <Modal /> close.

It seems auto-detection isn't working, so you'll need to specify the "elementToFocusOnExit" props.`;
            throw new Error(message);
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
     * Handle escape press and close the top modal.
     *
     * This listener is added to the document in the event a non-focusable element inside the modal is clicked.
     * In that case focus will be on the document.
     *
     * Because of this we have to be smarter and call only the top modal's escape handler.
     */
    private handleDocumentEscapePress = (event: React.SyntheticEvent | KeyboardEvent) => {
        const topModal = Modal.stack[Modal.stack.length - 1];
        const escKey = 27;

        if ("keyCode" in event && event.keyCode === escKey) {
            event.preventDefault();
            event.stopPropagation();
            if (Modal.stack.length === 1 && this.props.isWholePage) {
                return;
            } else {
                if (topModal.props.exitHandler) {
                    topModal.props.exitHandler(event as any);
                }
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
            this.props.exitHandler(event);
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
        const nextElement = this.tabHandler?.getNext(undefined, true);
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
        const previousElement = this.tabHandler?.getNext();
        if (previousElement) {
            event.preventDefault();
            event.stopPropagation();
            previousElement.focus();
        }
    }
}
