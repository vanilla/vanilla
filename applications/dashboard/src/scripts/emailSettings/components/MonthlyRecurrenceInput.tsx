/**
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */
import { RecurrenceSetPosition } from "@dashboard/emailSettings/EmailSettings.types";
import { useFormGroup } from "@dashboard/forms/DashboardFormGroupContext";
import { css } from "@emotion/css";
import { NestedSelect } from "@library/forms/nestedSelect";
import { globalVariables } from "@library/styles/globalStyleVars";
import { useThemeCache } from "@library/styles/styleUtils";
import { t } from "@vanilla/i18n";
import { IControlProps } from "@vanilla/json-schema-forms";

const inputClasses = useThemeCache(() => {
    const globalVars = globalVariables();

    return {
        root: css({
            display: "flex",
            gap: globalVars.gutter.size,
            flexWrap: "wrap",
        }),

        input: css({
            marginTop: "0 !important",
            flex: "1 1 140px",
        }),
    };
});

export default function MonthlyRecurrenceInput(props: IControlProps) {
    const { instance: value, onChange } = props;
    const setPosition: RecurrenceSetPosition = value?.setPosition;
    const dayOfWeek: number = value?.dayOfWeek;

    const classes = inputClasses();

    const formGroup = useFormGroup();

    const { labelID } = formGroup;

    return (
        <div className={classes.root}>
            <NestedSelect
                ariaDescribedBy={labelID}
                ariaLabel={t("Set position")}
                classes={{ root: classes.input }}
                required
                value={setPosition}
                onChange={(newVal) => {
                    onChange({ ...value, setPosition: newVal });
                }}
                options={[
                    { value: RecurrenceSetPosition.FIRST, label: t("First") },
                    { value: RecurrenceSetPosition.LAST, label: t("Last") },
                ]}
            />
            <NestedSelect
                ariaDescribedBy={labelID}
                ariaLabel={t("Day of the week")}
                classes={{ root: classes.input }}
                required
                value={dayOfWeek}
                onChange={(newVal) => {
                    onChange({ ...value, dayOfWeek: newVal });
                }}
                options={[
                    { value: 1, label: t("weekday.long.1", "Monday") },
                    { value: 2, label: t("weekday.long.2", "Tuesday") },
                    { value: 3, label: t("weekday.long.3", "Wednesday") },
                    { value: 4, label: t("weekday.long.4", "Thursday") },
                    { value: 5, label: t("weekday.long.5", "Friday") },
                    { value: 6, label: t("weekday.long.6", "Saturday") },
                    { value: 7, label: t("weekday.long.7", "Sunday") },
                ]}
            />
        </div>
    );
}
