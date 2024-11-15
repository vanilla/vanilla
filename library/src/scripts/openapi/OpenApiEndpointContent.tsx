/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import type { JsonSchema } from "@library/json-schema-forms";
import { openApiClasses } from "@library/openapi/OpenApiClasses";
import { OpenApiText } from "@library/openapi/OpenApiText";
import { PropertySchema } from "@library/openapi/PropertySchema";
import { t } from "@vanilla/i18n";

export function EndpointContent(props: {
    description?: string;
    contentType?: string;
    schema: JsonSchema;
    expandDefault?: boolean;
}) {
    const classes = openApiClasses();
    return (
        <>
            {props.description && (
                <div className={classes.responseRow}>
                    <strong className={classes.responseLabel}>{t("Description")}</strong>
                    <OpenApiText content={props.description} />
                </div>
            )}
            {props.contentType && (
                <div className={classes.responseRow}>
                    <strong className={classes.responseLabel}>{t("Content Type")}</strong>
                    <span>{props.contentType}</span>
                </div>
            )}
            <div className={classes.responseRow}>
                <strong className={classes.responseLabel} style={{ marginBottom: -10 }}>
                    {t("Content")}
                </strong>
                <PropertySchema expandDefault={props.expandDefault} schema={props.schema} />
            </div>
        </>
    );
}
