/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import classNames from "classnames";
import { globalVariables } from "@library/styles/globalStyleVars";
import { componentThemeVariables } from "@library/styles/styleHelpers";
import ScreenReaderContent from "@library/components/ScreenReaderContent";
import Heading from "@library/components/Heading";
import AdjacentLink, { LeftRight } from "@library/components/nextPrevious/AdjacentLink";
import { px } from "csx";
import { styleFactory, useThemeCache } from "@library/styles/styleUtils";

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

        const classes = this.nextPreviousStyles();
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

    public nextPreviousVariables = useThemeCache(() => {
        const globalVars = globalVariables();
        const themeVars = componentThemeVariables("nextPreviousVars");

        const fonts = {
            label: globalVars.fonts.size.small,
            title: globalVars.fonts.size.medium,
            ...themeVars.subComponentStyles("fonts"),
        };

        const lineHeights = {
            label: globalVars.lineHeights.condensed,
            title: globalVars.lineHeights.condensed,
            ...themeVars.subComponentStyles("lineHeights"),
        };

        const colors = {
            title: globalVars.mixBgAndFg(0.9),
            label: globalVars.mixBgAndFg(0.85),
            hover: globalVars.mainColors.primary,
            ...themeVars.subComponentStyles("colors"),
        };
        return { lineHeights, fonts, colors };
    });

    public nextPreviousStyles = useThemeCache(() => {
        const globalVars = globalVariables();
        const vars = this.nextPreviousVariables();
        const style = styleFactory("nextPrevious");
        const activeStyles = {
            color: vars.colors.title.toString(),
            $nest: {
                ".adjacentLinks-icon": {
                    color: globalVars.mainColors.primary.toString(),
                },
            },
        };

        const root = style({
            display: "flex",
            alignItems: "flex-start",
            flexWrap: "wrap",
            justifyContent: "space-between",
            color: globalVars.mainColors.fg.toString(),
        });

        const directionLabel = style({
            ...debug.name("label"),
            display: "block",
            fontSize: px(globalVars.fonts.size.small),
            lineHeight: globalVars.lineHeights.condensed,
            color: vars.colors.label.toString(),
            marginBottom: px(2),
        });

        const title = style({
            ...debug.name("title"),
            display: "block",
            position: "relative",
            fontSize: px(globalVars.fonts.size.medium),
            lineHeight: globalVars.lineHeights.condensed,
            fontWeight: globalVars.fonts.weights.semiBold,
        });

        const chevron = style({
            ...debug.name("chevron"),
            position: "absolute",
            top: px((vars.fonts.title * vars.lineHeights.title) / 2),
            transform: `translateY(-50%)`,
            color: globalVars.mixBgAndFg(0.75).toString(),
        });

        const chevronLeft = style({
            ...debug.name("chevronLeft"),
            left: px(0),
            marginLeft: px(-globalVars.icon.sizes.default),
        });

        const chevronRight = style("chevronRight", {
            right: px(0),
            marginRight: px(-globalVars.icon.sizes.default),
        });

        // Common to both left and right
        const adjacent = style("adjacent", {
            display: "block",
            marginTop: px(8),
            marginBottom: px(8),
            color: vars.colors.title.toString(),
            $nest: {
                "&.focus-visible": activeStyles,
                "&:hover": activeStyles,
                "&:active": activeStyles,
            },
        });

        const previous = style("previous", {
            paddingLeft: px(globalVars.icon.sizes.default),
            paddingRight: px(globalVars.icon.sizes.default / 2),
        });

        const next = style("next", {
            marginLeft: "auto",
            textAlign: "right",
            paddingRight: px(globalVars.icon.sizes.default),
            paddingLeft: px(globalVars.icon.sizes.default / 2),
        });

        return { root, adjacent, previous, next, title, chevron, directionLabel, chevronLeft, chevronRight };
    });
}
