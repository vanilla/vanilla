/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { IUserFragment } from "@library/@types/api/users";
import { capitalizeFirstLetter } from "@vanilla/utils";
import { t } from "@library/utility/appUtils";
import { PublishStatus } from "@library/@types/api/core";
import BreadCrumbString, { ICrumbString } from "@library/navigation/BreadCrumbString";
import Translate from "@library/content/Translate";
import ProfileLink from "@library/navigation/ProfileLink";
import { ICountResult } from "@library/search/searchTypes";
import NumberFormatted from "@library/content/NumberFormatted";
import DateTime from "@library/content/DateTime";
import { MetaIcon, MetaItem, MetaTag } from "@library/metas/Metas";
import { metasClasses } from "@library/metas/Metas.styles";
import { discussionListVariables } from "@library/features/discussions/DiscussionList.variables";

interface IProps {
    updateUser?: IUserFragment;
    dateUpdated?: string;
    crumbs?: ICrumbString[];
    labels?: string[];
    status?: PublishStatus;
    type?: string;
    isForeign?: boolean;
    counts?: ICountResult[];
    extra?: React.ReactNode;
}

export function ResultMeta(props: IProps) {
    const { dateUpdated, updateUser, labels, crumbs, status, type, isForeign, counts, extra } = props;
    const isDeleted = status === PublishStatus.DELETED;

    const typeMetaContents =
        type && updateUser?.userID != null ? (
            <Translate
                source="<0/> by <1/>"
                c0={type ? t(capitalizeFirstLetter(type)) : undefined}
                c1={<ProfileLink className={metasClasses().metaLink} userFragment={updateUser} />}
            />
        ) : type ? (
            t(capitalizeFirstLetter(type))
        ) : null;

    const countMeta =
        counts &&
        counts.length > 0 &&
        counts.map((item, i) => {
            let { count, labelCode } = item;
            // labelCode returned from backend is always in plural, e.g. groups, sub-categories
            if (count < 2 && count !== 0) {
                const p = /ies|s$/;
                const m = labelCode.match(p);
                labelCode = labelCode.replace(p, m && m[0] === "ies" ? "y" : "");
            }

            // Looking to end up with something like this
            // %s discussions
            // %s comments
            return (
                <MetaItem key={i}>
                    <Translate source={`%s ${labelCode}`} c0={<NumberFormatted value={count} />} />
                </MetaItem>
            );
        });

    const displayDate = !!dateUpdated && !isNaN(new Date(dateUpdated).getTime());

    return (
        <>
            {labels &&
                labels.map((label) => (
                    <MetaTag key={label} tagPreset={discussionListVariables().labels.tagPreset}>
                        {t(label)}
                    </MetaTag>
                ))}

            {typeMetaContents && (
                <MetaItem>
                    {isDeleted ? (
                        <span className={"isDeleted"}>
                            <Translate source="Deleted <0/>" c0={type} />
                        </span>
                    ) : (
                        typeMetaContents
                    )}
                </MetaItem>
            )}

            {isForeign && <MetaIcon icon="meta-external" />}

            {displayDate && (
                <MetaItem>
                    <Translate source="Last Updated: <0/>" c0={<DateTime timestamp={dateUpdated} />} />
                </MetaItem>
            )}
            {countMeta}
            {crumbs && crumbs.length > 0 && (
                <MetaItem>
                    <BreadCrumbString crumbs={crumbs} />
                </MetaItem>
            )}
            {extra}
        </>
    );
}
