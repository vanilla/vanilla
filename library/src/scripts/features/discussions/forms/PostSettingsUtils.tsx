/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import { PostField } from "@dashboard/postTypes/postType.types";
import { CreatableFieldVisibility } from "@dashboard/userProfiles/types/UserProfiles.types";
import { formatList, getJSLocaleKey, t } from "@vanilla/i18n";
import { Icon } from "@vanilla/icons";

export const getFormattedValue = (field: PostField, value?: string | string[]) => {
    const { dataType } = field;

    switch (dataType) {
        case "boolean":
            if (typeof value === "undefined") {
                return t("This field is empty in this post");
            }
            return value && (value === "true" || (value as any) === true) ? t("Yes") : t("No");
        case "date":
            const dateValue = new Date(`${value}`);
            if (isNaN(dateValue.getTime())) {
                return t("This field is empty in this post");
            }
            return dateValue.toLocaleString(getJSLocaleKey(), {
                timeZone: "UTC",
                year: "numeric",
                month: "long",
                day: "numeric",
            });
        case "string[]": {
            if (typeof value === "undefined") {
                return t("This field is empty in this post");
            }
            return value && formatList(value);
        }
        default:
            if (!value) {
                return t("This field is empty in this post");
            }
            return value;
    }
};

export const visibilityIcon = (visibility: CreatableFieldVisibility) => {
    if (visibility === "internal") {
        return <Icon icon={"visibility-internal"} size={"compact"} />;
    }
    if (visibility === "private") {
        return <Icon icon={"visibility-private"} size={"compact"} />;
    }
    return <></>;
};
