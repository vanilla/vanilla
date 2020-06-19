/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { IComboBoxOption } from "@library/features/search/SearchBar";
import MultiUserInput from "@library/features/users/MultiUserInput";
import Permission from "@library/features/users/Permission";
import Checkbox from "@library/forms/Checkbox";
import DateRange from "@library/forms/DateRange";
import { dateRangeClasses } from "@library/forms/dateRangeStyles";
import { inputBlockClasses } from "@library/forms/InputBlockStyles";
import InputTextBlock from "@library/forms/InputTextBlock";
import { FilterFrame } from "@library/search/panels/FilterFrame";
import { useSearchForm } from "@library/search/SearchFormContext";
import { t } from "@vanilla/i18n";
import React from "react";
import KnowledgeBaseInput from "@knowledge/knowledge-bases/KnowledgeBaseInput";
import { IKnowledgeSearchTypes } from "@knowledge/search/knowledgeSearchTypes";

/**
 * Implement search filter panel for articles
 */
export function SearchFilterPanelArticles() {
    const { form, updateForm, search, getFilterComponentsForDomain } = useSearchForm<IKnowledgeSearchTypes>();

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
            {getFilterComponentsForDomain(form.domain)}
            <KnowledgeBaseInput
                className={classesInputBlock.root}
                onChange={(option: IComboBoxOption) => {
                    updateForm({ knowledgeBaseOption: option });
                }}
                value={form.knowledgeBaseOption}
            />
            <Permission permission="articles.add">
                <Checkbox
                    label={t("Deleted Articles")}
                    onChange={(event: React.ChangeEvent<HTMLInputElement>) => {
                        updateForm({ includeDeleted: event.target.checked || false });
                    }}
                    checked={form.includeDeleted}
                    className={classesInputBlock.root}
                />
            </Permission>
        </FilterFrame>
    );
}
