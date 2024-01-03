import { css } from "@emotion/css";
import { globalVariables } from "@library/styles/globalStyleVars";

export const filteredProfileFieldsClasses = () => {
    const root = css({
        paddingTop: globalVariables().spacer.panelComponent,
    });

    return {
        root,
    };
};
