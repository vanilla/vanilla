/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { IDiscussion } from "@dashboard/@types/api/discussion";
import { css } from "@emotion/css";
import DiscussionBookmarkToggle from "@library/features/discussions/DiscussionBookmarkToggle";
import { discussionListVariables } from "@library/features/discussions/DiscussionList.variables";
import DiscussionOptionsMenu from "@library/features/discussions/DiscussionOptionsMenu";
import { useCurrentUser, useCurrentUserSignedIn } from "@library/features/users/userHooks";
import { Tag } from "@library/metas/Tags";
import { useFallbackBackUrl } from "@library/routing/links/BackRoutingProvider";
import { BorderType } from "@library/styles/styleHelpersBorders";
import { ICategoryFragment } from "@vanilla/addon-vanilla/categories/categoriesTypes";
import { t } from "@vanilla/i18n";
import { useState } from "react";
import { Icon } from "@vanilla/icons";
import { getMeta } from "@library/utility/appUtils";
import { usePermissionsContext } from "@library/features/users/PermissionsContext";
import type { IHomeWidgetContainerOptions } from "@library/homeWidget/HomeWidgetContainer.styles";
import { HomeWidgetContainer } from "@library/homeWidget/HomeWidgetContainer";
import { ContributionItem } from "@library/contributionItems/ContributionItem";
import { reactionsVariables } from "@library/reactions/Reactions.variables";
import type { DiscussionsApi } from "@vanilla/addon-vanilla/posts/DiscussionsApi";
import { ReportCountMeta } from "@vanilla/addon-vanilla/reporting/ReportCountMeta";
import { ContentItemContextProvider } from "@vanilla/addon-vanilla/contentItem/ContentItemContext";
import { ContentItem } from "@vanilla/addon-vanilla/contentItem/ContentItem";
import { ContentItemWarning } from "@vanilla/addon-vanilla/contentItem/ContentItemWarning";
import ContentItemClasses from "@vanilla/addon-vanilla/contentItem/ContentItem.classes";
import { ContentItemVisibilityRenderer } from "@vanilla/addon-vanilla/contentItem/ContentItemVisibilityRenderer";
import { useCommentThreadParentContext } from "@vanilla/addon-vanilla/comments/CommentThreadParentContext";
import { useDiscussionQuery } from "@vanilla/addon-vanilla/comments/CommentThread.hooks";
import { commentThreadClasses } from "@vanilla/addon-vanilla/comments/CommentThread.classes";
import CreateCommentAsset, { CreateOriginalPostReply } from "@vanilla/addon-vanilla/comments/CreateCommentAsset";
import { useCreateCommentContext } from "@vanilla/addon-vanilla/posts/CreateCommentContext";
import { useDraftContext } from "@vanilla/addon-vanilla/drafts/DraftContext";
import isEmpty from "lodash-es/isEmpty";
import { ToolTip, ToolTipIcon } from "@library/toolTip/ToolTip";
import { IPermissionOptions, PermissionMode } from "@library/features/users/Permission";
import { useFragmentImpl } from "@library/utility/FragmentImplContext";
import { LayoutWidget } from "@library/layout/LayoutWidget";

interface IProps {
    discussion: IDiscussion;
    discussionApiParams?: DiscussionsApi.GetParams;
    category: ICategoryFragment;
    containerOptions?: IHomeWidgetContainerOptions;
    title?: string;
    titleType: "discussion/name" | "none";
    isPreview?: boolean;
    authorBadges?: {
        display: boolean;
        limit: number;
    };
}

const classes = {
    container: css({
        position: "relative",
        container: "originalPostContainer / inline-size",
    }),
    actions: css({
        display: "flex",
        alignItems: "center",
        gap: 4,

        "@container originalPostContainer (width: 500px)": {
            gap: 16,
        },
    }),
};

export default function OriginalPostAsset(props: IProps) {
    const Impl = useFragmentImpl("OriginalPostFragment", OriginalPostAssetImpl);

    return <Impl {...props} />;
}

