/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { css, cx, keyframes } from "@emotion/css";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { globalVariables } from "@library/styles/globalStyleVars";

const scaleKeyframe = keyframes`
0%, 100% { transform: scale(1); opacity: 0.4;}
25% { transform: scale(1.5); opacity: 0.8;}
`;

const dotClasses = () => {
    const globalVars = globalVariables();

    const root = css({
        display: "flex",
        alignItems: "center",
        gap: 4,
    });
    const loader = css({
        "--size": "8px",
        display: "inline-flex",
    });
    const dot = css({
        display: "inline-flex",
        width: "var(--size)",
        height: "var(--size)",
        aspectRatio: "1",
        backgroundColor: ColorsUtils.colorOut(globalVars.mixBgAndFg(0.6)),
        opacity: 0.4,
        borderRadius: "50%",
        marginRight: "calc(var(--size) /2)",
        animation: `${scaleKeyframe} 3s linear infinite forwards`,

        "&:nth-of-type(1)": {
            animationDelay: "1s",
        },
        "&:nth-of-type(2)": {
            animationDelay: "2s",
        },
        "&:nth-of-type(3)": {
            animationDelay: "3s",
        },
    });
    return { root, loader, dot };
};

interface IProps {
    message?: string;
    className?: string;
}

export function DotLoader(props: IProps) {
    const { message, className } = props;
    const classes = dotClasses();

    return (
        <div className={cx(classes.root, className)}>
            <span className={classes.loader}>
                <span className={classes.dot}></span>
                <span className={classes.dot}></span>
                <span className={classes.dot}></span>
            </span>
            {message}
        </div>
    );
}
