/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import React from "react";
import { closeEditorFlyouts, CLOSE_FLYOUT_EVENT } from "../../Quill/utility";
import { withEditor, IEditorContextProps } from "../ContextProvider";
import { getRequiredID, uniqueIDFromPrefix, IRequiredComponentID } from "@core/Interfaces/componentIDs";

export interface IPopoverControllerChildParameters {
    id: string;
    initialFocusRef: React.RefObject<any>;
    blurHandler: React.FocusEventHandler<any>;
    isVisible: boolean;
    closeMenuHandler(event?: React.SyntheticEvent<any>);
}

interface IProps extends IEditorContextProps {
    id: string;
    contentID: string;
    classNameRoot: string;
    icon: JSX.Element;
    children: (props: IPopoverControllerChildParameters) => JSX.Element;
    onClose?: () => void;
}

interface IState {
    id: string;
    isVisible: boolean;
    contentID: string;
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
            contentID: props.contentID,
        };
    }

    get componentID() {
        return this.state.id + "-contents";
    }

    public render() {
        return (
            <div className={this.props.classNameRoot} ref={this.controllerRef}>
                <button
                    id={this.state.id}
                    onClick={this.togglePopover}
                    onBlur={this.blurHandler}
                    className="richEditor-button"
                    type="button"
                    aria-controls={this.componentID}
                    aria-expanded={this.state.isVisible}
                    aria-haspopup="true"
                    ref={this.buttonRef}
                >
                    {this.props.icon}
                </button>
                {this.props.children({
                    id: this.state.contentID,
                    blurHandler: this.blurHandler,
                    closeMenuHandler: this.closeMenuHandler,
                    initialFocusRef: this.initalFocusRef,
                    isVisible: this.state.isVisible,
                })}
            </div>
        );
    }

    public componentDidUpdate(prevProps: IProps, prevState: IState) {
        if (!prevState.isVisible && this.state.isVisible && this.initalFocusRef.current) {
            this.initalFocusRef.current.focus();
        }
    }

    public componentDidMount() {
        document.addEventListener("keydown", this.handleEscapeKey, false);
        document.addEventListener(CLOSE_FLYOUT_EVENT, this.closeMenuHandler);
    }

    public componentWillUnmount() {
        document.removeEventListener("keydown", this.handleEscapeKey, false);
        document.removeEventListener(CLOSE_FLYOUT_EVENT, this.closeMenuHandler);
    }

    /**
     * Handle the escape key.
     *
     * @param {React.KeyboardEvent} event - A synthetic keyboard event.
     */
    private handleEscapeKey = event => {
        if (this.state.isVisible) {
            if (event.code === "Escape") {
                this.closeMenuHandler(event);
            }
        }
    };

    /**
     * Close if we lose focus on the component.
     *
     * @param {React.FocusEvent} event - A synthetic event.
     */
    private blurHandler = event => {
        // https://reactjs.org/docs/events.html#event-pooling
        event.persist();

        setImmediate(() => {
            const { activeElement } = document;
            if (
                activeElement !== this.controllerRef.current &&
                this.controllerRef.current &&
                !this.controllerRef.current.contains(activeElement)
            ) {
                this.closeMenuHandler(event);
            }
        });
    };

    /**
     * Toggle Menu menu
     */
    private togglePopover = () => {
        closeEditorFlyouts(this.constructor.name);

        this.setState((prevState: IState) => {
            return { isVisible: !prevState.isVisible };
        });
    };

    /**
     * Closes menu
     * @param {SyntheticEvent} event - The fired event. This could be a custom event.
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
