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
import { inputClasses, inputMixin } from "@library/forms/inputStyles";
import classNames from "classnames";
import { DashboardCheckBox } from "@dashboard/forms/DashboardCheckBox";
import { DashboardRadioGroup } from "@dashboard/forms/DashboardRadioGroups";

export function PocketCategoryInput(props) {
    const [category, setCategory] = useState(
        props.name && props.value
            ? {
                  label: props.name,
                  value: props.value,
              }
            : undefined,
    );

    const [inheritCategory, setInheritCategory] = useState(props.inheritCategory === 1);

    const classes = selectOneClasses();
    inputClasses().applyInputCSSRules();

    const hasCategory = category && category.value;

    return (
        <DashboardFormGroup label={t("Category")} tag={"div"}>
            <div className="input-wrap">
                <div className={classes.inputWrap}>
                    <CommunityCategoryInput
                        label={null}
                        onChange={(option: IComboBoxOption) => {
                            setCategory(option);
                        }}
                        value={category}
                    />
                </div>
                <input name={props.fieldName} type={"hidden"} value={hasCategory ?? undefined} />
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
