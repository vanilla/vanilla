/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc
 * @license Proprietary
 */

import { JSONSchemaType, JsonSchema } from "@vanilla/json-schema-forms";
import { t } from "@vanilla/i18n";
import { MultiRoleInput } from "@dashboard/roles/MultiRoleInput";
import { PermissionChecker } from "@library/features/users/Permission";

interface IBaseSchema {
    username?: string;
    dateInserted?: { start?: string; end?: string };
    roleIDs?: number[];
}

interface ISchemaWithEmail extends IBaseSchema {
    email?: string;
}

export default function getMemberSearchFilterSchema(
    permissionChecker: PermissionChecker,
): JsonSchema<IBaseSchema | ISchemaWithEmail> {
    const hasEmailPermission = permissionChecker("personalInfo.view");

    const schema: JSONSchemaType<IBaseSchema | ISchemaWithEmail> = {
        type: "object",
        properties: {
            username: {
                type: "string",
                nullable: true,
                "x-control": {
                    label: t("Username"),
                    inputType: "textBox",
                },
            },
            ...(hasEmailPermission
                ? {
                      email: {
                          type: "string",
                          nullable: true,
                          "x-control": {
                              label: t("Email"),
                              inputType: "textBox",
                          },
                      },
                  }
                : undefined),
            dateInserted: {
                type: "object",
                nullable: true,
                "x-control": {
                    legend: t("Registered"),
                    inputType: "dateRange",
                },
                properties: {
                    start: {
                        nullable: true,
                        type: "string",
                    },
                    end: {
                        nullable: true,
                        type: "string",
                    },
                },
            },
            roleIDs: {
                type: "array",
                items: { type: "integer" },
                default: [],
                nullable: true,
                "x-control": {
                    legend: t("Role"),
                    inputType: "custom",
                    component: MultiRoleInput,
                },
            },
        },
        required: [], //all optional
    };

    return schema;
}
