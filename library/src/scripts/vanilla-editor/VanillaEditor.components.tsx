/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { useHLJS } from "@library/content/code";
import { ELEMENT_BLOCKQUOTE_ITEM } from "@library/vanilla-editor/plugins/blockquotePlugin/createBlockquotePlugin";
import { MentionInputElement } from "@library/vanilla-editor/plugins/mentionPlugin/MentionInputElement";
import { RichLinkElement } from "@library/vanilla-editor/plugins/richEmbedPlugin/elements/RichLinkElement";
import {
    ELEMENT_SPOILER,
    ELEMENT_SPOILER_CONTENT,
    ELEMENT_SPOILER_ITEM,
} from "@library/vanilla-editor/plugins/spoilerPlugin/createSpoilerPlugin";
import { PlateEditor, Value, withProps } from "@udecode/plate-core";
import {
    ELEMENT_BLOCKQUOTE,
    ELEMENT_CODE_BLOCK,
    ELEMENT_CODE_LINE,
    ELEMENT_CODE_SYNTAX,
    ELEMENT_H1,
    ELEMENT_H2,
    ELEMENT_H3,
    ELEMENT_H4,
    ELEMENT_H5,
    ELEMENT_H6,
    ELEMENT_IMAGE,
    ELEMENT_LI,
    ELEMENT_LIC,
    ELEMENT_LINK,
    ELEMENT_MENTION_INPUT,
    ELEMENT_OL,
    ELEMENT_PARAGRAPH,
    ELEMENT_TABLE,
    ELEMENT_TD,
    ELEMENT_TH,
    ELEMENT_TR,
    ELEMENT_UL,
    findNodePath,
    MARK_BOLD,
    MARK_CODE,
    MARK_ITALIC,
    MARK_STRIKETHROUGH,
    MARK_UNDERLINE,
    PlatePluginComponent,
    PlateRenderElementProps,
    setNodes,
    TCodeBlockElement,
} from "@udecode/plate-headless";
import { t } from "@vanilla/i18n";
import { Icon } from "@vanilla/icons";
import debounce from "lodash/debounce";
import "prismjs/components/prism-php";
import React, { ElementType, useCallback, useEffect } from "react";
import { Node } from "slate";

type HLJS = typeof import("@library/content/highlightJs").default;

export const createVanillaEditorComponents = () => {
    const components = {
        [ELEMENT_BLOCKQUOTE]: withProps(SimpleElement, { as: "blockquote", className: "blockquote" }),
        [ELEMENT_BLOCKQUOTE_ITEM]: withProps(SimpleElement, { as: "p", className: "blockquote-content" }),
        [ELEMENT_CODE_BLOCK]: CodeBlockElement,
        [ELEMENT_CODE_LINE]: withProps(SimpleElement, { as: "div" }),
        [ELEMENT_CODE_SYNTAX]: CodeSyntaxLeaf,
        [ELEMENT_H1]: withProps(SimpleElement, { as: "h1" }),
        [ELEMENT_H2]: withProps(SimpleElement, { as: "h2" }),
        [ELEMENT_H3]: withProps(SimpleElement, { as: "h3" }),
        [ELEMENT_H4]: withProps(SimpleElement, { as: "h4" }),
        [ELEMENT_H5]: withProps(SimpleElement, { as: "h5" }),
        [ELEMENT_H6]: withProps(SimpleElement, { as: "h6" }),
        [ELEMENT_IMAGE]: withProps(SimpleElement, { as: "img" }),
        [ELEMENT_LI]: withProps(SimpleElement, { as: "li" }),
        [ELEMENT_LIC]: withProps(SimpleElement, { as: "span", className: "listItemChild" }),
        [ELEMENT_LINK]: RichLinkElement,
        [ELEMENT_UL]: withProps(SimpleElement, { as: "ul" }),
        [ELEMENT_OL]: withProps(SimpleElement, { as: "ol" }),
        [ELEMENT_PARAGRAPH]: withProps(SimpleElement, { as: "p" }),
        [ELEMENT_SPOILER]: SpoilerElement,
        [ELEMENT_SPOILER_CONTENT]: withProps(SimpleElement, { as: "div", className: "spoiler-content" }),
        [ELEMENT_SPOILER_ITEM]: withProps(SimpleElement, { as: "p", className: "spoiler-line" }),
        [ELEMENT_TABLE]: TableElement,
        [ELEMENT_TD]: withProps(SimpleElement, { as: "td" }),
        [ELEMENT_TH]: withProps(SimpleElement, { as: "th" }),
        [ELEMENT_TR]: withProps(SimpleElement, { as: "tr" }),
        [MARK_BOLD]: withProps(SimpleElement, { as: "strong" }),
        [MARK_CODE]: withProps(SimpleElement, { as: "code", className: "code codeInline" }),
        [MARK_ITALIC]: withProps(SimpleElement, { as: "em" }),
        [MARK_STRIKETHROUGH]: withProps(SimpleElement, { as: "s" }),
        [MARK_UNDERLINE]: withProps(SimpleElement, { as: "u" }),
        [ELEMENT_MENTION_INPUT]: MentionInputElement,
    };

    return components;
};

