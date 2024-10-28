/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { useSearchForm } from "@library/search/SearchFormContext";
import { inputBlockClasses } from "@library/forms/InputBlockStyles";
import { FilterFrame } from "@library/search/panels/FilterFrame";
import { t } from "@library/utility/appUtils";
import { IComboBoxOption } from "@library/features/search/SearchBar";
import MultiUserInput from "@library/features/users/MultiUserInput";
import LazyDateRange from "@library/forms/LazyDateRange";
import InputBlock from "@library/forms/InputBlock";
import { IDiscussionSearchTypes } from "./discussionSearchTypes";

interface IProps {}

export function SearchFilterPanelComments(props: IProps) {
    const { form, updateForm, search } = useSearchForm<IDiscussionSearchTypes>();
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
