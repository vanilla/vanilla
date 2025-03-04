/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import { css } from "@emotion/css";
import { CollapsableContent } from "@library/content/CollapsableContent";
import Translate from "@library/content/Translate";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { LeftChevronCompactIcon } from "@library/icons/common";
import Heading from "@library/layout/Heading";
import { PageHeadingBox } from "@library/layout/PageHeadingBox";
import Modal from "@library/modal/Modal";
import ModalSizes from "@library/modal/ModalSizes";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { globalVariables } from "@library/styles/globalStyleVars";
import { CommentItem } from "@vanilla/addon-vanilla/comments/CommentItem";
import { useNestedCommentContext } from "@vanilla/addon-vanilla/comments/NestedCommentContext";
import { ICommentEditorRefHandle } from "@vanilla/addon-vanilla/comments/CommentEditor";
import { CommentReply } from "@vanilla/addon-vanilla/comments/CommentReply";
import type { IThreadItem } from "@vanilla/addon-vanilla/comments/NestedCommentTypes";
import { t } from "@vanilla/i18n/";
import { useEffect, useRef } from "react";

interface IProps {
    threadItem: IThreadItem & { type: "comment" };
    visibility: boolean;
    onVisibilityChange: (visibility: boolean) => void;
}

const mobileReplyModalClasses = () => {
    const global = globalVariables();
    const modalRoot = css({
        background: "#F1F1F1",
    });
    const modalLayout = css({
        paddingInline: global.spacer.panelComponent,
        display: "grid",
        gridTemplateColumns: "1fr",
        gridTemplateRows: "40px auto auto 50vh",
    });

    const header = css({
        display: "grid",
        gridTemplateColumns: "32px 1fr",
        alignItems: "center",
        marginInlineStart: -global.spacer.panelComponent,
        "& svg": {
            transform: "scale(.75)",
        },
    });

    const commentWrapper = css({
        position: "relative",
        background: ColorsUtils.colorOut(global.elementaryColors.white),
        paddingInline: global.spacer.panelComponent,
        borderRadius: 8,
        marginBottom: 16,
    });

    const editorContainer = css({
        "--expanded": "calc(45dvh - (40px + 150px))",
        "--collapsed": "100px",
        background: ColorsUtils.colorOut(global.elementaryColors.white),
        minHeight: "var(--collapsed)",
        maxHeight: "var(--expanded)",
        overflowX: "hidden",
        overflowY: "auto",
        height: "var(--expanded)",
        "& .collapsed": {
            height: "var(--collapsed)",
        },
    });

    const behindKeyboard = css({
        display: "flex",
        justifyContent: "center",
        alignItems: "center",
        fontSize: 16,
        paddingInline: global.spacer.panelComponent,
        textAlign: "center",
        textWrap: "pretty",
        lineHeight: 1.25,
        color: ColorsUtils.colorOut(global.elementaryColors.almostBlack),
    });

    const title = css({
        flexShrink: 0,
        marginBlockEnd: 0,
    });

    return {
        modalRoot,
        modalLayout,
        header,
        commentWrapper,
        editorContainer,
        behindKeyboard,
        title,
    };
};

export function CommentThreadMobileReply(props: IProps) {
    const { getComment } = useNestedCommentContext();
    const classes = mobileReplyModalClasses();

    const comment = getComment(props.threadItem.commentID);

    const editorRef = useRef<ICommentEditorRefHandle>(null);

    const handleToggle = () => {
        editorRef.current?.focusCommentEditor();
    };

    useEffect(() => {
        editorRef.current?.focusCommentEditor();
    }, []);

    return (
        <Modal
            isVisible={props.visibility}
            exitHandler={() => {
                null;
            }}
            size={ModalSizes.FULL_SCREEN}
            titleID={"mobile-reply"}
            className={classes.modalRoot}
        >
            <section className={classes.modalLayout}>
                <div className={classes.header}>
                    <Button buttonType={ButtonTypes.ICON} onClick={() => props.onVisibilityChange(false)}>
                        <LeftChevronCompactIcon />
                    </Button>
                    <Heading renderAsDepth={3}>
                        <Translate source={"Posting reply to <0/>"} c0={comment?.insertUser.name} />
                    </Heading>
                </div>
                <div className={classes.commentWrapper}>
                    {comment && (
                        <CollapsableContent maxHeight={100} onToggle={() => handleToggle()}>
                            <CommentItem comment={comment} readOnly />
                        </CollapsableContent>
                    )}
                </div>
                <div>
                    <CommentReply
                        title={<PageHeadingBox classNames={classes.title} depth={3} title={"Your comment"} />}
                        editorContainerClasses={classes.editorContainer}
                        threadItem={props.threadItem}
                        ref={editorRef}
                        onSuccess={() => props.onVisibilityChange(false)}
                        onCancel={() => {
                            props.onVisibilityChange(false);
                        }}
                        skipReplyThreadItem
                    />
                </div>
                <div className={classes.behindKeyboard}>
                    <p>
                        {t(
                            "Looks like your keyboard is collapsed, tap on 'Post Comment Reply' to post your comment or tap anywhere in the comment box to bring your keyboard back up.",
                        )}
                    </p>
                </div>
            </section>
        </Modal>
    );
}
