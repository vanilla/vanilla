import Utils from "@vanilla/injectables/Utils";
import Components from "@vanilla/injectables/Components";
import OriginalPost from "@vanilla/injectables/OriginalPostFragment";
import React from "react";

export default function OriginalPostFragment(props: OriginalPost.Props) {
    const { discussion, title } = props;
    const contentItem = OriginalPost.useContentItemContext();
    const currentUserSignedIn = Utils.useCurrentUserSignedIn();
    const permissions = Utils.usePermissionsContext();
    const showResolved = permissions.hasPermission("staff.allow") && Utils.getMeta("triage.enabled", false);

    return (
        <Components.LayoutWidget className={"originalPostFragment__root"}>
            <div className={"originalPostFragment__title"}>
                {showResolved && (
                    <div className={"originalPostFragment__resolvedStatus"}>
                        <span>
                            <Components.ToolTip
                                label={discussion.resolved ? Utils.t("Resolved") : Utils.t("Unresolved")}
                            >
                                <Components.ToolTipIcon>
                                    <Components.Icon icon={discussion.resolved ? "resolved" : "unresolved"} />
                                </Components.ToolTipIcon>
                            </Components.ToolTip>
                        </span>
                    </div>
                )}
                {title ? <h1>{title}</h1> : <h1>{discussion.name}</h1>}
                <div className={"originalPostFragment__post-meta"}>
                    {discussion.pinned && (
                        <Components.Tag className={"postHeader__tag"} preset={"greyscale"}>
                            {Utils.t("Announced")}
                        </Components.Tag>
                    )}
                    {discussion.closed && (
                        <Components.Tag className={"postHeader__tag"} preset={"greyscale"}>
                            {Utils.t("Closed")}
                        </Components.Tag>
                    )}
                </div>
                <div className={"originalPostFragment__action-lockup"}>
                    {currentUserSignedIn && (
                        <>
                            <OriginalPost.ReportCountMeta
                                countReports={discussion.reportMeta?.countReports}
                                recordID={discussion.discussionID}
                                recordType="discussion"
                                classNames={"report-count-meta"}
                            />
                            <OriginalPost.PostBookmarkToggle discussion={discussion} classNames={"bookmark-post"} />
                            <OriginalPost.PostOptionsMenu discussion={discussion} />
                        </>
                    )}
                </div>
            </div>
            <PostHeader {...props} />
            {props.moderationContent}
            <Components.UserContent
                vanillaSanitizedHtml={
                    // The discussion body is sanitized by the server, so we can safely use it here
                    // The type is optional because when fetching a discussion list the body may be excluded, but here it is definitely part of the response.
                    props.discussion.body!
                }
            />
            <OriginalPost.UserSignature user={contentItem.insertUser!} classNames={"originalPostFragment__signature"} />
            <div className={"originalPostFragment__footer"}>
                <OriginalPost.ContentItemActions reactions={props.discussion.reactions} />
                {props.onReply && <OriginalPost.PostReplyButton onReply={props.onReply} className={"reply-button"} />}
            </div>
        </Components.LayoutWidget>
    );
}

function PostHeader(props: OriginalPost.Props) {
    const { discussion } = props;
    const { insertUser } = discussion;

    if (!insertUser) {
        return null;
    }

    return (
        <div className="postHeader__root">
            <div className="postHeader__userPhoto">
                <Components.ProfileLink userFragment={insertUser}>
                    <Components.UserPhoto userInfo={insertUser} size={"medium"} />
                </Components.ProfileLink>
            </div>
            <div className={"postHeader__author-lockup"}>
                <div className="postHeader__userName">
                    <Components.ProfileLink userFragment={insertUser}>{insertUser.name}</Components.ProfileLink>
                </div>
                <div className="postHeader__userTitle">
                    <Components.UserTitle user={insertUser} />
                </div>
            </div>
            <div className={"postHeader__meta-lockup"}>
                <OriginalPost.ContentItemPermalink />
            </div>
        </div>
    );
}
