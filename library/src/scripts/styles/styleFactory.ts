/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { NestedCSSProperties } from "typestyle/lib/types";
import { style } from "typestyle";

/**
 * A better helper to generate human readable classes generated from TypeStyle.
 *
 * This works like debugHelper but automatically. The generated function behaves just like `style()`
 * but can automatically adds a debug name & allows the first argument to be a string subcomponent name.
 *
 * @example
 * const style = styleFactory("myComponent");
 * const myClass = style({ color: "red" }); // .myComponent-sad421s
 * const mySubClass = style("subcomponent", { color: "red" }) // .myComponent-subcomponent-23sdaf43
 *
 */
export default function styleFactory(componentName: string) {
    function styleCreator(subcomponentName: string, ...objects: Array<NestedCSSProperties | undefined>);
    function styleCreator(...objects: Array<NestedCSSProperties | undefined>);
    function styleCreator(...objects: Array<NestedCSSProperties | undefined | string>) {
        if (objects.length === 0) {
            return style();
        }

        let debugName = componentName;
        let styleObjs: Array<NestedCSSProperties | undefined> = objects as any;
        if (typeof objects[0] === "string") {
            const [subcomponentName, ...restObjects] = styleObjs;
            debugName += `-${subcomponentName}`;
            styleObjs = restObjects;
        }

        return style({ $debugName: debugName }, ...styleObjs);
    }

    return styleCreator;
}
