
import React from 'react';
import ReactDOM from 'react-dom';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome'

import CommonFunctions from '../../common/functions';

import ContentOrders from './content/orders';
import ContentPackages from './content/packages';
import ContentAddresses from './content/addresses';
import ContentReferrals from './content/referrals';
import ContentLedger from './content/ledger';
import ContentIntegrations from './content/integrations';
import ContentScanForms from './content/scanforms';
import ContentLabels from './content/labels';
import ContentPickups from './content/pickups';
import ContentCorrections from './content/corrections';

import Portal from '../../common/portal';

const MENUS = [
    {
        title: 'Labels',
        icon: 'fa fa-file',
        component: ContentLabels,
        link: '/',
    },
    {
        title: 'Orders',
        icon: 'fa fa-tags',
        component: ContentOrders,
        link: '/orders',
    },
    {
        title: 'Ledger',
        icon: 'fa fa-book',
        component: ContentLedger,
        link: '/ledger',
    },
    {
        title: 'Corrections',
        icon: 'fa fa-industry',
        component: ContentCorrections,
        link: '/corrections',
    },
    {
        title: 'Packages',
        icon: 'fa fa-cube',
        component: ContentPackages,
        link: '/packages',
        className: 'packages',
    },
    {
        title: 'Addresses',
        icon: 'fa fa-map-marker',
        component: ContentAddresses,
        link: '/addresses',
        className: 'addresses',
    },
    {
        title: 'Scan Forms',
        icon: 'fa fa-barcode',
        component: ContentScanForms,
        link: '/scan-forms'
    },
    {
        title: 'Pickups',
        icon: 'fa fa-truck',
        component: ContentPickups,
        link: '/pickups'
    },
    {
        title: 'Supplies',
        icon: 'fa fa-cubes',
        method: () => {
            window.open('https://store.usps.com/store/results/free-shipping-supplies/_/N-alnx4j', 'blank');
        }
    },
    {
        title: 'Referrals',
        icon: 'fa fa-address-book',
        showMethod: (user) => {
            return CommonFunctions.inputToBool(user.referral_program)
        },
        component: ContentReferrals,
        link: '/referrals',
    },
    {
        title: 'Admin',
        icon: 'fa fa-lock',
        showMethod: (user) => {
            return CommonFunctions.inputToBool(user.admin)
        },
        method: () => {
            window.location = '/admin'
        }
    },
    {
        viewOnly: true,
        link: '/integrations',
        component: ContentIntegrations
    }
]

ReactDOM.render(<Portal
    prefix='/portal'
    menus={MENUS}
/>, document.getElementById('goa'));