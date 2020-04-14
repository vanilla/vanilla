/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { useThemeVariableField } from "@library/forms/themeEditor/ThemeBuilderContext";
import { fontKey, GoogleFontDropdown } from "@themingapi/theme/GoogleFontDropdown";
import { ThemeBuilderBlock } from "@library/forms/themeEditor/ThemeBuilderBlock";
import { t } from "@vanilla/i18n/src";
import { CustomFontUrl } from "@themingapi/theme/CustomFontUrl";
import { themeBuilderClasses } from "@library/forms/themeEditor/ThemeBuilder.styles";
import Translate from "@library/content/Translate";
import SmartLink from "@library/routing/links/SmartLink";
import { CustomFontFamily } from "@library/forms/themeEditor/CustomFontFamily";

export function ThemeBuilderFontBlock(props: { forceDefaultKey?: string; forceError?: boolean }) {
    const { generatedValue } = useThemeVariableField(fontKey);
    const customFont = generatedValue === "custom" || props.forceDefaultKey === "custom";
    const docUrl = "https://success.vanillaforums.com/kb/articles/260-custom-font";
    const classes = themeBuilderClasses();
    return (
        <>
            <ThemeBuilderBlock label={t("Font")}>
                <GoogleFontDropdown forceDefaultKey={props.forceDefaultKey} />
            </ThemeBuilderBlock>
            {customFont && (
                <>
                    <ThemeBuilderBlock
                        label={t("Font URL")}
                        info={t(
                            "You can upload a Custom Font in your Theming System. Just copy & paste the URL in the field.",
                        )}
                        docUrl={docUrl}
                    >
                        <CustomFontUrl forceError={props.forceError} />
                    </ThemeBuilderBlock>
                    <div className={classes.block}>
                        <p className={classes.docBlockTextContainer}>
                            <Translate
                                source={
                                    "You need to add the font url’s domain to <0>AllowedDomains</0>, in the site's configuration. <1>Learn more about custom fonts.</1>."
                                }
                                c0={text => <code>{text}</code>}
                                c1={text => (
                                    <SmartLink to={docUrl} target={"_blank"}>
                                        {text}
                                    </SmartLink>
                                )}
                            />
                        </p>
                    </div>
                    <ThemeBuilderBlock label={t("Font Name")}>
                        <CustomFontFamily />
                    </ThemeBuilderBlock>
                </>
            )}
        </>
    );
}
