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
import { useSearchForm } from "@library/search/SearchFormContext";
import { t } from "@library/utility/appUtils";
import React from "react";
import Checkbox from "@library/forms/Checkbox";

import CommunityCategoryInput from "@vanilla/addon-vanilla/forms/CommunityCategoryInput";
import { ICommunitySearchTypes } from "@vanilla/addon-vanilla/search/communitySearchTypes";
import {PostTypeFilter} from "@library/search/panels/pieces/PostTypeFilter";
import MultiUserInput from "@library/features/users/MultiUserInput";
import classNames from "classnames";
import CheckboxGroup from "@library/forms/CheckboxGroup";

/**
 * Implement search filter panel for discussions
 */
export function SearchFilterPanelDiscussions() {
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
                    value: form.name,
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
                onChange={option => {
                    updateForm({ categoryOption: option });
                }}
                value={form.categoryOption}
            />
            <CheckboxGroup tight={true}>
                <Checkbox
                    label={t("Search Subcategories")}
                    onChange={(event: React.ChangeEvent<HTMLInputElement>) => {
                        updateForm({ includeChildCategories: event.target.checked || false });
                    }}
                    checked={form.includeChildCategories}
                    className={classesInputBlock.root}
                />
                <Checkbox
                    label={t("Search only followed Categories")}
                    onChange={(event: React.ChangeEvent<HTMLInputElement>) => {
                        updateForm({ followedCategories: event.target.checked || false });
                    }}
                    checked={form.followedCategories}
                    className={classesInputBlock.root}
                />
                <Checkbox
                    label={t("Search archived")}
                    onChange={(event: React.ChangeEvent<HTMLInputElement>) => {
                        updateForm({ includeArchivedCategories: event.target.checked || false });
                    }}
                    checked={form.includeArchivedCategories}
                    className={classesInputBlock.root}
                />
            </CheckboxGroup>

            {/* Do we have a tag input in React? */}

            {/* Hard coded temporarily */}
            <PostTypeFilter types={[{
                label: t("Discussions"),
            },{
                label: t("Questions"),
            },{
                label: t("Polls"),
            },{
                label: t("Ideas"),
            }
            ]}/>

        </FilterFrame>
    );
}
