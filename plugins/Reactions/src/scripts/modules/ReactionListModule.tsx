/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import React from "react";
import { HomeWidgetContainer } from "@library/homeWidget/HomeWidgetContainer";
import { t } from "@library/utility/appUtils";
import { ReactionList } from "../components/ReactionList";

interface IProps extends React.ComponentProps<typeof ReactionList> {
    title?: string;
    description?: string;
    subtitle?: string;
}

export function ReactionListModule(props: IProps) {
    const { title = t("Reactions"), description, subtitle } = props;

    return (
        <HomeWidgetContainer
            title={title}
            description={description}
            subtitle={subtitle}
            options={{
                isGrid: false,
                viewAll: undefined,
            }}
        >
            <ReactionList apiData={props.apiData} apiParams={props.apiParams} />
        </HomeWidgetContainer>
    );
}
