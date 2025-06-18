/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import React, { FunctionComponent } from "react";
import { bookmarkToggleClasses } from "@library/bookmarkToggle/BookmarkToggle.styles";
import ActsAsCheckbox from "@library/forms/ActsAsCheckbox";
import { cx } from "@emotion/css";
import { t } from "@vanilla/i18n";
import { Icon } from "@vanilla/icons";

interface IBookmarkToggleProps {
    bookmarked: boolean;
    onToggleBookmarked: () => any;
    classNames?: string;
}

const BookmarkToggle: FunctionComponent<IBookmarkToggleProps> = ({ bookmarked, onToggleBookmarked, classNames }) => {
    const { icon, iconChecked, iconDisabled } = bookmarkToggleClasses.useAsHook();

    return (
        <ActsAsCheckbox checked={bookmarked} onChange={onToggleBookmarked} title={t("Bookmark")}>
            {(props) => (
                <Icon
                    icon={bookmarked ? "bookmark-filled" : "bookmark-empty"}
                    className={cx(icon, classNames, {
                        [iconChecked]: bookmarked,
                    })}
                />
            )}
        </ActsAsCheckbox>
    );
};

export default BookmarkToggle;
