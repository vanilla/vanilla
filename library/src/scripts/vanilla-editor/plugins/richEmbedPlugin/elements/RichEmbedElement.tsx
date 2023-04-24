/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { LoadStatus } from "@library/@types/api/core";
import apiv2, { uploadFile } from "@library/apiv2";
import AttachmentError from "@library/content/attachments/AttachmentError";
import AttachmentLoading from "@library/content/attachments/AttachmentLoading";
import { mimeTypeToAttachmentType } from "@library/content/attachments/attachmentUtils";
import { EmbedContainer } from "@library/embeddedContent/components/EmbedContainer";
import { EmbedContainerSize } from "@library/embeddedContent/components/EmbedContainerSize";
import { EmbedContent } from "@library/embeddedContent/components/EmbedContent";
import { EmbedErrorBoundary } from "@library/embeddedContent/components/EmbedErrorBoundary";
import { embedContainerClasses } from "@library/embeddedContent/components/embedStyles";
import { getEmbedForType, IBaseEmbedData } from "@library/embeddedContent/embedService";
import { EmbedContext, useEmbedContext } from "@library/embeddedContent/IEmbedContext";
import { IError } from "@library/errorPages/CoreErrorMessages";
import { ErrorIcon } from "@library/icons/common";
import Message from "@library/messages/Message";
import { cx } from "@library/styles/styleShim";
import { getMeta, t } from "@library/utility/appUtils";
import ProgressEventEmitter from "@library/utility/ProgressEventEmitter";
import { setRichLinkAppearance } from "@library/vanilla-editor/plugins/richEmbedPlugin/transforms/setRichLinkAppearance";
import { IRichEmbedElement, RichLinkAppearance } from "@library/vanilla-editor/plugins/richEmbedPlugin/types";
import { getTrustedDomains } from "@library/vanilla-editor/utils/getTrustedDomains";
import {
    ELEMENT_DEFAULT,
    findNodePath,
    focusEditor,
    PlateRenderElementProps,
    removeNodes,
    setNodes,
    unsetNodes,
    withoutSavingHistory,
} from "@udecode/plate-headless";
import { useComponentDebug, useIsMounted } from "@vanilla/react-utils";
import { matchWithWildcard } from "@vanilla/utils";
import React, { useEffect, useRef, useState } from "react";
import { Transforms } from "slate";
import { useSelected } from "slate-react";

type IProps = PlateRenderElementProps<any, IRichEmbedElement> & { isInline: boolean };

/**
 * Element for rendering our embeds.
 *
 * - Is used in 2 different variations: "inline" and "card".
 * - Multiple different loading states: "file", "image", "url".
 * - Starts with just some loader data/resource link and makes network requests to load it.
 * - Notably this is registered for 2 different types to support 2 of the 3 appearances: "CARD" and "RICH".
 */
