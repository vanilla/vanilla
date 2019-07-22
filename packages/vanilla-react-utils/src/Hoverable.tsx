/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";

interface IProps {
    duration: number;
    onHover: React.MouseEventHandler | undefined;
    once?: boolean;
    children: (providedProps: IProvidedProps) => React.ReactNode;
}

interface IProvidedProps {
    onMouseEnter: React.MouseEventHandler;
    onMouseLeave: React.MouseEventHandler;
}

/**
 * Component with render props for handling a hover after a certain duration.
 *
 * Simply spread the provided props over the element you want to track the hover of.
 *
 * @example
 * <Hoverable duration={250} onHover={myCallback}>
 *     {providedProps => {
 *          <div {...providedProps} className="someClass">
 *              // Some deeply neested child.
 *          </div>
 *     }}
 * </Hoverable>
 */
export class Hoverable extends React.Component<IProps> {
    public static defaultProps = {
        once: true,
    };

    /**
     * @inheritdoc
     */
    public render(): React.ReactNode {
        return this.props.children({
            onMouseEnter: this.mouseEnterHandler,
            onMouseLeave: this.mouseLeaveHandler,
        });
    }

    /**
     * @inheritdoc
     */
    public componentWillUnmount() {
        this.dismissTimeout();
    }

    private hoverTimeout: NodeJS.Timeout;
    private hasExecuted = false;

    /**
     * Handle the hover event by checking if we've already called ourselves then calling the passed callback.
     */
    private handleHover = (event: React.MouseEvent) => {
        if (this.hasExecuted && this.props.once) {
            return;
        }

        this.hasExecuted = true;
        if (this.props.onHover) {
            this.props.onHover(event);
        }
    };

    /**
     * Set a timeout when the mouse enters. If the timeout is not removed we have a hover.
     */
    private mouseEnterHandler = (event: React.MouseEvent) => {
        this.hoverTimeout = setTimeout(() => {
            this.handleHover(event);
        }, this.props.duration);
    };

    /**
     * Remove the timeout for the hover so it doesn't execute again (or at all).
     */
    private mouseLeaveHandler = (event: React.MouseEvent) => {
        this.dismissTimeout();
    };

    /**
     * Cleanup timeouts and properties to reset.
     */
    private dismissTimeout() {
        this.hasExecuted = false;
        if (this.hoverTimeout) {
            clearTimeout(this.hoverTimeout);
        }
    }
}
