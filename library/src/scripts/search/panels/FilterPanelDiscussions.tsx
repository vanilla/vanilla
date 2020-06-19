/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { FilterFrame } from "@library/search/panels/FilterFrame";
import { useSearchFilters } from "@library/contexts/SearchFilterContext";
import DateRange from "@library/forms/DateRange";
import InputTextBlock from "@library/forms/InputTextBlock";
import { t } from "@library/utility/appUtils";
import MultiUserInput from "@library/features/users/MultiUserInput";
import { IComboBoxOption } from "@library/features/search/SearchBar";
import { inputBlockClasses } from "@library/forms/InputBlockStyles";
import { dateRangeClasses } from "@library/forms/dateRangeStyles";
import { useUnifySearchPageActions } from "@knowledge/modules/search/UnifySearchPageActions";
import { useSearchPageData } from "@knowledge/modules/search/unifySearchPageReducer";

/**
 * Implement search filter panel for discussions
 */
export function SearchFilterPanelDiscussions() {
    const { form } = useSearchPageData();
    const { updateForm, unifySearch } = useUnifySearchPageActions();
    const classesInputBlock = inputBlockClasses();
    const classesDateRange = dateRangeClasses();

    return (
        <FilterFrame handleSubmit={unifySearch}>
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
            <MultiUserInput
                className={classesInputBlock.root}
                onChange={(options: IComboBoxOption[]) => {
                    updateForm({ authors: options });
                }}
                value={form.authors}
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
