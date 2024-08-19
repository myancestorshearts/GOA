
import React from 'react';
import Header from '../../fields/header';
import Label from '../../fields/label';
import Spacer from '../../components/spacer';
import Functions from '../../functions';
import Button from '../../inputs/button';
import SelectModel from '../../inputs/select-model';
import toastr from 'toastr';
import GoaBrand from '../../brand';
import Weight from '../../inputs/weight';
import SelectPackage from '../../inputs/select-package';
import SelectFromAddress from '../../inputs/select-from-address';
import LabelModel from '../label/model';
import GoaState from '../../goa-state';
import ComponentLoading from '../../components/loading';
import LabelModelMass from '../label/model-mass';
import ApiMass from '../../api-mass';

import InputBoolean from '../../../common/inputs/boolean';
import InputText from '../../inputs/text';

export default class ModelMass extends React.Component {

    constructor(props) {
        super(props)
        this.state = {
            weight: 0,
            address: undefined,
            package: undefined,
            shipments: [],
            purchasedShipments: [],
            purchasedShipmentsFailed: [],
            invalidShipments: [],
            rate: undefined,
            rates: [],
            shipmentsLoading: false,
            purchasing: false,
            insurance: false
        }

        this.getRates = this.getRates.bind(this);
        this.handlePurchase = this.handlePurchase.bind(this);
        this.modelsLength = this.props.models.length;
        this.rateIndex = 0;
    }

    getRates() {

        if (this.state.shipmentsLoading) return;

        let rateIndex = ++this.rateIndex;

        this.setState({
            rate: undefined,
            shipments: [],
            invalidShipments: [],
            shipmentsLoading: true
        }, () => {

            let apiMass = new ApiMass(5);


            let ratesByService = {};



            let finalize = () => {

                if (this.state.shipments.length + this.state.invalidShipments.length != this.props.models.length) {
                    this.forceUpdate();
                    return;
                }

                this.state.shipments.map(x => {
                    x.rates.map(y => {
                        if (!ratesByService[y.service]) ratesByService[y.service] = {
                            price: 0,
                            carrier: y.carrier,
                            id: y.service
                        }
                        ratesByService[y.service].price += Number(y.rate);
                        ratesByService[y.service].name = y.service + ' ' + Functions.convertToMoney(ratesByService[y.service].price);
                    })
                })

                let rates = Object.keys(ratesByService).map(key => {
                    return ratesByService[key];
                });

                this.setState({ rates: rates, shipmentsLoading: false });
            }

            this.props.models.forEach(orderGroup => {


                let args = {
                    weight: this.state.weight,
                    from_address_id: this.state.address.id,
                    package: this.state.package,
                    order_group_id: orderGroup.id
                }

                apiMass.push(GoaApi.Shipment.rate, args, success => {
                    if (rateIndex != this.rateIndex) return;
                    this.state.shipments.push(success.data.model)
                    finalize();
                }, () => {
                    if (rateIndex != this.rateIndex) return;
                    this.state.invalidShipments.push(orderGroup)
                    finalize();
                });

            })

            apiMass.process();


        })
    }

    componentDidUpdate(prevProps) {
        if (this.modelsLength != this.props.models.length) {
            this.modelsLength = this.props.models.length;
            this.setState({
                shipments: [],
                rate: undefined
            })
        }
    }

    handlePurchase() {
        if (this.state.purchasing) return false;

        this.setState({
            purchasing: true,
            purchasedShipments: [],
            purchasedShipmentsFailed: []
        }, () => {

            let apiMass = new ApiMass(5);
            this.state.shipments.forEach(x => {
                let foundRate = false;

                x.rates.forEach(rate => {
                    if (rate.service == this.state.rate.id && rate.carrier == this.state.rate.carrier) {
                        foundRate = true;

                        apiMass.push(
                            GoaApi.Label.purchase, {
                            shipment_id: x.id,
                            rate_id: rate.id
                        }, success => {

                            // update the wallet balance
                            GoaUser.updateUser({ wallet_balance: success.data.wallet_transaction.balance });

                            // trigger success callback
                            this.handleSuccess(x, success.data.model);

                        }, failure => {
                            this.handleFail(x, failure.message);
                        })
                    }
                })
                if (!foundRate) return this.handleFail(x, 'Rate not found');
            })

            apiMass.process();

        })
    }

    handleSuccess(shipment, label) {
        this.state.purchasedShipments.push({
            shipment: shipment,
            label: label
        });
        this.forceUpdate();
        if (this.state.purchasedShipments.length + this.state.purchasedShipmentsFailed.length == this.state.shipments.length) this.handleFinalize();
    }

    handleFail(shipment, message) {
        this.state.purchasedShipmentsFailed.push({
            shipment: shipment,
            message: message
        });
        this.forceUpdate();
    }

    handleFinalize() {
        if (this.props.onPurchaseFinalized) this.props.onPurchaseFinalized()
        GoaState.set('active-model', { component: null, model: null })


        let labels = this.state.purchasedShipments.map(x => x.label);

        GoaState.set('active-modal', {
            component: <LabelModelMass
                labels={labels}
                onClose={() => GoaState.empty('active-modal')}
            />
        });
    }

