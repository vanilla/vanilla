import { threeColumnLayout } from "@library/layout/types/threeColumn";

export const getLayouts = (props: { gutter; globalVars; forcedVars }) => {
    return {
        threeColumns: threeColumnLayout(props),
    };
};
