/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { ReactNode, useDebugValue, useMemo } from "react";
import { homeWidgetItemVariables, homeWidgetItemClasses } from "@library/homeWidget/HomeWidgetItem.styles";
import { IHomeWidgetItemOptions, WidgetImageType, widgetItemContentTypeToImageType } from "./WidgetItemOptions";
import { WidgetItemContentType } from "./WidgetItemOptions";
import SmartLink from "@library/routing/links/SmartLink";
import { LocationDescriptor } from "history";
import Heading from "@library/layout/Heading";
import TruncatedText from "@library/content/TruncatedText";
import { ICountResult } from "@library/search/searchTypes";
import { ResultMeta } from "@library/result/ResultMeta";
import { getClassForButtonType } from "@library/forms/Button.getClassForButtonType";
import { createSourceSetValue, ImageSourceSet, siteUrl, t } from "@library/utility/appUtils";
import { ArrowIcon } from "@library/icons/common";
import { DeepPartial } from "redux";
import { MetaItem, Metas } from "@library/metas/Metas";
import { cx } from "@emotion/css";
import { buttonClasses } from "@library/forms/Button.styles";
import { WidgetDefaultImage } from "@library/homeWidget/WidgetDefaultImage";
import { Devices, useDevice } from "@library/layout/DeviceContext";
import { ButtonTypes } from "@library/forms/buttonTypes";
import Button from "@library/forms/Button";

export interface CommonHomeWidgetItemProps {
    // Content
    imageUrl?: string;
    imageUrlSrcSet?: ImageSourceSet;
    iconUrl?: string;
    iconUrlSrcSet?: ImageSourceSet;
    name?: string;
    nameClassName?: string;
    description?: string;
    descriptionClassName?: string;
    metas?: string;
    counts?: ICountResult[];
    url?: string;
    className?: string;
    tabIndex?: number;
    children?: React.ReactNode;
    metaComponent?: React.ReactNode;
    iconComponent?: React.ReactNode;

    // Layout options
    options?: DeepPartial<IHomeWidgetItemOptions>;
}

export type IHomeWidgetItemProps = CommonHomeWidgetItemProps &
    (
        | {
              to?: LocationDescriptor;
              callback?: never;
          }
        | {
              to?: never;
              callback?: () => void;
          }
    );

