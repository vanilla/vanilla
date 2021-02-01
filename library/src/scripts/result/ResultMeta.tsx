/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import classNames from "classnames";
import { IUserFragment } from "@library/@types/api/users";
import { capitalizeFirstLetter } from "@vanilla/utils";
import { t } from "@library/utility/appUtils";
import { PublishStatus } from "@library/@types/api/core";
import BreadCrumbString, { ICrumbString } from "@library/navigation/BreadCrumbString";
import { metasClasses } from "@library/styles/metasStyles";
import Translate from "@library/content/Translate";
import DateTime from "@library/content/DateTime";
import ProfileLink from "@library/navigation/ProfileLink";
import { ExternalIcon } from "@library/icons/common";
import { ICountResult } from "@library/search/searchTypes";
import NumberFormatted from "@library/content/NumberFormatted";

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
    const classesMetas = metasClasses();

    const typeMeta =
        type && updateUser?.userID != null ? (
            <Translate
                source="<0/> by <1/>"
                c0={type ? t(capitalizeFirstLetter(type)) : undefined}
                c1={<ProfileLink className={classesMetas.meta} username={updateUser.name} userID={updateUser.userID} />}
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
            return (
                <span className={classesMetas.meta} key={i}>
                    <Translate source={`<0/> ${labelCode}`} c0={<NumberFormatted value={count} />} />
                </span>
            );
        });

    return (
        <React.Fragment>
            {labels &&
                labels.map((label) => (
                    <span className={classesMetas.metaLabel} key={label}>
                        {t(label)}
                    </span>
                ))}

            {typeMeta && (
                <span className={classNames(classesMetas.meta)}>
                    {isDeleted ? (
                        <span className={classNames("meta-inline", "isDeleted")}>
                            <Translate source="Deleted <0/>" c0={type} />
                        </span>
                    ) : (
                        typeMeta
                    )}
                </span>
            )}

            {isForeign && (
                <span className={classesMetas.metaIcon}>
                    <ExternalIcon />
                </span>
            )}

            {dateUpdated && (
                <span className={classesMetas.meta}>
                    <Translate source="Last Updated: <0/>" c0={<DateTime timestamp={dateUpdated} />} />
                </span>
            )}
            {countMeta}
            {crumbs && crumbs.length > 0 && <BreadCrumbString className={classesMetas.meta} crumbs={crumbs} />}
            {extra}
        </React.Fragment>
    );
}
