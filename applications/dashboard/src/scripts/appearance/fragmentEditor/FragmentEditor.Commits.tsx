/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { FragmentCommitMeta } from "@dashboard/appearance/fragmentEditor/FragmentCommitMeta";
import { fragmentEditorClasses } from "@dashboard/appearance/fragmentEditor/FragmentEditor.classes";
import { useFragmentEditor } from "@dashboard/appearance/fragmentEditor/FragmentEditor.context";
import { FragmentEditorDiffViewer } from "@dashboard/appearance/fragmentEditor/FragmentEditorDiffViewer";
import { FragmentsApi } from "@dashboard/appearance/fragmentEditor/FragmentsApi";
import {
    useActiveRevisionQuery,
    useDeleteFragmentDraftMutation,
    useFragmentCommits,
} from "@dashboard/appearance/fragmentEditor/FragmentsApi.hooks";
import { css, cx } from "@emotion/css";
import { userContentClasses } from "@library/content/UserContent.styles";
import Button from "@library/forms/Button";
import { ButtonType } from "@library/forms/buttonTypes";
import { Row } from "@library/layout/Row";
import ButtonLoader from "@library/loaders/ButtonLoader";
import { QueryLoader } from "@library/loaders/QueryLoader";
import { singleBorder } from "@library/styles/styleHelpersBorders";
import { useQuery } from "@tanstack/react-query";
import { t } from "@vanilla/i18n";
import { useState } from "react";
import ReactMarkdown from "react-markdown";

export function FragmentEditorCommits() {
    const { fragmentUUID } = useFragmentEditor();
    const commitsQuery = useFragmentCommits(fragmentUUID);

    const editor = useFragmentEditor();

    return (
        <div className={classes.root}>
            <QueryLoader
                query={commitsQuery}
                success={(commits) => {
                    return (
                        <>
                            {(editor.formIsDirty || !fragmentUUID) && <UnsavedChangesItem />}
                            {commits.data.length > 0 && (
                                <div className={classes.list}>
                                    {commits.data.map((fragment) => {
                                        return <CommitItem key={fragment.fragmentRevisionUUID} fragment={fragment} />;
                                    })}
                                </div>
                            )}
                        </>
                    );
                }}
            />
        </div>
    );
}

function UnsavedChangesItem() {
    const [showCommitModal, setShowCommitModal] = useState(false);

    const editor = useFragmentEditor();

    return (
        <div className={classes.list}>
            <div className={classes.item}>
                <Row align={"center"} gap={16} justify={"space-between"}>
                    <div>
                        <strong className={classes.itemTitle}>{t("Unsaved Changes")}</strong>
                    </div>
                    <Button
                        buttonType={ButtonType.INPUT}
                        onClick={() => {
                            setShowCommitModal(true);
                        }}
                    >
                        {!editor.fragmentUUID ? t("Save Initial Commit") : t("Compare & Commit")}
                    </Button>
                </Row>
            </div>
            {showCommitModal && (
                <FragmentEditorDiffViewer
                    modifiedRevisionUUID={"latest"}
                    onClose={() => {
                        setShowCommitModal(false);
                    }}
                />
            )}
        </div>
    );
}

function CommitItem(props: { fragment: FragmentsApi.Fragment }) {
    const { fragment } = props;
    let message = fragment.commitMessage;
    if (!message && fragment.status === "draft") {
        message = "Latest Draft";
    }
    const [showDiff, setShowDiff] = useState(false);

    const deleteDraftMutation = useDeleteFragmentDraftMutation({
        fragmentUUID: fragment.fragmentUUID,
        fragmentRevisionUUID: fragment.fragmentRevisionUUID,
    });

    return (
        <div key={fragment.fragmentRevisionUUID} className={classes.item}>
            <Row align={"center"} gap={16} justify={"space-between"}>
                <div>
                    <strong className={classes.itemTitle}>{message}</strong>
                    <FragmentCommitMeta fragment={fragment} className={classes.itemMetas} />
                </div>
                {fragment.status === "draft" && (
                    <Row align={"center"} gap={8}>
                        <Button
                            buttonType={ButtonType.INPUT}
                            disabled={deleteDraftMutation.isLoading}
                            onClick={() => deleteDraftMutation.mutate()}
                        >
                            {deleteDraftMutation.isLoading ? <ButtonLoader /> : t("Delete Draft")}
                        </Button>
                        <Button buttonType={ButtonType.INPUT} onClick={() => setShowDiff(true)}>
                            {t("Compare & Commit")}
                        </Button>
                    </Row>
                )}
                {fragment.status === "past-revision" && (
                    <Button buttonType={ButtonType.INPUT} onClick={() => setShowDiff(true)}>
                        {t("Compare")}
                    </Button>
                )}
            </Row>
            {fragment.commitDescription && (
                <ReactMarkdown className={cx(userContentClasses().root, classes.itemDescription)}>
                    {fragment.commitDescription}
                </ReactMarkdown>
            )}
            {showDiff && (
                <FragmentEditorDiffViewer
                    onClose={() => setShowDiff(false)}
                    modifiedRevisionUUID={fragment.fragmentRevisionUUID}
                />
            )}
        </div>
    );
}

const classes = {
    root: css({
        padding: "12px 24px",
        overflowY: "auto",
        WebkitOverflowScrolling: "touch",
        maxHeight: "100%",
        height: "100%",
    }),
    list: css({
        display: "flex",
        flexDirection: "column",
        border: singleBorder(),
        borderRadius: 6,
        marginBottom: 16,
    }),
    item: css({
        borderBottom: singleBorder(),
        padding: 16,

        "&:last-child": {
            borderBottom: "none",
        },
    }),
    itemTitle: css({
        fontWeight: 600,
    }),
    itemDescription: css({
        marginTop: 8,
    }),
    itemMetas: css({
        marginTop: 4,
    }),
};
