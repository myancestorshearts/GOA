
import React from 'react';
import ReactDOM from 'react-dom';

import ContentClients from './content/clients';
import ContentTransactions from './content/transactions';
import ContentTotals from './content/totals';

import Portal from '../../common/portal';

const MENUS = [
    {
        title: 'Clients',
        icon: 'fa fa-users',
        component: ContentClients,
        link: '/'
    },
    {
        title: 'Transactions',
        icon: 'fa fa-barcode',
        component: ContentTransactions,
        link: '/transactions'
    },
    {
        title: 'Totals',
        icon: 'fa fa-dashboard',
        component: ContentTotals,
        link: '/totals'
    },
    {
        title: 'Portal',
        icon: 'fa fa-address-book',
        method: () => {
            window.location = '/portal'
        }
    }
]

ReactDOM.render(<Portal
    prefix='/admin'
    menus={MENUS}
/>, document.getElementById('goa'));