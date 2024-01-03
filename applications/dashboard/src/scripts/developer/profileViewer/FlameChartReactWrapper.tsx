/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { FlameChart } from "flame-chart-js";
import type { FlameChartSettings } from "flame-chart-js/dist/flame-chart";
import type { FlameChartNodes } from "flame-chart-js/dist/types";
import type { NodeTypes } from "flame-chart-js/react";
import { useCallback, useEffect, useRef } from "react";
import useResizeObserver from "use-resize-observer";

interface IProps {
    data?: FlameChartNodes;
    colors?: Record<string, string>;
    settings?: FlameChartSettings;
    position?: {
        x: number;
        y: number;
    };
    zoom?: {
        start: number;
        end: number;
    };
    className?: string;
    onSelect?: (data: NodeTypes) => void;
}
export function FlameChartReactWrapper(props: IProps) {
    const boxRef = useRef<HTMLDivElement | null>(null);
    const canvasRef = useRef<HTMLCanvasElement | null>(null);
    const flameChart = useRef<FlameChart | null>(null);

    useResizeObserver({
        ref: boxRef,
        onResize: ({ width = 0, height = 0 }) => {
            if (width === 0 || height === 0) {
                return;
            }
            flameChart.current?.resize(width, height);
        },
    });

    const initialize = useCallback(() => {
        const { data, settings, colors } = props;

        if (canvasRef.current && boxRef.current) {
            const { width = 0, height = 0 } = boxRef.current.getBoundingClientRect();

            canvasRef.current.width = width;
            canvasRef.current.height = height;

            flameChart.current = new FlameChart({
                canvas: canvasRef.current,
                data,
                settings,
                colors,
            });
        }
    }, []);

    const setBoxRef = useCallback((ref: HTMLDivElement) => {
        const isNewRef = ref !== boxRef.current;

        boxRef.current = ref;

        if (isNewRef) {
            initialize();
        }
    }, []);

    const setCanvasRef = useCallback((ref: HTMLCanvasElement) => {
        const isNewRef = ref !== canvasRef.current;

        canvasRef.current = ref;

        if (isNewRef) {
            initialize();
        }
    }, []);

    useEffect(() => {
        if (props.data) {
            flameChart.current?.setNodes(props.data);
        }
    }, [props.data]);

    useEffect(() => {
        if (props.settings && flameChart.current) {
            flameChart.current.setSettings(props.settings);
            flameChart.current.renderEngine.recalcChildrenLayout();
            flameChart.current.render();
        }
    }, [props.settings]);

    useEffect(() => {
        if (props.position) {
            flameChart.current?.setFlameChartPosition(props.position);
        }
    }, [props.position]);

    useEffect(() => {
        if (props.zoom) {
            flameChart.current?.setZoom(props.zoom.start, props.zoom.end);
        }
    }, [props.zoom]);

    useEffect(() => {
        if (props.onSelect) {
            flameChart.current?.on("select", props.onSelect);
        }

        return () => {
            if (props.onSelect) {
                flameChart.current?.removeListener("select", props.onSelect);
            }
        };
    }, [props.onSelect]);

    return (
        <div className={props.className} ref={setBoxRef}>
            <canvas ref={setCanvasRef} />
        </div>
    );
}
