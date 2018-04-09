import React from 'react';
import { t } from '@core/application';

export default class Or extends React.Component {
    public render() {
        return <div className="inputBlock">
            <div className="inputBlock-labelText authenticateUser-divider">
                {t('or')}
            </div>
        </div>;

    }
}
