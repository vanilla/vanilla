/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import React from "react";
import { IReaction } from "@Reactions/types/Reaction";
import { ContributionItemList } from "@library/contributionItems/ContributionItemList.views";
import { ILoadable, LoadStatus } from "@library/@types/api/core";
import Loader from "@library/loaders/Loader";
import { t } from "@library/utility/appUtils";
import { CoreErrorMessages } from "@library/errorPages/CoreErrorMessages";
import { reactionsVariables } from "@Reactions/variables/Reactions.variables";
interface IReactionListProps extends ILoadable<IReaction[]> {
    maximumLength?: number;
    openModal?(): void;
    stacked?: boolean;
}

export function ReactionList(props: IReactionListProps) {
    const reactionVars = reactionsVariables();

    if ([LoadStatus.LOADING, LoadStatus.PENDING].includes(props.status)) {
        return <Loader small minimumTime={100} />;
    }
    if (!props.data || props.status === LoadStatus.ERROR || props.error) {
        return <CoreErrorMessages apiError={props.error} />;
    }

    if (!!props.data && props.data!.length === 0) {
        return (
            <span style={props.stacked ? { lineHeight: `${reactionVars.stackedList.sizing.width}px` } : {}}>
                {t("No reactions yet.")}
            </span>
        );
    }

    return (
        <ContributionItemList
            keyID="tagID"
            themingVariables={reactionVars}
            items={props.data!}
            maximumLength={props.maximumLength}
            stacked={props.stacked}
            openModal={props.openModal}
        />
    );
}
