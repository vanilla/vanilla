/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { t } from "@vanilla/i18n/src";
import { FilterFrame } from "@library/search/panels/FilterFrame";
import InputTextBlock from "@library/forms/InputTextBlock";
import { useSearchFilters } from "@library/contexts/SearchFilterContext";
import MultiUserInput from "@library/features/users/MultiUserInput";
import { IComboBoxOption } from "@library/features/search/SearchBar";
import DateRange from "@library/forms/DateRange";
import { inputBlockClasses } from "@library/forms/InputBlockStyles";
import { dateRangeClasses } from "@library/forms/dateRangeStyles";
import { useSearchPageData } from "@knowledge/modules/search/unifySearchPageReducer";
import { useUnifySearchPageActions } from "@knowledge/modules/search/UnifySearchPageActions";

interface IProps {}

/**
 * Implement search filter panel for all types
 */
export function FilterPanelAll(props: IProps) {
    const { form } = useSearchPageData();
    const { updateForm, unifySearch } = useUnifySearchPageActions();
    const classesInputBlock = inputBlockClasses();
    const classesDateRange = dateRangeClasses();
    return (
        <FilterFrame title={t("Filter Results")} handleSubmit={unifySearch}>
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
