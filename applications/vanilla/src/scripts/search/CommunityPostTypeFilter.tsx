/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import Checkbox from "@library/forms/Checkbox";
import CheckboxGroup from "@library/forms/CheckboxGroup";
import { onReady, t } from "@library/utility/appUtils";
import { useSearchForm } from "@library/search/SearchContext";
import flatten from "lodash/flatten";

interface IProps {}

/**
 * Implement search filter panel main component.
 *
 * - If there is only 1 type, it does not display.
 * - IF there are multiple types, at least 1 must be selected.
 *
 * Other plugins can hook into this and add their own filters with
 *
 * @example
 *    CommunityPostTypeFilter.addPostType({
 *       label: t("Discussions"),
 *       values: ["discussion", "comment"],
 *   });
 */
export function CommunityPostTypeFilter(props: IProps) {
    const { form, updateForm } = useSearchForm<{}>();
    const registeredTypes = CommunityPostTypeFilter.postTypes;
    const allTypes = flatten(registeredTypes.map((v) => v.values));

    if (registeredTypes.length <= 1) {
        return null;
    }

    const formTypes = form.types;

    return (
        <CheckboxGroup label={"What to Search"} grid={true} tight={true}>
            {registeredTypes.map((registeredType, i) => {
                const noTypesSelected = !formTypes || formTypes.length === 0;
                let isChecked = false;

                if (noTypesSelected) {
                    isChecked = true;
                } else {
                    formTypes?.forEach((formType) => {
                        if (registeredType.values.includes(formType)) {
                            isChecked = true;
                        }
                    });
                }

                return (
                    <Checkbox
                        label={registeredType.label}
                        key={i}
                        checked={isChecked}
                        onChange={(e) => {
                            const newChecked = e.target.checked;

                            if (newChecked) {
                                const newTypesSet = new Set(formTypes ?? []);
                                registeredType.values.forEach((typeToPush) => {
                                    newTypesSet.add(typeToPush);
                                });

                                if (newTypesSet.size === allTypes.length) {
                                    updateForm({ types: undefined });
                                } else {
                                    updateForm({ types: Array.from(newTypesSet) });
                                }
                            } else {
                                const newTypesSet = new Set(form.types ?? []);
                                registeredType.values.forEach((typeToPush) => {
                                    newTypesSet.delete(typeToPush);
                                });

                                if (newTypesSet.size === 0) {
                                    // Make a new set with all values minus the current one.
                                    const newTypes = allTypes.filter((v) => !registeredType.values.includes(v));
                                    updateForm({ types: newTypes });
                                } else {
                                    updateForm({ types: Array.from(newTypesSet) });
                                }
                            }
                        }}
                    />
                );
            })}
        </CheckboxGroup>
    );
}

interface ICommunityPostType {
    label: string;
    values: string[];
}

CommunityPostTypeFilter.postTypes = [] as ICommunityPostType[];

CommunityPostTypeFilter.addPostType = (postType: ICommunityPostType) => {
    CommunityPostTypeFilter.postTypes.push(postType);
};

// Deferred to onReady for translation initialization.
onReady(() => {
    CommunityPostTypeFilter.addPostType({
        label: t("Discussions"),
        values: ["discussion", "comment"],
    });
});
