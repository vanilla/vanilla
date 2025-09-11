/**
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import image from "./AiSuggestedAnswersLabItem.svg";
import { VanillaLabsItem } from "@dashboard/labs/VanillaLabsItem";
import { t } from "@vanilla/i18n";
import SmartLink from "@library/routing/links/SmartLink";
import Translate from "@library/content/Translate";

export function AiSuggestedAnswersLabItem() {
    return (
        <VanillaLabsItem
            imageUrl={image}
            labName={"aiSuggestions"}
            name={t("AI Suggested Answers")}
            description={
                <>
                    <p>
                        <Translate
                            source={
                                "Leverage AI to comb through your KB and community content to provide suggested answers to user questions and ensure community members receive timely support. <0>Learn More</0>."
                            }
                            c0={(content) => (
                                <SmartLink to="https://success.vanillaforums.com/kb/articles/1606-ai-suggested-answers-beta">
                                    {content}
                                </SmartLink>
                            )}
                        />
                    </p>
                    <p>
                        <Translate
                            source={"N.B. AI Suggested Answers is only available on our new <0>Post Pages</0>"}
                            c0={(content) => (
                                <SmartLink to="https://success.vanillaforums.com/kb/articles/1717-overview-of-custom-page-layouts#post-page-layouts">
                                    {content}
                                </SmartLink>
                            )}
                        />
                    </p>
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
