/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { FilterFrame } from "@vanilla/library/src/scripts/search/panels/FilterFrame";
import { useSearchForm } from "@library/search/SearchContext";
import InputTextBlock from "@vanilla/library/src/scripts/forms/InputTextBlock";
import { t } from "@vanilla/library/src/scripts/utility/appUtils";
import { PlacesSearchTypeFilter } from "@dashboard/components/panels/PlacesSearchTypeFilter";

interface IProps {}

export default function PlacesSearchFilterPanel(props: IProps) {
    const { form, updateForm, search } = useSearchForm();

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

            <InputTextBlock
                label={t("Description")}
                inputProps={{
                    onChange: (event: React.ChangeEvent<HTMLInputElement>) => {
                        const { value } = event.target;
                        updateForm({ description: value });
                    },
                    value: form.description || undefined,
                }}
            />
            <PlacesSearchTypeFilter />
        </FilterFrame>
    );
}
