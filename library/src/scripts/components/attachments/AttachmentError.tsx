/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import classNames from "classnames";
import CloseButton from "@library/components/CloseButton";
import { IFileAttachment } from "@library/components/attachments/Attachment";
import { t } from "@library/application";
import { fileUploadError } from "@library/components/icons/fileTypes";
import { FOCUS_CLASS } from "@library/embeds";
import { uniqueIDFromPrefix } from "@library/componentIDs";
import { attachmentClasses } from "@library/styles/attachmentStyles";
import { attachmentIconClasses } from "@library/styles/attachmentIconsStyles";

interface IProps extends IFileAttachment {
    message: string;
}

/**
 * Implements file attachment with error
 */
export default class AttachmentError extends React.Component<IProps> {
    private descrID = uniqueIDFromPrefix("attachmentError");

    public render() {
        const { title, name } = this.props;
        const label = title || name;
        const messages = this.props.message.split("\n");

        const [errorTitle, ...errorBodyMessages] = messages;
        const showFileName = label && errorBodyMessages.length === 0;
        const classes = attachmentClasses();
        const iconClasses = attachmentIconClasses();

        return (
            <div
                className={classNames("attachment", "hasError", classes.root, this.props.className, FOCUS_CLASS)}
                tabIndex={0}
                aria-describedby={this.descrID}
                aria-label={t("Error")}
                role="alert"
                aria-live="assertive"
            >
                <div className={classNames("attachment-box", classes.box)}>
                    <div className={classNames("attachment-format", classes.format)}>
                        {fileUploadError(iconClasses.error)}
                    </div>
                    <div className={classNames("attachment-main", classes.main)}>
                        <div id={this.descrID} className={classNames("attachment-title", classes.title)}>
                            {errorTitle}
                        </div>
                        <div className={classNames("attachment-body")}>
                            {errorBodyMessages.map((message, index) => {
                                return (
                                    <>
                                        {message}
                                        {index !== errorBodyMessages.length - 1 ? <br /> : null}
                                    </>
                                );
                            })}
                        </div>
                        {showFileName && (
                            <div className={classNames("attachment-metas", "metas", classes.metas)}>
                                <span className="meta">{label}</span>
                            </div>
                        )}
                    </div>
                    <CloseButton
                        title={t("Cancel")}
                        className={classNames("attachment-close", classes.close)}
                        onClick={this.props.deleteAttachment}
                    />
                </div>
            </div>
        );
    }
}
