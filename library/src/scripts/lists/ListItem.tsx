/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { cx } from "@emotion/css";
import { listItemClasses } from "@library/lists/ListItem.styles";
import { Metas } from "@library/metas/Metas";
import SmartLink from "@library/routing/links/SmartLink";
import React, { ElementType, ReactElement, useContext, useRef } from "react";
import { useMeasure } from "@vanilla/react-utils";
import { PageBox } from "@library/layout/PageBox";
import {
    IListItemComponentOptions,
    IListItemOptions,
    ListItemIconPosition,
    ListItemLayout,
    listItemVariables,
} from "@library/lists/ListItem.variables";
import TruncatedText from "@library/content/TruncatedText";
import Heading from "@library/layout/Heading";
import Paragraph from "@library/layout/Paragraph";
import ConditionalWrap from "@library/layout/ConditionalWrap";
import { pointerEventsClass } from "@library/styles/styleHelpersFeedback";
import { IFeaturedImage, IImage } from "@library/@types/api/core";
import { ListItemMedia } from "@library/lists/ListItemMedia";
import { listItemMediaClasses } from "@library/lists/ListItemMedia.styles";
import { createSourceSetValue, t } from "@library/utility/appUtils";
import { HomeWidgetItemDefaultImage } from "@library/homeWidget/HomeWidgetItemDefaultImage";

export interface IListItemProps {
    className?: string;
    url?: string;
    name?: React.ReactNode;
    nameClassName?: string;
    description?: React.ReactNode;
    descriptionClassName?: string;
    descriptionMaxCharCount?: number;
    truncateDescription?: boolean;
    icon?: React.ReactNode;
    iconWrapperClass?: string;
    secondIcon?: React.ReactNode;
    metas?: React.ReactNode;
    metasWrapperClass?: string;
    mediaItem?: React.ReactNode;
    image?: IImage;
    featuredImage?: IFeaturedImage;
    actions?: React.ReactNode;
    as?: ElementType;
    headingDepth?: number;
    options?: Partial<IListItemComponentOptions>;
    checkbox?: React.ReactNode;
    asTile?: boolean;
    disableButtonsInItems?: boolean;
}

