/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import classNames from "classnames";
import CloseButton from "@library/components/CloseButton";
import { IFileAttachment } from "@library/components/attachments/Attachment";
import Permission from "@library/users/Permission";
import { t } from "@library/application";
import { fileUploadError } from "@library/components/icons/fileTypes";
import { FOCUS_CLASS } from "@library/embeds";

interface IProps extends IFileAttachment {
    message: string;
}

/**
 * Implements file attachment with error
 */
export default class AttachmentError extends React.Component<IProps> {
    public render() {
        const { title, name } = this.props;
        const label = title || name;
        return (
            <div className={classNames("attachment", "hasError", this.props.className, FOCUS_CLASS)}>
                <div className="attachment-box">
                    <div className="attachment-format">{fileUploadError()}</div>
                    <div className="attachment-main">
                        <div className="attachment-title">{this.props.message}</div>
                        {label && (
                            <div className="attachment-metas metas">
                                <span className="meta">{label}</span>
                            </div>
                        )}
                    </div>
                    <CloseButton
                        title={t("Cancel")}
                        className="attachment-close"
                        onClick={this.props.deleteAttachment}
                    />
                </div>
            </div>
        );
    }
}
