/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import classNames from "classnames";
import { t } from "@library/application";
import { style } from "typestyle";
import { globalVariables } from "@library/styles/globalStyleVars";
import { debugHelper } from "@library/styles/styleHelpers";
import ScreenReaderContent from "@library/components/ScreenReaderContent";
import Heading from "@library/components/Heading";
import AdjacentLink, { LeftRight } from "@library/components/nextPrevious/adjacentLink";
import { px } from "csx";

interface IProps {
    className?: string;
    theme?: object;
    accessibleTitle?: string;
    previousTitle: string;
    previousTo: string;
    nextTitle: string;
    nextTo: string;
}

/**
 * Implement mobile next/previous nav to articles
 */
export default class NextPrevious extends React.Component<IProps> {
    public static defaultProps = {
        accessibleTitle: t("More Articles"),
    };

    public nextPreviousVariables = (theme?: object) => {
        const sizing = {};
        const fonts = {};
        const colors = {};
        return { sizing, fonts, colors };
    };

    public nextPreviousStyles = (theme?: object) => {
        const globalVars = globalVariables(theme);
        const vars = this.nextPreviousVariables(theme);
        const debug = debugHelper("nextPrevious");
        const root = style({
            display: "flex",
            alignItems: "flex-start",
            flexWrap: "wrap",
            justifyContent: "space-between",
            paddingLeft: globalVars.icon.sizes.default,
            paddingRight: globalVars.icon.sizes.default,
            color: globalVars.mainColors.fg,
            $nest: {
                "&:hover": { color: globalVars.mainColors.fg },
                "&:focus": { color: globalVars.mainColors.fg },
                "&:active": { color: globalVars.mainColors.fg },
                "&.focus-visible": { color: globalVars.mainColors.fg },
            },
            ...debug.name(),
        });

        const title = style({
            display: "block",
            position: "relative",
            fontSize: globalVars.fonts.size.medium,
            lineHeight: globalVars.lineHeights.condensed,
            ...debug.name("title"),
        });

        const chevron = style({
            position: "absolute",
            top: px(20),
            ...debug.name("chevron"),
        });

        const chevronLeft = style({
            left: px(0),
            marginLeft: px(-globalVars.icon.sizes.default),
            ...debug.name("chevronLeft"),
        });

        const chevronRight = style({
            right: px(0),
            marginRight: px(globalVars.icon.sizes.default),
            ...debug.name("chevronRight"),
        });

        // Common to both left and right
        const adjacent = style({
            display: "block",
            ...debug.name("adjacent"),
        });

        const previous = style({
            ...debug.name("previous"),
        });

        const next = style({
            marginLeft: "auto",
            ...debug.name("next"),
        });

        const directionLabel = style({
            display: "block",
            fontSize: globalVars.fonts.size.small,
            lineHeight: globalVars.lineHeights.condensed,
            ...debug.name("label"),
        });

        return { root, adjacent, previous, next, title, chevron, directionLabel, chevronLeft, chevronRight };
    };

    public render() {
        const { accessibleTitle, theme, className, previousTitle, previousTo, nextTitle, nextTo } = this.props;
        const classes = this.nextPreviousStyles(theme);

        return (
            <nav className={classNames(className, classes.root)}>
                <ScreenReaderContent>
                    <Heading title={accessibleTitle} />
                </ScreenReaderContent>
                {/* Left */}
                <AdjacentLink
                    className={classes.previous}
                    classes={classes}
                    direction={LeftRight.LEFT}
                    to={previousTo}
                    title={previousTitle}
                />
                {/* Right */}
                <AdjacentLink
                    className={classes.next}
                    classes={classes}
                    direction={LeftRight.RIGHT}
                    to={nextTo}
                    title={nextTitle}
                />
            </nav>
        );
    }
}
