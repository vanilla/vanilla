/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { IDiscussion } from "@dashboard/@types/api/discussion";
import DropDownItemLink from "@library/flyouts/items/DropDownItemLink";
import { t } from "@vanilla/i18n";
import React from "react";

interface IProps {
    discussion: IDiscussion;
}

export function DiscussionOptionsChangeLog(props: IProps) {
    const discussionID = props.discussion.discussionID;
    return (
        <>
            <DropDownItemLink to={`/log/filter?recordType=discussion&recordID=${discussionID}`}>
                {t("Revision History")}
            </DropDownItemLink>
            <DropDownItemLink to={`/log/filter?parentRecordID=${discussionID}&recordType=comment&operation=delete`}>
                {t("Deleted Comments")}
            </DropDownItemLink>
        </>
    );
}
