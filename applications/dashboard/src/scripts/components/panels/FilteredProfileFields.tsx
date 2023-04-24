import React, { useEffect, useMemo, useState } from "react";
import { useProfileFields } from "@dashboard/userProfiles/state/UserProfiles.hooks";
import { IDateTimeRange, LoadStatus } from "@library/@types/api/core";
import { ProfileField } from "@dashboard/userProfiles/types/UserProfiles.types";
import { notEmpty } from "@vanilla/utils";
import omit from "lodash/omit";
import { GroupedTag, SortedTagCloud } from "@dashboard/components/panels/SortedTagCloud";
import { t } from "@vanilla/i18n";
import { filteredProfileFieldsClasses } from "@dashboard/components/panels/FilteredProfileFields.styles";
import sortBy from "lodash/sortBy";

/** This is a record of key API name and value  */
export type ProfileFieldValue = Record<ProfileField["apiName"], string | string[] | IDateTimeRange>;

interface IProps {
    /** This is a record of every filtered API name and its filter values */
    values?: ProfileFieldValue;
    /** Will return whats passed to the values prop, with the specific value removed */
    onChange(updatedValues: ProfileFieldValue): void;
}

/**
 * Assert the value passed in is a Timeframe object
 */
export function isDateRange(value: unknown): value is IDateTimeRange {
    return (
        typeof value === "object" &&
        !Array.isArray(value) &&
        Object.keys(value ?? {}).some((key) => !!key.match(/start|end/))
    );
}

/**
 * Assert the array passed in is a string array
 */
function isStringArray(array: unknown): array is string[] {
    return Array.isArray(array) ? array.every((entry) => typeof entry === "string") : false;
}

/**
 * Displays profile field filter values as removable tokens
 */
export function FilteredProfileFields(props: IProps) {
    const { values, onChange } = props;

    const classes = filteredProfileFieldsClasses();

    const profileFieldConfigs = useProfileFields();

    // Create a sentence describing the date range
    // TODO: These translations are gonna need work. I'm pretty sure we can't just build them up like this
    const getDateRangeText = (start?: string, end?: string) => {
        if (start && end) {
            return `${t("Between")} ${start} - ${end}`;
        }
        return `${start ? t("From") : end ? t("To") : ""} ${start ? start : end}`;
    };

    const handleRemove = (tag: Partial<GroupedTag> & { value: string }) => {
        const previousTagValue = values?.[`${tag.id}`];
        const newValues = () => {
            // If the tag being removed is a date range
            if (isDateRange(tag)) {
                return omit(values, [`${tag.id}`]);
            } else {
                const updatedFilterArray =
                    isStringArray(previousTagValue) && [previousTagValue].flat().filter((val) => val !== tag.value);
                if (updatedFilterArray && updatedFilterArray.length > 0) {
                    return {
                        ...values,
                        [`${tag.id}`]: updatedFilterArray,
                    };
                }
                return omit(values, [`${tag.id}`]);
            }
        };
        if (previousTagValue) {
            onChange(newValues());
        }
    };

    const filterGroups: GroupedTag[] | null = useMemo(() => {
        if (
            values &&
            profileFieldConfigs.status === LoadStatus.SUCCESS &&
            profileFieldConfigs.data &&
            profileFieldConfigs.data.length > 0
        ) {
            const unsortedGroups = Object.keys(values)
                .map((apiName) => {
                    const field = profileFieldConfigs.data?.find((profileField) => profileField.apiName === apiName);
                    const fieldValue = values[`${apiName}`];
                    if (field) {
                        if (isDateRange(fieldValue)) {
                            const { start, end } = fieldValue;
                            return {
                                id: apiName,
                                label: field.label,
                                tags: [getDateRangeText(start, end)],
                                start,
                                end,
                                sort: field.sort,
                                onRemove: handleRemove,
                            };
                        }
                        return {
                            id: apiName,
                            label: field.label,
                            tags: [fieldValue].flat(),
                            sort: field.sort,
                            onRemove: handleRemove,
                        };
                    }
                    return null;
                })
                .filter(notEmpty);

            return sortBy(unsortedGroups, (filterValue) => filterValue.sort);
        }
        return null;
    }, [values, profileFieldConfigs]);

    return (
        <>
            {filterGroups && filterGroups.length > 0 && (
                <div className={classes.root}>
                    <SortedTagCloud groupedTags={filterGroups} />
                </div>
            )}
        </>
    );
}
