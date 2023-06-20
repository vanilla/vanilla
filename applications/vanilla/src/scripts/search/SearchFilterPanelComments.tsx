/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { useSearchForm } from "@library/search/SearchContext";
import { inputBlockClasses } from "@library/forms/InputBlockStyles";
import { FilterFrame } from "@library/search/panels/FilterFrame";
import { t } from "@library/utility/appUtils";
import { IComboBoxOption } from "@library/features/search/SearchBar";
import MultiUserInput from "@library/features/users/MultiUserInput";
import LazyDateRange from "@library/forms/LazyDateRange";

interface IProps {}

export function SearchFilterPanelComments(props: IProps) {
    const { form, updateForm, search } = useSearchForm();
    const classesInputBlock = inputBlockClasses();

    return (
        <FilterFrame title={t("Filter Results")} handleSubmit={search}>
            <MultiUserInput
                className={classesInputBlock.root}
                onChange={(options: IComboBoxOption[]) => {
                    updateForm({ authors: options });
                }}
                value={form.authors ?? []}
            />
            <LazyDateRange
                label={t("Date Updated")}
                onStartChange={(date: string) => {
                    updateForm({ startDate: date });
                }}
                onEndChange={(date: string) => {
                    updateForm({ endDate: date });
                }}
                start={form.startDate}
                end={form.endDate}
            />
        </FilterFrame>
    );
}
