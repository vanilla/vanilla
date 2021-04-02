/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { noticeClasses } from "@library/metas/Notice.styles";

import React, { FunctionComponent } from "react";

interface INoticeProps {}

const Notice: FunctionComponent<INoticeProps> = ({ children }) => {
    const classes = noticeClasses();
    return <div className={classes.root}>{children}</div>;
};

export default Notice;
