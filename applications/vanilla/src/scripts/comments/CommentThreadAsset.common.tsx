/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { css } from "@emotion/css";
import { scrollToCurrentHash } from "@library/content/hashScrolling";
import { PermissionMode, type IPermissionOptions } from "@library/features/users/Permission";
import { usePermissionsContext } from "@library/features/users/PermissionsContext";
import { HomeWidgetContainer } from "@library/homeWidget/HomeWidgetContainer";
import { IHomeWidgetContainerOptions } from "@library/homeWidget/HomeWidgetContainer.styles";
import ConditionalWrap from "@library/layout/ConditionalWrap";
import { useScrollOffset } from "@library/layout/ScrollOffsetContext";
import { List } from "@library/lists/List";
import { Tag } from "@library/metas/Tags";
import { TagPreset } from "@library/metas/Tags.variables";
import { BorderType } from "@library/styles/styleHelpersBorders";
import { CommentsApi } from "@vanilla/addon-vanilla/comments/CommentsApi";
import { commentThreadClasses } from "@vanilla/addon-vanilla/comments/CommentThread.classes";
import { useCommentThreadParentContext } from "@vanilla/addon-vanilla/comments/CommentThreadParentContext";
import { t } from "@vanilla/i18n";
import { useLayoutEffect } from "react";
import { useLocation } from "react-router";
import { sprintf } from "sprintf-js";

interface IProps {
    title?: string;
    description?: string;
    subtitle?: string;
    showOPTag?: boolean;
    containerOptions?: IHomeWidgetContainerOptions;
    defaultSort?: CommentsApi.IndexThreadParams["sort"];
    authorBadges?: {
        display: boolean;
        limit: number;
    };
    isPreview?: boolean;
    children?: React.ReactNode;
    topPager?: React.ReactNode;
    bottomPager?: React.ReactNode;
    renderTitle?: boolean;
    hasComments?: boolean;
    selectAllCommentsCheckbox?: React.ReactNode;
}

export function CommentThreadAssetCommon(props: IProps) {
    const classes = commentThreadClasses();

    const { hasPermission } = usePermissionsContext();
    const commentParent = useCommentThreadParentContext();
    const permissionOptions: IPermissionOptions = {
        resourceType: "category",
        resourceID: commentParent.categoryID,
        mode: PermissionMode.RESOURCE_IF_JUNCTION,
    };
    const { getCalcedHashOffset } = useScrollOffset();
    const hash = useLocation().hash;
    useLayoutEffect(() => {
        scrollToCurrentHash(getCalcedHashOffset());
    }, [hash]);

    const renderTitle = props.renderTitle ?? true;

    const recordTerm = commentParent.recordType === "event" ? t("event") : t("post");

    return (
        <HomeWidgetContainer
            className={css({ "&&": { marginTop: 0 } })} // better solution for this later, this is really to prevent extra top margin when opening the bulk action modal
            depth={2}
            title={
                renderTitle ? (
                    <>
                        <span>{props.title ?? t("Comments")}</span>{" "}
                        {commentParent.closed && (
                            <Tag className={classes.closedTag} preset={TagPreset.GREYSCALE}>
                                {t("Closed")}
                            </Tag>
                        )}
                    </>
                ) : undefined
            }
            description={props.description}
            subtitle={props.subtitle}
            options={{
                ...props.containerOptions,
                isGrid: false,
            }}
            extraHeader={
                props.hasComments && (
                    <ConditionalWrap
                        condition={Boolean(props.selectAllCommentsCheckbox)}
                        className={classes.topPagerWrapper}
                    >
                        {Boolean(props.selectAllCommentsCheckbox) && props.selectAllCommentsCheckbox}
                        {props.topPager}
                    </ConditionalWrap>
                )
            }
        >
            {props.hasComments ? (
                <List
                    options={{
                        itemBox: {
                            borderType: BorderType.SEPARATOR,
                        },
                    }}
                >
                    {props.children}
                </List>
            ) : (
                <em>
                    {sprintf(
                        hasPermission("comments.add", permissionOptions)
                            ? t("Add a comment to this %s.")
                            : t("No comments on this %s."),
                        recordTerm,
                    )}
                </em>
            )}

            {commentParent.closed && (
                <div
                    className={css({
                        marginTop: 8,
                        marginBottom: 8,
                    })}
                >
                    {sprintf(t("This %s has been closed."), recordTerm)}
                </div>
            )}
            {props.bottomPager}
        </HomeWidgetContainer>
    );
}
