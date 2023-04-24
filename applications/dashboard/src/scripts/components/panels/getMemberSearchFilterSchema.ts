/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc
 * @license Proprietary
 */

import { ICustomControl, JsonSchema } from "@vanilla/json-schema-forms";
import { t } from "@vanilla/i18n";
import { MultiRoleInput } from "@dashboard/roles/MultiRoleInput";
import { PermissionChecker } from "@library/features/users/Permission";

export default function getMemberSearchFilterSchema(permissionChecker: PermissionChecker): JsonSchema {
    const hasEmailPermission = permissionChecker("personalInfo.view");

    const schema: JsonSchema = {
        type: "object",
        properties: {
            username: {
                type: "string",
                "x-control": {
                    label: t("Username"),
                    inputType: "textBox",
                },
            },
            ...(hasEmailPermission
                ? {
                      email: {
                          type: "string",
                          "x-control": {
                              label: t("Email"),
                              inputType: "textBox",
                          },
                      },
                  }
                : undefined),
            registered: {
                type: "string",
                "x-control": {
                    label: t("Registered"),
                    inputType: "dateRange",
                },
                properties: {
                    start: {
                        type: "string",
                    },
                    end: {
                        type: "string",
                    },
                },
            },
            roleIDs: {
                type: "array",
                items: { type: "integer" },
                default: [],
                "x-control": {
                    legend: t("Role"),
                    inputType: "custom",
                    component: MultiRoleInput,
                } as ICustomControl<typeof MultiRoleInput>,
            },
        },
        required: [], //all optional
    };

    return schema;
}
