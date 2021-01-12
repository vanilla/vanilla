/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { FilterFrame } from "@library/search/panels/FilterFrame";
import InputTextBlock from "@library/forms/InputTextBlock";
import { t } from "@vanilla/i18n";
import { useSearchForm } from "@library/search/SearchContext";
import DateRange from "@library/forms/DateRange";
import { dateRangeClasses } from "@library/forms/dateRangeStyles";
import Permission, { PermissionMode } from "@library/features/users/Permission";
import { MultiRoleInput } from "@dashboard/roles/MultiRoleInput";
import { IMemberSearchTypes } from "@dashboard/components/panels/memberSearchTypes";

interface IProps {}

export function MembersSearchFilterPanel(props: IProps) {
    const { form, updateForm, search, getFilterComponentsForDomain } = useSearchForm<IMemberSearchTypes>();

    const classesDateRange = dateRangeClasses();
    return (
        <FilterFrame title={t("Filter Results")} handleSubmit={search}>
            <InputTextBlock
                label={t("Username")}
                inputProps={{
                    onChange: (event: React.ChangeEvent<HTMLInputElement>) => {
                        const { value } = event.target;
                        updateForm({ username: value });
                    },
                    value: form.username || undefined,
                }}
            ></InputTextBlock>
            <Permission permission={"personalInfo.view"} mode={PermissionMode.GLOBAL}>
                <InputTextBlock
                    label={t("Email")}
                    inputProps={{
                        onChange: (event: React.ChangeEvent<HTMLInputElement>) => {
                            const { value } = event.target;
                            updateForm({ email: value });
                        },
                        value: form.email || undefined,
                    }}
                ></InputTextBlock>
            </Permission>

            <DateRange
                label={t("Registered")}
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
            <MultiRoleInput
                label={t("Role")}
                value={form.roleIDs ?? []}
                onChange={(ids: number[]) => {
                    updateForm({ roleIDs: ids });
                }}
            />
            {getFilterComponentsForDomain("members")}
        </FilterFrame>
    );
}
