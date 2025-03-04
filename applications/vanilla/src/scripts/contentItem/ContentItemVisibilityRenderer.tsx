/**
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { BottomChevronIcon } from "@library/icons/common";
import ContentItemClasses from "@vanilla/addon-vanilla/contentItem/ContentItem.classes";
import { t } from "@vanilla/i18n";

export function ContentItemVisibilityRenderer(props: {
    isPostHidden: boolean;
    contentText: string;
    onVisibilityChange: (isHidden: boolean) => void;
}) {
    const { isPostHidden, contentText, onVisibilityChange } = props;

    return (
        <div className={ContentItemClasses().ignoredUserPostHeader(!isPostHidden)}>
            <Button buttonType={ButtonTypes.ICON_COMPACT} onClick={() => onVisibilityChange(!isPostHidden)}>
                <BottomChevronIcon rotate={isPostHidden ? 0 : 180} />
            </Button>
            <span>{contentText}</span>
            <span></span>
            <Button buttonType={ButtonTypes.TEXT_PRIMARY} onClick={() => onVisibilityChange(!isPostHidden)}>
                {isPostHidden ? t("Show") : t("Hide")}
            </Button>
        </div>
    );
}
