/**
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import image from "./SentimentAnalysisLabItem.svg";
import { VanillaLabsItem } from "@dashboard/labs/VanillaLabsItem";
import { t } from "@vanilla/i18n";
import SmartLink from "@library/routing/links/SmartLink";
import Translate from "@library/content/Translate";

export function SentimentAnalysisLabItem() {
    return (
        <VanillaLabsItem
            imageUrl={image}
            labName={"sentimentAnalysis"}
            name={t("AI Sentiment Analysis & Keyword Tracking")}
            description={
                <>
                    <Translate
                        source={
                            "Use AI to analyze post content and measure the overall sentiment of your community, individual posts and specific keywords. <0>Learn more</0>"
                        }
                        c0={(content) => (
                            <SmartLink to="https://success.vanillaforums.com/kb/articles/1574-sentiment-analysis">
                                {content}
                            </SmartLink>
                        )}
                    />
                </>
            }
            notes={
                <>
                    <Translate
                        source={
                            "By enabling this feature, you consent to Azure OpenAI processing your data. Azure is a trusted subprocessor of Higher Logic. <0>See our list of subprocessors</0> and <1>review the AI Terms of Use</1>"
                        }
                        c0={(content) => (
                            <SmartLink to="https://www.higherlogic.com/legal/subprocessors/">{content}</SmartLink>
                        )}
                        c1={(content) => (
                            <SmartLink to="https://success.vanillaforums.com/kb/articles/1782-higher-logic-ai-terms-of-use">
                                {content}
                            </SmartLink>
                        )}
                    />
                </>
            }
        />
    );
}
