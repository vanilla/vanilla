/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { ContributionItem, IContributionItem } from "@library/contributionItems/ContributionItem";
import { contributionItemListClasses } from "@library/contributionItems/ContributionItem.classes";
import { contributionItemVariables } from "@library/contributionItems/ContributionItem.variables";
import { StackedList } from "@library/stackedList/StackedList";

export interface IProps {
    items: IContributionItem[];
    themingVariables: ReturnType<typeof contributionItemVariables>;
    keyID?: string;
    stacked?: boolean;
    maximumLength?: number;
    openModal?(): void;
}

export function ContributionItemList(props: IProps) {
    const { items, themingVariables } = props;

    const classes = contributionItemListClasses(themingVariables);

    return props.stacked ? (
        <StackedList
            themingVariables={themingVariables.stackedList}
            ItemComponent={(item) => (
                <ContributionItem
                    {...item}
                    themingVariables={{
                        ...themingVariables,
                        name: { ...themingVariables.name, display: false },
                        count: {
                            ...themingVariables.count,
                            display: false,
                        },
                    }}
                />
            )}
            data={props.items}
            maxCount={props.maximumLength}
            openModal={props.openModal}
        />
    ) : (
        <ul className={classes.list}>
            {items.slice(0, props.maximumLength).map((item, index) => (
                <li key={props?.keyID && item[props.keyID] ? item[props.keyID] : index} className={classes.listItem}>
                    <ContributionItem {...item} themingVariables={themingVariables} />
                </li>
            ))}
        </ul>
    );
}
