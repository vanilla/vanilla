/**
 * @author Isis Graziatto <igraziatto@higherlogic.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IHomeWidgetContainerOptions } from "@library/homeWidget/HomeWidgetContainer.styles";
import { tagCloudVariables } from "@library/metas/Tags.variables";
import { Mixins } from "@library/styles/Mixins";
import { styleFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";

export const tagWidgetClasses = useThemeCache((options?: IHomeWidgetContainerOptions) => {
    const vars = tagCloudVariables(options);
    const style = styleFactory("tagWidget");

    const root = style("root", {
        ...Mixins.box(vars.box),
    });

    const count = style("count", {});

    return { root, count };
});
