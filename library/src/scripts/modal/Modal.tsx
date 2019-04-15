/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import ReactDOM from "react-dom";
import React, { ReactElement, useCallback, useRef, useEffect } from "react";
import classNames from "classnames";
import ModalSizes from "@library/modal/ModalSizes";
import { uniqueIDFromPrefix } from "@library/utility/idUtils";
import { modalClasses } from "@library/modal/modalStyles";
import TabHandler, { useTabKeyboardHandler } from "@library/dom/TabHandler";
import { inheritHeightClass } from "@library/styles/styleHelpers";
import { logWarning } from "@library/utility/utils";
import { forceRenderStyles } from "typestyle";
import ScrollLock from "react-scrolllock";

interface IHeadingDescription {
    titleID: string;
}

interface ITextDescription {
    label: string; // Necessary if there's no proper title
}

type ExitHandler = (event?: KeyboardEvent | React.MouseEvent) => void;

interface IModalCommonProps {
    className?: string;
    exitHandler?: ExitHandler;
    pageContainer?: Element | null;
    container?: Element;
    description?: string;
    children: React.ReactNode;
    elementToFocus?: HTMLElement;
    size: ModalSizes;
    elementToFocusOnExit: HTMLElement; // Should either be a specific element or use document.activeElement
}

interface IModalTextDescription extends IModalCommonProps, ITextDescription {}

interface IModalHeadingDescription extends IModalCommonProps, IHeadingDescription {}

type IProps = IModalTextDescription | IModalHeadingDescription;

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

interface IStackItem {
    exitHandler?: ExitHandler;
    previousScrollPosition: number;
}
const modalStack: IStackItem[] = [];

