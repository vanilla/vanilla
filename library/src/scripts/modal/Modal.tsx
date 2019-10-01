/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import ModalSizes from "@library/modal/ModalSizes";
import { ModalView } from "@library/modal/ModalView";
import { logWarning } from "@vanilla/utils";
import React, { ReactElement } from "react";
import ReactDOM from "react-dom";
import { TabHandler } from "@vanilla/dom-utils";

interface IHeadingDescription {
    titleID: string;
}

interface ITextDescription {
    label: string; // Necessary if there's no proper title
}

interface IModalCommonProps {
    className?: string;
    exitHandler?: (event?: React.SyntheticEvent<any>) => void;
    pageContainer?: Element | null;
    container?: Element;
    description?: string;
    children: React.ReactNode;
    elementToFocus?: HTMLElement;
    size: ModalSizes;
    scrollable?: boolean;
    elementToFocusOnExit: HTMLElement; // Should either be a specific element or use document.activeElement
    isWholePage?: boolean;
}

interface IModalTextDescription extends IModalCommonProps, ITextDescription {}

interface IModalHeadingDescription extends IModalCommonProps, IHeadingDescription {}

type IProps = IModalTextDescription | IModalHeadingDescription;

interface IState {
    exitElementSet: boolean;
}

export const MODAL_CONTAINER_ID = "modals";
export const PAGE_CONTAINER_ID = "page";

/**
 * Mount a modal with ReactDOM. This is only needed at the top level context.
 *
 * If you are already in a react context, just use `<Modal />`.
 * Note: Using this will clear any other modals mounted with this component.
 *
 * @param element The <Modal /> element to render.
 */
export function mountModal(element: ReactElement<any>) {
    // Ensure we have our modal container.
    let modals = document.getElementById(MODAL_CONTAINER_ID);
    if (!modals) {
        modals = document.createElement("div");
        modals.id = MODAL_CONTAINER_ID;
        document.body.appendChild(modals);
    } else {
        ReactDOM.unmountComponentAtNode(modals);
    }

    ReactDOM.render(
        element,
        modals, // Who cares where we go. This is a portal anyways.
    );
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
    public static defaultProps: Partial<IProps> = {
        isWholePage: false,
    };

    public static focusHistory: HTMLElement[] = [];
    public static stack: Modal[] = [];
    private selfRef = React.createRef<HTMLDivElement>();

    public state = {
        exitElementSet: false,
    };

    /**
     * Render the contents into a portal.
     */
    public render() {
        const portal = ReactDOM.createPortal(
            <ModalView
                scrollable={this.props.scrollable}
                onKeyDown={this.handleTabbing}
                onModalClick={this.handleModalClick}
                onOverlayClick={this.handleScrimClick}
                description={this.props.description}
                size={this.props.size}
                modalRef={this.selfRef}
                titleID={"titleID" in this.props ? this.props.titleID : undefined}
                label={"label" in this.props ? this.props.label : undefined}
            >
                {this.props.children}
            </ModalView>,
            this.getModalContainer(),
        );
        return portal;
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
        const pageContainer = this.getPageContainer();
        if (!pageContainer) {
            logWarning(`
A modal was mounted, but the page container could not be found.
Please wrap your primary content area with the ID "${PAGE_CONTAINER_ID}" so it can be hidden to screenreaders.
            `);
        }

        this.focusInitialElement();
        pageContainer && pageContainer.setAttribute("aria-hidden", true);

        // Add the escape keyboard listener only on the first modal in the stack.
        if (Modal.stack.length === 0) {
            document.addEventListener("keydown", this.handleDocumentEscapePress);
        }
        Modal.stack.push(this);
        this.forceUpdate();
    }

    /**
     * We need to check again for focus if the focus is by ref
     */
    public componentDidUpdate(prevProps: IProps) {
        if (prevProps.elementToFocus !== this.props.elementToFocus) {
            this.focusInitialElement();
        }
        this.setCloseFocusElement();
    }

    /**
     * Tear down setup from componentDidMount
     */
    public componentWillUnmount() {
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
        const prevFocussedElement = Modal.focusHistory.pop() || document.body;
        prevFocussedElement.focus();
        setImmediate(() => {
            prevFocussedElement.focus();
        });
    }

    private getModalContainer(): HTMLElement {
        let container = document.getElementById(MODAL_CONTAINER_ID)!;
        if (container === null) {
            container = document.createElement("div");
            container.id = MODAL_CONTAINER_ID;
            document.body.appendChild(container);
        }
        return container;
    }

    private getPageContainer(): HTMLElement | null {
        return document.getElementById(PAGE_CONTAINER_ID);
    }

    /**
     * Focus the initial element in the Modal.
     */
    private focusInitialElement() {
        const focusElement = this.props.elementToFocus ? this.props.elementToFocus : this.tabHandler.getInitial();
        if (focusElement) {
            focusElement!.focus();
        }
    }

    /**
     * Set focus on element to target when we close the modal
     */
    private setCloseFocusElement() {
        // if we need to rerender the component, we don't want to include a bad value in the focus history
        if (this.props.elementToFocusOnExit && !this.state.exitElementSet) {
            Modal.focusHistory.push(this.props.elementToFocusOnExit);
            this.setState({
                exitElementSet: true,
            });
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
