/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { dropDownClasses } from "@library/flyouts/dropDownStyles";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonStyles";
import Modal from "@library/modal/Modal";
import ModalSizes from "@library/modal/ModalSizes";
import { t } from "@library/utility/appUtils";
import { uniqueIDFromPrefix } from "@library/utility/idUtils";
import classNames from "classnames";
import React, { useCallback, useEffect, useMemo, useRef, useState } from "react";
import { forceRenderStyles } from "typestyle";
import { useFocusWatcher, useEscapeListener } from "@vanilla/react-utils";

export interface IFlyoutToggleChildParameters {
    id: string;
    isVisible: boolean;
    closeMenuHandler(event?: React.SyntheticEvent<any>);
    renderAbove?: boolean;
    renderLeft?: boolean;
}

interface IProps {
    name?: string;
    id: string;
    className?: string;
    buttonContents: React.ReactNode;
    disabled?: boolean;
    children: (props: IFlyoutToggleChildParameters) => JSX.Element;
    onClose?: () => void;
    buttonBaseClass: ButtonTypes;
    buttonClassName?: string;
    isVisible?: boolean;
    onVisibilityChange?: (isVisible: boolean) => void;
    renderAbove?: boolean;
    renderLeft?: boolean;
    toggleButtonClassName?: string;
    buttonRef?: React.RefObject<HTMLButtonElement>;
    openAsModal: boolean;
    initialFocusElement?: HTMLElement | null;
    tag?: string;
}

export default function FlyoutToggle(props: IProps) {
    const { initialFocusElement, onVisibilityChange, onClose } = props;

    // IDs unique to the component instance.
    const ID = useMemo(() => uniqueIDFromPrefix("flyout"), []);
    const buttonID = ID + "-handle";
    const contentID = ID + "-contents";

    // Focus management & visibility
    const ownButtonRef = useRef<HTMLButtonElement>(null);
    const buttonRef = props.buttonRef || ownButtonRef;

    const controllerRef = useRef<HTMLDivElement>(null);
    const [ownIsVisible, ownSetVisibility] = useState(false);
    const isVisible = props.isVisible !== undefined ? props.isVisible : ownIsVisible;
    const setVisibility = useCallback(
        (visibility: boolean) => {
            ownSetVisibility(visibility);
            onVisibilityChange && onVisibilityChange(visibility);

            // Kludge for interaction with old flyout system.
            if (visibility === true && window.closeAllFlyouts) {
                window.closeAllFlyouts();
            }
        },
        [ownSetVisibility, onVisibilityChange],
    );

    useEffect(() => {
        if (isVisible && initialFocusElement) {
            // Focus the inital focusable element when we gain visibility.
            if (initialFocusElement) {
                initialFocusElement.focus();
            }
        }
    }, [isVisible, initialFocusElement]);

    /**
     * Toggle Menu menu
     */
    const buttonClickHandler = useCallback(
        (e: MouseEvent) => {
            e.stopPropagation();
            setVisibility(!isVisible);
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
        event => {
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

    /**
     * Stop click propagation outside the flyout
     */
    const handleBlockEventPropogation = useCallback((e: React.SyntheticEvent) => {
        e.stopPropagation();
    }, []);

    const handleFocusChange = (hasFocus: boolean) => {
        if (!hasFocus) {
            setVisibility(false);
        }
    };

    // Focus handling
    useFocusWatcher(controllerRef, handleFocusChange, props.openAsModal);
    useEscapeListener({
        root: controllerRef.current,
        returnElement: buttonRef.current,
        callback: closeMenuHandler,
    });

    const classes = dropDownClasses();
    const buttonClasses = classNames(props.buttonClassName, props.toggleButtonClassName, {
        isOpen: isVisible,
    });
    useEffect(() => {
        // Prevent flashing on the first render
        forceRenderStyles();
    }, []);

    const childrenData: IFlyoutToggleChildParameters = {
        id: contentID,
        isVisible: !!isVisible,
        closeMenuHandler,
        renderAbove: props.renderAbove,
        renderLeft: props.renderLeft,
    };

    const classesDropDown = !props.openAsModal ? classNames("flyouts", classes.root) : null;
    const Tag = (props.tag ?? `div`) as "div";

    const isContentVisible = !props.disabled && isVisible;
    return (
        <Tag
            id={ID}
            className={classNames(classesDropDown, props.className, {
                asModal: props.openAsModal,
            })}
            ref={controllerRef}
            onClick={handleBlockEventPropogation}
        >
            <Button
                id={buttonID}
                className={buttonClasses}
                title={props.name}
                aria-label={"name" in props ? props.name : undefined}
                aria-controls={contentID}
                aria-expanded={isVisible}
                aria-haspopup="true"
                disabled={props.disabled}
                baseClass={props.buttonBaseClass}
                buttonRef={buttonRef}
            >
                {props.buttonContents}
            </Button>

            <React.Fragment>
                {props.openAsModal ? (
                    <Modal
                        label={t("title")}
                        size={ModalSizes.SMALL}
                        exitHandler={closeMenuHandler}
                        elementToFocusOnExit={buttonRef.current!}
                        isVisible={isContentVisible}
                    >
                        {props.children(childrenData)}
                    </Modal>
                ) : (
                    isContentVisible && props.children(childrenData)
                )}
            </React.Fragment>
        </Tag>
    );
}
