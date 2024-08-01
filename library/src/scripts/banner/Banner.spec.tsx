/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only */

import React, { ReactNode } from "react";
import { act, render } from "@testing-library/react";
import Banner from "@library/banner/Banner";
import { MemoryRouter } from "react-router";
import { TestReduxProvider } from "@library/__tests__/TestReduxProvider";
import { stableObjectHash } from "@vanilla/utils";
import { LoadStatus } from "@library/@types/api/core";

const BG_IMG_VARS = "https://url-vars";
const BG_IMG_PROPS_UPLOAD = "https://url-upload";

const BG_SRC_SET = {
    10: `${BG_IMG_PROPS_UPLOAD}/10`,
    300: `${BG_IMG_PROPS_UPLOAD}/300`,
    800: `${BG_IMG_PROPS_UPLOAD}/800`,
    1200: `${BG_IMG_PROPS_UPLOAD}/1200`,
    1600: `${BG_IMG_PROPS_UPLOAD}/1600`,
};

function renderInProvider(children: ReactNode) {
    return render(
        <TestReduxProvider
            state={{
                config: {
                    configsByLookupKey: {
                        [stableObjectHash(["labs.*"])]: {
                            status: LoadStatus.SUCCESS,
                            data: {
                                "externalSearch.query": false,
                            },
                        },
                    },
                },
            }}
        >
            {children}
        </TestReduxProvider>,
    );
}

describe("Banner", () => {
    it("No image url passed, should render our default svg", () => {
        const { container } = renderInProvider(
            <MemoryRouter>
                <Banner />
            </MemoryRouter>,
        );
        const svgNodes = container.querySelectorAll("svg");
        expect(svgNodes.length).toBeGreaterThan(0);
    });
    it("Background image from variables", () => {
        const { container } = renderInProvider(
            <MemoryRouter>
                <Banner forcedVars={{ banner: { outerBackground: { image: BG_IMG_VARS } } }} />
            </MemoryRouter>,
        );
        const pictureNodes = container.querySelectorAll("picture");
        expect(pictureNodes.length).toBeGreaterThan(0);

        const imageNodes = container.querySelectorAll("img");
        expect(imageNodes.length).toBeGreaterThan(0);
        expect(imageNodes[0]).toHaveAttribute("src");
        expect(imageNodes[0].getAttribute("src")).toBe(BG_IMG_VARS);
    });

    it("Background image from as props/upload, should override vars", () => {
        const { container } = renderInProvider(
            <MemoryRouter>
                <Banner
                    backgroundImage={BG_IMG_PROPS_UPLOAD}
                    forcedVars={{ banner: { outerBackground: { image: BG_IMG_VARS } } }}
                    backgroundUrlSrcSet={BG_SRC_SET}
                />
            </MemoryRouter>,
        );
        const pictureNodes = container.querySelectorAll("picture");
        expect(pictureNodes.length).toBeGreaterThan(0);

        const imageNodes = container.querySelectorAll("img");
        expect(imageNodes.length).toBeGreaterThan(0);
        expect(imageNodes[0]).toHaveAttribute("src");
        expect(imageNodes[0].getAttribute("src")).toBe(BG_IMG_PROPS_UPLOAD);
        expect(imageNodes[0]).toHaveAttribute("srcset");
        expect(imageNodes[0].getAttribute("srcset")).toContain(BG_SRC_SET[10]);
        expect(imageNodes[0].getAttribute("srcset")).toContain(BG_SRC_SET[300]);
        expect(imageNodes[0].getAttribute("srcset")).toContain(BG_SRC_SET[800]);
        expect(imageNodes[0].getAttribute("srcset")).toContain(BG_SRC_SET[1200]);
        expect(imageNodes[0].getAttribute("srcset")).toContain(BG_SRC_SET[1600]);
    });
});
