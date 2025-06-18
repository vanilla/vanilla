/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { useFragmentEditor } from "@dashboard/appearance/fragmentEditor/FragmentEditor.context";
import { EmptyState } from "@dashboard/moderation/components/EmptyState";
import { css } from "@emotion/css";
import { userContentClasses } from "@library/content/UserContent.styles";
import { LoadingRectangle, LoadingSpacer } from "@library/loaders/LoadingRectangle";
import { QueryLoader } from "@library/loaders/QueryLoader";
import { getRegisteredFragments } from "@library/utility/fragmentsRegistry";
import { useQuery } from "@tanstack/react-query";
import { t } from "@vanilla/i18n";
import { root } from "postcss";
import ReactMarkdown from "react-markdown";

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

    return (
        <div className={classes.root}>
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
                    return <ReactMarkdown className={userContentClasses().root}>{docs}</ReactMarkdown>;
                }}
            />
        </div>
    );
}

const classes = {
    root: css({
        padding: "18px 28px",
        overflowY: "auto",
        WebkitOverflowScrolling: "touch",
        maxHeight: "100%",
        height: "100%",
    }),
};
