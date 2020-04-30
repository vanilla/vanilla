/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import Loader from "@vanilla/library/src/scripts/loaders/Loader";

/**
 * Custom layout for swagger-ui.
 *
 * - Use Vanilla's loader.
 * - Remove unwanted info module.
 */
export function VanillaSwaggerLayout(props: any) {
    let { errSelectors, specSelectors, getComponent } = props;

    let SvgAssets = getComponent("SvgAssets");
    let VersionPragmaFilter = getComponent("VersionPragmaFilter");
    let Operations = getComponent("operations", true);
    let Models = getComponent("Models", true);
    let Row = getComponent("Row");
    let Col = getComponent("Col");
    let Errors = getComponent("errors", true);

    let isSwagger2 = specSelectors.isSwagger2();
    let isOAS3 = specSelectors.isOAS3();

    const isSpecEmpty = !specSelectors.specStr();

    const loadingStatus = specSelectors.loadingStatus();

    let loadingMessage: React.ReactNode = null;

    if (loadingStatus === "loading") {
        return <Loader padding={200} />;
    }

    if (loadingStatus === "failed") {
        loadingMessage = (
            <div className="info">
                <div className="loading-container">
                    <h4 className="title">Failed to load API definition.</h4>
                    <Errors />
                </div>
            </div>
        );
    }

    if (loadingStatus === "failedConfig") {
        const lastErr = errSelectors.lastError();
        const lastErrMsg = lastErr ? lastErr.get("message") : "";
        loadingMessage = (
            <div
                className="info"
                style={{ maxWidth: "880px", marginLeft: "auto", marginRight: "auto", textAlign: "center" }}
            >
                <div className="loading-container">
                    <h4 className="title">Failed to load remote configuration.</h4>
                    <p>{lastErrMsg}</p>
                </div>
            </div>
        );
    }

    if (!loadingMessage && isSpecEmpty) {
        loadingMessage = <h4>No API definition provided.</h4>;
    }

    if (loadingMessage) {
        return (
            <div className="swagger-ui">
                <div className="loading-container">{loadingMessage}</div>
            </div>
        );
    }

    return (
        <div className="swagger-ui">
            <SvgAssets />
            <VersionPragmaFilter isSwagger2={isSwagger2} isOAS3={isOAS3} alsoShow={<Errors />}>
                <Errors />
                <Row>
                    <Col mobile={12} desktop={12}>
                        <Operations />
                    </Col>
                </Row>
                <Row>
                    <Col mobile={12} desktop={12}>
                        <Models />
                    </Col>
                </Row>
            </VersionPragmaFilter>
        </div>
    );
}
