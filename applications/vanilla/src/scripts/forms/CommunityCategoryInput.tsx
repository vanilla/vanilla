/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import apiv2 from "@library/apiv2";
import { t } from "@library/utility/appUtils";
import CategorySuggestionActions from "@vanilla/addon-vanilla/categories/CategorySuggestionActions";
import { IForumStoreState } from "@vanilla/addon-vanilla/redux/state";
import React, { useEffect, useState } from "react";
import { connect, useSelector } from "react-redux";
import { NoOptionsMessage } from "@library/forms/select/overwrites";
import { ILoadable, LoadStatus } from "@library/@types/api/core";
import { IComboBoxOption } from "@library/features/search/SearchBar";
import SelectOne from "@library/forms/select/SelectOne";
import { useReduxActions } from "@library/redux/ReduxActions";
import { CategoryDisplayAs, ICategory } from "@vanilla/addon-vanilla/categories/categoriesTypes";
import { Tokens } from "@library/forms/select/Tokens";

interface IProps {
    multiple?: boolean;
    onChange: (tokens: IComboBoxOption[]) => void;
    value: IComboBoxOption[];
    parentCategoryID?: number | null;
    displayAs?: CategoryDisplayAs;
    label: string | null;
    labelNote?: string;
    disabled?: boolean;
    className?: string;
    placeholder?: string;
    hideTitle?: boolean;
    maxHeight?: number;
}

/**
 * Form component for searching/selecting a category.
 */
export function CommunityCategoryInput(props: IProps) {
    const [query, setQuery] = useState<string>("");
    const [hasBeenFocused, setHasBeenFocused] = useState(false);

    const suggestions = useCategorySuggestions(query, props.parentCategoryID, hasBeenFocused);

    const setFocused = () => {
        setHasBeenFocused(true);
    };

    const { multiple } = props;
    let options: IComboBoxOption[] | undefined;
    if (suggestions.status === LoadStatus.SUCCESS && suggestions.data) {
        let data = suggestions.data;
        if (props.displayAs) {
            data = data.filter((suggestion) => suggestion.displayAs === props.displayAs);
        }
        options = data.map((suggestion) => {
            let parentLabel;
            const crumbLength = suggestion.breadcrumbs?.length ?? 0;
            if (crumbLength > 1) {
                parentLabel = suggestion.breadcrumbs?.[crumbLength - 2]?.name;
            }

            return {
                value: suggestion.categoryID,
                label: suggestion.name,
                data: {
                    parentLabel,
                },
            };
        });
    }

    const isLoading = suggestions.status === LoadStatus.LOADING;

    // TODO: Fix the AutoCompleteLookUp and use it here
    // https://higherlogic.atlassian.net/browse/VNLA-383

    if (multiple) {
        return (
            <Tokens
                onFocus={setFocused}
                {...props}
                placeholder={t("Search...")}
                isLoading={isLoading}
                onInputChange={setQuery}
                label={props.label ?? t("Community Category")}
                showIndicator
                options={options}
                maxHeight={props.maxHeight}
            />
        );
    }
    return (
        <SelectOne
            onFocus={setFocused}
            {...props}
            placeholder={t("Search...")}
            onInputChange={setQuery}
            isLoading={isLoading}
            onChange={(option) => {
                if (props.onChange) props.onChange([option]);
            }}
            options={options}
            label={props.label ?? t("Community Category")}
            value={(props.value ?? [])[0]}
            maxHeight={props.maxHeight}
        />
    );
}

export function useCategorySuggestions(
    query: string,
    parentCategoryID?: number | null,
    forceSearch?: boolean,
): ILoadable<ICategory[]> {
    const actions = useReduxActions(CategorySuggestionActions);
    const suggestions = useSelector((state: IForumStoreState) => {
        return state.forum.categories.suggestionsByQuery[query] ?? { status: LoadStatus.PENDING };
    });

    useEffect(() => {
        if (!query && !forceSearch) {
            // Don't do anything with empty queries.
            return;
        }

        actions.loadCategories(query, parentCategoryID);
    }, [query, parentCategoryID, forceSearch]);

    return suggestions;
}

export default CommunityCategoryInput;
