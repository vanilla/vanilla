import { cssRaw, flattenNests } from "@library/styles/styleShim";
import { CSSObject } from "@emotion/css";

export function cssOut(selector: string, ...objects: CSSObject[]): void {
    cssRaw({
        [selector]: objects.map(flattenNests),
    });
}
