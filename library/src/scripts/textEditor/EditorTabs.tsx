/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { css, cx } from "@emotion/css";
import { getClassForButtonType } from "@library/forms/Button.getClassForButtonType";
import { ButtonType } from "@library/forms/buttonTypes";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { ColorVar } from "@library/styles/CssVar";
import { Mixins } from "@library/styles/Mixins";
import * as RadixTabs from "@radix-ui/react-tabs";
import { mergeRefs, useIsOverflowing } from "@vanilla/react-utils";
import { createContext, forwardRef, useContext, useRef } from "react";

const EditorTabsContext = createContext({
    orientation: "horizontal" as "horizontal" | "vertical",
    eager: true,
});

const Root = forwardRef((props: RadixTabs.TabsProps & { eager?: boolean }, ref: React.Ref<HTMLDivElement>) => {
    const { eager, ...restProps } = props;
    return (
        <EditorTabsContext.Provider
            value={{ orientation: props.orientation ?? "horizontal", eager: props.eager ?? true }}
        >
            <RadixTabs.Root ref={ref} {...restProps} className={cx(classes.root, props.className)} />
        </EditorTabsContext.Provider>
    );
});
Root.displayName = "EditorTabs.Root";

const List = forwardRef(
    (props: RadixTabs.TabsListProps & { overflowBehaviour?: "scroll" | "wrap" }, ref: React.Ref<HTMLDivElement>) => {
        const ownRef = useRef<HTMLDivElement>(null);
        const listMeasure = useIsOverflowing();
        const { overflowBehaviour = "scroll", className, ...restProps } = props;

        let result = (
            <RadixTabs.TabsList
                ref={mergeRefs(ref, listMeasure.ref)}
                {...restProps}
                className={cx(
                    classes.list,
                    overflowBehaviour === "scroll" && classes.listWithOverflow,

                    // if we are not in scroll mode, we want to add the className
                    // to the list, otherwise we want to add it to the container
                    overflowBehaviour !== "scroll" && className,
                )}
            />
        );

        if (overflowBehaviour === "scroll") {
            const showStartScrim = listMeasure.isOverflowing && listMeasure.scrollX > 24;
            const scrollEndPoint = listMeasure.measure.scrollWidth - listMeasure.measure.clientWidth;
            const showEndScrim = listMeasure.isOverflowing && listMeasure.scrollX < scrollEndPoint - 24;

            result = (
                <>
                    <div className={cx(classes.listOverflowContainer, className)}>
                        <span
                            style={{ opacity: showStartScrim ? 1 : 0 }}
                            className={cx(classes.scrim, classes.scrimLeft)}
                        />
                        {result}
                        <span
                            style={{ opacity: showEndScrim ? 1 : 0 }}
                            className={cx(classes.scrim, classes.scrimRight)}
                        />
                    </div>
                </>
            );
        }

        return result;
    },
);
List.displayName = "EditorTabs.List";

const Trigger = forwardRef(
    (props: RadixTabs.TabsTriggerProps & { buttonType?: ButtonType }, ref: React.Ref<HTMLButtonElement>) => {
        const { buttonType, ...restProps } = props;
        const className = cx(getClassForButtonType(buttonType ?? ButtonType.INPUT), classes.trigger, props.className);
        return <RadixTabs.Trigger ref={ref} {...restProps} className={className} />;
    },
);
Trigger.displayName = "EditorTabs.Trigger";

const Content = forwardRef((props: RadixTabs.TabsContentProps, ref: React.Ref<HTMLDivElement>) => {
    const { eager } = useContext(EditorTabsContext);
    return (
        <RadixTabs.Content
            ref={ref}
            {...props}
            className={cx(classes.content, props.className)}
            forceMount={eager === true ? true : undefined}
        />
    );
});
Content.displayName = "EditorTabs.Content";

export const EditorTabs = { Root, List, Trigger, Content };

const classes = {
    root: css({
        height: "100%",
        display: "flex",
        flexDirection: "column",
    }),
    list: css({
        display: "flex",
        gap: 12,
        width: "100%",
        flexWrap: "wrap",
    }),
    listWithOverflow: css({
        flexWrap: "nowrap",
        overflowX: "auto",
    }),

    listOverflowContainer: css({
        position: "relative",
        width: "100%",
        ...Mixins.scrollbar({
            barColor: ColorsUtils.var(ColorVar.Border),
            trackColor: ColorsUtils.var(ColorVar.Background),
            width: "thin",
        }),
    }),
    scrim: css({
        transition: "opacity 0.2s ease-in-out",
        position: "absolute",
        top: 1,
        bottom: 1,
        width: 32,
        display: "block",
        zIndex: 1,
        pointerEvents: "none",
    }),
    scrimLeft: css({
        left: 0,
        background: `linear-gradient(to left, rgba(0,0,0,0), ${ColorsUtils.var(ColorVar.Background)} 100%)`,
    }),

    scrimRight: css({
        right: 0,
        background: `linear-gradient(to right, rgba(0,0,0,0), ${ColorsUtils.var(ColorVar.Background)} 100%)`,
    }),
    trigger: css({
        whiteSpace: "nowrap",
        position: "relative",
        minHeight: 36,
        display: "inline-flex",
        alignItems: "center",
        gap: 6,
        minWidth: "min-content",

        "&[data-state='active']": {
            "&::after": {
                content: `""`,
                display: "block",
                position: "absolute",
                left: 0,
                bottom: 0,
                right: 0,
                height: 2,
                borderBottomRightRadius: 6,
                borderBottomLeftRadius: 6,
                background: ColorsUtils.var(ColorVar.Primary),
            },
        },
    }),
    content: css({
        height: "100%",
        flexGrow: 1,
        minHeight: 0,
        "&[data-state='inactive']": {
            display: "none",
        },
    }),
};
