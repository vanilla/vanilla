/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import React from "react";
import Waypoint from "react-waypoint";
import { log } from "@dashboard/utility";

interface IProps {
    containerRef: Element;
}

interface IState {
    windowHeight: number;
}

export default class StickyMenuBar extends React.Component<IProps, IState> {
    public static getDerivedStateFromProps(nextProps, prevState) {
        prevState.containerRef = nextProps.containerRef;
        return prevState;
    }

    /**
     * @inheritDoc
     */
    constructor(props) {
        super(props);
        this.state = {
            windowHeight: window.innerHeight,
        };

        log("container ref: ", props.containerRef);

        log("window height: ", this.state.windowHeight);
    }

    public handleResize() {
        this.setState({
            windowHeight: window.innerHeight,
        });

        log(this.state);
    }

    public checkStickyness1(props) {
        log("checkstickyness1: ", props);
    }
    public checkStickyness2(props) {
        log("checkstickyness2: ", props);
    }

    public render() {
        return (
            <div className="richEditor-stickyMenuBar">
                {/* Handles when we stop being sticky from top */}
                <Waypoint bottomOffset="100%" onEnter={this.checkStickyness1} onLeave={this.checkStickyness1} />
                {/* Handles when we stick from bottom */}
                <Waypoint onEnter={this.checkStickyness2} onLeave={this.checkStickyness2} />
            </div>
        );
    }

    public componentDidMount() {
        window.addEventListener("resize", this.handleResize);
    }

    public componentWillUnmount() {
        window.removeEventListener("resize", this.handleResize);
    }
}
