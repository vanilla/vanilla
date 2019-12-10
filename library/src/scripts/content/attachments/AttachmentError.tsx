/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { IFileAttachment } from "@library/content/attachments/Attachment";
import { FOCUS_CLASS } from "@library/embeddedContent/embedService";
import { t } from "@library/utility/appUtils";
import { attachmentClasses } from "@library/content/attachments/attachmentStyles";
import { uniqueIDFromPrefix } from "@library/utility/idUtils";
import { metasClasses } from "@library/styles/metasStyles";
import CloseButton from "@library/navigation/CloseButton";
import { attachmentIconClasses } from "@library/content/attachments/attachmentIconsStyles";
import classNames from "classnames";
import { EmbedContainer, EmbedContainerSize } from "@library/embeddedContent/EmbedContainer";
import { ErrorIcon } from "@library/icons/common";

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
        const classesMetas = metasClasses();

        return (
            <EmbedContainer
                className={classNames("hasError", FOCUS_CLASS)}
                size={EmbedContainerSize.SMALL}
                tabIndex={0}
                aria-describedby={this.descrID}
                aria-label={t("Error")}
                role="alert"
                aria-live="assertive"
            >
                <div className={classNames("attachment-box", classes.box)}>
                    <div className={classNames("attachment-format", classes.format)}>
                        <ErrorIcon className={iconClasses.error} />
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
                                <span className={classesMetas.meta}>{label}</span>
                            </div>
                        )}
                    </div>
                    <CloseButton
                        title={t("Cancel")}
                        className={classNames("attachment-close", classes.close)}
                        onClick={this.props.deleteAttachment}
                    />
                </div>
            </EmbedContainer>
        );
    }
}