export function RichEmbedElement(props: IProps) {
    const { attributes, children, nodeProps, element, editor } = props;

    // Rich embed type has an empty error object that was saved to the database.
    // Convert it to an empty paragraph
    if (element.error && !element.error.message) {
        const path = findNodePath(editor, element);
        setNodes(editor, { type: ELEMENT_DEFAULT }, { at: path });
        unsetNodes(editor, ["error", "dataSourceType", "uploadFile"], { at: path });
    }

    const ownPath = findNodePath(editor, element);
    const isSelected = useSelected();
    useComponentDebug({ ownPath, isSelected });
    const deleteSelf = () => {
        removeNodes(editor, {
            at: ownPath,
        });
    };
    const isMounted = useIsMounted();
    const syncBackEmbedValues = (embedData: any, otherData: Partial<IRichEmbedElement> = {}) => {
        setNodes(
            editor,
            {
                ...element,
                ...otherData,
                embedData:
                    embedData === null
                        ? null
                        : {
                              ...element.embedData,
                              ...embedData,
                          },
            },
            {
                at: ownPath,
            },
        );
    };
    const selectSelf = () => {
        if (!ownPath) {
            return;
        }
        Transforms.select(editor as any, ownPath);
        focusEditor(editor);
    };

    const progressEmitterRef = useRef(new ProgressEventEmitter());

    let [isLoading, setIsLoading] = useState(false);
    async function loadEmbedData() {
        setIsLoading(true);
        try {
            switch (element.dataSourceType) {
                case "file":
                case "image":
                    const uploadedFile = await uploadFile(element.uploadFile, {
                        onUploadProgress: progressEmitterRef.current.emit,
                    });
                    // Don't save the history for this so that we don't get caught in an undo loop
                    // Where we undo, and the effect triggers again to load the data.
                    withoutSavingHistory(editor, () => {
                        syncBackEmbedValues(
                            { ...uploadedFile, embedType: element.dataSourceType },
                            { uploadFile: null, url: uploadedFile.url, error: undefined },
                        );
                    });

                    break;
                case "iframe":
                    // We should check if the user is allowed to embed here
                    const trustedDomains = getTrustedDomains();
                    const isKnowledge = getMeta("isKnowledge", false);
                    const elementURL = new URL(element.url);
                    const canEmbed = matchWithWildcard(elementURL.host, trustedDomains) || isKnowledge;
                    /**
                     * Grabbing the href here to ensure protocol relative urls don't get mangled into
                     * invalid URLs and force them to be secure
                     */
                    const url = elementURL.href.replace("http:", "https:");

                    if (canEmbed) {
                        // Push iframe specific values into the embedData
                        withoutSavingHistory(editor, () => {
                            syncBackEmbedValues({
                                url,
                                embedType: "iframe",
                                width: element.frameAttributes.width,
                                height: element.frameAttributes.height,
                                isKnowledge,
                            });
                        });
                        focusEditor(editor);
                    } else {
                        syncBackEmbedValues(null, {
                            error: {
                                message: t(
                                    "This domain is not supported so it cannot be embedded. You may try sharing a link to your media instead.",
                                ),
                            },
                        });
                    }

                    break;
                case "url":
                    const response = await apiv2.post("/media/scrape", { url: element.url });
                    // Don't save the history for this so that we don't get caught in an undo loop
                    // Where we undo, and the effect trigers again to load the data.
                    withoutSavingHistory(editor, () => {
                        syncBackEmbedValues(response.data, { error: undefined });
                    });

                    if (!isMounted) {
                        // Don't proceed with a conversion if we aren't mounted.
                        // the user may have changed our visual format already.
                        // https://github.com/vanilla/vanilla-cloud/pull/5419#discussion_r1046319846
                        return;
                    }

                    if (response.data.embedType !== "link") {
                        setRichLinkAppearance(editor, RichLinkAppearance.CARD, ownPath);
                        focusEditor(editor);
                    }
                    break;
            }
        } catch (err) {
            syncBackEmbedValues(null, { error: err });
        } finally {
            setIsLoading(false);
        }
    }

    // Notably we are not tracking our load status in component state, because component state may be erased at any moment.
    // Instead we are using the data stored in the element to simulate a loading state.
    let status: LoadStatus = LoadStatus.SUCCESS;
    if (element.embedData) {
        status = LoadStatus.SUCCESS;
    } else if (isLoading) {
        status = LoadStatus.LOADING;
    } else if (element.error) {
        status = LoadStatus.ERROR;
    } else {
        status = LoadStatus.PENDING;
    }

    const [elementUrl, setElementUrl] = useState(element.url);

    useEffect(() => {
        // scrape again if the URL has changed
        if (element.dataSourceType === "url" && elementUrl !== element.url) {
            setElementUrl(element.url);
            loadEmbedData();
        }
    }, [elementUrl, element.url]);

    useEffect(() => {
        if (status !== LoadStatus.PENDING) {
            return;
        }

        loadEmbedData();
    });

    return (
        <span {...attributes} {...nodeProps}>
            <span
                contentEditable={false}
                suppressContentEditableWarning={true}
                data-testid={`${props.isInline ? "inline-embed" : "card-embed"}:${element.url}`}
                className={cx("embedResponsive", {
                    "embed-isSelected": isSelected,
                })}
            >
                <EmbedContext.Provider
                    value={{
                        inEditor: true,
                        isSelected,
                        deleteSelf,
                        selectSelf,
                        isNewEditor: true,
                        syncBackEmbedValue: syncBackEmbedValues,
                    }}
                >
                    <EmbedErrorBoundary url={element.url ?? ""}>
                        {status === LoadStatus.LOADING && (
                            <EmbedLoader {...props} progressEventEmitter={progressEmitterRef.current} />
                        )}
                        {status === LoadStatus.ERROR && element.error != null && (
                            <EmbedError
                                {...props}
                                error={element.error}
                                onDismiss={
                                    element.dataSourceType === "url"
                                        ? () => {
                                              setRichLinkAppearance(editor, RichLinkAppearance.LINK);
                                          }
                                        : undefined
                                }
                                onDelete={deleteSelf}
                            />
                        )}
                        {status === LoadStatus.SUCCESS && element.embedData && (
                            <EmbedRenderer
                                {...element.embedData!}
                                embedStyle={props.isInline ? "rich_embed_inline" : undefined}
                            />
                        )}
                    </EmbedErrorBoundary>
                </EmbedContext.Provider>
            </span>
            {children}
        </span>
    );
}

