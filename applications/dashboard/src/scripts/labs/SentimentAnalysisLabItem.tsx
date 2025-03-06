/**
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import image from "./SentimentAnalysisLabItem.svg";
import { VanillaLabsItem } from "@dashboard/labs/VanillaLabsItem";
import { t } from "@vanilla/i18n";
import React from "react";
import SmartLink from "@library/routing/links/SmartLink";
import { css } from "@emotion/css";

export function SentimentAnalysisLabItem() {
    return (
        <VanillaLabsItem
            imageUrl={image}
            labName={"sentimentAnalysis"}
            name={t("AI Sentiment Analysis & Keyword Tracking")}
            description={
                <>
                    {t(
                        "Use AI to analyze post content and measure the overall sentiment of your community, individual posts and specific keywords.",
                        "Use AI to analyze post content and measure the overall sentiment of your community, individual posts and specific keywords.",
                    )}{" "}
                    <SmartLink to="https://success.vanillaforums.com/kb/articles/1574-sentiment-analysis">
                        {t("Learn more")}
                    </SmartLink>
                </>
            }
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
