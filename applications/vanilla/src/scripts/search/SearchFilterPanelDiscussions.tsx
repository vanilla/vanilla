/**
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { IComboBoxOption } from "@library/features/search/SearchBar";
import { SEARCH_SCOPE_LOCAL, useSearchScope } from "@library/features/search/SearchScopeContext";
import { TagsInput } from "@library/features/tags/TagsInput";
import MultiUserInput from "@library/features/users/MultiUserInput";
import Checkbox from "@library/forms/Checkbox";
import CheckboxGroup from "@library/forms/CheckboxGroup";
import InputBlock from "@library/forms/InputBlock";
import { inputBlockClasses } from "@library/forms/InputBlockStyles";
import InputTextBlock from "@library/forms/InputTextBlock";
import LazyDateRange from "@library/forms/LazyDateRange";
import { FilterFrame } from "@library/search/panels/FilterFrame";
import { useSearchForm } from "@library/search/SearchFormContext";
import { t } from "@library/utility/appUtils";
import { CommunityPostTypeFilter } from "@vanilla/addon-vanilla/search/CommunityPostTypeFilter";
import { IDiscussionSearchTypes } from "@vanilla/addon-vanilla/search/discussionSearchTypes";
import React, { useEffect, useMemo } from "react";
import { CategoryDropdown } from "@library/forms/nestedSelect/presets/CategoryDropdown";

/**
 * Implement search filter panel for discussions
 */
export function SearchFilterPanelDiscussions() {
    const { form, updateForm, search } = useSearchForm<IDiscussionSearchTypes>();
    const { value: scope, setValue } = useSearchScope();

    const classesInputBlock = inputBlockClasses();

    /**
     * Category and Scope are exclusive
     */
    const isFilteringCategories = useMemo(() => {
        return form?.categoryIDs && form.categoryIDs.length > 0;
    }, [form]);

    const isFilteringTags = useMemo(() => {
        return form?.tagsOptions && form.tagsOptions.length > 0;
    }, [form]);

    // If the scope is changed to a value other than 'local' the category or tag filters should be cleared
    useEffect(() => {
        if (scope && scope.value !== SEARCH_SCOPE_LOCAL) {
            updateForm({
                ...(isFilteringCategories ? { categoryIDs: [] } : {}),
                ...(isFilteringTags ? { tagsOptions: [] } : {}),
            });
        }
    }, [scope]);

    // If a tag filter or a category filter is applied, the scope should be set to 'local'.
    useEffect(() => {
        if (scope && scope.value !== SEARCH_SCOPE_LOCAL && (isFilteringTags || isFilteringCategories)) {
            setValue && setValue(SEARCH_SCOPE_LOCAL);
        }
    }, [isFilteringTags, isFilteringCategories]);

    return (
        <FilterFrame handleSubmit={search}>
            <InputTextBlock
                label={t("Title")}
                inputProps={{
                    onChange: (event: React.ChangeEvent<HTMLInputElement>) => {
                        const { value } = event.target;
                        updateForm({ name: value });
                    },
                    value: form.name ?? "",
                }}
            />
            <MultiUserInput
                className={classesInputBlock.root}
                onChange={(options: IComboBoxOption[]) => {
                    updateForm({ authors: options });
                }}
                value={form.authors ?? []}
            />
            <InputBlock legend={t("Date Created")}>
                <LazyDateRange
                    onStartChange={(date: string) => {
                        updateForm({ startDate: date });
                    }}
                    onEndChange={(date: string) => {
                        updateForm({ endDate: date });
                    }}
                    start={form.startDate}
                    end={form.endDate}
                />
            </InputBlock>
            <InputBlock legend={t("Date Updated")}>
                <LazyDateRange
                    onStartChange={(date: string) => {
                        updateForm({ startDateUpdated: date });
                    }}
                    onEndChange={(date: string) => {
                        updateForm({ endDateUpdated: date });
                    }}
                    start={form.startDateUpdated}
                    end={form.endDateUpdated}
                />
            </InputBlock>

            <CategoryDropdown
                multiple
                isClearable
                onChange={(categoryIDs: number[]) => {
                    updateForm({ categoryIDs });
                }}
                value={form.categoryIDs ?? []}
                label={t("Category")}
                labelNote={
                    scope && isFilteringCategories ? t("Searching categories on this community only") : undefined
                }
            />

            <CheckboxGroup tight={true}>
                <Checkbox
                    disabled={(form.categoryIDs?.length || 0) > 1}
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
                labelNote={scope && isFilteringTags ? t("Searching tags on this community only") : undefined}
            />

            <Checkbox
                label={t("Match All Tags")}
                onChange={(event: React.ChangeEvent<HTMLInputElement>) => {
                    updateForm({ tagOperator: event.target.checked ? "and" : "or" });
                }}
                checked={form.tagOperator === "and"}
                className={classesInputBlock.root}
            />

            <CommunityPostTypeFilter />
        </FilterFrame>
    );
}
