import { threeColumnLayout } from "@library/layout/types/threeColumn";
import { LayoutTypes } from "@library/layout/layoutStyles";

export const getLayouts = () => {
    const variables = {
        [LayoutTypes.THREE_COLUMNS]: threeColumnLayout(),
    };

    return {
        mediaQueries: (currentType: LayoutTypes) => {
            variables[currentType].mediaQueries();
        },
    };
};
