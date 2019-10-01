/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import shave from "shave";
import throttle from "lodash/throttle";

interface IProps {
    tag?: string;
    className?: string;
    children: React.ReactNode;
    useMaxHeight?: boolean;
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
        const Tag = (this.props.tag || "span") as "span";

        return (
            <Tag className={this.props.className} ref={this.ref}>
                {this.props.children}
            </Tag>
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

    private truncate() {
        if (this.props.useMaxHeight) {
            this.truncateTextBasedOnMaxHeight();
        } else {
            this.truncateBasedOnLines();
        }
    }

    /**
     * Truncate element text based on a certain number of lines.
     *
     * @param excerpt - The excerpt to truncate.
     */
    private truncateBasedOnLines() {
        const lineHeight = this.calculateLineHeight();
        if (lineHeight !== null) {
            const maxHeight = this.props.lines! * lineHeight;
            shave(this.ref.current!, maxHeight);
        }
    }

    /**
     * Truncate element text based on max-height
     *
     * @param excerpt - The excerpt to truncate.
     */
    private truncateTextBasedOnMaxHeight() {
        const element = this.ref.current!;
        const maxHeight = parseInt(getComputedStyle(element)["max-height"], 10);
        if (maxHeight && maxHeight > 0) {
            shave(element, maxHeight);
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
