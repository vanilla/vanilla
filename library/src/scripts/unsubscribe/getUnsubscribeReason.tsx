/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { ReactElement } from "react";
import { t } from "@library/utility/appUtils";
import { IUnsubscribeContent, IUnsubscribePreference } from "@library/unsubscribe/unsubscribePage.types";
import SmartLink from "@library/routing/links/SmartLink";
import Translate from "@library/content/Translate";

export interface IUnsubscribeOption {
    type: string;
    label: ReactElement;
}

const reasonsDescriptions = {
    WallComment: t("New posts on my profile's activity feed"),
    ActivityComment: t("New comments on my activity feed posts"),
    Badge: t("New badges"),
    BadgeRequest: t("New badge requests"),
    AuthorStatus: t("Status changes on my ideas"),
    VoterStatus: t("Status changes on ideas I voted on"),
    AnswerAccepted: t("My answer is accepted"),
    QuestionAnswered: t("New answers on my question"),
    QuestionAnswer: t("New answers on my question"),
    QuestionFollowUp: t("New follow-up to my answered questions"),
    DiscussionComment: t("New comments on my posts"),
    BookmarkComment: t("New comments on my bookmarked posts"),
    ParticipateComment: t("New comments on posts I've participated in"),
    Mention: t("I am mentioned"),
    ConversationMessage: t("Private messages"),
    AddedToConversation: t("Private Messages"),
    GroupInvite: t("I'm invited to a group"),
    GroupRequestApproved: t("My group membership request is approved"),
    GroupNewDiscussion: t("New posts in groups I'm a member of"),
    NewArticle: t("New articles"),
    UpdatedArticle: t("Updated articles"),
};

export function getUnsubscribeReason({ preferenceName, preferenceRaw }: IUnsubscribePreference): ReactElement {
    return <p key={preferenceRaw}>{reasonsDescriptions[preferenceName]}</p>;
}

export function getFollowedContentReason(
    content: IUnsubscribeContent,
    isUnfollowContent?: boolean,
    isDigestHideContent?: boolean,
    isContentCategory?: boolean,
): ReactElement {
    const { contentName, contentUrl, preferenceName, preferenceRaw } = content;
    const contentLink = (
        <SmartLink
            to={isContentCategory ? `/categories/${contentName.replace(/\s/g, "-").toLowerCase()}` : contentUrl ?? ""}
        >
            {contentName}
        </SmartLink>
    );

    if (isDigestHideContent) {
        return contentLink;
    }

    if (isUnfollowContent) {
        return (
            <p>
                <Translate source="You are no longer following <0/>" c0={contentLink} />
            </p>
        );
    }

    const description = ["NewArticle", "UpdatedArticle"].includes(preferenceName)
        ? reasonsDescriptions[preferenceName]
        : preferenceName.includes("NewDiscussion") || preferenceName.includes("Posts")
        ? t("New posts")
        : t("New comments on posts");
    return (
        <p key={preferenceRaw}>
            {contentLink}
            {` | ${description}`}
        </p>
    );
}
