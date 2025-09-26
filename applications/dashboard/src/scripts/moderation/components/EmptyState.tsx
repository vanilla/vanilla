/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { css, keyframes } from "@emotion/css";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { ColorVar } from "@library/styles/CssVar";
import { useThemeCache } from "@library/styles/themeCache";
import { t } from "@vanilla/i18n";
import { useRef } from "react";

interface IProps {
    text?: string;
    subtext?: string;
    isStorybook?: boolean;
}

const emojis = ["😀", "🥳", "🙌", "😌", "😎", "🏖️", "📭", "🏁", "🎯", "🍀", "🍃", "🌻", "✨"];

const emptyStateClasses = useThemeCache(() => {
    const slowRotate = keyframes({
        "0%,100%": { transform: `rotate(0turn)` },
        "50%": { transform: `rotate(0.5turn)` },
    });
    const layout = css({
        position: "relative",
        display: "flex",
        flexDirection: "column",
        alignItems: "center",
        justifyContent: "center",
        minHeight: "60dvh",
        width: "100%",
        "& > *": {
            zIndex: 1,
        },
        "&:before": {
            content: '""',
            position: "absolute",
            display: "block",
            width: "100%",
            height: "100%",
            top: 0,
            left: 0,
            color: ColorsUtils.var(ColorVar.Foreground),
            backgroundImage: `url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath fill='rgba(0,0,0,0.03)' d='M59.3,-34.3C72.1,-12.1,74.4,16.1,62.8,39.1C51.2,62.1,25.6,79.9,0.2,79.8C-25.1,79.7,-50.2,61.5,-60.7,39.2C-71.3,16.8,-67.3,-9.9,-54.8,-32C-42.3,-54.2,-21.1,-71.7,1.1,-72.4C23.2,-73,46.5,-56.6,59.3,-34.3Z' transform='translate(100 100)' /%3E%3C/svg%3E");`,
            backgroundRepeat: "no-repeat",
            backgroundSize: "auto 120%",
            backgroundPosition: "center",
            zIndex: 0,
            animationName: slowRotate,
            animationDuration: "120s",
            animationFillMode: "forwards",
            animationIterationCount: "infinite",
        },
    });
    const icon = css({
        fontSize: 90,
    });
    const textContainer = css({
        display: "flex",
        flexDirection: "column",
        alignItems: "center",
        justifyContent: "center",
        maxWidth: "70ch",
        textAlign: "center",
    });
    const text = css({
        fontSize: 32,
        fontWeight: "bold",
        color: ColorsUtils.var(ColorVar.Foreground),
        lineHeight: 1.5,
    });
    const subtext = css({
        fontSize: 24,
        fontWeight: "normal",
        color: ColorsUtils.var(ColorVar.Foreground),
        lineHeight: 1.5,
        textWrap: "balance",
    });
    return {
        layout,
        icon,
        textContainer,
        text,
        subtext,
    };
});

export function EmptyState(props: IProps) {
    const randomEmoji = useRef(!props.isStorybook ? emojis[Math.floor(Math.random() * emojis.length)] : emojis[0]);
    const classes = emptyStateClasses.useAsHook();
    return (
        <section className={classes.layout}>
            <div className={classes.icon}>{randomEmoji.current}</div>
            <div className={classes.textContainer}>
                <span className={classes.text}>{props.text ?? t("All clear!")}</span>
                {props.subtext && <span className={classes.subtext}>{props.subtext}</span>}
            </div>
        </section>
    );
}
