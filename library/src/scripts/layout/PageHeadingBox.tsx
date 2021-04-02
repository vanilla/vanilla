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
import { useWidgetLayoutClasses } from "@library/layout/WidgetLayout.context";
import React, { useRef } from "react";

interface IProps {
    title: React.ReactNode;
    description?: React.ReactNode;
    subtitle?: React.ReactNode;
    actions?: React.ReactNode;
    includeBackLink?: boolean;
    options?: Partial<IPageHeadingBoxOptions>;
}

export function PageHeadingBox(props: IProps) {
    const { title, description, subtitle, actions, includeBackLink } = props;
    const options = pageHeadingBoxVariables(props.options).options;
    const classes = pageHeadingBoxClasses(props.options);
    const { subtitleType } = options;
    const contextClasses = useWidgetLayoutClasses();

    const wrapperRef = useRef<HTMLDivElement | null>(null);
    const depth = useCalculatedDepth(wrapperRef);

    if (!title && !description && !subtitle && !actions) {
        return <></>;
    }

    const subtitleView = subtitle ? (
        <Heading className={cx(classes.subtitle, "subtitle")} depth={depth + 1}>
            {subtitle}
        </Heading>
    ) : (
        <></>
    );

    return (
        <div ref={wrapperRef} className={cx(classes.root, contextClasses.headingBlockClass, "pageHeadingBox")}>
            {subtitleType === SubtitleType.OVERLINE && subtitleView}
            <div className={classes.titleWrap}>
                <PageHeading depth={depth} actions={actions} includeBackLink={includeBackLink ?? false}>
                    {title}
                </PageHeading>
            </div>
            {subtitleType === SubtitleType.STANDARD && subtitleView}
            {description && <div className={classes.descriptionWrap}>{description}</div>}
        </div>
    );
}
