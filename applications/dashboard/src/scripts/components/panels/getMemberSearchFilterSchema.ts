/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc
 * @license Proprietary
 */

import { ICustomControl, JsonSchema } from "@vanilla/json-schema-forms";
import { t } from "@vanilla/i18n";
import { MultiRoleInput } from "@dashboard/roles/MultiRoleInput";
import { PermissionChecker } from "@library/features/users/Permission";
import { JSONSchemaType } from "ajv";

export default function getMemberSearchFilterSchema(permissionChecker: PermissionChecker): JsonSchema {
    const hasEmailPermission = permissionChecker("personalInfo.view");

    interface IBaseSchema {
        username?: string;
        registered?: { start?: string; end?: string };
        roleIDs?: number[];
    }

    interface ISchemaWithEmail extends IBaseSchema {
        email?: string;
    }

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
            registered: {
                type: "object",
                nullable: true,
                "x-control": {
                    label: t("Registered"),
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
                } as ICustomControl<typeof MultiRoleInput>,
            },
        },
        required: [], //all optional
    };

    return schema as JsonSchema;
}
