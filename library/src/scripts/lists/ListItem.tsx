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
    IListItemComponentOptions,
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
    truncateDescription?: boolean;
    icon?: React.ReactNode;
    iconWrapperClass?: string;
    metas?: React.ReactNode;
    metasWrapperClass?: string;
    mediaItem?: React.ReactNode;
    actions?: React.ReactNode;
    as?: keyof JSX.IntrinsicElements;
    headingDepth?: number;
    options?: Partial<IListItemComponentOptions>;
}

export function ListItem(props: IListItemProps) {
    const classes = listItemClasses();
    const selfRef = useRef<HTMLDivElement>(null);
    const measure = useMeasure(selfRef);
    const { layout } = useContext(ListItemContext);
    const listItemVars = listItemVariables(props.options);
    const {
        options: { iconPosition },
    } = listItemVars;

    const isMobileMedia = measure.width <= 600;

    const { headingDepth = 3, descriptionClassName, truncateDescription = true, descriptionMaxCharCount = 320 } = props;

    const media = props.mediaItem && (
        <div className={isMobileMedia ? classes.mobileMediaContainer : classes.mediaContainer}>{props.mediaItem}</div>
    );

    let metas = <Metas className={classes.metasContainer}>{props.metas}</Metas>;

    if (iconPosition === ListItemIconPosition.META) {
        metas = (
            <div className={cx(classes.inlineIconAndMetasContainer, props.metasWrapperClass)}>
                <div className={cx(classes.inlineIconContainer, props.iconWrapperClass)}>{props.icon}</div>
                {metas}
            </div>
        );
    }

    const descriptionView = props.description ? (
        <Paragraph className={cx(classes.description, descriptionClassName)}>
            {truncateDescription ? (
                <TruncatedText maxCharCount={descriptionMaxCharCount} lines={2}>
                    {props.description}
                </TruncatedText>
            ) : (
                props.description
            )}
        </Paragraph>
    ) : null;

    return (
        <PageBox as={props.as ?? "li"} ref={selfRef} className={cx(props.className)}>
            <div className={classes.item}>
                {iconPosition === ListItemIconPosition.DEFAULT && props.icon && (
                    <div className={cx(classes.iconContainer, props.iconWrapperClass)}>{props.icon}</div>
                )}
                <div className={classes.contentContainer}>
                    <div className={classes.titleContainer}>
                        <Heading custom className={classes.title} depth={headingDepth}>
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
                                metas}
                            {layout === ListItemLayout.TITLE_DESCRIPTION_METAS && descriptionView}
                            {isMobileMedia && media}
                            {layout === ListItemLayout.TITLE_METAS_DESCRIPTION && descriptionView}
                            {layout === ListItemLayout.TITLE_DESCRIPTION_METAS && metas}
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