export function ListItem(props: IListItemProps) {
    const { headingDepth = 3, descriptionClassName, truncateDescription = true, descriptionMaxCharCount = 200 } = props;
    const selfRef = useRef<HTMLDivElement>(null);
    const measure = useMeasure(selfRef);
    const { layout } = useContext(ListItemContext);
    const listItemVars = listItemVariables(props.options);
    const mediaClasses = listItemMediaClasses();
    const {
        options: { iconPosition },
    } = listItemVars;

    const isMobileMedia = measure.width <= 600;
    const hasImage = props.featuredImage?.display || Boolean(props.mediaItem);
    const asTile = props.asTile || isMobileMedia;
    const iconInMeta = iconPosition === ListItemIconPosition.META && !hasImage;
    const hasCheckbox = Boolean(props.checkbox);
    const classes = listItemClasses(asTile, hasImage, hasCheckbox, isMobileMedia && !props.asTile);

    const checkboxWrapped = props.checkbox && <div className={cx(classes.checkboxContainer)}>{props.checkbox}</div>;

    const shouldDisplayIcon = !!props.icon || !!props.secondIcon; //shouldDisplayIcon is true if either icon or secondIcon is defined.
    const primaryIcon: React.ReactNode | null = shouldDisplayIcon ? props.icon ?? props.secondIcon : null;
    const secondaryIcon: React.ReactNode | null = !!props.icon && !!props.secondIcon ? props.secondIcon : null;

    const icon = shouldDisplayIcon ? (
        <div className={cx(classes.iconContainer, props.iconWrapperClass)}>
            {<div className={classes.icon}>{primaryIcon}</div>}
            {secondaryIcon ? (
                <div
                    className={!asTile && props.featuredImage?.display ? classes.secondIconInList : classes.secondIcon}
                >
                    {secondaryIcon}
                </div>
            ) : asTile ? null : (
                <div></div>
            )}
        </div>
    ) : null;

    const getAlt = (value?: string, url?: string): string => {
        if (value && value !== "" && url && url !== "") {
            const filename = url.split("/").pop();
            if (value !== filename) return t(value);
        }

        return t(`Thumbnail for: ${props.name}`);
    };

    let media: ReactElement | null = null;

    if (props.featuredImage?.display || props.mediaItem) {
        const imageUrl = props.image?.url ?? props.featuredImage?.fallbackImage ?? "";
        const imageAlt = getAlt(props.image?.alt, props.image?.url ?? props.featuredImage?.fallbackImage);

        media = (
            <div className={asTile ? classes.mobileMediaContainer : classes.mediaWrapContainer}>
                {props.mediaItem ? (
                    props.mediaItem
                ) : imageUrl.length > 0 ? (
                    <ListItemMedia
                        src={imageUrl}
                        srcSet={props.image?.urlSrcSet ? createSourceSetValue(props.image.urlSrcSet) : ""}
                        alt={imageAlt}
                    />
                ) : (
                    <div
                        className={cx(
                            mediaClasses.mediaItem,
                            mediaClasses.ratioContainer({ vertical: 9, horizontal: 16 }),
                        )}
                    >
                        <HomeWidgetItemDefaultImage />
                    </div>
                )}
                {!asTile && icon}
            </div>
        );
    } else if (shouldDisplayIcon && !asTile && !iconInMeta) {
        media = (
            <div className={cx(classes.iconContainer, props.iconWrapperClass)}>
                {!!primaryIcon && <div className={classes.icon}>{primaryIcon}</div>}
                {!!secondaryIcon && <div className={classes.secondIcon}>{secondaryIcon}</div>}
            </div>
        );
    }

    if (checkboxWrapped && !asTile) {
        media = (
            <div className={classes.iconAndCheckbox}>
                {checkboxWrapped}
                {media}
            </div>
        );
    }

    const actionsContent = props.actions ? (
        <div className={cx(classes.actionsContainer, { [pointerEventsClass()]: props.disableButtonsInItems })}>
            {props.actions}
        </div>
    ) : undefined;

    const descriptionView = props.description ? (
        <Paragraph className={cx(classes.description, descriptionClassName)}>
            {truncateDescription ? (
                <TruncatedText maxCharCount={descriptionMaxCharCount} lines={asTile ? 3 : 1}>
                    {props.description}
                </TruncatedText>
            ) : (
                props.description
            )}
        </Paragraph>
    ) : null;

    let metas = <Metas className={classes.metasContainer}>{props.metas}</Metas>;

    if (iconInMeta) {
        metas = (
            <div className={cx(classes.inlineIconAndMetasContainer, props.metasWrapperClass)}>
                <div
                    className={cx(
                        classes.inlineIconContainer,
                        props.iconWrapperClass,
                        iconInMeta && props.secondIcon ? classes.twoIconsInMetas : null,
                    )}
                >
                    {icon}
                </div>
                {metas}
            </div>
        );
    }

    return (
        <PageBox as={props.as ?? "li"} ref={selfRef} className={cx(props.className)}>
            <div className={classes.item}>
                {media}
                {asTile && (icon || actionsContent || hasCheckbox) && (
                    <div className={classes.tileActions}>
                        <ConditionalWrap condition={hasCheckbox} className={classes.iconAndCheckbox}>
                            {props.checkbox && checkboxWrapped}
                            {!iconInMeta && icon}
                        </ConditionalWrap>
                        {(hasImage || (!asTile && !hasImage) || (isMobileMedia && hasCheckbox)) && actionsContent}
                    </div>
                )}
                <div className={classes.contentContainer}>
                    <div className={classes.titleContainer}>
                        <Heading custom className={classes.title} depth={headingDepth}>
                            {props.url ? (
                                <SmartLink to={props.url} className={cx(classes.titleLink, props.nameClassName)}>
                                    <TruncatedText lines={asTile ? 3 : 1}>{props.name}</TruncatedText>
                                </SmartLink>
                            ) : (
                                <span className={cx(props.className)}>{props.name}</span>
                            )}
                        </Heading>
                        {((!hasImage && asTile && !hasCheckbox) || (!isMobileMedia && asTile && hasCheckbox)) &&
                            actionsContent}
                    </div>
                    <div className={classes.metaWrapContainer}>
                        <div className={classes.metaDescriptionContainer}>
                            {[ListItemLayout.TITLE_METAS, ListItemLayout.TITLE_METAS_DESCRIPTION].includes(layout) &&
                                metas}
                            {layout === ListItemLayout.TITLE_DESCRIPTION_METAS && descriptionView}
                            {layout === ListItemLayout.TITLE_METAS_DESCRIPTION && descriptionView}
                            {layout === ListItemLayout.TITLE_DESCRIPTION_METAS && metas}
                        </div>
                    </div>
                </div>
                {!asTile && actionsContent}
            </div>
        </PageBox>
    );
}

export const ListItemContext = React.createContext<IListItemOptions>({
    layout: ListItemLayout.TITLE_DESCRIPTION_METAS,
});