export const CodeSyntaxLeaf: PlatePluginComponent = ({ attributes, children, leaf }) => (
    <span {...attributes}>
        <span className={`prism-token token ${leaf.tokenType}`}>{children}</span>
    </span>
);

export const CodeBlockElement = (props: PlateRenderElementProps<any, TCodeBlockElement>) => {
    const hljs = useHLJS();
    const { attributes, children, nodeProps, element, editor } = props;
    const { lang } = element;

    const textContent = Array.from(Node.texts(element, {}))
        .map((val) => val[0].text)
        .join("\n");

    const performAutoDetect = useCallback(
        debounce((hljs: HLJS | null, editor: PlateEditor, textContent: string) => {
            const lang = hljs.highlightAuto(textContent).language;
            if (!lang) {
                return;
            }
            const path = findNodePath(editor, element);
            path && setNodes<TCodeBlockElement>(editor, { lang: lang }, { at: path });
        }, 1000),
        [],
    );

    useEffect(() => {
        if (typeof textContent !== "string" || textContent.length < 10) {
            return;
        }
        if (!hljs) {
            return;
        }
        performAutoDetect(hljs, editor, textContent);
    }, [hljs, editor, performAutoDetect, textContent]);

    return (
        <pre {...attributes} {...nodeProps} className={"code codeBlock"}>
            <code className={`${lang} language-${lang}`}>{children}</code>
        </pre>
    );
};

/**
 * Table Element with a div wrapper with our proper wrapping class.
 */
const TableElement = (props: PlateRenderElementProps<any, TCodeBlockElement>) => {
    const { attributes, children, nodeProps } = props;

    return (
        <div {...attributes} {...nodeProps} className="tableWrapper">
            <table>
                <tbody>{children}</tbody>
            </table>
        </div>
    );
};

/**
 * Spoiler Element with wrapper and disabled button
 */
const SpoilerElement = (props: PlateRenderElementProps<any, TCodeBlockElement>) => {
    const { attributes, children, nodeProps } = props;

    return (
        <div {...attributes} {...nodeProps} className="spoiler isShowingSpoiler">
            <div className="spoiler-buttonContainer" contentEditable={false}>
                <button className="iconButton button-spoiler" disabled>
                    <span className="spoiler-warning">
                        <span className="spoiler-warningMain">
                            <Icon icon="editor-eye-slash" className="spoiler-icon" />
                            <span className="spoiler-warningLabel">{t("Spoiler Warning")}</span>
                        </span>
                    </span>
                </button>
            </div>
            {children}
        </div>
    );
};

const SimpleElement = <V extends Value = Value, N extends PlateRenderElementProps<V> = PlateRenderElementProps<V>>(
    props: { as: ElementType; className?: string } & N,
) => {
    const { attributes, children, nodeProps, className } = props;

    const Comp = props.as;

    return (
        <Comp {...attributes} {...nodeProps} className={className}>
            {children}
        </Comp>
    );
};
