/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { RangeStatic } from "quill/core";
import { withEditor, IWithEditorProps } from "@rich-editor/components/context";
import ToolbarPositioner from "@rich-editor/components/toolbars/pieces/ToolbarPositioner";

interface IProps extends IWithEditorProps {
    selection: RangeStatic;
    isVisible: boolean;
}

interface IState {
    flyoutWidth: number | null;
    flyoutHeight: number | null;
    nubHeight: number | null;
}

export class ToolbarContainer extends React.PureComponent<IProps, IState> {
    private flyoutRef: React.RefObject<any> = React.createRef();
    private nubRef: React.RefObject<any> = React.createRef();

    /**
     * @inheritDoc
     */
    constructor(props) {
        super(props);

        this.state = {
            flyoutHeight: null,
            flyoutWidth: null,
            nubHeight: null,
        };
    }

    public render() {
        const { isVisible, selection } = this.props;
        return (
            <ToolbarPositioner
                {...this.state}
                selectionIndex={selection.index}
                selectionLength={selection.length}
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
            </ToolbarPositioner>
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

export default withEditor<IProps>(ToolbarContainer);
