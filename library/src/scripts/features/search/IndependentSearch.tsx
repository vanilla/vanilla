/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useState, useRef, useMemo, useCallback } from "react";
import SearchOption from "@library/features/search/SearchOption";
import { t } from "@library/utility/appUtils";
import { IWithSearchProps, withSearch } from "@library/contexts/SearchContext";
import { ButtonTypes } from "@library/forms/buttonStyles";
import SearchBar from "@library/features/search/SearchBar";
import { uniqueIDFromPrefix } from "@library/utility/idUtils";
import { searchClasses } from "@library/features/search/searchStyles";
import { searchBarClasses } from "@library/features/search/searchBarStyles";
import { RouteComponentProps, withRouter } from "react-router";
import classNames from "classnames";
import { visibility } from "@library/styles/styleHelpers";

interface IProps extends IWithSearchProps, RouteComponentProps<{}> {
    className?: string;
    placeholder?: string;
    buttonClass?: string;
    inputClass?: string;
    iconClass?: string;
    buttonContentClassName?: string;
    buttonLoaderClassName?: string;
    cancelContentClassName?: string;
    contentClass?: string;
    valueContainerClasses?: string;
    hideSearchButton?: boolean;
    isLarge?: boolean;
    buttonBaseClass?: ButtonTypes;
}

interface IState {
    query: string;
    showingSuggestions: boolean;
}

/**
 * Implements independent search component. All wired up, just drop it where you need it.
 */
export function IndependentSearch(props: IProps) {
    const id = useMemo(() => uniqueIDFromPrefix("search"), []);
    const resultsRef = useRef<HTMLDivElement>(null);
    const [query, setQuery] = useState("");

    const handleSubmit = useCallback(() => {
        props.history.push(props.searchOptionProvider.makeSearchUrl(query));
    }, [props.searchOptionProvider, props.history, query]);

    const handleSearchChange = useCallback(
        (newQuery: string) => {
            setQuery(newQuery);
        },
        [setQuery],
    );

    const classes = searchClasses();
    const classesSearchBar = searchBarClasses();
    return (
        <div className={classNames(classes.root, props.className)}>
            <SearchBar
                id={id}
                placeholder={props.placeholder}
                optionComponent={SearchOption}
                noHeading={true}
                title={t("Search")}
                value={query}
                onChange={handleSearchChange}
                onSearch={handleSubmit}
                loadOptions={props.searchOptionProvider.autocomplete}
                triggerSearchOnClear={false}
                resultsRef={resultsRef}
                buttonClassName={props.buttonClass}
                buttonBaseClass={props.buttonBaseClass}
                className={classes.root}
                isBigInput={props.isLarge}
                buttonLoaderClassName={props.buttonLoaderClassName}
                hideSearchButton={props.hideSearchButton}
                contentClass={props.contentClass}
                valueContainerClasses={props.valueContainerClasses}
            />
            <div
                ref={resultsRef}
                className={classNames("search-results", {
                    [classesSearchBar.results]: !!query,
                    [classesSearchBar.resultsAsModal]: !!query,
                })}
            />
        </div>
    );
}

export default withSearch(withRouter(IndependentSearch));
