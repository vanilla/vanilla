/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license Proprietary
 */

import { JsonSchema } from "@vanilla/json-schema-forms";

export interface ITranslationService {
    type: string;
    name: string;
    isConfigured: boolean;
    isDefault: boolean;
    configSchema: JsonSchema;
}
export interface IAddon {
    addonID: string;
    name: string;
    key: string;
    type: "locale" | "addon" | "theme";
    description: string;
    iconUrl: string;
    version: string | number;
    attributes?: {
        locale: string;
    };
    enabled: boolean;
    require?: Partial<IAddon[]>;
    conflict?: Partial<IAddon[]>;
}

export interface ILocale {
    localeID: string;
    localeKey: string;
    regionalKey: string;
    displayNames: {
        [key: string]: string;
    };
}
