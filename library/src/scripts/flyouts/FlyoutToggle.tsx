/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { RefObject } from "react";
import { getRequiredID } from "../utility/idUtils";
import classNames from "classnames";
import Button from "../forms/Button";
import FocusWatcher from "../utility/FocusWatcher";
import EscapeListener from "../utility/EscapeListener";
import Modal from "../modal/Modal";
import { t } from "../dom/appUtils";
import ModalSizes from "../modal/ModalSizes";
import { dropDownClasses } from "library/src/scripts/flyouts/dropDownStyles";
import { ButtonTypes } from "@library/styles/buttonStyles";

export interface IFlyoutToggleChildParameters {
    id: string;
    initialFocusRef?: React.RefObject<any>;
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
    setExternalButtonRef?: (ref: React.RefObject<HTMLButtonElement>) => void;
    openAsModal: boolean;
}

export interface IFlyoutTogglePropsWithIcon extends IFlyoutToggleProps {
    name: string;
}

export interface IFlyoutTogglePropsWithTextLabel extends IFlyoutToggleProps {
    selectedItemLabel: string;
}

interface IState {
    id: string;
    isVisible: boolean;
}

export default class FlyoutToggle extends React.PureComponent<
    IFlyoutTogglePropsWithIcon | IFlyoutTogglePropsWithTextLabel,
    IState
> {
    public state = {
        id: getRequiredID(this.props, "flyout"),
        isVisible: false,
    };
    private initalFocusRef: React.RefObject<any> = React.createRef();
    private buttonRef: React.RefObject<HTMLButtonElement> = React.createRef();
    private controllerRef: React.RefObject<HTMLDivElement> = React.createRef();
    private focusWatcher: FocusWatcher;
    private escapeListener: EscapeListener;

    public render() {
        const classes = dropDownClasses();
        const buttonClasses = classNames(this.props.buttonClassName, this.props.toggleButtonClassName, {
            isOpen: this.state.isVisible,
        });

        const title = "name" in this.props ? this.props.name : this.props.selectedItemLabel;

        const childrenData = {
            id: this.contentID,
            initialFocusRef: this.initalFocusRef,
            isVisible: this.state.isVisible,
            closeMenuHandler: this.closeMenuHandler,
            renderAbove: this.props.renderAbove,
            renderLeft: this.props.renderLeft,
            openAsModal: this.props.openAsModal,
        };

        const classesDropDown = !this.props.openAsModal ? classNames("flyouts", classes.root) : null;
        return (
            <div
                id={this.state.id}
                className={classNames(classesDropDown, this.props.className, {
                    asModal: this.props.openAsModal,
                })}
                ref={this.controllerRef}
                onClick={this.stopPropagation}
            >
                <Button
                    id={this.buttonID}
                    onClick={this.toggleFlyout}
                    className={buttonClasses}
                    type="button"
                    title={title}
                    aria-label={"name" in this.props ? this.props.name : undefined}
                    aria-controls={this.contentID}
                    aria-expanded={this.state.isVisible}
                    aria-haspopup="true"
                    disabled={this.props.disabled}
                    baseClass={this.props.buttonBaseClass}
                    buttonRef={this.buttonRef}
                >
                    {this.props.buttonContents}
                </Button>

                {!this.props.disabled &&
                    this.state.isVisible && (
                        <React.Fragment>
                            {this.props.openAsModal ? (
                                <Modal
                                    label={t("title")}
                                    size={ModalSizes.SMALL}
                                    exitHandler={this.closeMenuHandler}
                                    elementToFocusOnExit={this.buttonRef.current!}
                                >
                                    {this.props.children(childrenData)}
                                </Modal>
                            ) : (
                                this.props.children(childrenData)
                            )}
                        </React.Fragment>
                    )}
            </div>
        );
    }

    public componentDidUpdate(
        prevProps: IFlyoutTogglePropsWithIcon | IFlyoutTogglePropsWithTextLabel,
        prevState: IState,
    ) {
        if (!prevState.isVisible && this.state.isVisible) {
            if (this.props.onVisibilityChange) {
                this.props.onVisibilityChange(this.state.isVisible);
            }
            if (this.initalFocusRef.current) {
                this.initalFocusRef.current.focus();
            } else if (this.buttonRef.current) {
                this.buttonRef.current.focus();
            }
        } else if (prevState.isVisible && !this.state.isVisible && this.props.onVisibilityChange) {
            this.props.onVisibilityChange(this.state.isVisible);
        }
    }

    /**
     * @inheritDoc
     */
    public componentDidMount() {
        this.focusWatcher = new FocusWatcher(this.controllerRef.current!, this.handleFocusChange);
        this.focusWatcher.start();

        this.escapeListener = new EscapeListener(
            this.controllerRef.current!,
            this.buttonRef.current!,
            this.closeMenuHandler,
        );
        this.escapeListener.start();
        if (this.props.setExternalButtonRef) {
            this.props.setExternalButtonRef(this.buttonRef);
        }
    }

    /**
     * @inheritDoc
     */
    public componentWillUnmount() {
        this.focusWatcher.stop();
        this.escapeListener.stop();
    }

    private handleFocusChange = hasFocus => {
        if (!hasFocus) {
            this.setState({ isVisible: false });
            if (this.props.onVisibilityChange) {
                this.props.onVisibilityChange(false);
            }
        }
    };

    private get buttonID(): string {
        return this.state.id + "-handle";
    }

    private get contentID(): string {
        return this.state.id + "-contents";
    }

    /**
     * Toggle Menu menu
     */
    private toggleFlyout = e => {
        e.stopPropagation();
        this.setState((prevState: IState) => {
            return { isVisible: !prevState.isVisible };
        });
        if (this.props.onVisibilityChange) {
            this.props.onVisibilityChange(this.state.isVisible);
        }
    };

    /**
     * Closes menu
     * @param event - The fired event. This could be a custom event.
     */
    private closeMenuHandler = event => {
        if (event.detail && event.detail.firingKey && event.detail.firingKey === this.constructor.name) {
            return;
        }
        event.stopPropagation();
        event.preventDefault();

        this.props.onClose && this.props.onClose();

        const { activeElement } = document;
        const parentElement = this.controllerRef.current;

        this.setState({
            isVisible: false,
        });

        if (parentElement && parentElement.contains(activeElement)) {
            if (this.buttonRef.current) {
                this.buttonRef.current.focus();
                this.buttonRef.current.classList.add("focus-visible");
            }
        }
        if (this.props.onVisibilityChange) {
            this.props.onVisibilityChange(false);
        }
    };

    /**
     * Stop click propagation outside the flyout
     */
    private stopPropagation = e => {
        e.stopPropagation();
    };
}