export default function Modal(props: IProps) {
    const { size } = props;
    const isWholePage = [ModalSizes.FULL_SCREEN, ModalSizes.MODAL_AS_SIDE_PANEL].includes[size];

    const classes = modalClasses();

    const selfRef = useRef<HTMLDivElement>(null);
    const tabHandler = selfRef.current ? new TabHandler(selfRef.current) : null;

    const domID = uniqueIDFromPrefix("modal");
    const modalID = domID + "-modal";
    const descriptionID = domID + "-description";

    const handleScrimClick = useCallback(
        (event: React.MouseEvent) => {
            event.preventDefault();
            if (props.exitHandler) {
                props.exitHandler(event);
            }
        },
        [props.exitHandler],
    );

    const getModalContainer = (): HTMLElement => {
        let container = document.getElementById(MODAL_CONTAINER_ID)!;
        if (container === null) {
            container = document.createElement("div");
            container.id = MODAL_CONTAINER_ID;
            document.body.appendChild(container);
        }
        return container;
    };

    const getPageContainer = (): HTMLElement | null => {
        return document.getElementById(PAGE_CONTAINER_ID);
    };

    /**
     * Focus the initial element in the Modal.
     */
    const focusInitialElement = () => {
        if (!tabHandler) {
            return null;
        }
        const focusElement = !!props.elementToFocus ? props.elementToFocus : tabHandler.getInitial();
        if (focusElement) {
            focusElement!.focus();
        }
    };

    /**
     * Stop propagation of events at the top of the modal so they don't make it
     * to the scrim click handler.
     */
    const handleModalClick = (event: React.MouseEvent) => {
        event.stopPropagation();
    };

    const handleKeyboardTab = useTabKeyboardHandler(selfRef.current);

    // Page container warning.
    useEffect(() => {
        if (modalStack.length === 0) {
            const pageContainer = getPageContainer();
            if (!pageContainer) {
                logWarning(`
    A modal was mounted, but the page container could not be found.
    Please wrap your primary content area with the ID "${PAGE_CONTAINER_ID}" so it can be hidden to screenreaders.
                `);
            }

            pageContainer && pageContainer.setAttribute("aria-hidden", true);
            return () => {
                pageContainer && pageContainer.removeAttribute("aria-hidden");
            };
        }
    });

    useEffect(() => {
        // Add the escape keyboard listener only on the first modal in the stack.
        if (modalStack.length === 0) {
            /**
             * Handle escape press and close the top modal.
             *
             * This listener is added to the document in the event a non-focusable element inside the modal is clicked.
             * In that case focus will be on the document.
             *
             * Because of this we have to be smarter and call only the top modal's escape handler.
             */
            const handleDocumentEscapePress = (event: KeyboardEvent) => {
                const topModal = modalStack[modalStack.length - 1];
                const escKey = 27;

                if ("keyCode" in event && event.keyCode === escKey) {
                    event.preventDefault();
                    event.stopPropagation();
                    if (modalStack.length === 1 && isWholePage) {
                        return;
                    } else {
                        if (topModal.exitHandler) {
                            topModal.exitHandler(event);
                        }
                    }
                }
            };
            document.addEventListener("keydown", handleDocumentEscapePress);
            return () => {
                document.removeEventListener("keydown", handleDocumentEscapePress);
            };
        }
    }, []);

    const { elementToFocusOnExit, exitHandler } = props;

    useEffect(() => {
        focusInitialElement();
        return () => {
            setImmediate(() => {
                elementToFocusOnExit && elementToFocusOnExit.focus();
            });
        };
    }, [elementToFocusOnExit]);

    // the modal stack
    useEffect(() => {
        // modalStack.push({
        //     exitHandler,
        //     previousScrollPosition: window.scrollY,
        // });
        // document.body.style.overflow = "hidden";
        // document.body.style.maxHeight = "100vh";
        // document.body.style.position = "fixed";
        // return () => {
        //     const modalData = modalStack.pop();
        //     if (modalStack.length === 0 && isWholePage && modalData) {
        //         document.body.style.overflow = "initial";
        //         document.body.style.maxHeight = "initial";
        //         document.body.style.position = "initial";
        //         window.scrollTo({ top: modalData.previousScrollPosition });
        //     }
        // };
    }, [exitHandler]); // Only ever runs once per modal).

    const overlayRef = useRef<HTMLDivElement>(null);

    const portal = ReactDOM.createPortal(
        <ScrollLock>
            <div className={classes.overlay} ref={overlayRef} onClick={handleScrimClick}>
                <div
                    id={modalID}
                    role="dialog"
                    aria-modal={true}
                    className={classNames(
                        classes.root,
                        {
                            isFullScreen: size === ModalSizes.FULL_SCREEN || size === ModalSizes.MODAL_AS_SIDE_PANEL,
                            isSidePanel: size === ModalSizes.MODAL_AS_SIDE_PANEL,
                            isDropDown: size === ModalSizes.MODAL_AS_DROP_DOWN,
                            isLarge: size === ModalSizes.LARGE,
                            isMedium: size === ModalSizes.MEDIUM,
                            isSmall: size === ModalSizes.SMALL,
                            isShadowed: size === ModalSizes.LARGE || ModalSizes.MEDIUM || ModalSizes.SMALL,
                        },
                        size === ModalSizes.FULL_SCREEN ? inheritHeightClass() : "",
                        props.className,
                    )}
                    ref={selfRef}
                    onKeyDown={handleKeyboardTab}
                    onClick={handleModalClick}
                    aria-label={"label" in props ? props.label : undefined}
                    aria-labelledby={"titleID" in props ? props.titleID : undefined}
                    aria-describedby={props.description ? descriptionID : undefined}
                >
                    {props.description && (
                        <div id={descriptionID} className="sr-only">
                            {props.description}
                        </div>
                    )}
                    {props.children}
                </div>
            </div>
        </ScrollLock>,
        getModalContainer(),
    );
    // We HAVE to render force the styles to render before componentDidMount
    // And our various focusing tricks or the page will jump.
    forceRenderStyles();
    return portal;
}
