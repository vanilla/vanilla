/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import React, { useRef, useEffect } from "react";
import ScreenReaderContent from "@library/layout/ScreenReaderContent";
import { LoadingCircle, LoadingRectangle, LoadingSpacer } from "@library/loaders/LoadingRectangle";
import { useMeasure } from "@vanilla/react-utils";
import {
    IHomeWidgetContainerOptions,
    WidgetContainerDisplayType,
} from "@library/homeWidget/HomeWidgetContainer.styles";
import { PageBox } from "@library/layout/PageBox";
import { discussionListVariables } from "@library/features/discussions/DiscussionList.variables";
import { List } from "@library/lists/List";
import { ListItemLayout } from "@library/lists/ListItem.variables";
import { t } from "@vanilla/i18n";
import { Icon } from "@vanilla/icons";
import CheckBox from "@library/forms/Checkbox";
import { css, cx } from "@emotion/css";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { globalVariables } from "@library/styles/globalStyleVars";
import { HomeWidgetContainer, IHomeWidgetContainerProps } from "@library/homeWidget/HomeWidgetContainer";
import { homeWidgetItemClasses } from "@library/homeWidget/HomeWidgetItem.styles";

export interface IDiscussionListLoaderItem {
    image?: boolean;
    icon?: boolean;
    checkbox?: boolean;
    secondIcon?: boolean;
    iconInMeta?: boolean;
    asTile?: boolean;
    excerpt?: boolean;
}

interface ILoaderContainerProps extends Partial<IHomeWidgetContainerProps> {
    containerOptions?: IHomeWidgetContainerOptions;
}
export interface IDiscussionListLoader {
    count?: number;
    itemOptions?: IDiscussionListLoaderItem;
    displayType?: WidgetContainerDisplayType;
    containerProps?: ILoaderContainerProps;
}

const classes = {
    checkbox: css({
        alignItems: "flex-start",
        paddingRight: 16,
    }),
    imageOrIcon: css({
        marginRight: 16,
    }),
    imageWrapper: css({
        display: "flex",
    }),
    image: css({
        width: 144,
        height: 81,
        borderRadius: 8,
        overflow: "hidden",
    }),
    imageIcons: css({
        display: "flex",
        flexDirection: "column",
        justifyContent: "space-between",
        alignItems: "center",
        marginTop: -10,
        marginBottom: -10,
        marginLeft: -24,
    }),
    iconOnly: css({
        display: "flex",
        flexDirection: "column",
        "& .secondIcon": {
            marginTop: -15,
            marginLeft: -10,
        },
    }),
    iconBorder: css({
        width: "fit-content",
        height: "fit-content",
        borderRadius: "50%",
        borderStyle: "solid",
        borderWidth: 2,
        borderColor: ColorsUtils.colorOut(globalVariables().mainColors.bg),
    }),
    metaIcon: css({
        marginRight: 8,
        display: "flex",
        "& .secondIcon": {
            marginLeft: -10,
            marginTop: 10,
        },
    }),
};

function DiscussionLoaderItemActions(props) {
    return (
        <div style={{ ...props.style, display: "flex", opacity: 0.5 }}>
            <Icon icon="discussion-bookmark" style={{ fill: "transparent", marginRight: 6 }} />
            <Icon icon="navigation-ellipsis" />
        </div>
    );
}

function LoaderTile(props: IDiscussionListLoaderItem) {
    const { icon = true } = props;

    return (
        <div
            style={{
                display: "flex",
                flexDirection: "column",
                alignItems: "stretch",
                justifyContent: "space-between",
                position: "relative",
            }}
        >
            {props.image && (
                <>
                    <LoadingRectangle height={(9 / 16) * 296} />
                    {props.asTile && <LoadingSpacer height={16} />}
                </>
            )}
            <div style={{ padding: props.asTile ? 0 : 16 }}>
                <div
                    style={{
                        display: "flex",
                        alignItems: "center",
                        justifyContent: "space-between",
                        marginBottom: 4,
                    }}
                >
                    <div style={{ display: "flex" }}>
                        {props.checkbox && <CheckBox className={classes.checkbox} />}
                        {icon && !props.iconInMeta && <LoadingCircle width={40} height={40} />}
                        {props.secondIcon && !props.iconInMeta && (
                            <div style={{ marginLeft: -10 }} className={classes.iconBorder}>
                                <LoadingCircle width={32} height={32} />
                            </div>
                        )}
                    </div>
                    <DiscussionLoaderItemActions />
                </div>
                <div
                    style={{
                        flex: 1,
                        display: "flex",
                        flexDirection: "column",
                        alignItems: "stretch",
                        justifyContent: "space-between",
                    }}
                >
                    <LoadingRectangle height={20} />
                    <LoadingSpacer height={8} />
                    {props.excerpt && <LoadingRectangle height={42} />}
                    <LoadingSpacer height={10} />
                    <div style={{ display: "flex", alignItems: "center" }}>
                        {props.iconInMeta && (
                            <div className={classes.metaIcon}>
                                <LoadingCircle width={40} height={40} />
                                {props.secondIcon && (
                                    <div className={cx("secondIcon", classes.iconBorder)}>
                                        <LoadingCircle width={32} height={32} />
                                    </div>
                                )}
                            </div>
                        )}
                        <LoadingRectangle height={props.iconInMeta ? 32 : 16} />
                    </div>
                </div>
            </div>
        </div>
    );
}

