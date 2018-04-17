import { t } from '@core/application';
import React from 'react';
import Paragraph from '../../Forms/Paragraph';
import {uniqueID, IComponentID} from '@core/Interfaces/componentIDs';

interface IProps extends IComponentID {
    ssoMethods?: any[];
}

interface IState {
    longestText: number;
}

export default class SSOMethods extends React.Component<IProps, IState> {
    public ID: string;

    constructor(props) {
        super(props);
        this.ID = uniqueID(props, 'SSOMethods', true);

        this.state = {
            longestText: 0,
        };
    }

    public render() {
        if (!this.props.ssoMethods || this.props.ssoMethods.length === 0) {
            return null;
        } else {
            const ssoMethods = this.props.ssoMethods.map((method, index) => {
                const nameLength = t(method.ui.buttonName).length;
                if ( nameLength > this.state.longestText) {
                    this.setState({
                        longestText: nameLength,
                    });
                }

                const methodStyles = {
                    backgroundColor: method.ui.backgroundColor,
                    color: method.ui.foregroundColor,
                };

                return <a href={method.ui.url} key={ index } className="BigButton button Button button-sso button-fullWidth" style={methodStyles}>
                    <span className="button-ssoContents">
                        <img src={method.ui.photoUrl} className="ssoMethod-icon" aria-hidden={true} />
                        <span className="button-ssoLabel">
                            {t(method.ui.buttonName)}
                        </span>
                        <span className="ssoMethod-icon ssoMethod-iconSpacer" aria-hidden="true"></span>
                    </span>
                </a>;
            });

            return <div className="ssoMethods">
                <Paragraph parentID={this.ID} content={t('Sign in with one of the following:')} />
                {ssoMethods}
            </div>;
        }
    }
}
