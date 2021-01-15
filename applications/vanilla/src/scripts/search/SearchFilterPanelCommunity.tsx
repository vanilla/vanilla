/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { IComboBoxOption } from "@library/features/search/SearchBar";
import DateRange from "@library/forms/DateRange";
import { dateRangeClasses } from "@library/forms/dateRangeStyles";
import { inputBlockClasses } from "@library/forms/InputBlockStyles";
import InputTextBlock from "@library/forms/InputTextBlock";
import { FilterFrame } from "@library/search/panels/FilterFrame";
import { useSearchForm } from "@library/search/SearchContext";
import { t } from "@library/utility/appUtils";
import React from "react";
import Checkbox from "@library/forms/Checkbox";
import CommunityCategoryInput from "@vanilla/addon-vanilla/forms/CommunityCategoryInput";
import { ICommunitySearchTypes } from "@vanilla/addon-vanilla/search/communitySearchTypes";
import MultiUserInput from "@library/features/users/MultiUserInput";
import CheckboxGroup from "@library/forms/CheckboxGroup";
import { CommunityPostTypeFilter } from "@vanilla/addon-vanilla/search/CommunityPostTypeFilter";
import { TagsInput } from "@library/features/tags/TagsInput";

/**
 * Implement search filter panel for discussions
 */
export function SearchFilterPanelCommunity() {
    const { form, updateForm, search } = useSearchForm<ICommunitySearchTypes>();

    const classesInputBlock = inputBlockClasses();
    const classesDateRange = dateRangeClasses();

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
            <DateRange
                onStartChange={(date: string) => {
                    updateForm({ startDate: date });
                }}
                onEndChange={(date: string) => {
                    updateForm({ endDate: date });
                }}
                start={form.startDate}
                end={form.endDate}
                className={classesDateRange.root}
            />
            <CommunityCategoryInput
                label={t("Category")}
                multiple
                onChange={(options) => {
                    updateForm({ categoryOptions: options });
                }}
                value={form.categoryOptions ?? []}
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
                value={form.tagsOptions ?? []}
                onChange={(options: IComboBoxOption[]) => {
                    updateForm({ tagsOptions: options });
                }}
            />

            <CommunityPostTypeFilter />
        </FilterFrame>
    );
}
