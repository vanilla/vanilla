/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { dropDownClasses } from "@library/flyouts/dropDownStyles";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import Modal from "@library/modal/Modal";
import ModalSizes from "@library/modal/ModalSizes";
import { t } from "@library/utility/appUtils";
import React, { useCallback, useEffect, useRef, useState } from "react";
import { useFocusWatcher, useEscapeListener } from "@vanilla/react-utils";
import ScreenReaderContent from "@library/layout/ScreenReaderContent";
import { cx } from "@emotion/css";
import classNames from "classnames";
import { TabHandler } from "@vanilla/dom-utils";

export interface IFlyoutToggleChildParameters {
    id: string;
    handleID: string;
    isVisible: boolean;
    closeMenuHandler(event?: React.SyntheticEvent<any>);
    renderAbove?: boolean;
    renderLeft?: boolean;
    buttonRef: React.RefObject<HTMLButtonElement | null>;
}

interface IProps {
    id: string;
    name?: string;
    contentID: string;
    className?: string;
    buttonProps?: React.ComponentProps<typeof Button>;
    buttonContents: React.ReactNode;
    disabled?: boolean;
    children: (props: IFlyoutToggleChildParameters) => JSX.Element;
    onClose?: () => void;
    buttonType?: ButtonTypes;
    buttonClassName?: string;
    isVisible?: boolean;
    forceVisible?: boolean;
    onVisibilityChange?: (isVisible: boolean) => void;
    renderAbove?: boolean;
    renderLeft?: boolean;
    buttonRef?: React.RefObject<HTMLButtonElement>;
    openAsModal: boolean;
    modalSize?: ModalSizes;
    initialFocusElement?: HTMLElement | null;
    tag?: string;
    alwaysRender?: boolean;
    preventFocusOnVisible?: boolean; //in some cases focus will be handled through parent components, so we'll prevent the responsibility here
}

export default function FlyoutToggle(props: IProps) {
    const { initialFocusElement, preventFocusOnVisible, onVisibilityChange, onClose, id, contentID, buttonType } =
        props;

    // Focus management & visibility
    const ownButtonRef = useRef<HTMLButtonElement>(null);
    const buttonRef = props.buttonRef || ownButtonRef;

    const controllerRef = useRef<HTMLDivElement>(null);
    const [ownIsVisible, ownSetVisibility] = useState(false);
    const isVisible = props.forceVisible ? true : props.isVisible !== undefined ? props.isVisible : ownIsVisible;
    const setVisibility = useCallback(
        (visibility: boolean) => {
            ownSetVisibility(visibility);
            onVisibilityChange && onVisibilityChange(visibility);

            // Kludge for interaction with old flyout system.
            if (visibility && window.closeAllFlyouts) {
                window.closeAllFlyouts();
            }
        },
        [ownSetVisibility, onVisibilityChange],
    );

    useEffect(() => {
        if (isVisible && !preventFocusOnVisible) {
            // Focus the inital focusable element when we gain visibility.
            if (initialFocusElement) {
                initialFocusElement.focus();
            } else {
                // Try to find it ourselves
                const tabRoot = document.getElementById(contentID);
                if (!tabRoot) {
                    return;
                }

                const tabber = new TabHandler(tabRoot);
                tabber.getInitial()?.focus();
            }
        }
    }, [isVisible, initialFocusElement, contentID, preventFocusOnVisible]);

    /**
     * Toggle Menu menu
     */
    const buttonClickHandler = useCallback(
        (e) => {
            e.stopPropagation();
            setVisibility(!isVisible);
            e.toElement?.focus();
        },
        [isVisible, setVisibility],
    );

    useEffect(() => {
        const buttonElement = buttonRef.current;
        if (!buttonElement) {
            return;
        }
        buttonElement.addEventListener("click", buttonClickHandler);
        return () => {
            buttonElement.removeEventListener("click", buttonClickHandler);
        };
    }, [buttonRef, buttonClickHandler]);

    const closeMenuHandler = useCallback(
        (event) => {
            event.stopPropagation();
            event.preventDefault();

            onClose && onClose();

            const { activeElement } = document;
            const parentElement = controllerRef.current;

            setVisibility(false);

            if (parentElement && parentElement.contains(activeElement)) {
                if (buttonRef.current) {
                    buttonRef.current.focus();
                    buttonRef.current.classList.add("focus-visible");
                }
            }
        },
        [onClose, controllerRef, buttonRef, setVisibility],
    );

    const onKeyPress = (event: React.KeyboardEvent<any>) => {
        switch (event.key) {
            case "Escape":
                closeMenuHandler(event);
        }
    };

    /**
     * Stop click propagation outside the flyout
     */
    const handleBlockEventPropagation = useCallback((e: React.SyntheticEvent) => {
        e.stopPropagation();
    }, []);

    const handleFocusChange = useCallback(
        (hasFocus: boolean) => {
            if (!hasFocus) {
                setVisibility(false);
            }
        },
        [setVisibility],
    );

    // Focus handling
    useFocusWatcher(controllerRef, handleFocusChange, props.openAsModal);
    useEscapeListener({
        root: controllerRef.current,
        returnElement: buttonRef.current,
        callback: closeMenuHandler,
    });

    const classes = dropDownClasses();
    const buttonClasses = cx(props.buttonClassName, {
        isOpen: isVisible,
    });

    const childrenData: IFlyoutToggleChildParameters = {
        id: contentID,
        handleID: id,
        isVisible: !!isVisible,
        closeMenuHandler,
        renderAbove: props.renderAbove,
        renderLeft: props.renderLeft,
        buttonRef,
    };

    const classesDropDown = !props.openAsModal ? cx("flyouts", classes.root) : null;
    const Tag = (props.tag ?? `div`) as "div";

    const isContentVisible = !props.disabled && isVisible;
    return (
        <Tag
            className={classNames(classesDropDown, props.className, {
                asModal: props.openAsModal,
            })}
            ref={controllerRef}
            onClick={handleBlockEventPropagation}
        >
            <Button
                {...props.buttonProps}
                id={id}
                className={buttonClasses}
                title={props.name}
                aria-label={"name" in props ? props.name : undefined}
                aria-controls={contentID}
                aria-expanded={isVisible}
                aria-haspopup="true"
                disabled={props.disabled}
                buttonType={buttonType}
                buttonRef={buttonRef}
            >
                {props.name && <ScreenReaderContent>{props.name}</ScreenReaderContent>}
                {props.buttonContents}
            </Button>

            <React.Fragment>
                {props.openAsModal ? (
                    <Modal
                        id={contentID}
                        label={t("title")}
                        size={props.modalSize ?? ModalSizes.SMALL}
                        exitHandler={closeMenuHandler}
                        elementToFocusOnExit={buttonRef.current!}
                        isVisible={isContentVisible}
                        onKeyPress={onKeyPress}
                    >
                        {props.children(childrenData)}
                    </Modal>
                ) : (
                    (isContentVisible || props.alwaysRender) && props.children(childrenData)
                )}
            </React.Fragment>
        </Tag>
    );
}
