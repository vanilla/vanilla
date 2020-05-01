import { styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { IThemeVariables } from "@library/theming/themeReducer";
import { defaultFontFamily, globalVariables } from "@library/styles/globalStyleVars";
import { borders } from "@library/styles/styleHelpersBorders";
import { paddings } from "@library/styles/styleHelpersSpacing";
import { colorOut, fonts, unit, userSelect } from "@library/styles/styleHelpers";
import { TextTransformProperty } from "csstype";

export const eventsVariables = useThemeCache((forcedVars?: IThemeVariables) => {
    const makeVars = variableFactory("dateTime", forcedVars);
    const globalVars = globalVariables();

    const compact = makeVars("compact", {});

    return { compact };
});

export const eventsClasses = useThemeCache(() => {
    const style = styleFactory("events");
    const vars = eventsVariables();

    const root = style("root", {
        display: "block",
    });

    const item = style("item", {
        display: "flex",
        flexWrap: "nowrap",
        justifyContent: "flex-start",
        alignItems: "flex-start",
    });

    const list = style("list", {
        display: "block",
    });

    const body = style("body", {
        display: "block",
    });

    const result = style("result", {
        display: "block",
    });

    const link = style("link", {
        display: "block",
    });

    const title = style("title", {
        display: "block",
    });

    const main = style("main", {
        display: "block",
    });

    const excerpt = style("excerpt", {
        display: "block",
    });

    const metas = style("metas", {
        display: "block",
    });

    const meta = style("meta", {
        display: "block",
    });

    const attendance = style("attendance", {
        display: "block",
    });

    return {
        root,
        item,
        list,
        body,
        result,
        link,
        title,
        main,
        excerpt,
        metas,
        meta,
        attendance,
    };
});
