/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useMemo, useState } from "react";
import { searchResultClasses } from "@vanilla/library/src/scripts/features/search/searchResultsStyles";
import { useLayout } from "@vanilla/library/src/scripts/layout/LayoutContext";
import { TypeDiscussionsIcon } from "@vanilla/library/src/scripts/icons/searchIcons";
import classNames from "classnames";
import SmartLink from "@vanilla/library/src/scripts/routing/links/SmartLink";
import { ResultMeta } from "@library/result/ResultMeta";
import CollapseCommentsSearchMetaLoader from "@vanilla/addon-vanilla/search/CollapseCommentsSearchMetaLoader";
import { ICountResult } from "@vanilla/library/src/scripts/search/searchTypes";
import { useDiscussion } from "@vanilla/library/src/scripts/features/discussions/discussionHooks";
import { LoadStatus } from "@vanilla/library/src/scripts/@types/api/core";
import { notEmpty } from "@vanilla/utils";
import ErrorMessages from "@vanilla/library/src/scripts/forms/ErrorMessages";
import { useFallbackBackUrl } from "@vanilla/library/src/scripts/routing/links/BackRoutingProvider";
import qs from "qs";
import { makeSearchUrl } from "@vanilla/library/src/scripts/search/SearchPageRoute";

interface IProps {
    discussionID: number;
    icon?: React.ReactNode;
    headingLevel?: 2 | 3;
}

export default function CollapseCommentSearchMeta(props: IProps) {
    const { icon = <TypeDiscussionsIcon />, headingLevel = 2, discussionID } = props;
    const layoutContext = useLayout();
    const [forceLoader] = useState(false);

    const discussion = useDiscussion({ discussionID });

    // Back link
    const backUrl = useMemo(() => {
        const { search, host, pathname } = window.location;
        if (search) {
            const queryObj = qs.parse(search);
            if (queryObj.discussionID !== undefined) {
                delete queryObj.discussionID;
            }
            const newQuery = qs.stringify(queryObj);
            return makeSearchUrl() + "?" + newQuery;
        }
        return makeSearchUrl();
    }, []);
    useFallbackBackUrl(backUrl);

    if (forceLoader || ([LoadStatus.PENDING, LoadStatus.LOADING].includes(discussion.status) && !discussion.data)) {
        return <CollapseCommentsSearchMetaLoader />;
    }

    if (!discussion.data || discussion.error) {
        return <ErrorMessages errors={[discussion.error].filter(notEmpty)} />;
    }

    const HeadingTag = `h${headingLevel}` as "h1";
    const counts: ICountResult = {
        count: discussion.data.countComments,
        labelCode: "comments",
    };

    const classes = searchResultClasses(layoutContext.mediaQueries, !!icon);
    return (
        <div className={classNames(classes.content, classes.commentWrap)}>
            <div className={classes.iconWrap}>{icon}</div>
            <div className={classNames(classes.main, { hasIcon: !!icon })}>
                <SmartLink to={discussion.data.url} tabIndex={0} className={classes.link}>
                    <HeadingTag className={classes.title}>{discussion.data.name}</HeadingTag>
                </SmartLink>
                <div className={classes.metas}>
                    <ResultMeta
                        updateUser={discussion.data.insertUser}
                        type={"discussion"}
                        counts={[counts]}
                        crumbs={discussion.data.breadcrumbs}
                    />
                </div>
            </div>
        </div>
    );
}
