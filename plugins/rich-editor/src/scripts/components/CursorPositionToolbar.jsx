/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import React from "react";
import * as PropTypes from "prop-types";
import Quill from "quill/core";

export default class CursorPositionToolbar extends React.Component {
    static propTypes = {
        quill: PropTypes.instanceOf(Quill).isRequired,
        children: PropTypes.node.isRequired,
        isVisible: PropTypes.bool.isRequired,
    };


    /**
     * @inheritDoc
     */
    constructor(props) {
        super(props);

        // Quill can directly on the class as it won't ever change in a single instance.
        this.quill = props.quill;

        this.state = {
            range: CursorPositionToolbar.initialRange,
        };

        this.handleEditorChange = this.handleEditorChange.bind(this);
    }

    /**
     * Calculate the Y coordinates for the toolbar and it's nub.
     *
     * @returns {Object} - The Y coordinates.
     * @property {number} toolbarPosition
     * @property {number} nubPosition
     * @property {boolean} nubPointsDown
     */
    getYCoordinates() {
        const bounds = this.quill.getBounds(this.state.range);

        if (!bounds || !this.selfNode) {
            return null;
        }

        const offset = 0;
        let pilcroPosition = bounds.top - this.selfNode.offsetHeight - offset;
        let nubPointsDown = true;

        const isNearStart = bounds.top < 30;
        if (isNearStart) {
            pilcroPosition = bounds.bottom + offset;
            nubPointsDown = false;
        }

        return {
            pilcroPosition,
            nubPointsDown,
        };
    }

    /**
     * @inheritDoc
     */
    render() {
        const y = this.getYCoordinates();
        console.log(y);
        let toolbarStyles = {
            visibility: "hidden",
            position: "absolute",
        };
        let nubStyles = {};
        let pilcroStyles = {
            transform: "translateY(0)",
        };
        let classes = "richEditor-inlineMenu ";

        if (y && this.props.isVisible) {
            toolbarStyles = {
                position: "absolute",
                top: y ? y.toolbarPosition : 0,
                left: "20px",
                zIndex: 5,
                visibility: "visible",
            };

            nubStyles = {
                left: "50%",
                top: y ? y.nubPosition : 0,
            };

            classes += y && y.nubPointsDown ? "isUp" : "isDown";
        }

        return <div className={classes} style={toolbarStyles} ref={(ref) => this.selfNode = ref}>
            {this.props.children}
            <div style={nubStyles} className="richEditor-nubPosition" ref={(nub) => this.nub = nub}>
                <div className="richEditor-nub"/>
            </div>
        </div>;
    }
}
