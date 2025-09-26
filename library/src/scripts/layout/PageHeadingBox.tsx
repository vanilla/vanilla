/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { cx } from "@emotion/css";
import Heading from "@library/layout/Heading";
import { useCalculatedDepth } from "@library/layout/PageBox.context";
import PageHeading from "@library/layout/PageHeading";
import { pageHeadingBoxClasses } from "@library/layout/PageHeadingBox.styles";
import {
    IPageHeadingBoxOptions,
    pageHeadingBoxVariables,
    SubtitleType,
} from "@library/layout/PageHeadingBox.variables";
import { useWidgetSectionClasses } from "@library/layout/WidgetLayout.context";
import React, { useRef } from "react";

interface IHeadingBoxProps {
    title: React.ReactNode;
    description?: React.ReactNode;
    subtitle?: React.ReactNode;
    actions?: React.ReactNode;
    includeBackLink?: boolean;
    options?: Partial<IPageHeadingBoxOptions>;
    titleCount?: string;
    depth?: number;
    classNames?: string;
    pageHeadingClasses?: string;
}

export function PageHeadingBox(props: IHeadingBoxProps) {
    const { title, description, subtitle, actions, includeBackLink, titleCount, classNames, pageHeadingClasses } =
        props;
    const options = pageHeadingBoxVariables.useAsHook(props.options).options;
    const classes = pageHeadingBoxClasses.useAsHook(props.options);
    const { subtitleType } = options;
    const contextClasses = useWidgetSectionClasses();

    const wrapperRef = useRef<HTMLDivElement | null>(null);
    const calcedDepth = useCalculatedDepth(wrapperRef);
    const depth = props.depth ?? calcedDepth;

    if (!title && !description && !subtitle && !actions) {
        return <></>;
    }

    const titleCountView = titleCount ? (
        <>
            <span className={classes.titleCount}>
                <span title={titleCount}>{titleCount}</span>
            </span>
        </>
    ) : (
        <></>
    );

    const subtitleView = subtitle ? (
        <Heading className={cx(classes.subtitle, "subtitle")} depth={depth + 1}>
            {subtitle}
        </Heading>
    ) : (
        <></>
    );

    return (
        <div
            ref={wrapperRef}
            className={cx(classes.root, contextClasses.headingBlockClass, "pageHeadingBox", classNames)}
        >
            {subtitleType === SubtitleType.OVERLINE && subtitleView}
            <div className={classes.titleWrap}>
                <PageHeading
                    depth={depth}
                    actions={actions}
                    titleCount={titleCountView}
                    includeBackLink={includeBackLink ?? false}
                    className={pageHeadingClasses}
                >
                    {title}
                </PageHeading>
            </div>
            {subtitleType === SubtitleType.STANDARD && subtitleView}
            {description && <div className={cx(classes.descriptionWrap, "pageHeadingDescription")}>{description}</div>}
        </div>
    );
}
