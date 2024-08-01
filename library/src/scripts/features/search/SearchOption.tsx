/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { SelectOption } from "@library/forms/select/overwrites";
import { ICrumb } from "@library/navigation/Breadcrumbs";
import SmartLink from "@library/routing/links/SmartLink";
import BreadCrumbString from "@library/navigation/BreadCrumbString";
import { IComboBoxOption } from "@library/features/search/SearchBar";
import { metasClasses } from "@library/metas/Metas.styles";
import classNames from "classnames";
import { OptionProps } from "react-select/lib/components/Option";
import { PlacesSearchListingItem } from "@library/search/PlacesSearchListingContainer";
import { searchBarClasses } from "@library/features/search/SearchBar.styles";
import DateTime from "@library/content/DateTime";
import { MetaIcon, MetaTag } from "@library/metas/Metas";

export interface ISearchOptionData {
    crumbs: ICrumb[];
    name: string;
    dateUpdated?: string;
    labels?: string[];
    url: string;
    type?: string;
    isFirst?: boolean;
    isForeign?: boolean;
}

interface IProps extends OptionProps<ISearchOptionData> {
    data: IComboBoxOption<ISearchOptionData>;
}

/**
 */
export default function SearchOption(props: IProps) {
    const { innerProps, isSelected, isFocused } = props;
    const data = props.data.data;
    const isForeign = data?.isForeign;

    if (data) {
        const label = props.label;
        const m = label.match(/places___(.+)___/);
        let placesListingLabel;
        if (m) {
            placesListingLabel = m[1];
        }

        const { dateUpdated, crumbs, url, labels } = data;
        const hasLocationData = crumbs && crumbs.length > 0;
        const classesMetas = metasClasses();

        return m ? (
            <>
                {data.isFirst && <div className={searchBarClasses().firstItemBorderTop}></div>}
                <PlacesSearchListingItem
                    className={classNames("suggestedTextInput-item", { [`${classesMetas.inlineBlock}`]: true })}
                    embedLinkClassName={classNames("suggestedTextInput-option", {
                        isSelected,
                        isFocused,
                    })}
                    name={placesListingLabel}
                    type={data.type}
                    url={data.url}
                />
            </>
        ) : (
            <li className="suggestedTextInput-item">
                <SmartLink
                    {...(innerProps as any)}
                    // We want to use the SmarkLink clickHandler, not the innerProps one from the SearchBar.
                    // The innerProps click handler will trigger a search event (goes to search page).
                    // The SmartLink will navigate to the result itself.
                    onClick={undefined}
                    to={url}
                    title={label}
                    aria-label={label}
                    className={classNames("suggestedTextInput-option", {
                        isSelected,
                        isFocused,
                    })}
                >
                    <span className="suggestedTextInput-head">
                        <span className="suggestedTextInput-title">{props.children}</span>
                    </span>
                    <span className="suggestedTextInput-main">
                        <span className={classNames("isFlexed", classesMetas.root)}>
                            {labels && labels.map((label) => <MetaTag key={label}>{label}</MetaTag>)}
                            {dateUpdated && (
                                <span className={classesMetas.meta}>
                                    <DateTime className={classesMetas.meta} timestamp={dateUpdated} />
                                </span>
                            )}
                            {hasLocationData && <BreadCrumbString className={classesMetas.meta} crumbs={crumbs} />}
                            {isForeign && <MetaIcon icon="meta-external-compact" />}
                        </span>
                    </span>
                </SmartLink>
            </li>
        );
    } else {
        return <SelectOption {...props} value={props.data.value} />;
    }
}
