/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import React from "react";
import * as PropTypes from "prop-types";
import debounce from "lodash/debounce";
import Quill, { RangeStatic } from "quill/core";
import Emitter from "quill/core/emitter";
import { CLOSE_FLYOUT_EVENT } from "../Quill/utility";
import { withEditor, IEditorContextProps } from "./ContextProvider";
import QuillFlyoutBounds from "./QuillFlyoutBounds";

interface IProps extends IEditorContextProps {
    forceVisibility: string;
    setVisibility(value: boolean): void;
}

interface IState {
    range: RangeStatic | null;
    isHidden: boolean;
    flyoutWidth: number | null;
    flyoutHeight: number | null;
    nubHeight: number | null;
}

export class SelectionPositionToolbarContainer extends React.Component<IProps, IState> {
    private quill: Quill;
    private flyoutRef: React.RefObject<any> = React.createRef();
    private nubRef: React.RefObject<any> = React.createRef();

    /**
     * @inheritDoc
     */
    constructor(props) {
        super(props);

        // Quill can directly on the class as it won't ever change in a single instance.
        this.quill = props.quill;

        this.state = {
            range: null,
            isHidden: false,
            flyoutHeight: null,
            flyoutWidth: null,
            nubHeight: null,
        };
        this.props.setVisibility(true);
    }

    public render() {
        const { isHidden, range, ...flyoutProps } = this.state;
        return (
            <QuillFlyoutBounds {...flyoutProps} isActive={!isHidden}>
                {({ x, y }) => {
                    let toolbarStyles: React.CSSProperties = {
                        visibility: "hidden",
                        position: "absolute",
                    };
                    let nubStyles = {};
                    let classes = "richEditor-inlineToolbarContainer richEditor-toolbarContainer ";

                    if (
                        (x && y && !this.state.isHidden && this.props.forceVisibility === "ignore") ||
                        this.props.forceVisibility === "visible"
                    ) {
                        toolbarStyles = {
                            position: "absolute",
                            top: y ? y.position : 0,
                            left: x ? x.position : 0,
                            zIndex: 5,
                            visibility: "visible",
                        };

                        nubStyles = {
                            left: x ? x.nubPosition : 0,
                            top: y ? y.nubPosition : 0,
                        };

                        classes += y && y.nubPointsDown ? "isUp" : "isDown";
                    }
                    return (
                        <div className={classes} style={toolbarStyles} ref={this.flyoutRef}>
                            {this.props.children}
                            <div style={nubStyles} className="richEditor-nubPosition" ref={this.nubRef}>
                                <div className="richEditor-nub" />
                            </div>
                        </div>
                    );
                }}
            </QuillFlyoutBounds>
        );
    }

    /**
     * Mount quill listeners.
     */
    public componentDidMount() {
        this.quill.on("selection-change", this.handleSelectionChange);
        document.addEventListener(CLOSE_FLYOUT_EVENT, this.hideSelf);
        this.setState({
            flyoutWidth: this.flyoutRef.current ? this.flyoutRef.current.offsetWidth : null,
            flyoutHeight: this.flyoutRef.current ? this.flyoutRef.current.offsetHeight : null,
            nubHeight: this.nubRef.current ? this.nubRef.current.offsetHeight : null,
        });
    }

    /**
     * Be sure to remove the listeners when the component unmounts.
     */
    public componentWillUnmount() {
        this.quill.off("selection-change", this.handleSelectionChange);
        document.removeEventListener(CLOSE_FLYOUT_EVENT, this.hideSelf);
    }

    private showSelf = () => {
        this.setState({
            isHidden: false,
        });
        this.props.setVisibility(true);
    };

    private hideSelf = () => {
        this.setState({
            isHidden: true,
        });
        this.props.setVisibility(false);
    };

    /**
     * Handle changes from the editor.
     */
    private handleSelectionChange = (range, oldRange, source) => {
        if (range && range.length > 0 && source === Emitter.sources.USER) {
            const content = this.quill.getText(range.index, range.length);
            const isNewLinesOnly = /(\n){1,}/.test(content);

            if (!isNewLinesOnly) {
                this.showSelf();
            }
        }
    };
}

export default withEditor<IProps>(SelectionPositionToolbarContainer);
