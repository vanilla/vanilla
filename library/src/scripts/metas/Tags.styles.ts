/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { css, CSSObject } from "@emotion/css";
import { tagPresetVariables, tagsVariables, TagType } from "@library/metas/Tags.variables";
import { Mixins } from "@library/styles/Mixins";
import { defaultTransition, userSelect } from "@library/styles/styleHelpers";
import { useThemeCache } from "@library/styles/themeCache";
import { Variables } from "@library/styles/Variables";

export const tagMixin = (
    tagVars: ReturnType<typeof tagsVariables>,
    tagPresetOptions: TagType,
    applyStateStyles: boolean,
) => {
    const { font, fontState, background, border, padding } = tagVars;

    const style: CSSObject = {
        display: "inline-block",
        maxWidth: "100%",
        whiteSpace: "normal",
        textOverflow: "ellipsis",
        ...userSelect(),
        ...Mixins.padding(padding),
        ...Mixins.font(Variables.font({ ...font, color: tagPresetOptions?.fontColor ?? font.color })),
        ...Mixins.background(
            Variables.background({ ...background, color: tagPresetOptions?.bgColor ?? background.color }),
        ),
        ...Mixins.border(
            Variables.border({
                ...border,
                color: tagPresetOptions?.borderColor ?? border.color,
            }),
        ),
        ...(applyStateStyles
            ? {
                  ...defaultTransition("border", "color", "background"),
                  "&:hover, &:focus, &:active, &.focus-visible": {
                      ...Mixins.font(
                          Variables.font({
                              color: tagPresetOptions?.fontColorState ?? tagPresetOptions?.fontColor ?? fontState.color,
                          }),
                      ),
                      ...(tagPresetOptions?.bgColorState
                          ? Mixins.background(Variables.background({ color: tagPresetOptions?.bgColorState }))
                          : {}),
                      ...Mixins.border(
                          Variables.border({
                              ...border,
                              color: tagPresetOptions?.borderColorState ?? fontState.color,
                          }),
                      ),
                  },
              }
            : {}),
    };

    return style;
};

export const tagClasses = useThemeCache(() => {
    const tagVars = tagsVariables();
    const presets = tagPresetVariables();

    return {
        primary: (applyStateStyles = false) => css(tagMixin(tagVars, presets.primary, applyStateStyles)),
        standard: (applyStateStyles = false) => css(tagMixin(tagVars, presets.standard, applyStateStyles)),
        greyscale: (applyStateStyles = false) => css(tagMixin(tagVars, presets.greyscale, applyStateStyles)),
        colored: (applyStateStyles = false) => css(tagMixin(tagVars, presets.colored, applyStateStyles)),
    };
});
