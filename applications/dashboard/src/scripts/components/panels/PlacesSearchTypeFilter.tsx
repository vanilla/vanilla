/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import CheckboxGroup from "@library/forms/CheckboxGroup";
import Checkbox from "@library/forms/Checkbox";
import { useSearchForm } from "@library/search/SearchFormContext";
import flatten from "lodash/flatten";
import { onReady, t } from "@library/utility/appUtils";
import { IPlacesSearchTypes } from "@dashboard/components/panels/placesSearchTypes";

interface IProps {}

//  https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/Set

function isSuperset(set: Set<any>, subset: Set<any>) {
    for (let elem of subset) {
        if (!set.has(elem)) {
            return false;
        }
    }
    return true;
}

function union(setA: Set<any>, setB: Set<any>) {
    let _union = new Set(setA);
    for (let elem of setB) {
        _union.add(elem);
    }
    return _union;
}

function difference(setA: Set<any>, setB: Set<any>) {
    let _difference = new Set(setA);
    for (let elem of setB) {
        _difference.delete(elem);
    }
    return _difference;
}

export function PlacesSearchTypeFilter(props: IProps) {
    const { form, updateForm } = useSearchForm<IPlacesSearchTypes>();
    const registeredTypes = PlacesSearchTypeFilter.searchTypes;
    const allSupportedTypes = flatten(registeredTypes.map((v) => v.values));
    const allSupportedTypesSet = new Set(allSupportedTypes);

    if (registeredTypes.length <= 1) {
        return null;
    }

    const formTypes = form.types;
    const formTypeSet = new Set(formTypes ?? []);

    return (
        <CheckboxGroup legend={t("What to search")} tight>
            {registeredTypes.map((registeredType, i) => {
                const valueSet = new Set(registeredType.values);

                const isChecked = formTypeSet.size === 0 || isSuperset(formTypeSet, valueSet);
                return (
                    <Checkbox
                        label={registeredType.label}
                        key={i}
                        onChange={(e) => {
                            let newTypes: string[] = [];

                            const newCheck = e.target.checked;
                            if (newCheck) {
                                // This works because of the isSuperset check above
                                const allNewTypeSet = union(valueSet, formTypeSet);
                                newTypes = Array.from(allNewTypeSet);
                            } else {
                                // There are two cases: this is the last checked box in which case other
                                // boxes needed to be checked, or this is not the last checked box.
                                const remainingTypeSet = difference(formTypeSet, valueSet);
                                if (remainingTypeSet.size === 0) {
                                    const allNewTypeSet = difference(allSupportedTypesSet, valueSet);
                                    newTypes = Array.from(allNewTypeSet);
                                } else {
                                    newTypes = Array.from(remainingTypeSet);
                                }
                            }
                            updateForm({
                                types: Array.from(newTypes.filter((type) => allSupportedTypes.includes(type))),
                            });
                        }}
                        checked={isChecked}
                    />
                );
            })}
        </CheckboxGroup>
    );
}

interface IPlacesSearchType {
    label: string;
    values: string[];
}

PlacesSearchTypeFilter.searchTypes = [] as IPlacesSearchType[];

PlacesSearchTypeFilter.addSearchTypes = (searchType: IPlacesSearchType) => {
    PlacesSearchTypeFilter.searchTypes.push(searchType);
};

// Deferred to onReady for translation initialization.
onReady(() => {
    PlacesSearchTypeFilter.addSearchTypes({
        label: t("Categories"),
        values: ["category"],
    });
});
