/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import React, { useReducer } from "react";
import SmartLink, { ISmartLinkProps } from "@library/routing/links/SmartLink";
import classNames from "classnames";
import ConditionalWrap from "@library/layout/ConditionalWrap";

interface IProps extends Omit<ISmartLinkProps, "to"> {
    label?: string; // location of event
    url?: string; // url of event
    tag?: string; // only used when no url is passed
    id?: string;
    linkClass?: string; // only applied to links
    textClass?: string; // only applied to text
    linkWrap?: string;
}

/**
 * Component for displaying event location, with location and/or locationSafeUrl
 */
export function LinkOrText(props: IProps) {
    let { label, url, tag, id, linkClass, textClass, linkWrap, ...passThrough } = props;
    const hasLabel = !!label;
    const hasUrl = !!url;

    // Get rid of possible empty strings
    const outputUrl = hasUrl ? url : undefined;
    const outputLabel = hasLabel ? label : hasUrl ? url : undefined;

    if (!hasLabel && !hasUrl) {
        return null;
    } else if (hasUrl) {
        return (
            <SmartLink
                to={outputUrl as string}
                title={outputUrl}
                target="_blank"
                id={id}
                {...passThrough}
                className={classNames(props.className, props.linkClass)}
            >
                <ConditionalWrap condition={!!linkWrap} tag={linkWrap as "span"}>
                    {outputLabel}
                </ConditionalWrap>
            </SmartLink>
        );
    } else {
        // Text only
        const Tag = (tag || "span") as "span";
        return (
            <Tag id={id} title={outputLabel} {...passThrough} className={classNames(props.className, props.textClass)}>
                {outputLabel}
            </Tag>
        );
    }
}
