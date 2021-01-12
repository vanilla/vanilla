/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
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
import { ICountResult } from "@library/search/searchTypes";
import { ResultMeta } from "@library/result/ResultMeta";
import { metasClasses } from "@library/styles/metasStyles";
import classNames from "classnames";
import { getButtonStyleFromBaseClass } from "@library/forms/Button";
import { t } from "@library/utility/appUtils";
import { ArrowIcon } from "@library/icons/common";

export interface IHomeWidgetItemProps {
    // Content
    to: LocationDescriptor;
    imageUrl?: string;
    iconUrl?: string;
    name?: string;
    description?: string;
    metas?: string;
    counts?: ICountResult[];
    callToAction?: string;
    url?: string;

    // Layout options
    options?: IHomeWidgetItemOptions;
}

export function HomeWidgetItem(props: IHomeWidgetItemProps) {
    const options = homeWidgetItemVariables(props.options).options;
    const classes = homeWidgetItemClasses(props.options);
    const isAbsoluteContent = [HomeWidgetItemContentType.TITLE_BACKGROUND].includes(options.contentType);
    const imageUrl = props.imageUrl ?? options.defaultImageUrl;
    const iconUrl = props.iconUrl ?? options.defaultIconUrl;
    const hasMetas = props.counts && options.display.counts;
    const isChatBubble = [HomeWidgetItemContentType.TITLE_CHAT_BUBBLE].includes(options.contentType);
    const hasCTA = props.callToAction && isChatBubble;
    const classesMeta = metasClasses();

    return (
        <SmartLink to={props.to} className={classes.root}>
            <div className={classes.backgroundContainer}>
                {[
                    HomeWidgetItemContentType.TITLE_DESCRIPTION_IMAGE,
                    HomeWidgetItemContentType.TITLE_BACKGROUND,
                ].includes(options.contentType) && (
                    <div className={classes.imageContainerWrapper}>
                        <div className={classes.imageContainer}>
                            {imageUrl && <img className={classes.image} src={imageUrl} alt={props.name} />}
                        </div>
                    </div>
                )}
                {[HomeWidgetItemContentType.TITLE_BACKGROUND].includes(options.contentType) && (
                    <div className={classes.backgroundScrim}></div>
                )}

                {HomeWidgetItemContentType.TITLE_DESCRIPTION_ICON === options.contentType && (
                    <div className={classes.iconContainer}>
                        {iconUrl && <img className={classes.icon} src={iconUrl} alt={props.name} />}
                    </div>
                )}

                {isAbsoluteContent ? <HomeWidgetAbsoluteContent {...props} /> : <HomeWidgetStaticContent {...props} />}
            </div>
            {!isChatBubble && (!isAbsoluteContent || hasMetas) && (
                <div className={classNames(classesMeta.root, classes.metas)}>
                    {hasMetas && <ResultMeta counts={props.counts} />}
                </div>
            )}

            {[HomeWidgetItemContentType.TITLE_CHAT_BUBBLE].includes(options.contentType) && (
                <a href={props.url} className={classes.callToAction}>
                    {props.callToAction}
                    <ArrowIcon />
                </a>
            )}
        </SmartLink>
    );
}

function HomeWidgetStaticContent(props: IHomeWidgetItemProps) {
    const options = homeWidgetItemVariables(props.options).options;
    const classes = homeWidgetItemClasses(props.options);

    return (
        <div className={classes.content}>
            <Heading depth={3} className={classes.name}>
                {props.name}
            </Heading>
            {[
                HomeWidgetItemContentType.TITLE_DESCRIPTION,
                HomeWidgetItemContentType.TITLE_DESCRIPTION_IMAGE,
                HomeWidgetItemContentType.TITLE_DESCRIPTION_ICON,
                HomeWidgetItemContentType.TITLE_CHAT_BUBBLE,
            ].includes(options.contentType) && (
                <TruncatedText maxCharCount={160} tag={"div"} className={classes.description}>
                    {props.description}
                </TruncatedText>
            )}
        </div>
    );
}

function HomeWidgetAbsoluteContent(props: IHomeWidgetItemProps) {
    const options = homeWidgetItemVariables(props.options).options;
    const classes = homeWidgetItemClasses(props.options);
    const viewMoreCode = options.viewMore?.labelCode;

    return (
        <>
            <div className={classes.absoluteContent}>
                {!options.name.hidden && (
                    <Heading depth={3} className={classes.absoluteName}>
                        {props.name}
                    </Heading>
                )}
                <div>
                    <span className={getButtonStyleFromBaseClass(options.viewMore?.buttonType)}>{t(viewMoreCode)}</span>
                </div>
            </div>
        </>
    );
}
