import { flattenNests } from "@library/styles/styleShim";
import { CSSObject, injectGlobal } from "@emotion/css";

/**
 * @deprecated Use injectGlobal instead.
 */
export function cssOut(selector: string, ...objects: CSSObject[]): void {
    injectGlobal({
        [selector]: objects.map(flattenNests),
    });
}
