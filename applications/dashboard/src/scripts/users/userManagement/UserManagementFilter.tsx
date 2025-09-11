/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import React, { useEffect, useMemo, useState } from "react";
import userManagementClasses from "@dashboard/users/userManagement/UserManagement.classes";
import { t } from "@vanilla/i18n";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { Icon } from "@vanilla/icons";
import { ToolTip } from "@library/toolTip/ToolTip";
import { ProfileField } from "@dashboard/userProfiles/types/UserProfiles.types";
import { ClearIcon } from "@vanilla/ui/src/forms/shared/ClearIcon";
import AdvancedMembersFilters from "@dashboard/components/panels/AdvancedMembersFilters";
import { JsonSchema } from "@vanilla/json-schema-forms";
import mapProfileFieldsToSchemaForFilterForm from "@dashboard/components/panels/mapProfileFieldsToSchemaForFilterForm";
import { useUserManagement } from "@dashboard/users/userManagement/UserManagementContext";
import {
    IUserManagementFilterValues,
    getBaseFilterSchema,
    mapFilterValuesToQueryParams,
} from "@dashboard/users/userManagement/UserManagementUtils";
import { IGetUsersQueryParams } from "@dashboard/users/userManagement/UserManagement.hooks";
import { spaceshipCompare } from "@vanilla/utils";
import DashboardListPageClasses from "@dashboard/components/DashboardListPage.classes";
interface IProps {
    profileFields?: ProfileField[];
    updateQuery: (newQueryParams: IGetUsersQueryParams) => void;
    initialFilters?: IUserManagementFilterValues;
}

export default function UserManagementFilter(props: IProps) {
    const { profileFields, updateQuery, initialFilters } = props;
    const { filterModal } = userManagementClasses.useAsHook();
    const { filterButtonsContainer, clearFilterButton } = DashboardListPageClasses.useAsHook();
    const { additionalFiltersSchemaFields } = useUserManagement();
    const [filters, setFilters] = useState<IUserManagementFilterValues | undefined>(initialFilters);

    useEffect(() => {
        setFilters(initialFilters);
    }, [initialFilters]);

    const profileFieldsFiltersSchema = useMemo<JsonSchema | undefined>(() => {
        if (profileFields) {
            const sortedProfileFields = profileFields.sort((a: ProfileField, b: ProfileField) => {
                return spaceshipCompare(a.label, b.label);
            });
            return mapProfileFieldsToSchemaForFilterForm(sortedProfileFields);
        }
    }, [profileFields]);

    const schema = {
        ...getBaseFilterSchema(),
        properties: {
            ...getBaseFilterSchema().properties,
            ...(additionalFiltersSchemaFields &&
                additionalFiltersSchemaFields.length &&
                Object.fromEntries(
                    additionalFiltersSchemaFields.map(({ fieldName, schema }) => {
                        return [fieldName, schema];
                    }),
                )),
            ...(!!profileFieldsFiltersSchema && { profileFields: profileFieldsFiltersSchema }),
        },
    };

    const isDirty = useMemo(() => {
        const hasFilters = filters && Object.keys(filters).length > 0;

        if (hasFilters) {
            return Object.keys(filters).some((filter) => {
                if (filter === "banFilter") {
                    return filters["banFilter"] !== "none";
                }
                if (filter !== "profileFields") {
                    return Array.isArray(filters[filter]) ? filters[filter].length : !!filters[filter];
                } else {
                    const profileFieldsFilter = filters["profileFields"];
                    return (
                        profileFieldsFilter &&
                        Object.keys(profileFieldsFilter).some((field) => {
                            return Array.isArray(profileFieldsFilter?.[field])
                                ? profileFieldsFilter?.[field].length
                                : !!profileFieldsFilter?.[field];
                        })
                    );
                }
            });
        }
        return false;
    }, [filters]);

    return (
        <div className={filterButtonsContainer}>
            <AdvancedMembersFilters
                ModalTriggerButton={FilterModalTriggerButton}
                modalTriggerButtonProps={{ dirty: isDirty }}
                values={filters}
                onSubmit={async (newValues) => {
                    newValues.page = 1;
                    await updateQuery(mapFilterValuesToQueryParams(newValues));
                    setFilters(newValues);
                }}
                schema={schema}
                noFocusOnModalExit
                modalClassName={filterModal}
                modalTitleAndDescription={{
                    titleID: "UserManagementFilterModal",
                    title: "Filters",
                    description: "",
                }}
                dateRangeDirection="below"
            />
            {isDirty && (
                <ToolTip label={t("Clear all filters")}>
                    <span>
                        <Button
                            buttonType={ButtonTypes.ICON_COMPACT}
                            onClick={() => {
                                updateQuery({ page: 1 });
                                setFilters({});
                            }}
                            className={clearFilterButton}
                        >
                            <ClearIcon />
                        </Button>
                    </span>
                </ToolTip>
            )}
        </div>
    );
}

export function FilterModalTriggerButton(props: { onClick: () => void; dirty: boolean }) {
    const { actionButton } = DashboardListPageClasses.useAsHook();

    return (
        <ToolTip label={t("Filter")} customWidth={60}>
            <span>
                <Button
                    buttonType={ButtonTypes.ICON}
                    onClick={() => {
                        props.onClick();
                    }}
                    className={actionButton}
                >
                    <Icon icon={`filter${props.dirty ? "-applied" : ""}`} />
                </Button>
            </span>
        </ToolTip>
    );
}
