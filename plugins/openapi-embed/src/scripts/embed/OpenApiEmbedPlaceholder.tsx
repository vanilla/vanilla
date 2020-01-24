/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { openApiEmbedClasses, openApiEmbedPlaceholderClasses } from "@openapi-embed/embed/openApiEmbedStyles";
import SmartLink from "@vanilla/library/src/scripts/routing/links/SmartLink";
import Heading from "@vanilla/library/src/scripts/layout/Heading";

interface IProps {
    embedUrl: string;
    name: string;
}

export type PlaceholderType = "get" | "post" | "delete";

export function OpenApiEmbedPlaceholder(props: IProps) {
    const classes = openApiEmbedPlaceholderClasses("get");
    return (
        <div className={classes.root}>
            <Heading depth={4} className={classes.name}>
                {props.name}
            </Heading>
            <SmartLink className={classes.url} to={props.embedUrl}>
                {props.embedUrl}
            </SmartLink>
            <PlaceholderRow type="get"></PlaceholderRow>
            <PlaceholderRow type="post"></PlaceholderRow>
            <PlaceholderRow type="delete"></PlaceholderRow>
        </div>
    );
}

export function PlaceholderRow(props: { type: PlaceholderType }) {
    const classes = openApiEmbedPlaceholderClasses(props.type);
    return (
        <div className={classes.placeholderRow}>
            <span className={classes.placeholderTitle}>{props.type}</span>
            <div className={classes.placeholderTextContainer}>
                <span className={classes.placeholderText1}></span>
                <span className={classes.placeholderText2}></span>
            </div>
        </div>
    );
}
