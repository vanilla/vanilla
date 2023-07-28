/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { IComboBoxOption } from "@library/features/search/SearchBar";
import { inputBlockClasses } from "@library/forms/InputBlockStyles";
import InputTextBlock from "@library/forms/InputTextBlock";
import { FilterFrame } from "@library/search/panels/FilterFrame";
import { useSearchForm } from "@library/search/SearchContext";
import { getSiteSection, t } from "@library/utility/appUtils";
import React, { useEffect, useMemo } from "react";
import Checkbox from "@library/forms/Checkbox";
import CommunityCategoryInput from "@vanilla/addon-vanilla/forms/CommunityCategoryInput";
import { ICommunitySearchTypes } from "@vanilla/addon-vanilla/search/communitySearchTypes";
import MultiUserInput from "@library/features/users/MultiUserInput";
import CheckboxGroup from "@library/forms/CheckboxGroup";
import { CommunityPostTypeFilter } from "@vanilla/addon-vanilla/search/CommunityPostTypeFilter";
import { TagsInput } from "@library/features/tags/TagsInput";
import LazyDateRange from "@library/forms/LazyDateRange";
import { SEARCH_SCOPE_LOCAL, useSearchScope } from "@library/features/search/SearchScopeContext";

/**
 * Implement search filter panel for discussions
 */
export function SearchFilterPanelCommunity() {
    const { form, updateForm, search } = useSearchForm<ICommunitySearchTypes>();
    const { value, setValue } = useSearchScope();

    const classesInputBlock = inputBlockClasses();

    /**
     * Category and Scope are exclusive
     */
    const isFilteringCategories = useMemo(() => {
        return form?.categoryOptions && form.categoryOptions.length > 0;
    }, [form]);

    const isFilteringTags = useMemo(() => {
        return form?.tagsOptions && form.tagsOptions.length > 0;
    }, [form]);

    // If the scope is everywhere, the category or tag filters should be cleared
    useEffect(() => {
        if (value?.value !== SEARCH_SCOPE_LOCAL) {
            if (isFilteringCategories) {
                updateForm({ categoryOptions: [] });
            }
            if (isFilteringTags) {
                updateForm({ tagsOptions: [] });
            }
        }
    }, [value]);

    // If a category filter is selected, the scope must be local
    useEffect(() => {
        if (value?.value !== SEARCH_SCOPE_LOCAL && isFilteringCategories) {
            setValue && setValue(SEARCH_SCOPE_LOCAL);
        }
    }, [isFilteringCategories]);

    // If a tag filter is selected, the scope must be local
    useEffect(() => {
        if (value?.value !== SEARCH_SCOPE_LOCAL && isFilteringTags) {
            setValue && setValue(SEARCH_SCOPE_LOCAL);
        }
    }, [isFilteringTags]);

    return (
        <FilterFrame handleSubmit={search}>
            <InputTextBlock
                label={t("Title")}
                inputProps={{
                    onChange: (event: React.ChangeEvent<HTMLInputElement>) => {
                        const { value } = event.target;
                        updateForm({ name: value });
                    },
                    value: form.name || undefined,
                }}
            />
            <MultiUserInput
                className={classesInputBlock.root}
                onChange={(options: IComboBoxOption[]) => {
                    updateForm({ authors: options });
                }}
                value={form.authors ?? []}
            />
            <LazyDateRange
                label={t("Date Updated")}
                onStartChange={(date: string) => {
                    updateForm({ startDate: date });
                }}
                onEndChange={(date: string) => {
                    updateForm({ endDate: date });
                }}
                start={form.startDate}
                end={form.endDate}
            />
            <CommunityCategoryInput
                label={t("Category")}
                multiple
                onChange={(options) => {
                    updateForm({ categoryOptions: options });
                }}
                parentCategoryID={getSiteSection().attributes.categoryID ?? null}
                value={form.categoryOptions ?? []}
                labelNote={isFilteringCategories ? t("Searching categories on this community only") : undefined}
            />

            <CheckboxGroup tight={true}>
                <Checkbox
                    disabled={(form.categoryOptions?.length || 0) > 1}
                    label={t("Search Subcategories")}
                    onChange={(event: React.ChangeEvent<HTMLInputElement>) => {
                        updateForm({ includeChildCategories: event.target.checked || false });
                    }}
                    checked={form.includeChildCategories ?? false}
                    className={classesInputBlock.root}
                />
                <Checkbox
                    label={t("Search Followed Categories Only")}
                    onChange={(event: React.ChangeEvent<HTMLInputElement>) => {
                        updateForm({ followedCategories: event.target.checked || false });
                    }}
                    checked={form.followedCategories ?? false}
                    className={classesInputBlock.root}
                />
                <Checkbox
                    label={t("Search Archived Categories")}
                    onChange={(event: React.ChangeEvent<HTMLInputElement>) => {
                        updateForm({ includeArchivedCategories: event.target.checked || false });
                    }}
                    checked={form.includeArchivedCategories ?? false}
                    className={classesInputBlock.root}
                />
            </CheckboxGroup>

            <TagsInput
                label={t("Tags")}
                placeholder={t("Select...")}
                value={form.tagsOptions ?? []}
                onChange={(options: IComboBoxOption[]) => {
                    updateForm({ tagsOptions: options });
                }}
                labelNote={isFilteringTags ? t("Searching tags on this community only") : undefined}
            />

            <CommunityPostTypeFilter />
        </FilterFrame>
    );
}
