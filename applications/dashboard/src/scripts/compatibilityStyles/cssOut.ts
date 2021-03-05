import { cssRaw, flattenNests } from "@library/styles/styleShim";
import { CSSObject, injectGlobal, css } from "@emotion/css";

/**
 * @deprecated Use injectGlobals instead.
 */
export function cssOut(selector: string, ...objects: CSSObject[]): void {
    injectGlobal({
        [selector]: objects.map(flattenNests),
    });
}
