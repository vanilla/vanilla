/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { getRequiredID } from "@library/componentIDs";
import { addEscapeListener, watchFocusInDomTree } from "@library/dom";
import classNames from "classnames";

export interface IPopoverControllerChildParameters {
    id: string;
    initialFocusRef?: React.RefObject<any>;
    isVisible: boolean;
    closeMenuHandler(event?: React.SyntheticEvent<any>);
}

interface IProps {
    id: string;
    classNameRoot: string;
    icon: JSX.Element;
    children: (props: IPopoverControllerChildParameters) => JSX.Element;
    onClose?: () => void;
    buttonClasses: string;
    onVisibilityChange?: () => void;
    name?: string;
}

interface IState {
    id: string;
    isVisible: boolean;
}

export default class PopoverController extends React.PureComponent<IProps, IState> {
    private initalFocusRef: React.RefObject<any>;
    private buttonRef: React.RefObject<HTMLButtonElement>;
    private controllerRef: React.RefObject<HTMLDivElement>;

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
        const buttonClasses = classNames(this.props.buttonClasses, {
            isOpen: this.state.isVisible,
        });

        return (
            <div id={this.state.id} className={this.props.classNameRoot} ref={this.controllerRef}>
                <button
                    id={this.buttonID}
                    onClick={this.togglePopover}
                    className={buttonClasses}
                    type="button"
                    title={this.props.name}
                    aria-label={this.props.name}
                    aria-controls={this.contentID}
                    aria-expanded={this.state.isVisible}
                    aria-haspopup="true"
                    ref={this.buttonRef}
                >
                    <span className="u-noInteraction">{this.props.icon}</span>
                </button>
                {this.props.children({
                    id: this.contentID,
                    initialFocusRef: this.initalFocusRef,
                    isVisible: this.state.isVisible,
                    closeMenuHandler: this.closeMenuHandler,
                })}
            </div>
        );
    }

    public componentDidUpdate(prevProps: IProps, prevState: IState) {
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

    public componentDidMount() {
        watchFocusInDomTree(this.controllerRef.current!, this.handleFocusChange);
        addEscapeListener(this.controllerRef.current!, this.buttonRef.current!, this.closeMenuHandler);
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
