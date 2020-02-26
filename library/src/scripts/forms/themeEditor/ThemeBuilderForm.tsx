/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useMemo } from "react";
import { themeBuilderClasses } from "@library/forms/themeEditor/themeBuilderStyles";
import { Form, FormikProvider, useFormik } from "formik";
import ThemeBuilderTitle from "@library/forms/themeEditor/ThemeBuilderTitle";
import ColorPickerBlock from "@library/forms/themeEditor/ColorPickerBlock";
import ThemeBuilderSection from "@library/forms/themeEditor/ThemeBuilderSection";
import ThemeBuilderSectionGroup from "@library/forms/themeEditor/ThemeBuilderSectionGroup";
import { globalVariables } from "@library/styles/globalStyleVars";
import { colorOut } from "@library/styles/styleHelpersColors";

export interface IThemeBuilderForm {}

export default function ThemeBuilderForm(props: IThemeBuilderForm) {
    const classes = themeBuilderClasses();
    const vars = globalVariables();
    const form = useFormik({
        initialValues: {},
        onSubmit: values => {
            // co<form action="#" class="themeBuilder-root_femt1sx">nsole.log(values);
        },
    });
    return (
        <FormikProvider value={form}>
            {/* The translate shouldn't be mandatory, it's a bug in this version of Formik */}
            <Form translate={true} className={classes.root}>
                <ThemeBuilderTitle />
                <ColorPickerBlock
                    colorPicker={{
                        variableID: "global.mainColors.primary",
                        defaultValue: colorOut(vars.mainColors.primary),
                    }}
                    inputBlock={{ label: "Test 1" }}
                />
                <ColorPickerBlock
                    colorPicker={{
                        variableID: "global.something.or.other.color.2",
                        defaultValue: "#00ca25",
                    }}
                    inputBlock={{ label: "Test 2" }}
                />
                <ThemeBuilderSection label={"Section 1"}>
                    <ColorPickerBlock
                        colorPicker={{
                            variableID: "global.something.or.other.color.3",
                            defaultValue: "#3139ca",
                        }}
                        inputBlock={{ label: "Test 3" }}
                    />
                    <ColorPickerBlock
                        colorPicker={{
                            variableID: "global.something.or.other.color.4",
                            defaultValue: "#c627ca",
                        }}
                        inputBlock={{ label: "Test 4" }}
                    />
                </ThemeBuilderSection>
                <ThemeBuilderSection label={"Section 31"}>
                    <ColorPickerBlock
                        colorPicker={{
                            variableID: "global.something.or.other.color.5",
                            defaultValue: "#c7cac4",
                        }}
                        inputBlock={{ label: "Test 5" }}
                    />
                    <ColorPickerBlock
                        colorPicker={{
                            variableID: "global.something.or.other.color.6",
                            defaultValue: "#15206f",
                        }}
                        inputBlock={{ label: "Test 6" }}
                    />
                    <ThemeBuilderSectionGroup label={"Section Sub Group"}>
                        <ColorPickerBlock
                            colorPicker={{
                                variableID: "global.something.or.other.color.7",
                                defaultValue: "cat" as any, // Intentionally bypassing typescript for error
                            }}
                            inputBlock={{ label: "With Error" }}
                        />
                        <ColorPickerBlock
                            colorPicker={{
                                variableID: "global.something.or.other.color.8",
                                defaultValue: "chinchilla" as any, // Intentionally bypassing typescript for error
                            }}
                            inputBlock={{ label: "With Error" }}
                        />
                        <ColorPickerBlock
                            colorPicker={{
                                variableID: "global.something.or.other.color.9",
                            }}
                            inputBlock={{ label: "Test 8 - No default value" }}
                        />
                    </ThemeBuilderSectionGroup>
                </ThemeBuilderSection>
            </Form>
        </FormikProvider>
    );
}
