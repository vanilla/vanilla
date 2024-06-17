/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { css } from "@emotion/css";
import SelectBox, { ISelectBoxItem } from "@library/forms/select/SelectBox";
import { metasVariables } from "@library/metas/Metas.variables";
import { Mixins } from "@library/styles/Mixins";
import { globalVariables } from "@library/styles/globalStyleVars";
import { styleUnit } from "@library/styles/styleUnit";
import { t } from "@vanilla/i18n";

export interface ISort {
    sort: ISelectBoxItem["value"];
}

interface IProps {
    sortOptions: ISelectBoxItem[];
    defaultSort?: ISelectBoxItem;
    selectedSort?: ISelectBoxItem;
    onChange?: (option: ISelectBoxItem) => void;
}

const sort = css({
    display: "flex",
    flexWrap: "wrap",
    ...Mixins.margin({
        all: 0,
    }),
});

const sortLabel = css({
    alignSelf: "center",
    marginRight: styleUnit(6),
    ...Mixins.font({
        color: metasVariables().font.color,
        weight: globalVariables().fonts.weights.normal,
    }),
});

export function Sort(props: IProps) {
    return (
        <label className={sort}>
            <span id={"sortID"} className={sortLabel}>
                {`${t("Sort By")}: `}
            </span>
            <SelectBox
                options={props.sortOptions}
                value={props.selectedSort ?? props.defaultSort ?? props.sortOptions[0]}
                onChange={props.onChange}
                describedBy={"sortID"}
                widthOfParent={false}
                renderLeft={false}
                verticalPadding={false}
            />
        </label>
    );
}