export function HomeWidgetItem(props: IHomeWidgetItemProps) {
    const { to, callback } = props;
    const vars = homeWidgetItemVariables.useAsHook(props.options);
    const options = vars.options;
    const classes = homeWidgetItemClasses.useAsHook(props.options);
    const isMobile = [Devices.MOBILE, Devices.XS].includes(useDevice());

    const imageUrlSrcSet = useMemo(() => {
        if (props.imageUrlSrcSet) {
            return { srcSet: createSourceSetValue(props.imageUrlSrcSet) };
        }
        return {};
    }, [props.imageUrlSrcSet]);

    const iconUrlSrcSet = useMemo(() => {
        if (props.iconUrlSrcSet) {
            return { srcSet: createSourceSetValue(props.iconUrlSrcSet) };
        }
        return {};
    }, [props.iconUrlSrcSet]);

    useDebugValue({ opts: options });

    if (props.children && !Array.isArray(props.children)) {
        if (props.to) {
            return (
                <SmartLink
                    to={props.to}
                    className={cx(classes.root, props.className)}
                    tabIndex={props.tabIndex}
                    // Prevent dragging these guys as links.
                    draggable={"false"}
                    onDragStart={(e) => e.preventDefault()}
                >
                    {props.children}
                </SmartLink>
            );
        }
        return <>{props.children}</>;
    }

    const imageType = widgetItemContentTypeToImageType(options.contentType);

    const isAbsoluteContent = imageType === WidgetImageType.Background;
    const imageUrl = props.imageUrl ?? options.defaultImageUrl;
    const iconUrl = props.iconUrl ?? options.defaultIconUrl;
    const hasMetas = (props.counts && options.display.counts) || props.metaComponent;
    const hasMetaDescription =
        options.contentType === WidgetItemContentType.TitleBackgroundDescription && props.description;
    const isChatBubble = options.contentType === WidgetItemContentType.TitleChatBubble;

    const metas = props.metaComponent
        ? props.metaComponent
        : (hasMetas || hasMetaDescription) &&
          !isChatBubble && (
              <Metas className={classes.metas}>
                  {hasMetaDescription ? (
                      <MetaItem className={classes.longMetaItem}>
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

    const icon = props.iconComponent
        ? props.iconComponent
        : WidgetItemContentType.TitleDescriptionIcon === options.contentType &&
          iconUrl && (
              <div className={classes.iconContainer}>
                  <div className={classes.iconWrap}>
                      <img
                          className={classes.icon}
                          height={isMobile ? vars.icon.sizeMobile : vars.icon.size}
                          width={"auto"}
                          role="presentation"
                          src={siteUrl(iconUrl)}
                          alt={props.name}
                          loading="lazy"
                          {...iconUrlSrcSet}
                      />
                  </div>
              </div>
          );

    const content = (
        <>
            <div className={classes.backgroundContainer}>
                {(
                    [
                        WidgetItemContentType.TitleBackgroundDescription,
                        WidgetItemContentType.TitleDescriptionImage,
                        WidgetItemContentType.TitleBackground,
                    ] as WidgetItemContentType[]
                ).includes(options.contentType) && (
                    <div className={classes.imageContainerWrapper}>
                        <div className={classes.imageContainer}>
                            {imageUrl ? (
                                <img
                                    height={vars.icon.size}
                                    className={classes.image}
                                    src={siteUrl(imageUrl)}
                                    alt={props.name}
                                    loading="lazy"
                                    {...imageUrlSrcSet}
                                />
                            ) : (
                                <WidgetDefaultImage />
                            )}
                        </div>
                    </div>
                )}
                {imageType === WidgetImageType.Background && <div className={classes.backgroundScrim}></div>}

                {icon}

                {isAbsoluteContent ? (
                    <HomeWidgetAbsoluteContent {...props} />
                ) : (
                    <HomeWidgetStaticContent {...props} extraChildren={metas} />
                )}
            </div>
            {isAbsoluteContent && metas}
            {options.contentType === WidgetItemContentType.TitleChatBubble && (
                <span className={classes.callToAction}>
                    <span>{t(options.callToActionText)}</span>
                    <ArrowIcon />
                </span>
            )}
        </>
    );

    return (
        <>
            {to && !callback && (
                <SmartLink
                    to={to}
                    className={cx(classes.root, props.className)}
                    tabIndex={props.tabIndex}
                    // Prevent dragging these guys as links.
                    draggable={"false"}
                    onDragStart={(e) => e.preventDefault()}
                >
                    {content}
                </SmartLink>
            )}
            {!to && !!callback && (
                <Button
                    buttonType={ButtonTypes.CUSTOM}
                    className={cx(classes.root, props.className)}
                    onClick={() => callback()}
                >
                    {content}
                </Button>
            )}
            {!to && !callback && <div className={cx(classes.root, props.className)}>{content}</div>}
        </>
    );
}

function HomeWidgetStaticContent(props: IHomeWidgetItemProps & { extraChildren?: React.ReactNode }) {
    const options = homeWidgetItemVariables.useAsHook(props.options).options;
    const classes = homeWidgetItemClasses.useAsHook(props.options);

    return (
        <div className={classes.content}>
            <Heading depth={3} className={cx(classes.name, props.nameClassName)}>
                {props.name}
            </Heading>
            {(
                [
                    WidgetItemContentType.TitleDescription,
                    WidgetItemContentType.TitleDescriptionImage,
                    WidgetItemContentType.TitleDescriptionIcon,
                    WidgetItemContentType.TitleChatBubble,
                ] as WidgetItemContentType[]
            ).includes(options.contentType) &&
                options.display.description &&
                props.description && (
                    <TruncatedText
                        maxCharCount={160}
                        tag={"div"}
                        className={cx(classes.description, props.descriptionClassName)}
                    >
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
    const options = homeWidgetItemVariables.useAsHook(props.options).options;
    const classes = homeWidgetItemClasses.useAsHook(props.options);
    const viewMoreCode = options.viewMore?.labelCode;

    return (
        <>
            <div className={classes.absoluteContent}>
                {options.display.name && (
                    <Heading depth={3} className={cx(classes.absoluteName, props.nameClassName)}>
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