export function OriginalPostAssetImpl(props: IProps) {
    const { discussion: discussionPreload, discussionApiParams, category, authorBadges } = props;
    const { discussionID } = discussionPreload;

    const currentUser = useCurrentUser();
    const { hasPermission } = usePermissionsContext();
    const { currentPage } = useCommentThreadParentContext();
    const { setCreateCommentLocation } = useCreateCommentContext();
    const { draft } = useDraftContext();
    const isTopLevelComment = !isEmpty(draft)
        ? !(draft?.attributes?.draftMeta ?? {}).hasOwnProperty("commentPath")
        : false;

    const {
        query: { data },
        invalidate: invalidateDiscussionQuery,
    } = useDiscussionQuery(discussionID, discussionApiParams, discussionPreload);

    const currentUserSignedIn = useCurrentUserSignedIn();
    useFallbackBackUrl(category.url);

    const discussion = data!;

    const permissionOptions: IPermissionOptions = {
        mode: PermissionMode.RESOURCE_IF_JUNCTION,
        resourceType: "category",
        resourceID: category.categoryID,
    };

    const replyPermission = hasPermission("comments.add", permissionOptions);
    const closePermission = hasPermission("discussions.close", permissionOptions);

    const canReply = discussion.closed ? closePermission : replyPermission;

    // Hide discussion (e.g. from ignored users)
    const discussionIsFromIgnoredUser =
        getMeta("ignoredUserIDs", []).includes(discussion.insertUserID) && !props.isPreview;
    const [isDiscussionHidden, setIsDiscussionHidden] = useState(discussionIsFromIgnoredUser);

    const showResolved = hasPermission("staff.allow") && getMeta("triage.enabled", false);
    const showWarningStatus = discussion.insertUserID === currentUser?.userID || hasPermission("community.moderate");
    const classesCommentThread = commentThreadClasses.useAsHook();

    const postMeta = (
        <>
            {showResolved && (
                <span className={classesCommentThread.metaIconContainer}>
                    <ToolTip customWidth={50} label={discussion.resolved ? t("Resolved") : t("Unresolved")}>
                        <ToolTipIcon>
                            <Icon icon={discussion.resolved ? "resolved" : "unresolved"} />
                        </ToolTipIcon>
                    </ToolTip>
                </span>
            )}
            {discussion.muted && (
                <span className={classesCommentThread.metaIconContainer}>
                    <ToolTip customWidth={50} label={t("Muted")}>
                        <ToolTipIcon>
                            <Icon icon={"data-muted"} />
                        </ToolTipIcon>
                    </ToolTip>
                </span>
            )}
            {discussion.pinned && (
                <Tag className={classesCommentThread.closedTag} preset={discussionListVariables().labels.tagPreset}>
                    {t("Announced")}
                </Tag>
            )}
            {discussion.closed && (
                <Tag className={classesCommentThread.closedTag} preset={discussionListVariables().labels.tagPreset}>
                    {t("Closed")}
                </Tag>
            )}
        </>
    );

    const actions = currentUserSignedIn && (
        <div className={classes.actions}>
            {props.titleType === "none" ? postMeta : null}
            <ReportCountMeta
                countReports={discussion.reportMeta?.countReports}
                recordID={discussion.discussionID}
                recordType="discussion"
            />
            <DiscussionBookmarkToggle discussion={discussion} onSuccess={invalidateDiscussionQuery} />
            <DiscussionOptionsMenu
                discussion={discussion}
                onMutateSuccess={invalidateDiscussionQuery}
                onDiscussionPage
            />
        </div>
    );

    const replyProps = canReply && {
        onReply: () => {
            setCreateCommentLocation("original-post");
        },
        replyLabel: isTopLevelComment ? t("Continue Replying") : t("Reply"),
    };

    return (
        <LayoutWidget>
            <HomeWidgetContainer
                options={{ ...props.containerOptions }}
                depth={1}
                title={
                    props.titleType !== "none" ? (
                        <>
                            <span>{props.title ?? discussion.name}</span>
                            {postMeta}
                        </>
                    ) : undefined
                }
                actions={props.titleType !== "none" ? actions : undefined}
            >
                <ContentItemContextProvider
                    recordType={"discussion"}
                    recordID={discussion.discussionID}
                    recordUrl={discussion.url}
                    name={discussion.name}
                    timestamp={discussion.dateInserted}
                    dateUpdated={discussion.dateUpdated}
                    insertUser={discussion.insertUser}
                    updateUser={discussion.updateUser}
                    attributes={discussion.attributes}
                    authorID={discussion.insertUserID}
                >
                    <ContentItem
                        boxOptions={{
                            borderType: BorderType.NONE,
                        }}
                        user={discussion.insertUser!}
                        warnings={
                            discussion.warning &&
                            showWarningStatus && (
                                <ContentItemWarning
                                    warning={discussion.warning}
                                    recordName={discussion.name}
                                    recordUrl={discussion.url}
                                    moderatorNoteVisible={hasPermission("community.moderate")}
                                />
                            )
                        }
                        content={discussion.body!}
                        userPhotoLocation={"header"}
                        collapsed={currentPage > 1}
                        reactions={discussion.reactions}
                        categoryID={discussion.categoryID}
                        options={props.titleType === "none" ? actions : undefined}
                        additionalAuthorMeta={
                            authorBadges?.display &&
                            discussion.insertUser?.badges?.length && (
                                <>
                                    {discussion.insertUser.badges
                                        .map((badge, index) => (
                                            <ContributionItem
                                                key={index}
                                                name={badge.name}
                                                url={badge.url}
                                                photoUrl={badge.photoUrl}
                                                themingVariables={reactionsVariables()}
                                                className={ContentItemClasses().authorBadgesMeta}
                                            />
                                        ))
                                        .slice(0, authorBadges.limit ?? 5)}
                                </>
                            )
                        }
                        isHidden={isDiscussionHidden}
                        visibilityHandlerComponent={
                            discussionIsFromIgnoredUser && (
                                <ContentItemVisibilityRenderer
                                    onVisibilityChange={setIsDiscussionHidden}
                                    contentText={t("Content from Ignored User.")}
                                    isPostHidden={isDiscussionHidden}
                                />
                            )
                        }
                        {...replyProps}
                    />

                    <CreateOriginalPostReply replyTo={discussion.insertUser?.name} />
                </ContentItemContextProvider>
            </HomeWidgetContainer>
        </LayoutWidget>
    );
}
