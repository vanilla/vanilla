/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { IComboBoxOption } from "@library/features/search/SearchBar";
import MultiUserInput from "@library/features/users/MultiUserInput";
import { inputBlockClasses } from "@library/forms/InputBlockStyles";
import InputTextBlock from "@library/forms/InputTextBlock";
import LazyDateRange from "@library/forms/LazyDateRange";
import { FilterFrame } from "@library/search/panels/FilterFrame";
import { useSearchForm } from "@library/search/SearchFormContext";
import { t } from "@vanilla/i18n";
import React from "react";
import InputBlock from "@library/forms/InputBlock";

interface IProps {}

/**
 * Implement search filter panel for all types
 */
export function FilterPanelAll(props: IProps) {
    const { form, updateForm, search } = useSearchForm<{
        startDate?: string;
        endDate?: string;
        startDateUpdated?: string;
        endDateUpdated?: string;
    }>();
    const classesInputBlock = inputBlockClasses();
    return (
        <FilterFrame title={t("Filter Results")} handleSubmit={search}>
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
        </FilterFrame>
    );
}
