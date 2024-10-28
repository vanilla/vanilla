/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import type { PartialSchemaDefinition } from "@library/json-schema-forms";
import { Metas, MetaItem } from "@library/metas/Metas";
import { openApiClasses } from "@library/openapi/OpenApiClasses";
import { jsonSchemaType, useResolvedOpenApiSchema } from "@library/openapi/OpenApiUtils";
import { t } from "@vanilla/i18n";

export function PropertySchemaLabel(props: {
    propertyName?: string;
    schema: PartialSchemaDefinition;
    required?: boolean;
}) {
    const { propertyName, schema, required } = props;
    const resolvedSchema = useResolvedOpenApiSchema(schema);
    const typeLabel = jsonSchemaType(resolvedSchema);

    const classes = openApiClasses();

    return (
        <Metas>
            {propertyName && (
                <MetaItem className={classes.parameterLabel}>
                    {props.required && (
                        <span aria-label={t("required")} className={classes.required}>
                            *
                        </span>
                    )}
                    {propertyName}
                </MetaItem>
            )}
            {props.required && <MetaItem>{t("Required")}</MetaItem>}
            <MetaItem>{typeLabel}</MetaItem>
        </Metas>
    );
}
