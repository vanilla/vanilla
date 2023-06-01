/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import {
    DashboardEditSelf,
    IUserDataProps,
} from "@dashboard/users/userManagement/dashboardAddEditUser/DashboardAddEditUser";
import { IUser } from "@library/@types/api/users";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { t } from "@vanilla/i18n";
import { PartialSchemaDefinition, JSONSchemaType, JsonSchema, ICustomControl } from "@vanilla/json-schema-forms";

type DashboardAddUserInitialValues = Omit<Partial<IUser>, "email"> & {
    email: {
        email: string;
        emailConfirmed: boolean;
        bypassSpam: boolean;
    };
    password: string;
    privacy: {
        showEmail: boolean;
        private: boolean;
    };
};

export const ADD_USER_EMPTY_INITIAL_VALUES: DashboardAddUserInitialValues = {
    name: "",
    email: {
        email: "",
        emailConfirmed: false,
        bypassSpam: false,
    },
    password: "",
    rankID: undefined,
    privacy: {
        showEmail: false,
        private: false,
    },
    profileFields: {},
};

/**
 * This will map schema values format into the one our API endpoint waits for.
 *
 * @param values - Our schema/form values.
 * @param profileFields All profile fields
 * @returns Values for api request
 */
export const mappedFormValuesForApiRequest = (values: { [key: string]: any }): Partial<IUser> => {
    const formattedValues = {
        ...values,
        email: values?.email?.email ?? "",
        bypassSpam: values?.email?.bypassSpam ?? "",
        emailConfirmed: values?.email?.emailConfirmed ?? "",
        roleID: values.roles?.roles ?? values.roles,
        banned: values.roles?.banned,
        rankID: values.rankID ? Number(values.rankID) : null,
        showEmail: values.privacy?.showEmail,
        private: !values.privacy?.private,
    };

    //some clean up
    if (values.passwordOptions) {
        if (values.passwordOptions.option) {
            switch (values.passwordOptions.option) {
                case "forceReset":
                    formattedValues["resetPassword"] = true;
                    delete formattedValues["password"];
                    break;
                case "setManually":
                    formattedValues["password"] = values.passwordOptions.newPassword;
                    break;
                case "keepCurrent":
                    //just don't send password with
                    delete formattedValues["password"];
            }
        }
    }

    if (values["roles"]) {
        delete formattedValues["roles"];
    }

    if (values["privacy"]) {
        delete formattedValues["privacy"];
    }

    //this should go away once api is good for unbanning when already unbanned
    const isBannedValid = formattedValues["banned"] === true || formattedValues["banned"] === false;
    if (!isBannedValid) {
        delete formattedValues["banned"];
    }

    return formattedValues;
};

/**
 * This will map the data from BE into schema format (some properties are nested in schema for proper grouping).
 *
 * @param initialValues - User data received from BE.
 */
export const mapEditInitialValuesToSchemaFormat = (initialValues: IUserDataProps, ranks?: Record<number, string>) => {
    const roles =
        initialValues.roles && Object.keys(initialValues.roles)
            ? Object.keys(initialValues.roles).map((role) => Number(role))
            : [];
    return {
        ...initialValues,
        email: {
            email: initialValues.email,
            emailConfirmed: initialValues.emailConfirmed,
            bypassSpam: initialValues.bypassSpam,
        },
        passwordOptions: {
            option: "keepCurrent",
        },
        privacy: {
            showEmail: initialValues.showEmail,
            private: !Number(initialValues.private),
        },
        roles: {
            roles: roles,
            banned: initialValues.banned,
        },
        rankID:
            ranks && initialValues.rankID && Object.keys(ranks).some((item) => Number(item) === initialValues.rankID)
                ? initialValues.rankID
                : undefined,
        profileFields: initialValues.profileFields ?? {},
    };
};

/**
 * Generates userschema depending on add/edit and some other factors
 *
 * @param isEdit - What is the schema for, add or edit
 * @param ranks - Optional params as ranks might be enabled or no
 * @returns  User schema
 */
