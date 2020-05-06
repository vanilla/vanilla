/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { styleFactory, useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { IThemeVariables } from "@library/theming/themeReducer";
import { globalVariables } from "@library/styles/globalStyleVars";
import { borders, EMPTY_BORDER, IBorderStyles, singleBorder } from "@library/styles/styleHelpersBorders";
import { margins, paddings } from "@library/styles/styleHelpersSpacing";
import {
    colorOut,
    EMPTY_FONTS,
    fonts,
    IFont,
    negativeUnit,
    singleLineEllipsis,
    unit,
} from "@library/styles/styleHelpers";
import { calc, percent, translateY } from "csx";
import { dateTimeVariables } from "@library/content/dateTimeStyles";
import { lineHeightAdjustment } from "@library/styles/textUtils";
import { metaContainerStyles, metaItemStyle } from "@library/styles/metasStyles";
import { selectBoxClasses } from "@library/forms/select/selectBoxStyles";
import { clickableItemStates } from "@dashboard/compatibilityStyles/clickableItemHelpers";
import { camelCaseToDash } from "@dashboard/compatibilityStyles";
import { EventAttendance } from "@library/events/eventOptions";

export const eventsVariables = useThemeCache((forcedVars?: IThemeVariables) => {
    const makeVars = variableFactory("dateTime", forcedVars);
    const globalVars = globalVariables();

    const compact = makeVars("compact", {
        gutter: globalVars.gutter.size,
    });

    const title = makeVars("title", {
        font: {
            lineHeight: globalVars.lineHeights.condensed,
            size: globalVars.fonts.size.large,
            weight: globalVars.fonts.weights.semiBold,
        },
    });

    const alignment = makeVars("alignment", {
        verticalCheat: 1,
    });

    const spacing = makeVars("spacing", {
        contentSpacer: globalVars.gutter.half - alignment.verticalCheat, // Cheated for alignment
        attendanceOffset: 5,
        padding: {
            vertical: 20,
            horizontal: 5,
        },
    });

    const attendanceStamp = makeVars("attendanceStamp", {
        border: {
            ...EMPTY_BORDER,
            radius: 2,
        } as IBorderStyles,
        font: {
            ...EMPTY_FONTS,
            color: globalVars.mixBgAndFg(0.7),
            size: 10,
            fontWeight: globalVars.fonts.weights.semiBold,
            transform: "uppercase",
        } as IFont,
        padding: {
            horizontal: 4,
            vertical: 2,
        },
        going: {
            fg: globalVars.mainColors.primary,
        },
    });

    return { compact, title, spacing, attendanceStamp, alignment };
});

export const eventsClasses = useThemeCache((props: { compact?: boolean } = {}) => {
    const style = styleFactory("events");
    const vars = eventsVariables();
    const globalVars = globalVariables();
    const compactDateSize = dateTimeVariables().compact.container.size;

    const root = style("root", {
        display: "block",
    });

    const empty = style("empty", {
        display: "block",
    });

    const list = style("list", {
        display: "block",
        marginLeft: negativeUnit(vars.spacing.padding.horizontal * 2),
        width: calc(`100% + ${vars.spacing.padding.horizontal * 4}`),
    });

    const item = style("item", {
        display: "block",
        borderBottom: singleBorder(),
        ...paddings({
            horizontal: vars.spacing.padding.horizontal,
        }),
        $nest: {
            [`&.isFirst`]: {
                borderTop: singleBorder(),
            },
        },
    });

    const title = style("title", {
        display: "block",
        ...fonts(vars.title.font),
    });

    const linkColors = clickableItemStates()["$nest"];
    const toggleClass = selectBoxClasses().toggle;

    const link = style("link", {
        color: colorOut(globalVars.mainColors.fg),
        display: "flex",
        width: percent(100),
        flexWrap: "nowrap",
        flexGrow: 1,
        justifyContent: "flex-start",
        alignItems: "flex-start",
        ...paddings(vars.spacing.padding),
        $nest: {
            [`& .${toggleClass}`]: {
                marginLeft: "auto",
                fontWeight: globalVars.fonts.weights.normal,
            },
            [`&:hover .${title}`]: {
                ...linkColors!["&&:hover"],
            },
            [`&:focus .${title}`]: {
                ...linkColors!["&&:focus"],
            },
            [`&.focus-visible .${title}`]: {
                ...linkColors!["&&:focus-visible"],
            },
            [`&:active .${title}`]: {
                ...linkColors!["&&:active"],
            },
            [`&:visited .${title}`]: {
                ...linkColors!["&&:visited"],
            },
        },
    });

    const linkAlignment = style("linkAlignment", {
        display: "flex",
        flexWrap: "nowrap",
        alignItems: "center",
        justifyContent: "flex-start",
        minHeight: unit(compactDateSize),
        flexGrow: 1,
        overflow: "hidden",
    });

    const result = style("result", {
        display: "flex",
        flexWrap: "nowrap",
        justifyContent: "flex-start",
        alignItems: "flex-start",
        width: percent(100),
    });

    const dateCompact = style("dateCompact", {
        flexBasis: unit(compactDateSize),
        flexShrink: 1,
        alignSelf: "flex-start",
    });

    const body = style("body", {
        display: "block",
    });

    const main = style("main", {
        ...lineHeightAdjustment(),
        display: "block",
        paddingLeft: unit(vars.compact.gutter),
        transform: translateY(`${unit(vars.alignment.verticalCheat)}`), // text alignment cheat
        maxWidth: calc(`100% - ${unit(compactDateSize)}`),
    });

    const excerpt = style("excerpt", {
        display: "block",
        marginTop: unit(vars.spacing.contentSpacer),
    });

    const metas = style("metas", {
        // ...metaContainerStyles(),
        marginTop: unit(vars.spacing.contentSpacer),
    });

    const meta = style("meta", {
        display: "inline",
        ...fonts({
            size: globalVars.meta.text.fontSize,
            color: globalVars.meta.colors.fg,
            lineHeight: globalVars.lineHeights.meta,
        }),
        ...margins({
            right: globalVars.meta.spacing.default * 2,
        }),
    });

    const attendance = style("attendance", {
        display: "block",
        ...paddings({
            vertical: vars.spacing.padding.vertical,
        }),
    });

    const dropDown = style("dropDown", {
        ...lineHeightAdjustment(),
        $nest: {
            [`& .${selectBoxClasses().toggle}`]: {
                marginLeft: "auto",
                fontWeight: globalVars.fonts.weights.normal,
            },
        },
    });

    const attendanceClass = (attendance: EventAttendance) => {
        return `eventAttendance-${camelCaseToDash(attendance)}`;
    };

    const attendanceVars = vars.attendanceStamp;
    const attendanceStamp = style("attendanceStamp", {
        $nest: {
            [`&&`]: {
                display: "inline-flex",
                ...margins({
                    left: "auto",
                }),
                ...fonts(attendanceVars.font),
                ...borders({
                    ...attendanceVars.border,
                    color: attendanceVars.border.color ?? attendanceVars.font.color, // default to font color. Darkenned because border is very thin and get anti-aliased
                }),
                ...paddings(attendanceVars.padding),
                whiteSpace: "nowrap",
                lineHeight: 1,
            },
            [`&.${attendanceClass(EventAttendance.GOING)}`]: {
                color: colorOut(attendanceVars.going.fg),
                borderColor: colorOut(attendanceVars.going.fg),
            },
            [`&.${meta}`]: {
                marginRight: globalVars.meta.spacing.default * 2,
            },
        },
    });

    const viewMore = style("viewMore", {
        marginLeft: "auto",
    });

    const filter = style("filter", {});
    const filterLabel = style("filterLabel", {});

    return {
        root,
        item,
        list,
        body,
        result,
        link,
        linkAlignment,
        title,
        main,
        excerpt,
        metas,
        meta,
        empty,
        attendance,
        dateCompact,
        dropDown,
        attendanceClass,
        attendanceStamp,
        viewMore,
        filter,
        filterLabel,
    };
});