    getCompletedPercent() {
        let totalCount = this.state.shipments ? this.state.shipments.length : 1;

        let completedSuccessful = Math.round(Number((this.state.purchasedShipments.length + this.state.purchasedShipmentsFailed.length) / totalCount) * 100, 0);
        return completedSuccessful;
    }

    getCompletedRatePercent() {
        let totalCount = this.props.models.length

        let completedSuccessful = Math.round(Number((this.state.shipments.length + this.state.invalidShipments.length) / totalCount) * 100, 0);
        return completedSuccessful;
    }


    render() {

        let models = this.props.models;

        return (
            <React.Fragment>
                <Header title='Details' top={true} />
                <Spacer space='10px' />

                <Label label='Order Count'>
                    {models.length}
                </Label>

                {
                    this.state.invalidShipments.length > 0 ?
                        <React.Fragment>
                            <Spacer space='10px' />
                            <Header title='Unable To Rate' />
                            <Spacer space='10px' />
                            <Label label='Count'>
                                {this.state.invalidShipments.length}
                            </Label>
                            <Label label='Refs'>
                                <div>{this.state.invalidShipments.map((x) => <div key={x.id}>{Functions.isEmpty(x.reference) ? '' : `(${x.reference})`} {x.name}</div>)}</div>
                            </Label>
                        </React.Fragment> : null
                }


                <Spacer space='10px' />
                <Header title='Purchase Labels' />
                <Spacer space='10px' />

                {/* From Address */}
                <SelectFromAddress
                    onChange={x => this.setState({ address: x, shipments: [], shipmentsLoading: false })}
                    color='rgb(175, 175, 175)'
                    stylesinput={STYLES.selectInput}
                    styleslabel={STYLES.label}
                />

                <Weight
                    ref={e => this.weightInput = e}
                    value={this.state.weight}
                    onChange={x => this.setState({ weight: x, shipments: [], shipmentsLoading: false })}
                    color='rgb(175, 175, 175)'
                    stylesinput={STYLES.textInput}
                    styleslabel={STYLES.label}
                />



                {/*<Label label='Insurance'>
                    <InputBoolean title='' model={this.state} property='insurance' onChange={() => this.forceUpdate()} />
                </Label>

                {
                    this.state.insurance ?
                        <InputText
                            stylesinput={STYLES.textInput}
                            styleslabel={STYLES.label}
                            title='Amount To Insure'
                        /> : null
                }*/}

                {/* Package */}
                <SelectPackage
                    onChange={x => this.setState({ package: x, shipments: [], shipmentsLoading: false })}
                    color='rgb(175, 175, 175)'
                    stylesselect={STYLES.selectInput}
                    stylestext={STYLES.textInput}
                    styleslabel={STYLES.label}
                />
                {this.state.shipmentsLoading || this.state.shipments.length > 0 ?
                    <ComponentLoading loading={this.state.shipmentsLoading} percentCompleted={this.getCompletedRatePercent()}>
                        <React.Fragment>
                            {/* Service */}
                            <SelectModel
                                placeholderLoading='Loading Rates'
                                placeholder={this.state.shipments ? 'Select Rate' : 'Fill out all required fields'}
                                disabled={!this.state.shipments}
                                loading={this.state.shipmentsLoading}
                                title='Rate'
                                models={this.state.rates}
                                value={this.state.rate}
                                onChange={x => this.setState({ rate: x })}
                                color='rgb(175, 175, 175)'
                                stylesselect={STYLES.selectInput}
                                styleslabel={STYLES.label}
                            />

                            {/* Purchase Button */}
                            {
                                this.state.rate ?
                                    <ComponentLoading loading={this.state.purchasing} percentCompleted={this.getCompletedPercent()}>
                                        <Button
                                            color={GoaBrand.getPrimaryColor()}
                                            stylesbutton={STYLES.purchaseButton}
                                            props={{ onClick: this.handlePurchase }}
                                        >
                                            Purchase Labels
                                        </Button>
                                    </ComponentLoading> : null
                            }
                        </React.Fragment>
                    </ComponentLoading> :
                    <Button
                        color={GoaBrand.getPrimaryColor()}
                        stylesbutton={STYLES.purchaseButton}
                        props={{ onClick: this.getRates }}
                    >
                        Rate
                    </Button>
                }
            </React.Fragment>
        )
    }
}


const STYLES = {
    textInput: {
        fontWeight: '600',
        fontFamily: 'poppins',
        fontSize: '18px',
        color: '#273240',
        borderRadius: '20px',
        height: '44px',
        borderColor: '#96A0AF'
    },
    selectInput: {
        fontWeight: '600',
        fontFamily: 'poppins',
        fontSize: '18px',
        color: '#273240',
        borderRadius: '20px',
        height: '50px',
        borderColor: '#96A0AF'
    },
    label: {
        fontFamily: 'poppins',
        fontWeight: '600',
        fontSize: '12px',
        color: '#96A0AF'
    },
    purchaseButton: {
        height: '50px',
        marginTop: '20px',
        borderRadius: '20px'
    }
}