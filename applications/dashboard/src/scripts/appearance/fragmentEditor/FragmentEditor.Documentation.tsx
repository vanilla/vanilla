/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { useFragmentEditor } from "@dashboard/appearance/fragmentEditor/FragmentEditor.context";
import { EmptyState } from "@dashboard/moderation/components/EmptyState";
import { css } from "@emotion/css";
import { userContentClasses } from "@library/content/UserContent.styles";
import { userContentVariables } from "@library/content/UserContent.variables";
import { LoadingRectangle, LoadingSpacer } from "@library/loaders/LoadingRectangle";
import { QueryLoader } from "@library/loaders/QueryLoader";
import { getRegisteredFragments } from "@library/utility/fragmentsRegistry";
import { useQuery } from "@tanstack/react-query";
import { t } from "@vanilla/i18n";
import { root } from "postcss";
import ReactMarkdown from "react-markdown";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { Mixins } from "@library/styles/Mixins";

export function FragmentEditorDocumentation() {
    const { form } = useFragmentEditor();
    const { fragmentType } = form;

    const fragmentDocsQuery = useQuery({
        // Dunno why this thinks getRegisteredFragments() should be a dependency.
        // eslint-disable-next-line @tanstack/query/exhaustive-deps
        queryKey: ["fragmentDocs", fragmentType],
        queryFn: async () => {
            const fragmentMeta = getRegisteredFragments()[fragmentType] ?? null;
            return (await fragmentMeta.docs?.()) ?? "";
        },
    });

    const classesInstance = classes();

    return (
        <div className={classesInstance.root}>
            <QueryLoader
                query={fragmentDocsQuery}
                loader={
                    <div>
                        <LoadingRectangle height={24} width={"100%"} />
                        <LoadingSpacer height={8} />
                        <LoadingRectangle height={24} width={"80%"} />
                        <LoadingSpacer height={8} />
                        <LoadingRectangle height={24} width={"90%"} />
                        <LoadingSpacer height={8} />
                        <LoadingRectangle height={24} width={"40%"} />
                    </div>
                }
                success={(docs) => {
                    if (!docs) {
                        return <EmptyState text={t("No documentation available for this fragment.")} />;
                    }
                    return (
                        <ReactMarkdown className={`${userContentClasses().root} ${classesInstance.documentation}`}>
                            {docs}
                        </ReactMarkdown>
                    );
                }}
            />
        </div>
    );
}

const classes = useThemeCache(() => {
    const userVars = userContentVariables();

    return {
        root: css({
            padding: "18px 28px",
            overflowY: "auto",
            WebkitOverflowScrolling: "touch",
            maxHeight: "100%",
            height: "100%",
        }),
        documentation: css({
            "& pre": {
                backgroundColor: ColorsUtils.colorOut(userVars.codeBlock.bg),
                color: ColorsUtils.colorOut(userVars.codeBlock.fg),
                borderRadius: userVars.codeBlock.borderRadius,
                ...Mixins.padding(userVars.codeBlock.padding),
                overflow: "auto",
                fontSize: userVars.code.fontSize,
                lineHeight: userVars.codeBlock.lineHeight,
                fontFamily: "SFMono-Regular, Menlo, Monaco, Consolas, 'Liberation Mono', 'Courier New', monospace",
                border: "none",
                display: "block",
                wordWrap: "normal",
                whiteSpace: "pre",
                flexShrink: 0,
            },
            "& code": {
                backgroundColor: ColorsUtils.colorOut(userVars.codeInline.bg),
                color: ColorsUtils.colorOut(userVars.codeInline.fg),
                borderRadius: userVars.codeInline.borderRadius,
                ...Mixins.padding(userVars.codeInline.padding),
                fontSize: "85%",
                fontFamily: "SFMono-Regular, Menlo, Monaco, Consolas, 'Liberation Mono', 'Courier New', monospace",
                border: "none",
                display: "inline",
                whiteSpace: "normal",
            },
            "& pre code": {
                backgroundColor: "transparent",
                color: "inherit",
                border: "none",
                borderRadius: "0",
                padding: "0",
                fontSize: "100%",
                display: "inline",
                whiteSpace: "pre",
            },
        }),
    };
});
