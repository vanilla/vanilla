/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { getRequiredID } from "@library/componentIDs";
import classNames from "classnames";
import Button, { ButtonBaseClass } from "@library/components/forms/Button";
import FocusWatcher from "@library/FocusWatcher";
import EscapeListener from "@library/EscapeListener";

export interface IPopoverControllerChildParameters {
    id: string;
    initialFocusRef?: React.RefObject<any>;
    isVisible: boolean;
    closeMenuHandler(event?: React.SyntheticEvent<any>);
}

export interface IPopoverControllerProps {
    id: string;
    classNameRoot: string;
    buttonContents: React.ReactNode;
    disabled?: boolean;
    children: (props: IPopoverControllerChildParameters) => JSX.Element;
    onClose?: () => void;
    buttonBaseClass: ButtonBaseClass;
    buttonClassName?: string;
    onVisibilityChange?: () => void;
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
    private initalFocusRef: React.RefObject<any>;
    private buttonRef: React.RefObject<HTMLButtonElement>;
    private controllerRef: React.RefObject<HTMLDivElement>;
    private focusWatcher: FocusWatcher;
    private escapeListener: EscapeListener;

    constructor(props) {
        super(props);
        this.controllerRef = React.createRef();
        this.initalFocusRef = React.createRef();
        this.buttonRef = React.createRef();

        this.state = {
            id: getRequiredID(props, "popover"),
            isVisible: false,
        };
    }

    get buttonID(): string {
        return this.state.id + "-handle";
    }

    get contentID(): string {
        return this.state.id + "-contents";
    }

    public render() {
        const buttonClasses = classNames(this.props.buttonClassName, {
            isOpen: this.state.isVisible,
        });

        const title = "name" in this.props ? this.props.name : this.props.selectedItemLabel;

        return (
            <div id={this.state.id} className={this.props.classNameRoot} ref={this.controllerRef}>
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
                >
                    {this.props.buttonContents}
                </Button>
                {!this.props.disabled &&
                    this.props.children({
                        id: this.contentID,
                        initialFocusRef: this.initalFocusRef,
                        isVisible: this.state.isVisible,
                        closeMenuHandler: this.closeMenuHandler,
                    })}
            </div>
        );
    }

    public componentDidUpdate(
        prevProps: IPopoverControllerPropsWithIcon | IPopoverControllerPropsWithTextLabel,
        prevState: IState,
    ) {
        if (!prevState.isVisible && this.state.isVisible) {
            if (this.initalFocusRef.current) {
                this.initalFocusRef.current.focus();
                if (this.props.onVisibilityChange) {
                    this.props.onVisibilityChange();
                }
            } else if (this.buttonRef.current) {
                this.buttonRef.current.focus();
                if (this.props.onVisibilityChange) {
                    this.props.onVisibilityChange();
                }
            }
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
        }
    };

    /**
     * Toggle Menu menu
     */
    private togglePopover = () => {
        this.setState((prevState: IState) => {
            return { isVisible: !prevState.isVisible };
        });
    };

    /**
     * Closes menu
     * @param event - The fired event. This could be a custom event.
     */
    private closeMenuHandler = event => {
        if (event.detail && event.detail.firingKey && event.detail.firingKey === this.constructor.name) {
            return;
        }

        this.props.onClose && this.props.onClose();

        const { activeElement } = document;
        const parentElement = this.controllerRef.current;

        this.setState({
            isVisible: false,
        });

        if (parentElement && parentElement.contains(activeElement)) {
            this.buttonRef.current && this.buttonRef.current.focus();
        }
    };
}
