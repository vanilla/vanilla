/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import React from "react";
import { ContributionItemList } from "@library/contributionItems/ContributionItemList.views";
import { IReaction } from "@Reactions/types/Reaction";
import { IGetUserReactionsParams } from "@Reactions/state/ReactionsActions";
import { useUserReactions } from "@Reactions/hooks/ReactionsHooks";
import { LoadStatus } from "@library/@types/api/core";
import Loader from "@library/loaders/Loader";
import { t } from "@library/utility/appUtils";
import { CoreErrorMessages } from "@library/errorPages/CoreErrorMessages";
import { reactionVariables } from "@Reactions/variables/Reaction.variables";
interface IProps {
    apiParams: IGetUserReactionsParams;
    apiData?: IReaction[];
    maximumLength?: number;
}

export function ReactionList(props: IProps) {
    const apiData = useUserReactions(props.apiParams, props.apiData);
    const reactionVars = reactionVariables();

    if ([LoadStatus.LOADING, LoadStatus.PENDING].includes(apiData.status)) {
        return <Loader />;
    }
    if (!apiData.data || apiData.status === LoadStatus.ERROR || apiData.error) {
        return <CoreErrorMessages apiError={apiData.error} />;
    }

    if (!!apiData.data && apiData.data!.length === 0) {
        return <span>{t("NoReactionEarned", "Any minute now…")}</span>;
    }

    return (
        <ContributionItemList
            items={apiData.data.slice(0, props.maximumLength)}
            keyID="tagID"
            themingVariables={reactionVars}
        />
    );
}
