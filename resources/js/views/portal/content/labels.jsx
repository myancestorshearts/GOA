import React from 'react';
import Spacer from '../../../common/components/spacer';
import PanelSearchTable from '../../../common/portal/panel/search-table';

import ActionPanel from '../../../common/portal/panel/action';

import GoaState from '../../../common/goa-state';


import ActiveLabel from '../../../common/models/label/model';
import ActiveReturnLabel from '../../../common/models/return-label/model';

import FormPurchaseLabel from './forms/purchase-label';

import toastr from 'toastr';


import LabelModelMass from '../../../common/models/label/model-mass';


const LABEL_PROPERTIES = {
    tracking: {
        title: 'Tracking',
        property: 'tracking',
        type: 'LINK',
        default: true,
        linkMethod: (model) => {
            return `https://tools.usps.com/go/TrackConfirmAction?tLabels=${model.tracking}`
        }
    },
    status: {
        title: 'Status',
        property: 'refunded',
        type: 'BOOLEAN',
        boolTrue: 'Refunded',
        boolFalse: 'Active',
        default: true
    },
    from: {
        title: 'From',
        property: 'from_address',
        type: 'ADDRESS',
        default: true
    },
    to: {
        title: 'To',
        property: 'shipment,to_address',
        type: 'ADDRESS',
        default: true
    },
    reference: {
        title: 'Reference',
        property: 'shipment,reference',
        type: 'TEXT',
        default: true
    },
    wieght: {
        title: 'Weight',
        property: 'weight',
        type: 'WEIGHT',
        default: true
    },
    service: {
        title: 'Service',
        property: 'service',
        type: 'TEXT',
        default: true
    },
    width: {
        title: 'Width',
        property: 'shipment,package,width',
        type: 'LENGTH'
    },
    length: {
        title: 'Length',
        property: 'shipment,package,length',
        type: 'LENGTH'
    },
    height: {
        title: 'Height',
        property: 'shipment,package,height',
        type: 'LENGTH'
    },
    created: {
        title: 'Date Submitted',
        property: 'created_at',
        type: 'DATETIME',
        default: true
    }
};

export default class Orders extends React.Component {

    constructor(props) {
        super(props)

        this.state = {
            models: []
        }

        this.handleShowLabel = this.handleShowLabel.bind(this);
        this.handleShowPurchaseLabel = this.handleShowPurchaseLabel.bind(this);
        this.handleCancel = this.handleCancel.bind(this);
        this.handleReturn = this.handleReturn.bind(this);
        this.handleShowReturnLabel = this.handleShowReturnLabel.bind(this);
        this.handleSelectModels = this.handleSelectModels.bind(this);
        this.handlePrint = this.handlePrint.bind(this);
    }

    handleShowLabel(model, print = false) {
        this.setState({ models: [model] }, () => {
            if (print) this.handlePrint()
        });
    }

    handleShowReturnLabel(returnLabel) {

        GoaState.set('active-modal', {
            component: <ActiveReturnLabel
                returnLabel={returnLabel}
                onClose={() => GoaState.empty('active-modal')}
            />
        });
    }

    handleShowPurchaseLabel() {



        GoaState.set('active-modal', {
            component: <FormPurchaseLabel
                onPurchase={(model) => {
                    this.handleShowLabel(model, true)
                    if (this.tableLabels) this.tableLabels.handleSearch();
                }}
                onCancel={() => GoaState.empty('active-modal')}
            />
        });
    }

    handleCancel(model) {
        GoaApi.Label.refund({
            label_id: model.id
        }, success => {
            if (this.tableLabels) this.tableLabels.handleSearch();
        }, failure => toastr.error(failure.message))
    }

    handleReturn(model) {
        GoaApi.Label.return({
            label_id: model.id
        }, success => {
            if (this.tableLabels) this.tableLabels.handleSearch();
            this.handleShowReturnLabel(success.data.model);
        }, failure => toastr.error(failure.message))
    }

    handleSelectModels(models) {
        this.setState({
            models: models
        })
    }

    handlePrint() {
        GoaState.set('active-modal', {
            component: <LabelModelMass
                labels={this.state.models}
                onClose={() => GoaState.empty('active-modal')}
            />
        });
    }
    render() {
        return (
            <React.Fragment>
                <div style={STYLES.dashboardActionsContainer}>
                    <ActionPanel label='Purchase Label' icon='fa fa-plus' onClick={this.handleShowPurchaseLabel} />
                </div>

                <Spacer space='20px' />
                <PanelSearchTable
                    ref={e => this.tableLabels = e}
                    properties={LABEL_PROPERTIES}
                    onSelectModel={this.handleShowLabel}
                    tableKey='Labels'
                    tableTitle='History'
                    tableIcon='fa fa-file'
                    searchMethod={GoaApi.Label.search}
                    searchArgs={{
                        include_classes: 'address,package,rate,shipment',
                        pending: 1
                    }}
                    onCancel={this.handleCancel}
                    onUndo={this.handleReturn}
                    onSelectModels={this.handleSelectModels}
                    onPrint={this.state.models.length > 0 ? this.handlePrint : undefined}
                />

            </React.Fragment>
        )
    }
}

const STYLES = {
    dashboardActionsContainer: {
        display: 'flex',
    },
    button: {
        marginLeft: '10px'
    },
    buttonsContainer: {
        marginLeft: 'auto',
        display: 'flex'
    }
}

