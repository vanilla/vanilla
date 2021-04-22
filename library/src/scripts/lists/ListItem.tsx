/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { cx } from "@emotion/css";
import { listItemClasses } from "@library/lists/ListItem.styles";
import { Metas } from "@library/metas/Metas";
import SmartLink from "@library/routing/links/SmartLink";
import React, { useContext, useRef } from "react";
import { useMeasure } from "@vanilla/react-utils";
import { PageBox } from "@library/layout/PageBox";
import {
    IListItemOptions,
    ListItemIconPosition,
    ListItemLayout,
    listItemVariables,
} from "@library/lists/ListItem.variables";
import TruncatedText from "@library/content/TruncatedText";
import Heading from "@library/layout/Heading";
import Paragraph from "@library/layout/Paragraph";

export interface IListItemProps {
    className?: string;
    url?: string;
    name?: React.ReactNode;
    nameClassName?: string;
    description?: React.ReactNode;
    descriptionClassName?: string;
    descriptionMaxCharCount?: number;
    icon?: React.ReactNode;
    metas?: React.ReactNode;
    mediaItem?: React.ReactNode;
    actions?: React.ReactNode;
    as?: keyof JSX.IntrinsicElements;
    headingDepth?: number;
}

export function ListItem(props: IListItemProps) {
    const classes = listItemClasses();
    const selfRef = useRef<HTMLDivElement>(null);
    const measure = useMeasure(selfRef);
    const { layout } = useContext(ListItemContext);
    const iconPosition = listItemVariables().options.iconPosition;

    const isMobileMedia = measure.width <= 600;

    const { headingDepth = 3, descriptionClassName, descriptionMaxCharCount = 320 } = props;

    const media = props.mediaItem && (
        <div className={isMobileMedia ? classes.mobileMediaContainer : classes.mediaContainer}>{props.mediaItem}</div>
    );

    const metaView = props.metas && (
        <Metas className={classes.metasContainer}>
            {iconPosition === ListItemIconPosition.META && (
                <div className={cx(classes.iconContainer, classes.iconContainerInline)}>{props.icon}</div>
            )}
            {props.metas}
        </Metas>
    );
    const descriptionView = props.description && (
        <Paragraph className={cx(classes.description, descriptionClassName)}>
            <TruncatedText maxCharCount={descriptionMaxCharCount} lines={2}>
                {props.description}
            </TruncatedText>
        </Paragraph>
    );

    return (
        <PageBox as={props.as ?? "li"} ref={selfRef} className={cx(props.className)}>
            <div className={classes.item}>
                {iconPosition === ListItemIconPosition.DEFAULT && props.icon && (
                    <div className={classes.iconContainer}>{props.icon}</div>
                )}
                <div className={classes.contentContainer}>
                    <div className={classes.titleContainer}>
                        <Heading className={classes.title} depth={headingDepth}>
                            {props.url ? (
                                <SmartLink to={props.url} className={cx(classes.titleLink, props.nameClassName)}>
                                    {props.name}
                                </SmartLink>
                            ) : (
                                <span className={cx(props.nameClassName)}>{props.name}</span>
                            )}
                        </Heading>

                        {props.actions && <div className={classes.actionsContainer}>{props.actions}</div>}
                    </div>
                    <div className={classes.mediaWrapContainer}>
                        {!isMobileMedia && media}
                        <div className={classes.metaDescriptionContainer}>
                            {[ListItemLayout.TITLE_METAS, ListItemLayout.TITLE_METAS_DESCRIPTION].includes(layout) &&
                                metaView}
                            {layout === ListItemLayout.TITLE_DESCRIPTION_METAS && descriptionView}
                            {isMobileMedia && media}
                            {layout === ListItemLayout.TITLE_METAS_DESCRIPTION && descriptionView}
                            {layout === ListItemLayout.TITLE_DESCRIPTION_METAS && metaView}
                        </div>
                    </div>
                </div>
            </div>
        </PageBox>
    );
}

export const ListItemContext = React.createContext<IListItemOptions>({
    layout: ListItemLayout.TITLE_DESCRIPTION_METAS,
});
