/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { IDiscussion } from "@dashboard/@types/api/discussion";
import { cx } from "@emotion/css";
import DateTime from "@library/content/DateTime";
import Translate from "@library/content/Translate";
import { discussionListClasses } from "@library/features/discussions/DiscussionList.classes";
import { discussionListVariables } from "@library/features/discussions/DiscussionList.variables";
import { userCardClasses } from "@library/features/userCard/UserCard.styles";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { UserPhoto } from "@library/headers/mebox/pieces/UserPhoto";
import { BookmarkIcon } from "@library/icons/common";
import { iconClasses } from "@library/icons/iconStyles";
import { ListItem } from "@library/lists/ListItem";
import { MetaItem } from "@library/metas/Metas";
import { Tag } from "@library/metas/Tags";
import ProfileLink from "@library/navigation/ProfileLink";
import SmartLink from "@library/routing/links/SmartLink";
import { t } from "@vanilla/i18n";
import React from "react";

interface IProps {
    discussion: IDiscussion;
}

export default function DiscussionListItem(props: IProps) {
    const { discussion } = props;
    const classes = discussionListClasses();
    const variables = discussionListVariables();
    let photo = <UserPhoto userInfo={discussion.insertUser} size={variables.profilePhoto.size} />;

    if (discussion.insertUser) {
        photo = <ProfileLink userFragment={discussion.insertUser}>{photo}</ProfileLink>;
    }

    const actions = (
        <>
            {/* Not implemented at the moment */}
            {/* <Button buttonType={ButtonTypes.ICON} className={iconClasses().bookmark()}>
                <BookmarkIcon />
            </Button> */}
            {/* <Button buttonType={ButtonTypes.ICON}>
                <DropDownMenuIcon />
            </Button> */}
        </>
    );
    return (
        <ListItem
            url={discussion.url}
            name={discussion.name}
            nameClassName={cx(classes.title, { isRead: !discussion.unread })}
            description={discussion.excerpt} // FIXME: excerpt.
            metas={<DiscussionListItemMeta {...discussion} />}
            // actions={actions}
            icon={photo}
        ></ListItem>
    );
}

function DiscussionListItemMeta(props: IDiscussion) {
    const { pinned, closed, insertUser, countViews, countComments, category, dateLastComment, lastUser } = props;
    return (
        <>
            {closed && <Tag>{t("Closed")}</Tag>}
            {pinned && <Tag>{t("Announcement")}</Tag>}
            {countViews != null && (
                <MetaItem>
                    <Translate source="<0/> views" c0={countViews} />
                </MetaItem>
            )}
            {countComments != null && (
                <MetaItem>
                    <Translate source="<0/> comments" c0={countComments} />
                </MetaItem>
            )}
            {insertUser && (
                <MetaItem>
                    <Translate source="Started by <0/>" c0={<ProfileLink userFragment={insertUser} />} />
                </MetaItem>
            )}
            {lastUser && (
                <MetaItem>
                    <Translate source="Most recent by <0/>" c0={<ProfileLink userFragment={lastUser} />} />
                </MetaItem>
            )}
            {}
            {dateLastComment && (
                <MetaItem>
                    <DateTime timestamp={dateLastComment} />
                </MetaItem>
            )}
            {category && (
                <MetaItem>
                    <SmartLink to={category.url}>{category.name}</SmartLink>
                </MetaItem>
            )}
        </>
    );
}
