import { t } from '@core/application';
import React from 'react';
import Paragraph from '../../Forms/Paragraph';
import Or from '../../Forms/Or';
import {uniqueID, IComponentID} from '@core/Interfaces/componentIDs';

interface IProps extends IComponentID {
    includeOr?: boolean;
    ssoMethods?: any[];
}

interface IState {
    editable: boolean;
    passwordAuthenticators?: any[];
}

export default class SSOMethods extends React.Component<IProps, IState> {
    public ssoMethods: any[];
    public includeOr: boolean;
    public ID: string;

    constructor(props) {
        super(props);
        this.ID = uniqueID(props, 'SSOMethods', true);
        this.includeOr = props.includeOr || true;

        this.state = {
            editable: false,
            passwordAuthenticators: props.passwordAuthenticators,
        };

    }

    public handleClick (method):any {
        window.console.log("do sign in");
    }

    public render() {
        if (!this.state.passwordAuthenticators || this.state.passwordAuthenticators.length === 0) {
            return null;
        } else {
            let longestText = 0;
            const or = this.includeOr ? <Or/> : null;
            const ssoMethods = this.state.passwordAuthenticators.map((method, index) => {
                const nameLength = t(method.ui.buttonName).length;
                if ( nameLength > longestText) {
                    longestText = nameLength;
                }

                const methodStyles = {
                    backgroundColor: method.ui.backgroundColor,
                    color: method.ui.foregroundColor,
                };

                const labelStyles = {
                    minWidth: `calc(36px + ${longestText + 2}ex)`
                };

                const buttonClick = () => {
                    this.handleClick(method);
                };

                return <button type="button" key={ index } onClick={buttonClick} className="BigButton button Button button-sso button-fullWidth bg-facebook" style={methodStyles}>
                    <span className="button-ssoContents" style={labelStyles}>
                        <img src={method.ui.photoUrl} className="ssoMethod-icon" aria-hidden={true} />
                        <span className="button-ssoLabel">
                            {t(method.ui.buttonName)}
                        </span>
                    </span>
                </button>;
            });

            return <div className="ssoMethods">
                <Paragraph parentID={this.ID} content={t('Sign in with one of the following:')} />
                {ssoMethods}
                {or}
            </div>;
        }
    }
}
