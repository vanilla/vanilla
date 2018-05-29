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
import { withEditor, IEditorContextProps } from "./ContextProvider";
import QuillFlyoutBounds from "./QuillFlyoutBounds";

interface IProps extends IEditorContextProps {
    selection: RangeStatic | null;
    isVisible: boolean;
}

interface IState {
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
            flyoutHeight: null,
            flyoutWidth: null,
            nubHeight: null,
        };
    }

    public render() {
        const { isVisible, selection } = this.props;
        const selectionIndex = selection ? selection.index : null;
        const selectionLength = selection ? selection.length : null;
        return (
            <QuillFlyoutBounds
                {...this.state}
                selectionIndex={selectionIndex}
                selectionLength={selectionLength}
                isActive={isVisible}
            >
                {({ x, y }) => {
                    let toolbarStyles: React.CSSProperties = {
                        visibility: "hidden",
                        position: "absolute",
                    };
                    let nubStyles = {};
                    let classes = "richEditor-inlineToolbarContainer richEditor-toolbarContainer ";

                    if (x && y && isVisible) {
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
        this.setState({
            flyoutWidth: this.flyoutRef.current ? this.flyoutRef.current.offsetWidth : null,
            flyoutHeight: this.flyoutRef.current ? this.flyoutRef.current.offsetHeight : null,
            nubHeight: this.nubRef.current ? this.nubRef.current.offsetHeight : null,
        });
    }
}

export default withEditor<IProps>(SelectionPositionToolbarContainer);
