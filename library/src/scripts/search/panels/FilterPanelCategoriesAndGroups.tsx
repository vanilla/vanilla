/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import Checkbox from "@library/forms/Checkbox";
import CheckboxGroup from "@library/forms/CheckboxGroup";
import DateRange from "@library/forms/DateRange";
import { dateRangeClasses } from "@library/forms/dateRangeStyles";
import InputTextBlock from "@library/forms/InputTextBlock";
import { FilterFrame } from "@library/search/panels/FilterFrame";
import { useSearchForm } from "@library/search/SearchFormContext";
import { t } from "@library/utility/appUtils";
import React from "react";

interface IProps {}
/**
 * Implement search filter panel for categories and groups
 */
export function SearchFilterPanelCategoriesAndGroups(props: IProps) {
    const { form, updateForm, search } = useSearchForm();

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

            <CheckboxGroup label={"What to Search"}>
                <Checkbox label="Categories" />
                <Checkbox label="Groups" />
            </CheckboxGroup>
        </FilterFrame>
    );
}
