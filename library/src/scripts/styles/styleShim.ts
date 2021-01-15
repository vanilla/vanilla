import createEmotion from "@emotion/css/create-instance";
import { CSSObject } from "@emotion/css";

const emotion = createEmotion({
    key: "vanilla",
    prepend: false,
});

export const { keyframes, cx, injectGlobal: cssRaw, css: style } = emotion;

export declare type TLength = number | string; // a few files are stil coupled to this type from typestyle

type NestCssObject = CSSObject & { $nest?: NestCssObject };

/**
 * Flatten $nest properties from typestyle declarations to be compatible with emotion.
 */
export function flattenNests(original?: NestCssObject) {
    if (!original?.$nest) {
        return original;
    }

    for (const [key, value] of Object.entries(original.$nest)) {
        original[key] = flattenNests(value as NestCssObject);
    }
    delete original.$nest;
    return original;
}

export function cssRule(selector: string, ...objects: CSSObject[]): void {
    objects.forEach((object) => {
        emotion.injectGlobal({
            [selector]: flattenNests(object),
        });
    });
}
interface IMediaQuery {
    minWidth?: number;
    maxWidth?: number;
}

//  adapted from typestyle // emotion doesn't have an equivalent function to typestyle.media.
export const media = (mediaQuery: IMediaQuery, ...objects: CSSObject[]): CSSObject => {
    const mediaQuerySections: string[] = [];
    if (mediaQuery.minWidth) mediaQuerySections.push(`(min-width: ${mediaQuery.minWidth}px)`);
    if (mediaQuery.maxWidth) mediaQuerySections.push(`(max-width: ${mediaQuery.maxWidth}px)`);

    const stringMediaQuery = `@media ${mediaQuerySections.join(" and ")}`;

    const object: CSSObject = {
        [stringMediaQuery]: objects,
    };
    return object;
};
