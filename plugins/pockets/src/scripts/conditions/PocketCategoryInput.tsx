/*
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useEffect, useState } from "react";
import { DashboardFormGroup } from "@dashboard/forms/DashboardFormGroup";
import { t } from "@vanilla/i18n/src";
import { CommunityCategoryInput } from "@vanilla/addon-vanilla/forms/CommunityCategoryInput";
import { IComboBoxOption } from "@library/features/search/SearchBar";
import { selectOneClasses } from "@library/forms/select/selectOneStyles";
import { inputClasses } from "@library/forms/inputStyles";
import classNames from "classnames";

export function PocketCategoryInput(props) {
    const [inheritCategory, setInheritCategory] = useState(!!props.inherit);
    const [category, setCategory] = useState(props.category);

    const classes = selectOneClasses();
    inputClasses().applyInputCSSRules();

    return (
        <DashboardFormGroup label={t("Category")} tag={"div"}>
            <div className="input-wrap">
                <div className={classes.inputWrap}>
                    <CommunityCategoryInput
                        label={null}
                        onChange={(option: IComboBoxOption) => {
                            setCategory(option.value);
                        }}
                    />
                </div>
                <input name={props.fieldName} type={"hidden"} value={category} />
                <div className={classNames("checkbox", classes.checkBoxAfterInput)}>
                    <label>
                        <input
                            type="checkbox"
                            name={"InheritCategory"}
                            onClick={() => {
                                setInheritCategory(!inheritCategory);
                            }}
                            checked={inheritCategory}
                        />
                        {t("Apply to subcategories")}
                    </label>
                </div>
            </div>
        </DashboardFormGroup>
    );
}
