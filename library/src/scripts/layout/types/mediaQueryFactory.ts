import { CSSObject } from "@emotion/css";
import { logError } from "@vanilla/utils";
import {
    IAllSectionMediaQueries,
    ISectionMediaQueryFunction,
    IMediaQueryFunction,
} from "@library/layout/types/interface.panelLayout";
import { SectionTypes } from "@library/layout/types/interface.layoutTypes";
import { twoColumnVariables } from "@library/layout/types/layout.twoColumns";
import { threeColumnClasses, threeColumnVariables } from "@library/layout/types/layout.threeColumns";

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
    return (mediaQueriesForAllLayouts: IAllSectionMediaQueries): CSSObject => {
        let output = {};
        Object.keys(mediaQueriesForAllLayouts).forEach((layoutName) => {
            // Check if we're in the correct layout before applying
            if (layoutName === type) {
                // Fetch the available styles and the media queries for the current layout
                const stylesByMediaQuery = mediaQueriesForAllLayouts[layoutName];
                const mediaQueries =
                    type === SectionTypes.TWO_COLUMNS
                        ? twoColumnVariables().mediaQueries()
                        : threeColumnVariables().mediaQueries();

                // Match the two together
                if (stylesByMediaQuery) {
                    Object.keys(stylesByMediaQuery).forEach((queryName) => {
                        const query: ISectionMediaQueryFunction = mediaQueries[queryName];
                        const styles: CSSObject = stylesByMediaQuery[queryName];
                        if (!query) {
                            logError(
                                `Error calculating media queries: \nThe styles provided were not in a valid media query.\nYou likely forgot to wrap your styles in the key of the proper media query.\nMedia queries available: ${JSON.stringify(
                                    Object.keys(mediaQueries),
                                )}\nLooking for media query called "${queryName}"\nin: `,
                                JSON.stringify(stylesByMediaQuery),
                            );
                        } else {
                            output = query(styles as any);
                        }
                    });
                }
            }
        });
        return output;
    };
};
