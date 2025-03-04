/**
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IComment } from "@dashboard/@types/api/comment";
import CheckBox from "@library/forms/Checkbox";
import intersection from "lodash-es/intersection";
import { t } from "@vanilla/i18n";

interface IProps {
    selectableCommentIDs: Array<IComment["commentID"]>;
    checkedCommentIDs: Array<IComment["commentID"]>;
    addCheckedCommentsByIDs: (commentIDs: Array<IComment["commentID"]>) => void;
    removeCheckedCommentsByIDs: (commentIDs: Array<IComment["commentID"]>) => void;
    className?: string;
}

/**
 * Checkbox to select all comment thread items.
 */
export function CommentsSelectAll(props: IProps) {
    const { selectableCommentIDs, checkedCommentIDs, addCheckedCommentsByIDs, removeCheckedCommentsByIDs } = props;

    const allChecked =
        selectableCommentIDs.length > 0 &&
        intersection(selectableCommentIDs, checkedCommentIDs).length === selectableCommentIDs.length;

    return (
        <CheckBox
            label={t("Select All")}
            hideLabel
            checked={allChecked}
            disabled={selectableCommentIDs?.length === 0}
            className={props.className}
            onChange={(e) => {
                if (e.target.checked) {
                    addCheckedCommentsByIDs(selectableCommentIDs);
                } else {
                    removeCheckedCommentsByIDs(selectableCommentIDs);
                }
            }}
        />
    );
}
