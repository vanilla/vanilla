/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { fragmentEditorClasses } from "@dashboard/appearance/fragmentEditor/FragmentEditor.classes";
import { IconHexGrid } from "@dashboard/appearance/manageIcons/IconHexGrid";
import { css, cx } from "@emotion/css";
import type { IUploadedFile } from "@library/apiv2";
import { GetAttachmentIcon, mimeTypeToAttachmentType } from "@library/content/attachments/attachmentUtils";
import { AttachmentType } from "@library/content/attachments/AttatchmentType";
import DateTime from "@library/content/DateTime";
import { DataList } from "@library/dataLists/DataList";
import { ButtonType } from "@library/forms/buttonTypes";
import { CopyLinkButton } from "@library/forms/CopyLinkButton";
import { Row } from "@library/layout/Row";
import { siteUrl } from "@library/utility/appUtils";
import { HumanFileSize } from "@library/utility/fileUtils";
import { t } from "@vanilla/i18n";

interface IProps {
    uploadedFile: IUploadedFile;
    className?: string;
}

export function FragmentEditorFileViewer(props: IProps) {
    const { uploadedFile } = props;

    const attachmentType = mimeTypeToAttachmentType(uploadedFile.type);
    const editorClasses = fragmentEditorClasses();

    return (
        <div className={cx(classes.root, props.className)}>
            <DataList
                title={t("File Information")}
                data={[
                    {
                        key: t("File Name"),
                        value: uploadedFile.name,
                    },
                    {
                        key: t("Type"),
                        value: (
                            <Row align={"center"} gap={4}>
                                <GetAttachmentIcon type={mimeTypeToAttachmentType(uploadedFile.type)} />{" "}
                                {uploadedFile.type}
                            </Row>
                        ),
                    },
                    {
                        key: t("Size"),
                        value: <HumanFileSize numBytes={uploadedFile.size} />,
                    },
                    {
                        key: t("Uploaded At"),
                        value: <DateTime timestamp={uploadedFile.dateInserted} />,
                    },
                    {
                        key: t("File URLs"),
                        value: (
                            <Row align={"center"} gap={8}>
                                <CopyLinkButton buttonType={ButtonType.INPUT} url={uploadedFile.url}>
                                    {t("Copy File URL")}
                                </CopyLinkButton>
                                <CopyLinkButton
                                    buttonType={ButtonType.INPUT}
                                    url={siteUrl(
                                        `/api/v2/media/download-by-url?url=${encodeURIComponent(uploadedFile.url)}`,
                                    )}
                                >
                                    {t("Copy Trackable Download URL")}
                                </CopyLinkButton>
                            </Row>
                        ),
                    },
                ]}
            />
            {attachmentType === AttachmentType.IMAGE && (
                <div className={classes.previewGroup}>
                    <h2>Preview</h2>
                    <div className={classes.imagePreviewContainer}>
                        <IconHexGrid className={classes.imagePreviewGrid} />
                        <img className={classes.imagePreviewImage} src={uploadedFile.url} alt={uploadedFile.name} />
                    </div>
                </div>
            )}
        </div>
    );
}

const classes = {
    root: css({
        padding: "12px 28px",
    }),
    previewGroup: css({
        marginTop: 24,
    }),
    imagePreviewContainer: css({
        background: "#fff",
        marginTop: 12,
        position: "relative",
        display: "inline-block",
    }),
    imagePreviewGrid: css({
        position: "absolute",
        top: 0,
        right: 0,
        bottom: 0,
        left: 0,
    }),
    imagePreviewImage: css({
        position: "relative",
        maxWidth: "100%",
        maxHeight: "400px",
        objectFit: "contain",
    }),
};
