/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

// import React, { PureComponent } from "react";

// export default class OperationSummaryPath extends PureComponent {
//     private onCopyCapture = e => {
//         // strips injected zero-width spaces (`\u200b`) from copied content
//         e.clipboardData.setData("text/plain", this.props.operationProps.get("path"));
//         e.preventDefault();
//     };

//     public render() {
//         let { getComponent, operationProps } = this.props;

//         let { deprecated, isShown, path, tag, operationId, isDeepLinkingEnabled } = operationProps.toJS();

//         return (
//             <span
//                 className={deprecated ? "opblock-summary-path__deprecated" : "opblock-summary-path"}
//                 onCopyCapture={this.onCopyCapture}
//                 data-path={path}
//             >
//                 <VanillaDep
//                     enabled={isDeepLinkingEnabled}
//                     isShown={isShown}
//                     path={createDeepLinkPath(`${tag}/${operationId}`)}
//                     text={path.replace(/\//g, "\u200b/")}
//                 />
//             </span>
//         );
//     }
// }
