/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { IComboBoxOption } from "@library/features/search/SearchBar";
import MultiUserInput from "@library/features/users/MultiUserInput";
import DateRange from "@library/forms/DateRange";
import { dateRangeClasses } from "@library/forms/dateRangeStyles";
import { inputBlockClasses } from "@library/forms/InputBlockStyles";
import InputTextBlock from "@library/forms/InputTextBlock";
import { FilterFrame } from "@library/search/panels/FilterFrame";
import { useSearchForm } from "@library/search/SearchContext";
import { t } from "@vanilla/i18n";
import React from "react";

interface IProps {}

/**
 * Implement search filter panel for all types
 */
export function FilterPanelAll(props: IProps) {
    const { form, updateForm, search } = useSearchForm();
    const classesInputBlock = inputBlockClasses();
    const classesDateRange = dateRangeClasses();
    return (
        <FilterFrame title={t("Filter Results")} handleSubmit={search}>
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
        </FilterFrame>
    );
}
