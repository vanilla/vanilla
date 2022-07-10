/*
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useEffect, useState } from "react";
import { DashboardFormGroup } from "@dashboard/forms/DashboardFormGroup";
import { t } from "@vanilla/i18n/src";
import CommunityCategoryInput from "@vanilla/addon-vanilla/forms/CommunityCategoryInput";
import { IComboBoxOption } from "@library/features/search/SearchBar";
import { selectOneClasses } from "@library/forms/select/selectOneStyles";
import { inputClasses } from "@library/forms/inputStyles";
import classNames from "classnames";
import { DashboardCheckBox } from "@dashboard/forms/DashboardCheckBox";
import { DashboardRadioGroup } from "@dashboard/forms/DashboardRadioGroups";
import { dashboardClasses } from "@dashboard/forms/dashboardStyles";

export function PocketCategoryInput(props) {
    const [category, setCategory] = useState(
        props.label && props.initialValue
            ? {
                  label: props.label,
                  value: props.initialValue,
              }
            : undefined,
    );

    const [inheritCategory, setInheritCategory] = useState(props.inheritCategory);

    const classes = selectOneClasses();
    inputClasses().applyInputCSSRules();

    const hasCategory = category && category.value;

    return (
        <DashboardFormGroup label={t("Category")} tag={"div"}>
            <div className="input-wrap">
                <div className={classes.inputWrap}>
                    <CommunityCategoryInput
                        placeholder={t("Select...")}
                        label={null}
                        className={dashboardClasses().selectOne}
                        onChange={(option) => {
                            setCategory(option[0]);
                        }}
                        value={category ? [category] : []}
                    />
                </div>
                <input name={props.fieldName} type={"hidden"} value={hasCategory ?? ""} />
                <div className={classNames("checkbox", classes.checkBoxAfterInput)}>
                    <DashboardRadioGroup>
                        <DashboardCheckBox
                            checked={hasCategory ? inheritCategory : false}
                            onChange={() => {
                                setInheritCategory(!inheritCategory);
                            }}
                            label={t("Apply to subcategories")}
                            disabled={!hasCategory}
                        />
                    </DashboardRadioGroup>
                </div>
                <input name={"InheritCategory"} type={"hidden"} value={`${inheritCategory ? 1 : 0}`} />
            </div>
        </DashboardFormGroup>
    );
}
