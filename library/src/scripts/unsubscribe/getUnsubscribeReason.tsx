/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import React, { ReactElement } from "react";
import { t } from "@library/utility/appUtils";
import { IUnsubscribeCategory, IUnsubscribePreference } from "@library/unsubscribe/unsubscribePage.types";
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
};

export function getUnsubscribeReason({ preferenceName, preferenceRaw }: IUnsubscribePreference): ReactElement {
    return <p key={preferenceRaw}>{reasonsDescriptions[preferenceName]}</p>;
}

export function getCategoryReason(category: IUnsubscribeCategory, isUnfollowCategory?: boolean): ReactElement {
    const { categoryName, preferenceName, preferenceRaw } = category;
    const slug = categoryName.replace(/\s/g, "-").toLowerCase();
    const categoryLink = <SmartLink to={`/categories/${slug}`}>{categoryName}</SmartLink>;

    if (isUnfollowCategory) {
        return (
            <p>
                <Translate source="You are no longer following <0/>" c0={categoryLink} />
            </p>
        );
    }

    const description = preferenceName === "NewDiscussion" ? t("New posts and comments") : t("New comments on posts");
    return (
        <p key={preferenceRaw}>
            {categoryLink}
            {` | ${description}`}
        </p>
    );
}
