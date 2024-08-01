/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import { css } from "@emotion/css";
import { Mixins } from "@library/styles/Mixins";
import { homeWidgetItemVariables } from "@library/homeWidget/HomeWidgetItem.styles";
import { useThemeCache } from "@library/styles/themeCache";

export const featuredCollectionsClasses = useThemeCache(() => {
    const defaultItemOptions = homeWidgetItemVariables().options;

    const listWrapper = css({
        ...Mixins.padding(defaultItemOptions.box.spacing),
    });

    return { listWrapper };
});
