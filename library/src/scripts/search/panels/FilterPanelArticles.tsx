/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { FilterFrame } from "@library/search/panels/FilterFrame";
import { t } from "@vanilla/i18n/src";
import { SearchDomain, useSearchPageData } from "@knowledge/modules/search/searchPageReducer";
import { useSearchPageActions } from "@knowledge/modules/search/SearchPageActions";
import { inputBlockClasses } from "@library/forms/InputBlockStyles";
import { dateRangeClasses } from "@library/forms/dateRangeStyles";
import InputTextBlock from "@library/forms/InputTextBlock";
import MultiUserInput from "@library/features/users/MultiUserInput";
import { IComboBoxOption } from "@library/features/search/SearchBar";
import DateRange from "@library/forms/DateRange";
import KnowledgeBaseInput from "@knowledge/knowledge-bases/KnowledgeBaseInput";
import { useSearchFilters } from "@library/contexts/SearchFilterContext";
import Permission from "@library/features/users/Permission";
import Checkbox from "@library/forms/Checkbox";

interface IProps {
    handleSubmit: (data) => void;
}
/**
 * Implement search filter panel for articles
 */
export function SearchFilterPanelArticles(props) {
    const { handleSubmit } = props;
    const { form, results } = useSearchPageData();
    const { updateForm, search } = useSearchPageActions();
    const { getFilterComponentsForDomain } = useSearchFilters();

    const classesInputBlock = inputBlockClasses();
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
            {getFilterComponentsForDomain(form.domain)}
            {form.domain === SearchDomain.ARTICLES && (
                <KnowledgeBaseInput
                    className={classesInputBlock.root}
                    onChange={(option: IComboBoxOption) => {
                        updateForm({ kb: option });
                    }}
                    value={form.kb}
                />
            )}
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
