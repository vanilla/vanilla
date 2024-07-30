/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { DashboardFormLabel } from "@dashboard/forms/DashboardFormLabel";
import Translate from "@library/content/Translate";
import CheckBox from "@library/forms/Checkbox";
import CheckboxGroup from "@library/forms/CheckboxGroup";
import SmartLink from "@library/routing/links/SmartLink";
import { t } from "@library/utility/appUtils";
import { IFieldError } from "@vanilla/json-schema-forms/src/types";
import pull from "lodash-es/pull";
import { ChangeEvent } from "react";

interface IProps {
    options: Record<string, string>;
    value?: string[];
    onChange?: (val: any) => void;
    errors?: IFieldError[];
}

/**
 * Custom control input for the enabling sources
 */
export function ManageSourcesInput(props: IProps) {
    const { options, value = [], onChange } = props;

    const handleChange = (event: ChangeEvent<HTMLInputElement>, sourceID: string) => {
        const { checked } = event.target;
        const tmpValue = [...value];
        if (checked) {
            tmpValue.push(sourceID);
        } else {
            pull(tmpValue, sourceID);
        }
        onChange?.(tmpValue);
    };

    return (
        <>
            <DashboardFormLabel
                label={t("Manage Sources")}
                description={
                    <Translate
                        source="Sources that are selected here will be available to AI Suggested Answers. Add additional sources through Federated Search. <0/>"
                        c0={
                            <SmartLink to="https://success.vanillaforums.com/kb/articles/1606-ai-suggested-answers#adding-additional-sources">
                                {t("Learn more.")}
                            </SmartLink>
                        }
                    />
                }
            />
            <CheckboxGroup>
                {Object.entries(options).map(([sourceID, label]) => (
                    <CheckBox
                        key={sourceID}
                        label={label}
                        labelBold={false}
                        checked={value.includes(sourceID)}
                        onChange={(event: ChangeEvent<HTMLInputElement>) => handleChange(event, sourceID)}
                    />
                ))}
            </CheckboxGroup>
        </>
    );
}
