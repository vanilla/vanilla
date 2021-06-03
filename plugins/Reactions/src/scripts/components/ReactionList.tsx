/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import React from "react";
import { BadgeListView } from "@library/badge/BadgeList.views";
import { IReaction } from "@Reactions/types/Reaction";
import { IGetUserReactionsParams } from "@Reactions/state/ReactionsActions";
import { useUserReactions } from "@Reactions/hooks/ReactionsHooks";
import { LoadStatus } from "@library/@types/api/core";
import Loader from "@library/loaders/Loader";
import { t } from "@library/utility/appUtils";
import { CoreErrorMessages } from "@library/errorPages/CoreErrorMessages";
interface IProps {
    apiParams: IGetUserReactionsParams;
    apiData?: IReaction[];
}

export function ReactionList(props: IProps) {
    const apiData = useUserReactions(props.apiParams, props.apiData);

    if ([LoadStatus.LOADING, LoadStatus.PENDING].includes(apiData.status)) {
        return <Loader />;
    }
    if (apiData.error) {
        return <CoreErrorMessages apiError={apiData.error} />;
    }

    if (!!apiData.data && apiData.data!.length === 0) {
        return <span>{t("NoReactionEarned", "Any minute now…")}</span>;
    }
    const reactions = apiData.data as IReaction[];
    return <BadgeListView items={reactions} keyID="tagID" />;
}
