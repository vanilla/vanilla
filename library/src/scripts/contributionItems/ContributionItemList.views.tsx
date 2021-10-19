/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { ContributionItem, IContributionItem } from "@library/contributionItems/ContributionItem";
import { contributionItemListClasses } from "@library/contributionItems/ContributionItem.classes";
import { contributionItemVariables } from "@library/contributionItems/ContributionItem.variables";

export interface IProps {
    items: IContributionItem[];
    keyID?: string;
    themingVariables: ReturnType<typeof contributionItemVariables>;
}

export function ContributionItemList(props: IProps) {
    const { items, themingVariables } = props;

    const classes = contributionItemListClasses(themingVariables);

    return (
        <ul className={classes.list}>
            {items.map((item, index) => (
                <li key={props?.keyID && item[props.keyID] ? item[props.keyID] : index} className={classes.listItem}>
                    <ContributionItem {...item} themingVariables={themingVariables} />
                </li>
            ))}
        </ul>
    );
}
