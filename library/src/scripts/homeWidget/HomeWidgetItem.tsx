/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { BorderType } from "@library/styles/styleHelpers";
import { CsxBackgroundOptions } from "csx/lib/types";
import {
    IHomeWidgetItemOptions,
    homeWidgetItemVariables,
    homeWidgetItemClasses,
    HomeWidgetItemContentType,
} from "@library/homeWidget/HomeWidgetItem.styles";
import SmartLink from "@library/routing/links/SmartLink";
import { LocationDescriptor } from "history";
import Heading from "@library/layout/Heading";
import TruncatedText from "@library/content/TruncatedText";

interface IProps {
    // Content
    to: LocationDescriptor;
    imageUrl?: string;
    name?: string;
    description?: string;

    // Layout options
    options?: IHomeWidgetItemOptions;
}

export function HomeWidgetItem(props: IProps) {
    const options = homeWidgetItemVariables(props.options).options;
    const classes = homeWidgetItemClasses(props.options);

    console.log("options", { options, propOptions: props.options });

    return (
        <SmartLink to={props.to} className={classes.root}>
            {HomeWidgetItemContentType.TITLE_DESCRIPTION_IMAGE === options.contentType && (
                <div className={classes.imageContainer}>
                    {props.imageUrl && <img className={classes.image} src={props.imageUrl} alt={props.name} />}
                </div>
            )}
            <div className={classes.content}>
                <Heading depth={3} className={classes.name}>
                    {props.name}
                </Heading>
                {[
                    HomeWidgetItemContentType.TITLE_DESCRIPTION,
                    HomeWidgetItemContentType.TITLE_DESCRIPTION_IMAGE,
                ].includes(options.contentType) && <TruncatedText tag={"div"}>{props.description}</TruncatedText>}
            </div>
        </SmartLink>
    );
}
