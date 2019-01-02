/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import shave from "shave";
import throttle from "lodash/throttle";

interface IProps {
    children: React.ReactNode;
    lines?: number;
    expand?: boolean;
}

export default class TruncatedText extends React.PureComponent<IProps> {
    private ref = React.createRef<HTMLDivElement>();

    public static defaultProps: Partial<IProps> = {
        lines: 3,
        expand: false,
    };

    public render() {
        return (
            <span className="truncatedText" ref={this.ref}>
                {this.props.children}
            </span>
        );
    }

    public componentDidMount() {
        this.truncate();
        window.addEventListener("resize", this.resizeListener);
    }

    public componentWillUnmount() {
        window.removeEventListener("resize", this.resizeListener);
    }

    public componentDidUpdate() {
        this.truncate();
    }

    private resizeListener = throttle(() => {
        this.truncate();
    }, 250);

    /**
     * Truncate element text based on max-height
     *
     * @param excerpt - The excerpt to truncate.
     */
    private truncate() {
        const lineHeight = this.calculateLineHeight();
        if (lineHeight !== null) {
            const maxHeight = this.props.lines! * lineHeight;
            shave(this.ref.current!, maxHeight);
        }
    }

    private calculateLineHeight(): number | null {
        if (this.ref.current) {
            return parseInt(getComputedStyle(this.ref.current)["line-height"], 10);
        } else {
            return null;
        }
    }
}
