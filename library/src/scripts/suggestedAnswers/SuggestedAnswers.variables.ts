/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { userContentVariables } from "@library/content/UserContent.variables";
import { Variables } from "@library/styles/Variables";
import { globalVariables } from "@library/styles/globalStyleVars";
import { BorderType } from "@library/styles/styleHelpersBorders";
import { useThemeCache, variableFactory } from "@library/styles/styleUtils";
import { IconType } from "@vanilla/icons";
import { RecordID } from "@vanilla/utils";

export interface ISuggestedAnswer {
    aiSuggestionID: RecordID;
    format: string;
    type: string;
    documentID: RecordID;
    url: string;
    title: string;
    summary: string;
    hidden?: boolean;
    commentID?: RecordID;
    sourceIcon?: IconType;
}

export const suggestedAnswersVariables = useThemeCache(() => {
    const makeThemeVars = variableFactory("suggestedAnswers");
    const globalVars = globalVariables();
    const userContentVars = userContentVariables();

    /**
     * @varGroup suggestedAnswers.box
     * @expand box
     */
    const box = makeThemeVars(
        "box",
        Variables.box({
            borderType: BorderType.SEPARATOR,
            background: {
                color: globalVars.mainColors.bg,
            },
            spacing: { all: 0 },
        }),
    );

    /**
     * @varGroup suggestedAnswers.font
     * @expand font
     */
    const font = makeThemeVars(
        "font",
        Variables.font({
            color: globalVars.mainColors.fg,
            size: userContentVars.fonts.size,
            lineHeight: 1.5,
        }),
    );

    /**
     * @varGroup suggestedAnswers.item
     */
    const item = makeThemeVars("item", {
        /**
         * @varGroup suggestedAnswers.item.box
         * @expand box
         */
        box: Variables.box({
            background: box.background,
            borderType: BorderType.BORDER,
            border: Variables.border({
                ...globalVars.border,
                color: globalVars.mainColors.primary,
                radius: 0,
            }),
            spacing: {
                vertical: globalVars.gutter.half,
                left: globalVars.gutter.size,
                right: globalVars.gutter.half,
            },
        }),
        /**
         * @varGroup suggestedAnswers.item.font
         * @expand font
         */
        font: Variables.font(font),
        /**
         * @varGroup suggestedAnswers.item.title
         * @expand font
         */
        title: Variables.font({
            weight: globalVars.fonts.weights.bold,
            color: globalVars.mainColors.fg,
        }),
    });

    /**
     * @varGroup suggestedAnswers.acceptAnswer
     */
    const acceptAnswer = makeThemeVars("acceptAnswer", {
        /**
         * @varGroup suggestedAnswers.acceptAnswer.font
         * @expand font
         */
        font: Variables.font({}),
    });

    return { box, font, item };
});
