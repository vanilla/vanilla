/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { useRoles, useRoleSelectOptions } from "@dashboard/roles/roleHooks";
import { LoadStatus } from "@library/@types/api/core";
import { notEmpty } from "@vanilla/utils";
import { Tokens } from "@library/forms/select/Tokens";

interface IProps extends Omit<React.ComponentProps<typeof Tokens>, "options" | "isLoading" | "value" | "onChange"> {
    value: number[];
    onChange: (tokens: number[]) => void;
    menuPlacement?: string;
}

export function MultiRoleInput(props: IProps) {
    const rolesByID = useRoles();
    const roleOptions = useRoleSelectOptions();

    return (
        <Tokens
            {...props}
            value={props.value
                .map((roleID) => {
                    const role = rolesByID.data?.[roleID];
                    if (!role) {
                        return null;
                    } else {
                        return {
                            label: role.name,
                            value: role.roleID,
                        };
                    }
                })
                .filter(notEmpty)}
            onChange={(options) => {
                const result = options?.map((option) => option.value as number);
                props.onChange(result);
            }}
            options={roleOptions.data ?? []}
            isLoading={[LoadStatus.PENDING, LoadStatus.LOADING].includes(roleOptions.status)}
        />
    );
}
