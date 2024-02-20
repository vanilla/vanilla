/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { FilterFrame } from "@library/search/panels/FilterFrame";
import { useSearchForm } from "@library/search/SearchFormContext";
import InputTextBlock from "@library/forms/InputTextBlock";
import { t } from "@library/utility/appUtils";
import { PlacesSearchTypeFilter } from "@dashboard/components/panels/PlacesSearchTypeFilter";
import { IPlacesSearchTypes } from "@dashboard/components/panels/placesSearchTypes";

export default function PlacesSearchFilterPanel() {
    const { form, updateForm, search } = useSearchForm<IPlacesSearchTypes>();

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

            <InputTextBlock
                label={t("Description")}
                inputProps={{
                    onChange: (event: React.ChangeEvent<HTMLInputElement>) => {
                        const { value } = event.target;
                        updateForm({ description: value });
                    },
                    value: form.description ?? "",
                }}
            />
            <PlacesSearchTypeFilter />
        </FilterFrame>
    );
}
