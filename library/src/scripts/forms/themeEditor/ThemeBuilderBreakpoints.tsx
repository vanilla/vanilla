/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { useThemeVariableField } from "@library/forms/themeEditor/ThemeBuilderContext";
import { ThemeBuilderBlock, useThemeBlock } from "@library/forms/themeEditor/ThemeBuilderBlock";
import { t } from "@vanilla/i18n";
import { ThemeToggle } from "@library/forms/themeEditor/ThemeToggle";
import { logWarning } from "@vanilla/utils";
import { Spring, animated, config as springConfig } from "react-spring/renderprops";
import { style } from "@library/styles/styleShim";
import { ThemeBuilderUpload } from "@library/forms/themeEditor/ThemeBuilderUpload";

interface IProps {
    baseKey: string;
    responsiveKey: string;
    enabledView: BreakpointViewType | BreakpointCallback;
    forceState?: boolean;
    label?: string;
    info?: string;
    toggleDisabled?: boolean;
}

export function ThemeBuilderBreakpoints(props: IProps) {
    const { baseKey, responsiveKey, enabledView, forceState, label, info, toggleDisabled } = props;
    const {
        rawValue: rawBreakpoints = {},
        generatedValue: breakpoints = {},
        setValue: setBreakpoints,
    } = useThemeVariableField(baseKey + ".breakpoints");
    const { rawValue: isOn } = useThemeVariableField(baseKey + ".breakpointUIEnabled");

    // We have 1 key here by default. "breakpointUIEnabled".
    // If we have more, than means some breakpoints have been configured.
    const isForcedOn =
        forceState !== undefined
            ? forceState
            : rawBreakpoints && Object.keys(rawBreakpoints).length > 1
            ? true
            : undefined;
    const callback = typeof enabledView === "function" ? enabledView : breakpointCallbackForType(enabledView);
    const isForceEnabled = forceState || (rawBreakpoints && Object.keys(rawBreakpoints).length > 1 ? true : undefined);
    const isExpanded = isOn || isForceEnabled;

    return (
        <>
            <ThemeBuilderBlock
                label={label ?? t("Responsive Breakpoints")}
                info={info ?? t("You can configure some values differently for different screensizes.")}
            >
                <ThemeToggle
                    disabled={toggleDisabled}
                    forcedValue={isForcedOn}
                    afterChange={(value) => {
                        if (!value) {
                            // We were turned off, so clear all of the breakpoint values.
                            setBreakpoints(undefined);
                        }
                    }}
                    variableKey={baseKey + ".breakpointUIEnabled"}
                />
            </ThemeBuilderBlock>
            <Spring
                config={{ ...springConfig.stiff, clamp: true }}
                from={{ height: 0 }}
                to={{ height: isExpanded ? "auto" : 0 }}
            >
                {({ height }) => {
                    return (
                        <animated.div style={{ height }} className={style({ overflow: "hidden" })}>
                            {breakpoints &&
                                Object.entries(breakpoints).map(([breakpointKey, value]) => {
                                    const variableKey = `${baseKey}.breakpoints.${breakpointKey}.${responsiveKey}`;
                                    if (typeof value === "object" && value && value["breakpointUILabel"]) {
                                        return (
                                            <React.Fragment key={variableKey}>
                                                {callback({ variableKey, label: value["breakpointUILabel"] })}
                                            </React.Fragment>
                                        );
                                    } else {
                                        logWarning("variableKey is not a well formed breakpoint");
                                        return <React.Fragment key={variableKey} />;
                                    }
                                })}
                        </animated.div>
                    );
                }}
            </Spring>
        </>
    );
}

export enum BreakpointViewType {
    IMAGE = "image",
}

type BreakpointCallback = (params: { variableKey: string; label: string }) => React.ReactNode;

function breakpointCallbackForType(type: BreakpointViewType): BreakpointCallback {
    switch (type) {
        case BreakpointViewType.IMAGE:
        default:
            return breakpointImageCallback;
    }
}

function breakpointImageCallback({ variableKey, label }) {
    return (
        <ThemeBuilderBlock label={label}>
            <ThemeBuilderUpload variableKey={variableKey} />
        </ThemeBuilderBlock>
    );
}
