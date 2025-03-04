/**
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import image from "./AiSuggestedAnswersLabItem.svg";
import { VanillaLabsItem } from "@dashboard/labs/VanillaLabsItem";
import { t } from "@vanilla/i18n";
import React from "react";
import SmartLink from "@library/routing/links/SmartLink";
import { css } from "@emotion/css";

export function AiSuggestedAnswersLabItem() {
    return (
        <VanillaLabsItem
            imageUrl={image}
            labName={"aiSuggestions"}
            name={t("AI Suggested Answers")}
            description={t(
                "Leverage AI to comb through your KB and community content to provide suggested answers to user questions and ensure community members receive timely support.",
                "Leverage AI to comb through your KB and community content to provide suggested answers to user questions and ensure community members receive timely support.",
            )}
            notes={
                <>
                    {t(
                        "By enabling this feature, you are consenting to allow Azure OpenAI to process your data. Azure is a trusted subprocessor of Higher Logic.",
                    )}{" "}
                    <SmartLink to="https://www.higherlogic.com/legal/subprocessors">{t("Read more")}</SmartLink>
                </>
            }
        />
    );
}
