/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { useSearchForm } from "@library/search/SearchContext";
import { inputBlockClasses } from "@vanilla/library/src/scripts/forms/InputBlockStyles";
import { dateRangeClasses } from "@vanilla/library/src/scripts/forms/dateRangeStyles";
import { FilterFrame } from "@vanilla/library/src/scripts/search/panels/FilterFrame";
import { t } from "@vanilla/library/src/scripts/utility/appUtils";
import { IComboBoxOption } from "@vanilla/library/src/scripts/features/search/SearchBar";
import MultiUserInput from "@vanilla/library/src/scripts/features/users/MultiUserInput";
import DateRange from "@vanilla/library/src/scripts/forms/DateRange";

interface IProps {}

/**
 * Implement search filter panel for all types
 */
export function SearchFilterPanelComments(props: IProps) {
    const { form, updateForm, search } = useSearchForm();
    const classesInputBlock = inputBlockClasses();
    const classesDateRange = dateRangeClasses();
    return (
        <FilterFrame title={t("Filter Results")} handleSubmit={search}>
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
        </FilterFrame>
    );
}
