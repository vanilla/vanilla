/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import SmartLink from "@library/routing/links/SmartLink";
import { contributionItemClasses } from "@library/contributionItems/ContributionItem.classes";
import Count from "@library/content/Count";
import { t } from "@library/utility/appUtils";
import { contributionItemVariables } from "@library/contributionItems/ContributionItem.variables";

export interface IContributionItem {
    name: string;
    url: string;
    photoUrl: string;
    count?: number;
}

export function ContributionItem(
    props: IContributionItem & { themingVariables: ReturnType<typeof contributionItemVariables> },
) {
    const { name, photoUrl, url, count, themingVariables } = props;
    const classes = contributionItemClasses(themingVariables);

    return (
        <SmartLink to={url} title={name} className={classes.link}>
            <div className={classes.imageAndCountWrapper}>
                <img alt={name} src={photoUrl} className={classes.image} />
                {themingVariables.count.display && (count ?? 0) > 0 && (
                    <Count label={name} count={count} className={classes.count} useFormatted={true} useMax={false} />
                )}
            </div>
            {themingVariables.name.display && <div className={classes.name}>{t(name)}</div>}
        </SmartLink>
    );
}
