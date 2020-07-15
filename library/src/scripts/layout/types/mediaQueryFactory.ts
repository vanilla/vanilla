import { NestedCSSProperties } from "typestyle/lib/types";
import { logError } from "@vanilla/utils";
import {
    IAllLayoutMediaQueries,
    ILayoutMediaQueryFunction,
    IMediaQueryFunction,
} from "@library/layout/types/interface.panelLayout";
import { LayoutTypes } from "@library/layout/types/interface.layoutTypes";
import { twoColumnLayoutVariables } from "@library/layout/types/layout.twoColumns";
import { threeColumnLayoutClasses, threeColumnLayoutVariables } from "@library/layout/types/layout.threeColumns";

/* Allows to declare styles for any layout without causing errors
Declare media query styles like this:

    mediaQueries({
        [LayoutTypes.TWO_COLUMNS]: {
            oneColumnDown: {
                ...srOnly(),
            },
        },
        [LayoutTypes.THREE_COLUMNS]: {
            twoColumns: {
                // Styles go here
            }
        }
    }),
    Note that "twoColumns" does not exist in two column layout media queries, but it does not crash!
*/
export const mediaQueryFactory = (mediaQueriesByType, type): IMediaQueryFunction => {
    // The following function is the one called in component styles.
    return (mediaQueriesForAllLayouts: IAllLayoutMediaQueries): NestedCSSProperties => {
        let output = { $nest: {} };
        Object.keys(mediaQueriesForAllLayouts).forEach(layoutName => {
            // Check if we're in the correct layout before applying
            if (layoutName === type) {
                // Fetch the available styles and the media queries for the current layout
                const stylesByMediaQuery = mediaQueriesForAllLayouts[layoutName];
                const mediaQueries =
                    type === LayoutTypes.TWO_COLUMNS
                        ? twoColumnLayoutVariables().mediaQueries()
                        : threeColumnLayoutVariables().mediaQueries();

                // Match the two together
                if (stylesByMediaQuery) {
                    Object.keys(stylesByMediaQuery).forEach(queryName => {
                        const query: ILayoutMediaQueryFunction = mediaQueries[queryName];
                        const styles: NestedCSSProperties = stylesByMediaQuery[queryName];
                        if (!query) {
                            logError(
                                `Error calculating media queries: \nThe styles provided were not in a valid media query.\nYou likely forgot to wrap your styles in the key of the proper media query.\nMedia queries available: ${JSON.stringify(
                                    Object.keys(mediaQueries),
                                )}\nLooking for media query called "${queryName}"\nin: `,
                                JSON.stringify(stylesByMediaQuery),
                            );
                        } else {
                            output = {
                                $nest: {
                                    ...output.$nest,
                                    ...query(styles as any).$nest,
                                },
                            };
                        }
                    });
                }
            }
        });
        return output;
    };
};
