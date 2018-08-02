/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import React from "react";
import { withEditor, IWithEditorProps } from "@rich-editor/components/context";
import { getRequiredID } from "@dashboard/componentIDs";
import { watchFocusInDomTree } from "@dashboard/dom";
import { createEditorFlyoutEscapeListener } from "@rich-editor/quill/utility";

export interface IPopoverControllerChildParameters {
    id: string;
    initialFocusRef: React.RefObject<any>;
    isVisible: boolean;
    closeMenuHandler(event?: React.SyntheticEvent<any>);
}

interface IProps {
    id: string;
    classNameRoot: string;
    icon: JSX.Element;
    children: (props: IPopoverControllerChildParameters) => JSX.Element;
    onClose?: () => void;
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

    get componentID(): string {
        return this.state.id + "-contents";
    }

    public render() {
        return (
            <div className={this.props.classNameRoot} ref={this.controllerRef}>
                <button
                    id={this.state.id}
                    onClick={this.togglePopover}
                    className="richEditor-button richEditor-embedButton"
                    type="button"
                    aria-controls={this.componentID}
                    aria-expanded={this.state.isVisible}
                    aria-haspopup="true"
                    ref={this.buttonRef}
                >
                    {this.props.icon}
                </button>
                {this.props.children({
                    id: this.componentID,
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
            } else if (this.buttonRef.current) {
                this.buttonRef.current.focus();
            }
        }
    }

    public componentDidMount() {
        watchFocusInDomTree(this.controllerRef.current!, this.handleFocusChange);
        createEditorFlyoutEscapeListener(this.controllerRef.current!, this.buttonRef.current!, this.closeMenuHandler);
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
