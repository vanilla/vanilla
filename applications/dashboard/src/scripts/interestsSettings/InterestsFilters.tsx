/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { interestsClasses } from "@dashboard/interestsSettings/Interests.styles";
import { InterestFilters } from "@dashboard/interestsSettings/Interests.types";
import CheckBox from "@library/forms/Checkbox";
import CheckboxGroup from "@library/forms/CheckboxGroup";
import InputTextBlock from "@library/forms/InputTextBlock";
import { NestedSelect } from "@library/forms/nestedSelect";
import { CategoryDropdown } from "@library/forms/nestedSelect/presets/CategoryDropdown";
import { TagDropdown } from "@library/forms/nestedSelect/presets/TagDropdown";
import { t } from "@library/utility/appUtils";
import { RecordID } from "@vanilla/utils";
import { ChangeEvent, useEffect, useState } from "react";

interface IProps {
    filters: InterestFilters;
    updateFilters: (newFilters: InterestFilters) => void;
}

export function InterestsFilters(props: IProps) {
    const { filters = {}, updateFilters } = props;
    const classes = interestsClasses();
    const [defaultInterestsOnly, setDefaultInterestsOnly] = useState<boolean>(false);
    const [excludeDefaultInterests, setExcludeDefaultInterests] = useState<boolean>(false);

    // Weird UI choice for checkboxes here
    useEffect(() => {
        updateFilters({
            ...filters,
            ...(defaultInterestsOnly && { isDefault: true }),
            ...(excludeDefaultInterests && { isDefault: false }),
            ...(!defaultInterestsOnly && !excludeDefaultInterests && { isDefault: undefined }),
        });
    }, [defaultInterestsOnly, excludeDefaultInterests]);

    // Reset the default interests values when the filters are cleared
    useEffect(() => {
        if (!filters.hasOwnProperty("isDefault")) {
            setDefaultInterestsOnly(false);
            setExcludeDefaultInterests(false);
        }
        if (filters.hasOwnProperty("isDefault")) {
            setDefaultInterestsOnly(filters.isDefault === true);
            setExcludeDefaultInterests(filters.isDefault === false);
        }
    }, [filters]);

    return (
        <>
            <InputTextBlock
                label={t("Interest Name")}
                inputProps={{
                    value: filters.name ?? "",
                    onChange: (evt: ChangeEvent<HTMLInputElement>) => {
                        const {
                            target: { value },
                        } = evt;
                        updateFilters({
                            ...filters,
                            name: value,
                        });
                    },
                }}
                className={classes.filterField}
            />
            <NestedSelect
                label={t("Profile Fields")}
                optionsLookup={{
                    searchUrl: "/profile-fields?enabled=true&formType=dropdown,tokens,checkbox",
                    singleUrl: "/profile-fields/%s",
                    labelKey: "label",
                    valueKey: "apiName",
                }}
                multiple
                isClearable
                onChange={(values: string[]) =>
                    updateFilters({
                        ...filters,
                        profileFields: values,
                    })
                }
                value={filters.profileFields}
                classes={{ root: classes.filterField }}
            />
            <TagDropdown
                label={t("Tags")}
                multiple
                isClearable
                onChange={(values: RecordID[]) =>
                    updateFilters({
                        ...filters,
                        tagIDs: values,
                    })
                }
                value={filters.tagIDs}
                classes={{ root: classes.filterField }}
            />
            <CategoryDropdown
                label={t("Categories")}
                multiple
                isClearable
                onChange={(values: RecordID[]) =>
                    updateFilters({
                        ...filters,
                        categoryIDs: values,
                    })
                }
                value={filters.categoryIDs}
                classes={{ root: classes.filterField }}
            />
            <CheckboxGroup>
                <CheckBox
                    label={t("Default Interests Only")}
                    labelBold={false}
                    checked={defaultInterestsOnly}
                    onChange={(evt) => {
                        setDefaultInterestsOnly(evt.target.checked);
                        if (excludeDefaultInterests) {
                            setExcludeDefaultInterests(false);
                        }
                    }}
                />
                <CheckBox
                    label={t("Exclude Default Interests")}
                    labelBold={false}
                    checked={excludeDefaultInterests}
                    onChange={(evt) => {
                        setExcludeDefaultInterests(evt.target.checked);
                        if (defaultInterestsOnly) {
                            setDefaultInterestsOnly(false);
                        }
                    }}
                />
            </CheckboxGroup>
        </>
    );
}
