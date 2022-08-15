/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useMemo, useState } from "react";
import { searchResultClasses } from "@library/features/search/searchResultsStyles";
import { TypeDiscussionsIcon } from "@library/icons/searchIcons";
import { ResultMeta } from "@library/result/ResultMeta";
import CollapseCommentsSearchMetaLoader from "@vanilla/addon-vanilla/search/CollapseCommentsSearchMetaLoader";
import { ICountResult } from "@library/search/searchTypes";
import { useDiscussion } from "@library/features/discussions/discussionHooks";
import { LoadStatus } from "@library/@types/api/core";
import { notEmpty } from "@vanilla/utils";
import ErrorMessages from "@library/forms/ErrorMessages";
import { useFallbackBackUrl } from "@library/routing/links/BackRoutingProvider";
import qs from "qs";
import { makeSearchUrl } from "@library/search/SearchPageRoute";
import { ListItem } from "@library/lists/ListItem";
import { IDiscussion } from "@dashboard/@types/api/discussion";

interface IProps {
    discussionID: IDiscussion["discussionID"];
    icon?: React.ReactNode;
}

export default function CollapseCommentSearchMeta(props: IProps) {
    const { icon = <TypeDiscussionsIcon />, discussionID } = props;
    const [forceLoader] = useState(false);

    const discussion = useDiscussion(discussionID);

    // Back link
    const backUrl = useMemo(() => {
        const { search } = window.location;
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

    const counts: ICountResult = {
        count: discussion.data.countComments ?? 0,
        labelCode: "comments",
    };

    const classes = searchResultClasses();

    return (
        <ListItem
            as="div"
            name={discussion.data.name}
            url={discussion.data.url}
            icon={icon}
            iconWrapperClass={classes.iconWrap}
            metas={<ResultMeta updateUser={discussion.data.insertUser} type={"discussion"} counts={[counts]} />}
        />
    );
}
