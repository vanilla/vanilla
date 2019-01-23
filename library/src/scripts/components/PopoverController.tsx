/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { RefObject } from "react";
import { getRequiredID } from "@library/componentIDs";
import classNames from "classnames";
import Button, { ButtonBaseClass } from "@library/components/forms/Button";
import FocusWatcher from "@library/FocusWatcher";
import EscapeListener from "@library/EscapeListener";
import Modal from "@library/components/modal/Modal";
import { t } from "@library/application";
import ModalSizes from "@library/components/modal/ModalSizes";

export interface IPopoverControllerChildParameters {
    id: string;
    initialFocusRef?: React.RefObject<any>;
    isVisible: boolean;
    closeMenuHandler(event?: React.SyntheticEvent<any>);
    renderAbove?: boolean;
    renderLeft?: boolean;
}

export interface IPopoverControllerProps {
    id: string;
    className?: string;
    buttonContents: React.ReactNode;
    disabled?: boolean;
    children: (props: IPopoverControllerChildParameters) => JSX.Element;
    onClose?: () => void;
    buttonBaseClass: ButtonBaseClass;
    buttonClassName?: string;
    onVisibilityChange?: (isVisible: boolean) => void;
    renderAbove?: boolean;
    renderLeft?: boolean;
    PopoverController?: string;
    toggleButtonClassName?: string;
    setExternalButtonRef?: (ref: React.RefObject<HTMLButtonElement>) => void;
    openAsModal: boolean;
}

export interface IPopoverControllerPropsWithIcon extends IPopoverControllerProps {
    name: string;
}

export interface IPopoverControllerPropsWithTextLabel extends IPopoverControllerProps {
    selectedItemLabel: string;
}

interface IState {
    id: string;
    isVisible: boolean;
}

export default class PopoverController extends React.PureComponent<
    IPopoverControllerPropsWithIcon | IPopoverControllerPropsWithTextLabel,
    IState
> {
    public state = {
        id: getRequiredID(this.props, "popover"),
        isVisible: false,
    };
    private initalFocusRef: React.RefObject<any> = React.createRef();
    private buttonRef: React.RefObject<HTMLButtonElement> = React.createRef();
    private controllerRef: React.RefObject<HTMLDivElement> = React.createRef();
    private focusWatcher: FocusWatcher;
    private escapeListener: EscapeListener;

    public render() {
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

        return (
            <div
                id={this.state.id}
                className={classNames(
                    { dropDown: !this.props.openAsModal, asModal: this.props.openAsModal },
                    this.props.className,
                )}
                ref={this.controllerRef}
                onClick={this.stopPropagation}
            >
                <Button
                    id={this.buttonID}
                    onClick={this.togglePopover}
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
        prevProps: IPopoverControllerPropsWithIcon | IPopoverControllerPropsWithTextLabel,
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
    private togglePopover = e => {
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
     * Stop click propagation outside popover
     */
    private stopPropagation = e => {
        e.stopPropagation();
    };
}
