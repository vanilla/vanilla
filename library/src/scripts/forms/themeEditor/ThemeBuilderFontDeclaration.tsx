// /*
//  * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
//  * @copyright 2009-2019 Vanilla Forums Inc.
//  * @license GPL-2.0-only
//  */
//
// import React, { useEffect, useState } from "react";
// import { useThemeVariableField } from "@library/forms/themeEditor/ThemeBuilderContext";
// import { ThemeBuilderBlock } from "@library/forms/themeEditor/ThemeBuilderBlock";
// import { t } from "@vanilla/i18n/src";
// import { isAllowedUrl } from "@library/utility/appUtils";
// import { customFontFamily } from "@themingapi/theme/customFontFamily";
// import { CustomFontUrl } from "@themingapi/theme/CustomFontUrl";
//
// export const fontURLKey = "global.fonts.customFont.url";
//
// function urlValidation(url: any) {
//     return url ? isAllowedUrl(url.toString()) : false;
// }
//
// export function ThemeBuilderFontDeclaration() {
//     const { generatedValue, initialValue, rawValue } = useThemeVariableField(fontURLKey);
//     const [valid, setValid] = useState(false);
//
//     useEffect(() => {
//         setValid(urlValidation(generatedValue));
//     }, [generatedValue, rawValue]);
//
//     // initial value
//     useEffect(() => {
//         setValid(urlValidation(generatedValue));
//     }, []);
//
//     return (
//         <>
//             <ThemeBuilderBlock
//                 label={t("Font URL")}
//                 info={t("You can upload a Custom Font in your Theming System. Just copy & paste the URL in the field.")}
//             >
//                 <CustomFontUrl
//                     onChange={() => {
//                         setValid(urlValidation(generatedValue));
//                     }}
//                 />
//             </ThemeBuilderBlock>
//         </>
//     );
// }
