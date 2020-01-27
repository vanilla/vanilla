/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { openApiEmbedClasses, openApiEmbedPlaceholderClasses } from "@openapi-embed/embed/openApiEmbedStyles";
import SmartLink from "@vanilla/library/src/scripts/routing/links/SmartLink";
import Heading from "@vanilla/library/src/scripts/layout/Heading";
import { IOpenApiEmbedData } from "@openapi-embed/embed/OpenApiEmbed";
import { t } from "@vanilla/i18n";

interface IProps {
    data: IOpenApiEmbedData;
}

export type PlaceholderType = "get" | "post" | "delete";

/**
 * A placholder for an API Embed.
 *
 * - Multiple example rows.
 * - Shows URL and name of the API spec.
 */
export function OpenApiEmbedPlaceholder(props: IProps) {
    const { name, url } = props.data;
    const classes = openApiEmbedPlaceholderClasses("get");
    return (
        <div className={classes.root}>
            <Heading depth={4} className={classes.name}>
                {t("API Reference")}
            </Heading>
            <SmartLink className={classes.url} to={url}>
                {url}
            </SmartLink>
            <PlaceholderRow type="get"></PlaceholderRow>
            <PlaceholderRow type="post"></PlaceholderRow>
            <PlaceholderRow type="delete"></PlaceholderRow>
        </div>
    );
}

/**
 * A single row of a placeholder for an embed.
 */
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
