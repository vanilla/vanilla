/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { css } from "@emotion/css";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { globalVariables } from "@library/styles/globalStyleVars";
import { Mixins } from "@library/styles/Mixins";
import { useThemeCache } from "@library/styles/styleUtils";

interface PostMetaClassesArgs {
    numberOfFields?: number;
}

export const PostMetaAssetClasses = useThemeCache((args?: PostMetaClassesArgs) => {
    const { numberOfFields } = args ?? {};
    const globalVars = globalVariables();

    const canQuarter = numberOfFields ? numberOfFields % 4 === 0 : false;
    const columnWidth = canQuarter ? "25%" : "33.33%";

    const layout = css({
        container: "postMeta / inline-size",
        display: "flex",
        flexDirection: "row",
        flexWrap: "wrap",
        borderRadius: globalVars.border.radius,
    });
    const field = css({
        display: "flex",
        flexDirection: "column",
        padding: globalVars.widget.padding / 2,
        width: "100%",
        "@container postMeta (width > 290px)": {
            width: columnWidth,
        },
    });
    const fieldName = css({
        display: "inline-flex",
        ...Mixins.font({ ...globalVars.fontSizeAndWeightVars("small") }),
        lineHeight: 1.5,
        cursor: "default",
        alignItems: "center",
    });
    const fieldValue = css({});
    const metaFieldName = css({});
    const metaFieldValue = css({});
    return { layout, field, fieldName, fieldValue, metaFieldName, metaFieldValue };
});
