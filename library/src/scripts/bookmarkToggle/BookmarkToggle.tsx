/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import React, { FunctionComponent } from "react";
import { BookmarkIcon } from "@library/icons/common";
import { bookmarkToggleClasses } from "@library/bookmarkToggle/BookmarkToggle.styles";
import ActsAsCheckbox from "@library/forms/ActsAsCheckbox";
import { cx } from "@emotion/css";

interface IProps {
    bookmarked: boolean;
    onToggleBookmarked: () => any;
}

const BookmarkToggle: FunctionComponent<IProps> = ({ bookmarked, onToggleBookmarked }) => {
    const { icon, iconChecked, iconDisabled } = bookmarkToggleClasses();

    return (
        <ActsAsCheckbox checked={bookmarked} onChange={onToggleBookmarked}>
            {({ disabled }) => (
                <BookmarkIcon
                    className={cx(icon, {
                        [iconChecked]: bookmarked && !disabled,
                        [iconDisabled]: disabled,
                    })}
                />
            )}
        </ActsAsCheckbox>
    );
};

export default BookmarkToggle;
