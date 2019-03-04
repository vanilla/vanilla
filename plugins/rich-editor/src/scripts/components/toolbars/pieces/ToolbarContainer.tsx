/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { RangeStatic } from "quill/core";
import { withEditor, IWithEditorProps } from "@rich-editor/components/context";
import ToolbarPositioner from "@rich-editor/components/toolbars/pieces/ToolbarPositioner";
import classNames from "classnames";
import { inlineToolbarClasses } from "@rich-editor/styles/richEditorStyles/inlineToolbarClasses";
import { nubClasses } from "@rich-editor/styles/richEditorStyles/nubClasses";
import { richEditorVariables } from "@rich-editor/styles/richEditorStyles/richEditorVariables";

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
        const classesInlineToolbar = inlineToolbarClasses();
        const editorVariables = richEditorVariables();
        return (
            <ToolbarPositioner
                {...this.state}
                selectionIndex={selection.index}
                selectionLength={selection.length}
                isActive={isVisible}
                className={classesInlineToolbar.root}
            >
                {({ x, y, offsetX = editorVariables.scrollContainer.overshoot }) => {
                    const classesNub = nubClasses();

                    let toolbarStyles: React.CSSProperties = {
                        visibility: "hidden",
                        position: "absolute",
                    };
                    let nubStyles = {};
                    let classes = classNames(
                        "richEditor-inlineToolbarContainer",
                        "richEditor-toolbarContainer",
                        classesInlineToolbar.root,
                    );

                    if (x && y && isVisible) {
                        toolbarStyles = {
                            position: "absolute",
                            top: y ? y.position : 0,
                            left: (x ? x.position : 0) + editorVariables.scrollContainer.overshoot,
                            zIndex: 5,
                            visibility: "visible",
                        };

                        nubStyles = {
                            left: x && x.nubPosition ? x.nubPosition : 0,
                            top: y && y.nubPosition ? y.nubPosition : 0,
                        };
                        classes += y && y.nubPointsDown ? " isUp" : " isDown";
                    }
                    return (
                        <div className={classes} style={toolbarStyles} ref={this.flyoutRef}>
                            {this.props.children}
                            <div
                                style={nubStyles}
                                className={classNames("richEditor-nubPosition", classesNub.position)}
                                ref={this.nubRef}
                            >
                                <div className={classNames("richEditor-nub", classesNub.root)} />
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
        const richEditorVars = richEditorVariables();
        this.setState({
            flyoutWidth: this.flyoutRef.current ? this.flyoutRef.current.offsetWidth : null,
            flyoutHeight: this.flyoutRef.current ? this.flyoutRef.current.offsetHeight : null,
            nubHeight: richEditorVars.nub.width || 12,
        });
    }
}

export default withEditor<IProps>(ToolbarContainer);
