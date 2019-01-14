/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";

import { t } from "@library/application";
import classNames from "classnames";
import CloseButton from "@library/components/CloseButton";
import { IFileAttachment } from "@library/components/attachments/Attachment";
import Permission from "@library/users/Permission";
import { AttachmentType } from "@library/components/attachments/AttachmentIcon";
import { getAttachmentIcon } from "@library/components/attachments";

interface IProps extends IFileAttachment {
    progress: number; // 0 to 100
    type: AttachmentType;
    size: number; // bytes
}

/**
 * Implements file attachment item
 */
export default class AttachmentLoading extends React.Component<IProps> {
    public render() {
        const { title, name, type } = this.props;
        const label = title || name;
        return (
            <div className={classNames("attachment", "isLoading", this.props.className)}>
                <div className="attachment-box attachment-loadingContent">
                    <div className="attachment-format">{getAttachmentIcon(type)}</div>
                    <div className="attachment-main">
                        <div className="attachment-title">{label}</div>
                        <div className="attachment-metas metas">
                            <span className="meta">{t("Uploading...")}</span>
                        </div>
                    </div>
                    <Permission permission="articles.add">
                        <CloseButton
                            title={t("Cancel")}
                            className="attachment-close"
                            onClick={this.props.deleteAttachment}
                        />
                    </Permission>
                </div>
                <div
                    className="attachment-loadingProgress"
                    style={{ width: `${Math.min(this.props.progress, 100)}%` }}
                />
            </div>
        );
    }
}
