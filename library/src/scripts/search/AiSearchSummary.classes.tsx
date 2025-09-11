/**
 * @author Daisy Barrette <dbarrette@higherlogic.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import { css } from "@emotion/css";
import { useThemeCache } from "@library/styles/themeCache";
import { globalVariables } from "@library/styles/globalStyleVars";
import { singleBorder } from "@library/styles/styleHelpersBorders";
import { Mixins } from "@library/styles/Mixins";
import { metasVariables } from "@library/metas/Metas.variables";
import { ColorsUtils } from "@library/styles/ColorsUtils";

const aiSearchSummaryClasses = useThemeCache(() => {
    const globalVars = globalVariables();
    const metasVars = metasVariables();

    const labelContainer = css({
        display: "inline-flex",
        alignItems: "center",
        marginBottom: "6px",
    });

    const label = css({
        marginLeft: "6px",
    });

    const resultsContainer = css({
        border: singleBorder(),
        padding: "1em",

        "& a[href]": {
            ...Mixins.font({
                ...globalVars.fontSizeAndWeightVars("medium"),
                color: globalVars.mainColors.primary,
                lineHeight: metasVars.font.lineHeight,
            }),
        },

        "& a[href]:hover": {
            textDecoration: "underline",
        },
    });

    const footer = css({
        display: "flex",
        flexDirection: "row",
        justifyContent: "flex-end",
        marginTop: "1em",
    });

    const iconInButton = css({
        marginRight: 5,
    });

    const sourcesPanel = css({
        display: "flex",
        flexDirection: "column",
        gap: "1em",
    });

    const sourcesModalButton = css({
        alignSelf: "flex-end",
    });

    const sourcesContainer = css({
        background: ColorsUtils.colorOut(globalVariables().mainColors.primary.fade(0.1)),
        display: "flex",
        flexDirection: "column",
        padding: "1em",
        borderRadius: "10px",
    });

    const sourcesContainerTitle = css({
        marginBottom: "1em",
    });

    const sourcesContainerTitleNumber = css({
        color: ColorsUtils.colorOut(globalVariables().mainColors.primary),
        background: ColorsUtils.colorOut(globalVariables().mainColors.primary.fade(0.2)),
        borderRadius: "5px",
        padding: "3px",
        fontSize: globalVariables().fontSizeAndWeightVars("small").size,
        fontWeight: "100",
    });

    const sourcesList = css({
        counterReset: "sources-counter",
        maxHeight: "400px",
        overflowY: "auto",
        paddingBottom: "2em",
    });

    const sourcesListItemMeta = css({
        width: "100%",
        marginLeft: "-4px", // override meta styles
        marginTop: "4px",
    });

    const sourcesListItem = css({
        listStyleType: "none",
        marginBottom: "1em",
        display: "flex",

        background: "white",
        borderRadius: "5px",

        "& .contents": {
            padding: "1em",
            flexGrow: 1,
        },

        "&::before": {
            content: "counter(sources-counter)",
            counterIncrement: "sources-counter",
            marginRight: "0.5em",
            display: "inline-flex",
            alignItems: "flex-start",
            justifyContent: "center",
            padding: "1em",
            fontSize: globalVariables().fontSizeAndWeightVars("small").size,
            color: ColorsUtils.colorOut(globalVariables().mainColors.primary),
            background: ColorsUtils.colorOut(globalVariables().mainColors.primary.fade(0.2)),
            borderRadius: "5px",
            borderTopRightRadius: "0",
            borderBottomRightRadius: "0",
        },
    });

    const sourcesListItemMetaTag = css({
        display: "inline-flex",
        alignItems: "center",
        gap: "0.5em",
    });

    const askCommunityCTA = css({
        alignSelf: "center",
        textAlign: "center",
        backgroundColor: "#EEEEEF",
        padding: "1em",
        marginTop: "-2em",
        width: "100%",
        marginLeft: "3em",
        marginRight: "4em",
        zIndex: 5000,
        borderRadius: "10px",
        boxShadow: "0 0 10px 0 rgba(0, 0, 0, 0.1)",
    });

    const askCommunityText = css({
        marginBottom: "1em",
    });

    const sourcesModalButtonIcon = css({
        marginLeft: "0.5em",
    });

    const sourcesListItemLink = css({
        textDecoration: "none",

        "&:hover": {
            textDecoration: "underline",
        },
    });

    return {
        labelContainer,
        label,
        resultsContainer,
        footer,
        iconInButton,
        sourcesPanel,
        sourcesContainer,
        sourcesList,
        sourcesListItem,
        sourcesListItemMeta,
        sourcesListItemMetaTag,
        sourcesContainerTitle,
        sourcesContainerTitleNumber,
        sourcesModalButton,
        askCommunityCTA,
        askCommunityText,
        sourcesModalButtonIcon,
        sourcesListItemLink,
    };
});

export default aiSearchSummaryClasses;
