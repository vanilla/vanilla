/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { cx } from "@emotion/css";
import { noticeClasses } from "@library/metas/Notice.styles";

import React, { FunctionComponent } from "react";

interface INoticeProps {
    className?: string;
}

const Notice: FunctionComponent<INoticeProps> = ({ children, className }) => {
    const classes = noticeClasses();
    return <div className={cx(classes.root, className)}>{children}</div>;
};

export default Notice;