function EmbedError(props: IProps & { error: IError; onDelete: () => void; onDismiss?: () => void }) {
    const { element } = props;
    const classes = embedContainerClasses();

    switch (element.dataSourceType) {
        case "file":
            const now = new Date();
            return (
                <AttachmentError
                    mimeType={element.uploadFile.type}
                    name={element.uploadFile.name}
                    message={props.error.message}
                    deleteAttachment={props.onDelete}
                    dateUploaded={now.toISOString()}
                />
            );
        default:
            if (props.isInline) {
                return (
                    <a
                        tabIndex={-1}
                        onClick={(e) => e.preventDefault()}
                        href={element.url}
                        className={cx(classes.makeRootClass(EmbedContainerSize.INLINE, true))}
                    >
                        {element.url}
                        <span style={{ marginLeft: 4, height: 16, width: 16 }}>
                            <ErrorIcon />
                        </span>
                    </a>
                );
            } else {
                return (
                    <EmbedContainer>
                        <EmbedContent type="error">
                            <Message
                                type="error"
                                stringContents={props.error.message}
                                onCancel={() => {
                                    props.onDismiss ? props.onDismiss() : props.onDelete();
                                }}
                                cancelText={element.dataSourceType === "url" ? t("Display as Text") : t("Dismiss")}
                            ></Message>
                        </EmbedContent>
                    </EmbedContainer>
                );
            }
    }
}

/**
 * Component to render the loading state of the embeds.
 */
function EmbedLoader(props: IProps & { progressEventEmitter?: ProgressEventEmitter }) {
    const { element } = props;

    const classes = embedContainerClasses();

    switch (element.dataSourceType) {
        case "file":
            const attachmentType = mimeTypeToAttachmentType(element.uploadFile.type);
            const now = new Date();
            return (
                <AttachmentLoading
                    type={attachmentType}
                    size={element.uploadFile.size}
                    dateUploaded={now.toISOString()}
                    name={element.uploadFile.name}
                    progressEventEmitter={props.progressEventEmitter}
                />
            );
        case "image":
            return (
                <div className="embedLinkLoader">
                    <div className="embedLoader">
                        <div className="embedLoader-box" aria-label={t("Loading...")}>
                            <div className="embedLoader-loader"></div>
                        </div>
                    </div>
                </div>
            );
        case "url":
        default:
            return (
                <a
                    tabIndex={-1}
                    onClick={(e) => e.preventDefault()}
                    href={element.url}
                    className={cx("isLoading", classes.makeRootClass(EmbedContainerSize.INLINE, true))}
                >
                    {element.url}
                    <span style={{ marginLeft: 4 }} className={"embedLinkLoader-loader"}></span>
                </a>
            );
    }
}

/**
 * Component to render an embed.
 */
function EmbedRenderer(props: IBaseEmbedData) {
    const EmbedComponent = getEmbedForType(props.embedType);
    const embedContext = useEmbedContext();

    if (!EmbedComponent) {
        const message = `Could not find embed of type '${props.embedType}'`;
        return (
            <EmbedContainer>
                <EmbedContent type="error">
                    <Message
                        type="error"
                        title={message}
                        stringContents={message}
                        onCancel={embedContext.deleteSelf}
                        cancelText={t("Dismiss")}
                    ></Message>
                </EmbedContent>
            </EmbedContainer>
        );
    }

    return <EmbedComponent {...embedContext} {...props} />;
}
