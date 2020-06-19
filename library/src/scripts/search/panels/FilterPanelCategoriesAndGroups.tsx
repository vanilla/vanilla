/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { FilterFrame } from "@library/search/panels/FilterFrame";
import InputTextBlock from "@library/forms/InputTextBlock";
import { t } from "@library/utility/appUtils";
import { SearchDomain, useSearchPageData } from "@knowledge/modules/search/searchPageReducer";
import { useSearchPageActions } from "@knowledge/modules/search/SearchPageActions";
import { inputBlockClasses } from "@library/forms/InputBlockStyles";
import DateRange from "@library/forms/DateRange";
import { dateRangeClasses } from "@library/forms/dateRangeStyles";
import CheckboxGroup from "@library/forms/CheckboxGroup";
import Checkbox from "@library/forms/Checkbox";

interface IProps {
    handleSubmit: (data) => void;
}
/**
 * Implement search filter panel for categories and groups
 */
export function SearchFilterPanelCategoriesAndGroups(props) {
    const { handleSubmit } = props;
    const { form } = useSearchPageData();
    const { updateForm } = useSearchPageActions();
    const classesDateRange = dateRangeClasses();
    return (
        <FilterFrame handleSubmit={handleSubmit}>
            <InputTextBlock
                label={t("Title")}
                inputProps={{
                    onChange: (event: React.ChangeEvent<HTMLInputElement>) => {
                        const { value } = event.target;
                        updateForm({ title: value });
                    },
                    value: form.title,
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
