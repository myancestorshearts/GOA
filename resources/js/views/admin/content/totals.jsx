
import React from 'react';
import Spacer from '../../../common/components/spacer';
import PanelSearchTable from '../../../common/portal/panel/search-table';
import Panel from '../../../common/portal/panel/index';

import UserModel from '../../../common/models/user/model';

import toastr from 'toastr';

import AdminApi from '../../../common/api/admin';
import GoaState from '../../../common/goa-state';

import ComponentFlexContainer from '../../../common/components/flex-container';
import InputDate from '../../../common/inputs/date';
import Functions from '../../../common/functions';

const TOTAL_PROPERTIES = {
    name: {
        title: 'Name',
        property: 'USER_NAME',
        type: 'TEXT',
        default: true
    },
    company: {
        title: 'Company',
        property: 'USER_COMPANY',
        type: 'TEXT',
        default: true
    },
    email: {
        title: 'Email',
        property: 'email',
        type: 'TEXT',
        default: false
    },
    phone: {
        title: 'Phone',
        property: 'phone',
        type: 'TEXT',
        default: false
    },
    spend: {
        title: 'Total',
        property: 'SPEND',
        type: 'CURRENCY',
        default: true
    },
    spenduspsfirstclass: {
        title: 'USPS FC',
        property: 'SPEND_USPS_FIRST_CLASS',
        type: 'CURRENCY',
        default: true
    },
    spenduspspriority: {
        title: 'USPS P',
        property: 'SPEND_USPS_PRIORITY',
        type: 'CURRENCY',
        default: true
    },
    spenduspscubic: {
        title: 'USPS C',
        property: 'SPEND_USPS_CUBIC',
        type: 'CURRENCY',
        default: true
    },
    spenduspspriorityexpress: {
        title: 'USPS PE',
        property: 'SPEND_USPS_PRIORITY_EXPRESS',
        type: 'CURRENCY',
        default: true
    },
    spenduspsparcelselect: {
        title: 'USPS PS',
        property: 'SPEND_USPS_PARCEL_SELECT',
        type: 'CURRENCY',
        default: true
    },
    profit: {
        title: 'PROFIT',
        property: 'PROFIT',
        type: 'CURRENCY',
        default: true
    },
    profitcalculatedcount: {
        title: 'Count Calculated',
        property: 'PROFIT_CALCULATED',
        type: 'TEXT',
        default: true
    },
    count: {
        title: 'Count Total',
        property: 'COUNT',
        type: 'TEXT',
        default: true
    }
};

export default class Totals extends React.Component {

    constructor(props) {
        super(props)

        this.state = {
            filter_start: (new Date()).getStartOfMonth(),
            filter_end: (new Date()).getEndOfMonth()
        }

        this.handleRefresh = this.handleRefresh.bind(this);
    }

    handleRefresh() {
        if (this.tableTotals) this.tableTotals.handleSearch();
    }

    render() {
        return (
            <React.Fragment>
                <ComponentFlexContainer>
                    <Panel>
                        <InputDate
                            title='Filter Start'
                            value={this.state.filter_start}
                            onChange={x => this.setState({ filter_start: x }, this.handleRefresh)}
                        />
                        <InputDate
                            title='Filter End'
                            value={this.state.filter_end}
                            onChange={x => this.setState({ filter_end: x }, this.handleRefresh)}
                        />
                    </Panel>
                    <PanelSearchTable
                        ref={e => this.tableTotals = e}
                        properties={TOTAL_PROPERTIES}
                        tableKey='AdminWalletTransactionTotals'
                        tableTitle='Totals'
                        tableIcon='fa fa-dashboard'
                        searchMethod={AdminApi.WalletTransaction.totals}
                        searchArgs={{
                            created_after: Functions.convertDateToMysql(this.state.filter_start.getStartOfDay()),
                            created_before: Functions.convertDateToMysql(this.state.filter_end.getEndOfDay())
                        }}
                    //showSearch={true}
                    />
                </ComponentFlexContainer>

            </React.Fragment>
        )
    }
}

const STYES = {

}
