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
import { IListItemOptions, ListItemLayout } from "@library/lists/ListItem.variables";
import TruncatedText from "@library/content/TruncatedText";

export interface IListItemProps {
    className?: string;
    url: string;
    name?: React.ReactNode;
    nameClassName?: string;
    description?: React.ReactNode;
    icon?: React.ReactNode;
    metas?: React.ReactNode;
    mediaItem?: React.ReactNode;
    actions?: React.ReactNode;
    as?: keyof JSX.IntrinsicElements;
}

export function ListItem(props: IListItemProps) {
    const classes = listItemClasses();
    const selfRef = useRef<HTMLDivElement>(null);
    const measure = useMeasure(selfRef);
    const { layout } = useContext(ListItemContext);

    const isMobileMedia = measure.width <= 600;

    const media = props.mediaItem && (
        <div className={isMobileMedia ? classes.mobileMediaContainer : classes.mediaContainer}>{props.mediaItem}</div>
    );

    const metaView = props.metas && <Metas className={classes.metasContainer}>{props.metas}</Metas>;
    const descriptionView = props.description && (
        <div className={classes.description}>
            <TruncatedText maxCharCount={320} lines={2}>
                {props.description}
            </TruncatedText>
        </div>
    );

    return (
        <PageBox as={props.as ?? "li"} ref={selfRef} className={cx(props.className)}>
            <div className={classes.item}>
                {props.icon && <div className={classes.iconContainer}>{props.icon}</div>}
                <div className={classes.contentContainer}>
                    <div className={classes.titleContainer}>
                        <h3 className={cx(classes.title)}>
                            <SmartLink to={props.url} className={cx(classes.titleLink, props.nameClassName)}>
                                {props.name}
                            </SmartLink>
                        </h3>
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
