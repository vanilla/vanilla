/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { css, cx } from "@emotion/css";
import { metasVariables } from "@library/metas/Metas.variables";
import { OpenApiText } from "@library/openapi/OpenApiText";
import type { IOpenApiProcessedEndpoint } from "@library/openapi/OpenApiTypes";
import { openApiMethodColor } from "@library/openapi/OpenApiUtils";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { globalVariables } from "@library/styles/globalStyleVars";
import { Mixins } from "@library/styles/Mixins";
import { notEmpty } from "@vanilla/utils";

interface IProps {
    endpoint: IOpenApiProcessedEndpoint;
}

export function OpenApiEndpointLabel(props: IProps) {
    const { endpoint } = props;
    const description = [endpoint.description, endpoint.summary].filter(notEmpty).join(" ").trim();

    return (
        <div>
            {/* Not using a heading because we fight with user content styles too much. */}
            <div className={cx(classes.endpointDetailTitle)} role="heading" aria-level={3}>
                <span style={{ color: openApiMethodColor(endpoint.method), textTransform: "uppercase" }}>
                    {endpoint.method}
                </span>{" "}
                {endpoint.path}
            </div>
            {description && <OpenApiText className={classes.endpointDetailDescription} content={description} />}
        </div>
    );
}

const classes = {
    endpointDetailTitle: css({
        textTransform: "uppercase",
        fontWeight: 400,
        ...Mixins.font({ family: globalVariables().fonts.families.monospace, size: 16 }),
    }),
    endpointDetailDescription: css({
        marginTop: 0,
        color: ColorsUtils.colorOut(metasVariables().font.color),
        fontSize: 14,
    }),
};
