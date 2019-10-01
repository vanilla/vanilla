/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import classNames from "classnames";
import AdjacentLink, { LeftRight } from "@library/navigation/AdjacentLink";
import Heading from "@library/layout/Heading";
import ScreenReaderContent from "@library/layout/ScreenReaderContent";
import { nextPreviousClasses } from "@library/navigation/nextPreviousStyles";

interface IUrlItem {
    name: string;
    url: string;
}

interface IProps {
    className?: string;
    accessibleTitle: string;
    prevItem?: IUrlItem | null;
    nextItem?: IUrlItem | null;
}

/**
 * Implement mobile next/previous nav to articles
 */
export default class NextPrevious extends React.Component<IProps> {
    public render() {
        const { accessibleTitle, className, prevItem, nextItem } = this.props;
        if (!nextItem && !prevItem) {
            return null; // skip if no sibling pages exist
        }
        const classes = nextPreviousClasses();
        return (
            <nav className={classNames(className, classes.root)}>
                <ScreenReaderContent>
                    <Heading title={accessibleTitle} />
                </ScreenReaderContent>
                {/* Left */}
                {prevItem && (
                    <AdjacentLink
                        className={classes.previous}
                        classes={classes}
                        direction={LeftRight.LEFT}
                        to={prevItem.url}
                        title={prevItem.name}
                    />
                )}
                {/* Right */}
                {nextItem && (
                    <AdjacentLink
                        className={classes.next}
                        classes={classes}
                        direction={LeftRight.RIGHT}
                        to={nextItem.url}
                        title={nextItem.name}
                    />
                )}
            </nav>
        );
    }
}
