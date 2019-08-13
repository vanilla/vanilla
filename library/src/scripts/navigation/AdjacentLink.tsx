/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import classNames from "classnames";
import { t } from "@library/utility/appUtils";
import SmartLink from "@library/routing/links/SmartLink";
import { LeftChevronIcon, RightChevronIcon } from "@library/icons/common";

export enum LeftRight {
    LEFT = "left",
    RIGHT = "right",
}

export interface IAdjacentLinks {
    className?: string;
    to: string;
    direction: LeftRight;
    title: string;
    classes: {
        title: string;
        directionLabel: string;
        chevron: string;
        chevronLeft: string;
        chevronRight: string;
        adjacent: string; // common to both left and right
    };
}

/**
 * Implements a link to either the next or previous article
 */
export default class AdjacentLink extends React.Component<IAdjacentLinks> {
    public render() {
        const { className, direction, to, classes, title } = this.props;
        const isLeft = direction === LeftRight.LEFT;
        return (
            <SmartLink to={to} className={classNames(className, classes.adjacent)}>
                <span className={classNames(classes.directionLabel, "adjacentLinks-label")}>
                    {isLeft ? t("Previous") : t("Next")}
                </span>
                <span className={classNames(classes.title, "adjacentLinks-title")}>
                    <span className={classNames(classes.chevron, isLeft ? classes.chevronLeft : classes.chevronRight)}>
                        {isLeft ? (
                            <LeftChevronIcon className={"adjacentLinks-icon"} />
                        ) : (
                            <RightChevronIcon className={"adjacentLinks-icon"} />
                        )}
                    </span>
                    {title}
                </span>
            </SmartLink>
        );
    }
}
