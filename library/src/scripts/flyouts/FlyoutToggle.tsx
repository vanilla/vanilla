/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useRef, useMemo, useState, useCallback, useEffect, useLayoutEffect } from "react";
import FocusWatcher, { useFocusWatcher } from "@library/dom/FocusWatcher";
import EscapeListener, { useEscapeListener } from "@library/dom/EscapeListener";
import { t } from "@library/utility/appUtils";
import { ButtonTypes } from "@library/forms/buttonStyles";
import { getRequiredID, uniqueIDFromPrefix } from "@library/utility/idUtils";
import Button from "@library/forms/Button";
import { dropDownClasses } from "@library/flyouts/dropDownStyles";
import classNames from "classnames";
import Modal from "@library/modal/Modal";
import ModalSizes from "@library/modal/ModalSizes";
import { forceRenderStyles } from "typestyle";

export interface IFlyoutToggleChildParameters {
    id: string;
    isVisible: boolean;
    closeMenuHandler(event?: React.SyntheticEvent<any>);
    renderAbove?: boolean;
    renderLeft?: boolean;
}

export interface IFlyoutToggleProps {
    id: string;
    className?: string;
    buttonContents: React.ReactNode;
    disabled?: boolean;
    children: (props: IFlyoutToggleChildParameters) => JSX.Element;
    onClose?: () => void;
    buttonBaseClass: ButtonTypes;
    buttonClassName?: string;
    onVisibilityChange?: (isVisible: boolean) => void;
    renderAbove?: boolean;
    renderLeft?: boolean;
    toggleButtonClassName?: string;
    buttonRef?: React.RefObject<HTMLButtonElement>;
    openAsModal: boolean;
    initialFocusElement?: HTMLElement | null;
}

export interface IFlyoutTogglePropsWithIcon extends IFlyoutToggleProps {
    name: string;
}

export interface IFlyoutTogglePropsWithTextLabel extends IFlyoutToggleProps {
    selectedItemLabel: string;
}

type IProps = IFlyoutTogglePropsWithIcon | IFlyoutTogglePropsWithTextLabel;

export default function FlyoutToggle(props: IProps) {
    const { initialFocusElement } = props;
    const title = "name" in props ? props.name : props.selectedItemLabel;

    // IDs unique to the component instance.
    const ID = useMemo(() => uniqueIDFromPrefix("flyout"), []);
    const buttonID = ID + "-handle";
    const contentID = ID + "-contents";

    // Focus management & visibility
    const ownButtonRef = useRef<HTMLButtonElement>(null);
    const buttonRef = props.buttonRef || ownButtonRef;

    const controllerRef = useRef<HTMLDivElement>(null);
    const [isVisible, setVisibility] = useState(false);
    useEffect(() => {
        if (isVisible && initialFocusElement) {
            // Focus the inital focusable element when we gain visibility.
            if (initialFocusElement) {
                initialFocusElement.focus();
            }
        }
        props.onVisibilityChange && props.onVisibilityChange(isVisible);
    }, [isVisible, initialFocusElement, props.onVisibilityChange]);

    /**
     * Toggle Menu menu
     */
    const buttonClickHandler = useCallback(
        (e: React.MouseEvent) => {
            e.stopPropagation();
            setVisibility(!isVisible);
            if (props.onVisibilityChange) {
                props.onVisibilityChange(isVisible);
            }
        },
        [isVisible, setVisibility, props.onVisibilityChange],
    );

    const closeMenuHandler = useCallback(
        event => {
            if (event) {
                event.stopPropagation();
                event.preventDefault();

                props.onClose && props.onClose();

                const { activeElement } = document;
                const parentElement = controllerRef.current;

                setVisibility(false);

                if (parentElement && parentElement.contains(activeElement)) {
                    if (buttonRef.current) {
                        buttonRef.current.focus();
                        buttonRef.current.classList.add("focus-visible");
                    }
                }
                if (props.onVisibilityChange) {
                    props.onVisibilityChange(false);
                }
            }
        },
        [props.onClose, controllerRef.current, buttonRef.current, props.onVisibilityChange],
    );

    /**
     * Stop click propagation outside the flyout
     */
    const handleBlockEventPropogation = useCallback((e: React.SyntheticEvent) => {
        if (e && e.stopPropagation) {
            e.stopPropagation();
        }
    }, []);

    const handleFocusChange = (hasFocus: boolean) => {
        if (!hasFocus) {
            setVisibility(false);
            if (props.onVisibilityChange) {
                props.onVisibilityChange(false);
            }
        }
    };

    // Focus handling
    useFocusWatcher(controllerRef.current, handleFocusChange, props.openAsModal);
    useEscapeListener({
        root: controllerRef.current,
        returnElement: buttonRef.current,
        callback: closeMenuHandler,
    });

    const classes = dropDownClasses();
    const buttonClasses = classNames(props.buttonClassName, props.toggleButtonClassName, {
        isOpen: isVisible,
    });
    // Prevent flashing of content sometimes.
    forceRenderStyles();

    const childrenData = {
        id: contentID,
        isVisible,
        closeMenuHandler,
        renderAbove: props.renderAbove,
        renderLeft: props.renderLeft,
        openAsModal: props.openAsModal,
    };

    const classesDropDown = !props.openAsModal ? classNames("flyouts", classes.root) : null;
    return (
        <div
            id={ID}
            className={classNames(classesDropDown, props.className, {
                asModal: props.openAsModal,
            })}
            ref={controllerRef}
            onClick={handleBlockEventPropogation}
        >
            <Button
                id={buttonID}
                onClick={buttonClickHandler}
                className={buttonClasses}
                title={title}
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

            {!props.disabled && isVisible && (
                <React.Fragment>
                    {props.openAsModal ? (
                        <Modal
                            label={t("title")}
                            size={ModalSizes.SMALL}
                            exitHandler={closeMenuHandler}
                            elementToFocusOnExit={buttonRef.current!}
                        >
                            {props.children(childrenData)}
                        </Modal>
                    ) : (
                        props.children(childrenData)
                    )}
                </React.Fragment>
            )}
        </div>
    );
}