export function DiscussionListLoaderTile(props: IDiscussionListLoaderItem) {
    return (
        <PageBox as="div" className={homeWidgetItemClasses().root}>
            <ScreenReaderContent>{t("Loading")}</ScreenReaderContent>
            <LoaderTile {...props} />
        </PageBox>
    );
}

export function DiscussionListLoaderItem(props: IDiscussionListLoaderItem) {
    const { icon = true } = props;
    const showIconOrImage = (icon && !props.iconInMeta) || props.image || (props.secondIcon && !props.iconInMeta);

    return (
        <PageBox as="li">
            <ScreenReaderContent>{t("Loading")}</ScreenReaderContent>
            {props.asTile ? (
                <LoaderTile {...props} />
            ) : (
                <div style={{ display: "flex" }}>
                    {props.checkbox && <CheckBox className={classes.checkbox} />}
                    {showIconOrImage && (
                        <div style={{ marginRight: 16 }}>
                            {props.image ? (
                                <div className={classes.imageWrapper}>
                                    <div className={classes.image}>
                                        <LoadingRectangle width="100%" height="100%" />
                                    </div>
                                    {(icon || props.secondIcon) && (
                                        <div className={classes.imageIcons}>
                                            {props.secondIcon ? (
                                                <div className={classes.iconBorder}>
                                                    <LoadingCircle width={32} height={32} />
                                                </div>
                                            ) : (
                                                <div />
                                            )}
                                            {icon && (
                                                <div className={classes.iconBorder}>
                                                    <LoadingCircle width={40} height={40} />
                                                </div>
                                            )}
                                        </div>
                                    )}
                                </div>
                            ) : (
                                <div className={classes.iconOnly}>
                                    {icon && !props.iconInMeta && <LoadingCircle width={40} height={40} />}
                                    {props.secondIcon && (
                                        <div className={cx("secondIcon", classes.iconBorder)}>
                                            <LoadingCircle width={32} height={32} />
                                        </div>
                                    )}
                                </div>
                            )}
                        </div>
                    )}
                    <div style={{ flex: 1 }}>
                        <LoadingRectangle height={20} />
                        <LoadingSpacer height={8} />
                        {props.excerpt && <LoadingRectangle height={42} />}
                        <LoadingSpacer height={10} />
                        <div style={{ display: "flex", alignItems: "center" }}>
                            {props.iconInMeta && (
                                <div className={classes.metaIcon}>
                                    <LoadingCircle width={40} height={40} />
                                    {props.secondIcon && (
                                        <div className={cx("secondIcon", classes.iconBorder)}>
                                            <LoadingCircle width={32} height={32} />
                                        </div>
                                    )}
                                </div>
                            )}
                            <LoadingRectangle height={props.iconInMeta ? 32 : 16} />
                        </div>
                    </div>
                    <DiscussionLoaderItemActions style={{ marginLeft: 16 }} />
                </div>
            )}
        </PageBox>
    );
}

export function DiscussionListLoader(props: IDiscussionListLoader) {
    const { count = 10, itemOptions = {}, displayType = WidgetContainerDisplayType.LIST } = props;
    const selfRef = useRef<HTMLDivElement>(null);
    const measure = useMeasure(selfRef);
    const isMobileMedia = measure.width <= 600;
    const variables = discussionListVariables();
    const items = Array.from(new Array(count)).map((_, idx) => idx);

    if (displayType === WidgetContainerDisplayType.LIST) {
        return (
            <div ref={selfRef}>
                <List
                    options={{
                        box: variables.contentBoxes.depth1,
                        itemBox: variables.contentBoxes.depth2,
                        itemLayout: !variables.item.excerpt.display ? ListItemLayout.TITLE_METAS : undefined,
                    }}
                >
                    {items.map((key) => (
                        <DiscussionListLoaderItem key={key} {...itemOptions} asTile={isMobileMedia} />
                    ))}
                </List>
            </div>
        );
    }

    if (displayType === WidgetContainerDisplayType.LINK) {
        return (
            <>
                {items.map((key) => (
                    <div key={key}>
                        <ScreenReaderContent>{t("Loading")}</ScreenReaderContent>
                        <LoadingSpacer height={8} width={300} />
                        <LoadingRectangle height={16} width={300} />
                        <LoadingSpacer height={8} width={300} />
                    </div>
                ))}
            </>
        );
    }

    return (
        <HomeWidgetContainer
            {...props.containerProps}
            options={props.containerProps?.containerOptions || props.containerProps?.options}
        >
            {items.map((key) => (
                <DiscussionListLoaderTile key={key} {...itemOptions} />
            ))}
        </HomeWidgetContainer>
    );
}

export default DiscussionListLoader;
