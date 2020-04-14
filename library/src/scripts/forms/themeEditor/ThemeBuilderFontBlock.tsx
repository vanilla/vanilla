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
import { CustomFontFamily } from "@themingapi/theme/CustomFontFamily";
import { CustomFontUrl } from "@themingapi/theme/CustomFontUrl";
import { themeBuilderClasses } from "@library/forms/themeEditor/ThemeBuilder.styles";
import { DocumentationIcon, WarningIcon } from "@library/icons/common";
import Translate from "@library/content/Translate";
import SmartLink from "@library/routing/links/SmartLink";
import ScreenReaderContent from "@library/layout/ScreenReaderContent";

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
                        docBlock={
                            <>
                                <WarningIcon />
                                <p className={classes.docBlockText}>
                                    <Translate
                                        source={
                                            "You need to add the font url’s domain to your <0>AllowedDomains</0> configuration. <1>See documentation for details.</1>"
                                        }
                                        c0={text => <code>{text}</code>}
                                        c1={text => (
                                            <SmartLink to={docUrl} className={classes.documentationIconLink}>
                                                <ScreenReaderContent>
                                                    {t("Custom Font Documentation.")}
                                                </ScreenReaderContent>
                                                <DocumentationIcon />
                                            </SmartLink>
                                        )}
                                    />
                                </p>
                            </>
                        }
                    >
                        <CustomFontUrl forceError={props.forceError} />
                    </ThemeBuilderBlock>
                    <ThemeBuilderBlock label={t("Font Name")}>
                        <CustomFontFamily />
                    </ThemeBuilderBlock>
                </>
            )}
        </>
    );
}
