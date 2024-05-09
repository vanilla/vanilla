/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc
 * @license Proprietary
 */

import { css } from "@emotion/css";
import { StoryContent } from "@library/storybook/StoryContent";
import { StoryHeading } from "@library/storybook/StoryHeading";
import { StoryParagraph } from "@library/storybook/StoryParagraph";
import { Mixins } from "@library/styles/Mixins";
import { Icon } from "@vanilla/icons";
import { useState } from "react";
import { DurationPicker as DurationPickerComponent } from "./DurationPicker";
import { DurationPickerUnit, IDurationValue } from "./DurationPicker.types";

export default {
    title: "Components/DurationPicker",
};

const durationClass = css({
    ...Mixins.margin({ vertical: 16 }),
});

const messageClass = css({
    fontSize: "0.875em",
    fontWeight: "bold",
});

export function DurationPicker() {
    const [valueOne, setValueOne] = useState<IDurationValue>({ length: 0, unit: DurationPickerUnit.DAYS });
    const [valueTwo, setValueTwo] = useState<IDurationValue>({ length: 0, unit: DurationPickerUnit.DAYS });
    const [valueThree, setValueThree] = useState<IDurationValue>({ length: 0, unit: DurationPickerUnit.DAYS });
    const [messageOne, setMessageOne] = useState<string | undefined>();
    const [messageTwo, setMessageTwo] = useState<string | undefined>();
    const [messageThree, setMessageThree] = useState<string | undefined>();

    return (
        <StoryContent>
            <StoryHeading>Duration Picker</StoryHeading>
            <StoryParagraph>
                The duration picker is a component that allows users to select a duration with length and unit of time.
            </StoryParagraph>
            <DurationPickerComponent
                onChange={(newVal) => {
                    setValueOne(newVal);
                    setMessageOne(`Duration: ${newVal.length} ${newVal.unit}`);
                }}
                value={valueOne}
                className={durationClass}
            />
            <StoryParagraph className={messageClass}>{messageOne ?? "Duration: "}</StoryParagraph>
            <DurationPickerComponent
                onChange={setValueTwo}
                value={valueTwo}
                className={durationClass}
                submitButton={{
                    children: "Submit",
                    onClick: () => setMessageTwo(`Duration: ${valueTwo.length} ${valueTwo.unit}`),
                }}
            />
            <StoryParagraph className={messageClass}>{messageTwo ?? "Duration: "}</StoryParagraph>
            <DurationPickerComponent
                onChange={setValueThree}
                value={valueThree}
                className={durationClass}
                submitButton={{
                    children: <Icon icon="data-send" />,
                    onClick: () => setMessageThree(`Duration: ${valueThree.length} ${valueThree.unit}`),
                    tooltip: "Save Duration",
                }}
            />
            <StoryParagraph className={messageClass}>{messageThree ?? "Duration: "}</StoryParagraph>
        </StoryContent>
    );
}
