import { threeColumnLayout } from "@library/layout/types/threeColumn";
import { useLayout, LayoutTypes, IAllLayoutMediaQueries } from "@library/layout/LayoutContext";
import { NestedCSSProperties } from "typestyle/src/types";

const filterQueriesByType = mediaQueriesByType => {
    return (mediaQueriesByLayout: IAllLayoutMediaQueries) => {
        const { type } = useLayout();
        const mediaQueriesByType: NestedCSSProperties[] = [];

        console.log("filterQueriesByType: ", filterQueriesByType);
        Object.keys(mediaQueriesByLayout).forEach(layoutName => {
            console.log("layoutName: ", layoutName);
            if (layoutName === type) {
                // Check if we're in the correct layout before applying
                const mediaQueriesForLayout = mediaQueriesByLayout[layoutName];
                const stylesForLayout = mediaQueriesByLayout[layoutName];
                console.log("mediaQueriesForLayout: ", mediaQueriesForLayout);
                console.log("stylesForLayout: ", stylesForLayout);
                Object.keys(mediaQueriesForLayout).forEach(queryName => {
                    mediaQueriesByType.push(mediaQueriesForLayout[queryName](stylesForLayout));
                });
            }
        });

        console.log("mediaQueriesByType: ", mediaQueriesByType);
        return mediaQueriesByType;
    };
};

export const getLayouts = () => {
    const types = {
        [LayoutTypes.THREE_COLUMNS]: threeColumnLayout(),
    };

    const mediaQueriesByType = {};

    Object.keys(LayoutTypes).forEach((layoutName, index) => {
        if (types[layoutName]) {
            mediaQueriesByType[layoutName] = types[layoutName].mediaQueries();
        }
    });

    const mediaQueries = filterQueriesByType(mediaQueriesByType);

    return {
        types,
        mediaQueries,
    };
};