export const userSchema = (
    isEdit: boolean,
    isOwnUser: boolean,
    ranks?: Record<number, string>,
    generatePasswordFn?: () => void,
    newPasswordFieldID?: string,
): JsonSchema => {
    interface IPrivacyJson {
        showEmail?: boolean;
        private?: boolean;
    }

    const privacySchema: JSONSchemaType<IPrivacyJson> = {
        type: "object",
        "x-control": {
            label: t("Privacy"),
        },
        properties: {
            showEmail: {
                type: "boolean",
                nullable: true,
                "x-control": {
                    label: t("Show email publicly"),
                    inputType: "checkBox",
                },
            },
            private: {
                type: "boolean",
                nullable: true,
                "x-control": {
                    label: t("Show profile publicly"),
                    inputType: "checkBox",
                },
            },
        },
        required: [],
    };

    const newUserPasswordSchema: PartialSchemaDefinition = {
        type: "string",
        nullable: true,
        minLength: 1,
        "x-control": {
            errorPathString: "/password",
            type: "password",
            label: t("Password"),
            inputType: "textBox",
            inputAriaLabel: t("Password"),
        },
    };

    interface IEditPasswordJson {
        isSelf?: boolean;
        option?: string;
        newPassword?: string;
        button?: null;
        isOwnUserHelper?: null;
    }

    const editUserPasswordSchema: JSONSchemaType<IEditPasswordJson> = {
        type: "object",
        "x-control": {
            label: t("Password Options"),
        },
        properties: {
            option: {
                type: "string",
                default: "keepCurrent",
                nullable: true,
                ...(!isOwnUser && {
                    "x-control": {
                        label: t("Password Options"),
                        inputType: "radio",
                        enum: ["keepCurrent", "forceReset", "setManually"],
                        choices: {
                            staticOptions: {
                                keepCurrent: t("Keep current password"),
                                forceReset: t("Force user to reset password and send email notification"),
                                setManually: t("Manually set user password with no email notification"),
                            },
                        },
                    },
                }),
            },
            newPassword: {
                type: "string",
                minLength: 1,
                nullable: true,
                "x-control": {
                    errorPathString: "/password",
                    type: "password",
                    inputType: "textBox",
                    conditions: [{ type: "string", const: "setManually", field: "passwordOptions.option" }],
                    inputID: newPasswordFieldID,
                    inputAriaLabel: t("New Password"), // this is the case when we won't have a label, but still want the input to be accessible
                },
            },

            // this one won't be passed to the api but for some live manipulation for a password field
            button: {
                type: "null",
                nullable: true,
                "x-control": {
                    inputType: "custom",
                    conditions: [{ type: "string", const: "setManually", field: "passwordOptions.option" }],
                    component: Button,
                    componentProps: {
                        buttonType: ButtonTypes.OUTLINE,
                        children: t("Generate Password"),
                        onClick: generatePasswordFn,
                    },
                } as ICustomControl<typeof Button>,
            },
            ...(isOwnUser && {
                isOwnUserHelper: {
                    type: "null",
                    nullable: true,
                    "x-control": {
                        inputType: "custom",
                        component: DashboardEditSelf,
                        componentProps: {
                            text: "Change your password in ",
                        },
                    } as ICustomControl<typeof DashboardEditSelf>,
                },
            }),
        },
        required: [],
    };

    let rolesSchema: PartialSchemaDefinition = {
        type: "array",
        items: { type: "string", minLength: 1 },
        "x-control": {
            errorPathString: "/roleID",
            inputType: "dropDown",
            label: t("Role"),
            description: t("You can select more than one role"),
            multiple: true,
            choices: {
                api: {
                    searchUrl: "/api/v2/roles",
                    singleUrl: "/api/v2/roles/%s",
                    valueKey: "roleID",
                    labelKey: "name",
                },
            },
        },
    };

    //banned should go into roles group
    if (isEdit) {
        const initialRolesSchema = rolesSchema;
        let newRolesSchema: any = {};
        newRolesSchema.type = "object";
        newRolesSchema["x-control"] = { label: t("Roles") };
        newRolesSchema["properties"] = {
            roles: initialRolesSchema,
            banned: {
                type: "number",
                "x-control": {
                    label: t("Banned"),
                    inputType: "checkBox",
                },
            },
        };
        newRolesSchema["required"] = ["roles"];

        rolesSchema = newRolesSchema;
    }

    interface IEmailJson {
        email: string;
        isOwnUserHelper?: null;
        emailConfirmed?: boolean;
        bypassSpam?: boolean;
    }

    const emailSchema: JSONSchemaType<IEmailJson> = {
        type: "object",
        "x-control": {
            label: t("Email"),
        },
        properties: {
            email: {
                type: "string",
                disabled: isOwnUser,
                minLength: 1,
                "x-control": {
                    errorPathString: "/email",
                    label: t("Email"),
                    inputType: "textBox",
                },
            },
            ...(isOwnUser && {
                isOwnUserHelper: {
                    type: "null",
                    nullable: true,
                    "x-control": {
                        inputType: "custom",
                        component: DashboardEditSelf,
                        componentProps: {
                            text: "Edit your username and email in  ",
                        },
                    } as ICustomControl<typeof DashboardEditSelf>,
                },
            }),
            emailConfirmed: {
                type: "boolean",
                nullable: true,
                "x-control": {
                    label: t("Email is confirmed"),
                    inputType: "checkBox",
                },
            },
            bypassSpam: {
                type: "boolean",
                nullable: true,
                "x-control": {
                    label: t("Verified: Bypasses spam and pre-moderation filters"),
                    inputType: "checkBox",
                },
            },
        },
        required: ["email"],
    };

    const nameSchema: PartialSchemaDefinition = {
        type: "string",
        minLength: 1,
        disabled: isOwnUser,
        "x-control": {
            label: t("Username"),
            inputType: "textBox",
        },
    };

    const rankIdSchema: PartialSchemaDefinition = {
        type: "number",
        "x-control": {
            inputType: "dropDown",
            label: t("Rank"),
            choices: {
                staticOptions: ranks,
            },
        },
    };

    interface IBaseSchemaJson {
        name: PartialSchemaDefinition<string>;
        roles: string[];
        email?: IEmailJson;
        privacy?: IPrivacyJson;
    }

    type EditSchemaType = IBaseSchemaJson & { passwordOptions: IEditPasswordJson };

    if (isEdit) {
        const schema: JsonSchema<EditSchemaType> = {
            type: "object",
            properties: {
                name: nameSchema,
                email: { ...emailSchema, nullable: true },
                passwordOptions: editUserPasswordSchema,
                privacy: { ...privacySchema, nullable: true },
                roles: rolesSchema,
            },
            required: ["name", "roles"],
        };

        if (ranks) {
            const schemaWithRanks: JSONSchemaType<
                EditSchemaType & {
                    rankID?: number;
                }
            > = {
                ...schema,
                properties: {
                    ...schema.properties!,
                    rankID: { ...rankIdSchema, nullable: true },
                },
            };

            return schemaWithRanks as JsonSchema;
        }

        return schema as JsonSchema;
    } else {
        type AddSchemaType = IBaseSchemaJson & { password: string };

        const schema: JSONSchemaType<AddSchemaType> = {
            type: "object",
            properties: {
                name: nameSchema,
                email: { ...emailSchema, nullable: true },
                password: newUserPasswordSchema,
                privacy: { ...privacySchema, nullable: true },
                roles: rolesSchema,
            },
            required: ["name", "roles", "password"],
        };

        if (ranks) {
            const schemaWithRanks: JSONSchemaType<
                AddSchemaType & {
                    rankID?: number;
                }
            > = {
                ...schema,
                properties: {
                    ...schema.properties!,
                    rankID: { ...rankIdSchema, nullable: true },
                },
            };

            return schemaWithRanks as JsonSchema;
        }

        return schema as JsonSchema;
    }
};

/**
 * Merges initial userSchema with profileFieldsSchema
 *
 * @param userSchema - Schema
 * @param profileFieldSchema - Schema
 * @returns  Complete schema
 */
export const mergeProfileFieldsSchema = (userSchema: JsonSchema, profileFieldSchema: JsonSchema | null): JsonSchema => {
    if (!profileFieldSchema) {
        return userSchema;
    }
    return {
        ...userSchema,
        properties: {
            ...userSchema.properties,
            profileFields: profileFieldSchema,
        },
        required: [...userSchema.required, "profileFields"],
    };
};
