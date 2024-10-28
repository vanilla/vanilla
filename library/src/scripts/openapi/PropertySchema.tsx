/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { css, cx } from "@emotion/css";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import type { JSONSchemaType, PartialSchemaDefinition } from "@library/json-schema-forms";
import { metasVariables } from "@library/metas/Metas.variables";
import { openApiClasses } from "@library/openapi/OpenApiClasses";
import { OpenApiEnumList } from "@library/openapi/OpenApiEnumList";
import { OpenApiText } from "@library/openapi/OpenApiText";
import type { OpenApiRef } from "@library/openapi/OpenApiTypes";
import { useResolvedOpenApiSchema } from "@library/openapi/OpenApiUtils";
import { PropertySchemaLabel } from "@library/openapi/ProperySchemaLabel";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { globalVariables } from "@library/styles/globalStyleVars";
import { singleBorder } from "@library/styles/styleHelpersBorders";
import { DropDownArrow } from "@vanilla/ui/src/forms/shared/DropDownArrow";
import { useState } from "react";

interface IProps {
    propertyName?: string;
    className?: string;
    required?: boolean;
    expandDefault?: boolean;
    schema:
        | PartialSchemaDefinition
        | OpenApiRef
        | { oneOf: PartialSchemaDefinition[] }
        | { allOf: PartialSchemaDefinition[] };
}

export function PropertySchema(props: IProps) {
    const { schema } = props;

    const resolvedSchema = useResolvedOpenApiSchema(schema);

    return (
        <div className={cx(classes.parameterWrap, props.className)}>
            <PropertySchemaLabel {...props} />
            {"description" in resolvedSchema && (
                <OpenApiText className={classes.propertyDescription} content={resolvedSchema.description} />
            )}
            {"oneOf" in resolvedSchema && (
                <OneOfDetails schemas={resolvedSchema.oneOf} expandDefault={props.expandDefault} />
            )}
            {"allOf" in resolvedSchema && (
                <OneOfDetails schemas={resolvedSchema.allOf} expandDefault={props.expandDefault} />
            )}
            {"enum" in resolvedSchema && <OpenApiEnumList enumValues={resolvedSchema.enum} />}
            {"properties" in resolvedSchema && (
                <PropertyDetails
                    properties={resolvedSchema.properties}
                    requiredProperties={resolvedSchema.required}
                    expandDefault={props.expandDefault}
                />
            )}
            {"items" in resolvedSchema && "properties" in resolvedSchema.items && (
                <PropertyDetails
                    expandDefault={props.expandDefault}
                    properties={resolvedSchema.items.properties}
                    requiredProperties={resolvedSchema.items.required}
                />
            )}
        </div>
    );
}

function PropertyDetails(props: {
    properties: JSONSchemaType["properties"];
    requiredProperties?: string[];
    expandDefault?: boolean;
}) {
    const [isCollapsed, setIsCollapsed] = useState(!props.expandDefault);
    return (
        <div className={classes.wrapper}>
            <Button
                className={cx(classes.collapseHeader, { collapsed: isCollapsed })}
                buttonType={ButtonTypes.CUSTOM}
                onClick={() => {
                    setIsCollapsed(!isCollapsed);
                }}
            >
                <DropDownArrow className={classes.accordionArrow(isCollapsed)} />
                {isCollapsed ? "Show" : "Hide"} Properties
            </Button>
            {!isCollapsed && (
                <div className={classes.subParamWrap}>
                    {Object.entries(props.properties).map(([key, value]) => (
                        <PropertySchema
                            key={key}
                            propertyName={key}
                            schema={value}
                            required={props.requiredProperties?.includes(key)}
                        />
                    ))}
                </div>
            )}
        </div>
    );
}

function OneOfDetails(props: { schemas: PartialSchemaDefinition[]; expandDefault?: boolean }) {
    const classes = openApiClasses();
    return (
        <ul className={classes.enumList}>
            {props.schemas.map((schema, index) => (
                <li key={index}>
                    <PropertySchema schema={schema} expandDefault={props.expandDefault} />
                </li>
            ))}
        </ul>
    );
}

const classes = {
    wrapper: css({
        borderRadius: 6,
        border: singleBorder(),
        backgroundColor: ColorsUtils.colorOut(globalVariables().mixBgAndFg(0.02)),
        marginTop: 4,
    }),
    collapseHeader: css({
        display: "flex",
        alignItems: "center",
        padding: 8,
        borderBottom: singleBorder(),
        width: "100%",

        "&.collapsed": {
            borderBottom: "none",
        },
    }),
    accordionArrow: (isCollapsed?: boolean) => {
        return css({
            transform: isCollapsed ? "rotate(-90deg)" : undefined,
            width: 10,
            height: 10,
            marginRight: 8,
        });
    },
    subParamWrap: css({
        padding: "8px 16px",
    }),
    parameterWrap: css({
        position: "relative",
        borderBottom: singleBorder(),
        paddingTop: 8,
        paddingBottom: 12,
        "&:last-child": {
            borderBottom: "none",
            paddingBottom: 4,
        },
    }),

    propertyDescription: css({
        color: ColorsUtils.colorOut(metasVariables().font.color),
        fontSize: 14,
    }),
};
