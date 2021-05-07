/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useDebugValue } from "react";
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
import { metasClasses } from "@library/metas/Metas.styles";
import classNames from "classnames";
import { getClassForButtonType } from "@library/forms/Button";
import { t } from "@library/utility/appUtils";
import { ArrowIcon } from "@library/icons/common";
import { DeepPartial } from "redux";
import { MetaItem, Metas } from "@library/metas/Metas";
import { Devices, useDevice } from "@library/layout/DeviceContext";
import { cx } from "@emotion/css";
import { buttonClasses } from "@library/forms/buttonStyles";

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
    className?: string;

    // Layout options
    options?: DeepPartial<IHomeWidgetItemOptions>;
}

export function HomeWidgetItem(props: IHomeWidgetItemProps) {
    const options = homeWidgetItemVariables(props.options).options;
    const classes = homeWidgetItemClasses(props.options);
    const isAbsoluteContent = [
        HomeWidgetItemContentType.TITLE_BACKGROUND,
        HomeWidgetItemContentType.TITLE_BACKGROUND_DESCRIPTION,
    ].includes(options.contentType);
    const imageUrl = props.imageUrl ?? options.defaultImageUrl;
    const iconUrl = props.iconUrl ?? options.defaultIconUrl;
    const hasMetas = props.counts && options.display.counts;
    const hasMetaDescription =
        [HomeWidgetItemContentType.TITLE_BACKGROUND_DESCRIPTION].includes(options.contentType) && props.description;
    const isChatBubble = [HomeWidgetItemContentType.TITLE_CHAT_BUBBLE].includes(options.contentType);
    useDebugValue({ opts: options });

    const metas = (hasMetas || hasMetaDescription) && !isChatBubble && (
        <Metas className={classes.metas}>
            {hasMetaDescription ? (
                <MetaItem>
                    <span className={cx(buttonClasses().textPrimary, classes.metaDescription)}>
                        {props.description}
                        {props.description && " ➔"}
                    </span>
                </MetaItem>
            ) : (
                <ResultMeta counts={props.counts} />
            )}
        </Metas>
    );

    return (
        <SmartLink to={props.to} className={cx(classes.root, props.className)}>
            <div className={classes.backgroundContainer}>
                {[
                    HomeWidgetItemContentType.TITLE_BACKGROUND_DESCRIPTION,
                    HomeWidgetItemContentType.TITLE_DESCRIPTION_IMAGE,
                    HomeWidgetItemContentType.TITLE_BACKGROUND,
                ].includes(options.contentType) && (
                    <div className={classes.imageContainerWrapper}>
                        <div className={classes.imageContainer}>
                            {imageUrl && (
                                <img className={classes.image} src={imageUrl} alt={props.name} loading="lazy" />
                            )}
                        </div>
                    </div>
                )}
                {[
                    HomeWidgetItemContentType.TITLE_BACKGROUND,
                    HomeWidgetItemContentType.TITLE_BACKGROUND_DESCRIPTION,
                ].includes(options.contentType) && <div className={classes.backgroundScrim}></div>}

                {HomeWidgetItemContentType.TITLE_DESCRIPTION_ICON === options.contentType && iconUrl && (
                    <div className={classes.iconContainer}>
                        <div className={classes.iconWrap}>
                            <img className={classes.icon} src={iconUrl} alt={props.name} loading="lazy" />
                        </div>
                    </div>
                )}

                {isAbsoluteContent ? (
                    <HomeWidgetAbsoluteContent {...props} />
                ) : (
                    <HomeWidgetStaticContent {...props} extraChildren={metas} />
                )}
            </div>
            {isAbsoluteContent && metas}
            {[HomeWidgetItemContentType.TITLE_CHAT_BUBBLE].includes(options.contentType) && (
                <span className={classes.callToAction}>
                    {props.callToAction}
                    <ArrowIcon />
                </span>
            )}
        </SmartLink>
    );
}

function HomeWidgetStaticContent(props: IHomeWidgetItemProps & { extraChildren?: React.ReactNode }) {
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
            ].includes(options.contentType) &&
                options.display.description &&
                props.description && (
                    <TruncatedText maxCharCount={160} tag={"div"} className={classes.description}>
                        {props.description}
                    </TruncatedText>
                )}
            {/* Flex spacer */}
            <div style={{ flex: 1 }} />
            {props.extraChildren}
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
                {options.display.name && (
                    <Heading depth={3} className={classes.absoluteName}>
                        {props.name}
                    </Heading>
                )}
                {options.display.cta && (
                    <div>
                        <span className={getClassForButtonType(options.viewMore?.buttonType)}>{t(viewMoreCode)}</span>
                    </div>
                )}
            </div>
        </>
    );
}
