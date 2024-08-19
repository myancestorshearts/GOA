
import React from 'react';
import Button from '../../../../common/inputs/button';
import Spacer from '../../../../common/components/spacer';
import toastr from 'toastr';
import StateSelect from '../../../../common/inputs/state-select';
import Text from '../../../../common/inputs/text';
import Header from '../../../../common/fields/header';
import StreetAddress from '../../../../common/inputs/street-address';
import GoaBrand from '../../../../common/brand';
import SelectModel from '../../../../common/inputs/select-model';
import Functions from '../../../../common/functions';
import Weight from '../../../../common/inputs/weight';
import SelectPackage from '../../../../common/inputs/select-package';
import SelectFromAddress from '../../../../common/inputs/select-from-address';
import ContainerFlex from '../../../../common/components/flex-container';
import ComponentLoading from '../../../../common/components/loading';


export default class FormPurchaseLabel extends React.Component {

    constructor(props) {
        super(props);
        
        this.state = {
            name: '',
            email: '',
            company: '',
            phone: '',
            reference: '',
            street_1: '',
            street_2: '',
            city: '',
            state: '',
            postal: '',
            country: 'US',
            weight: 0,
            address: undefined,
            package: undefined,
            shipment: undefined,
            rate: undefined,
            rates: [],
            shipmentLoading: false, 
            purchasing: false,

           
        };

        this.handleSubmit = this.handleSubmit.bind(this);
        this.handleAddProduct = this.handleAddProduct.bind(this);
        this.handleRemoveProduct = this.handleRemoveProduct.bind(this);
        this.handleAutoFill = this.handleAutofill.bind(this);

        this.getRate = this.getRate.bind(this);
        this.rateIndex = 0;
    }

    getRate() {
        
        if (!this.state.address) {
            toastr.error('Address must be selected');
            return;
        }
        if (this.shipmentLoading) return;

        let rateIndex = ++this.rateIndex;
        this.setState({
            rate: undefined,
            shipment: undefined,
            shipmentLoading: true
        }, () => {
            GoaApi.Shipment.rate({
                weight: this.state.weight,
                from_address_id: this.state.address.id,
                package: this.state.package,
                to_address: {
                    name: this.state.name,
                    email: this.state.email,
                    company: this.state.company,
                    phone: this.state.phone,
                    street_1: this.state.street_1,
                    street_2: this.state.street_2,
                    city: this.state.city,
                    state: this.state.state,
                    postal: this.state.postal,
                    country: this.state.country,
                },
                reference: this.state.reference
            }, success => {
                // protects users from showing incorrect rates
                if (rateIndex != this.rateIndex) return;
                let rates = success.data.model.rates.map(x => ({
                    price: x.total_charge,
                    name: x.service + ' - ' + x.delivery_days + ' Day(s) - ' + Functions.convertToMoney(x.total_charge),
                    id: x.id
                }))
                this.setState({ shipment: success.data.model, rates: rates, shipmentLoading: false })
            }, failure => {
                this.setState({ shipmentLoading: false })
                toastr.error(failure.message);
            })
        })
    }

    handleAddProduct(e) {
        e.stopPropagation()
        this.state.orderProducts.push({ ...DEFAULT_PRODUCT });
        this.forceUpdate();
    }

    handleRemoveProduct(i) {
        let index = (i - 1)
        let productArray = this.state.orderProducts
        productArray.splice(index, 1)
        this.setState({ orderProducts: productArray }, success => this.forceUpdate())
    }

    handleSubmit(e) {
        if (e) e.preventDefault();

        if(this.shipmentLoading) return;

       
       

        if (!this.state.shipment) {
            toastr.error('Must select rate before purchasing label');
            return;
        }
        this.setState({
            purchasing: true
        }, () => { 
            GoaApi.Label.purchase({
                shipment_id: this.state.shipment.id,
                rate_id: this.state.rate.id
            }, success => {
                // update wallet balance on user
                let newBalance = (success.data.refill_transaction && !Functions.inputToBool(success.data.refill_transaction.pending)) ?
                    success.data.refill_transaction.balance : success.data.wallet_transaction.balance;
                GoaUser.updateUser({ wallet_balance: newBalance });
    
                // remove purchasing so we can run api again
                this.setState ({purchasing: false});
    
    
                if (this.props.onPurchase) this.props.onPurchase(success.data.model);
            }, failure => {
                toastr.error(failure.message);
                this.setState ({purchasing: false});
              
            })
        })
       
    }
   
    

    handleAutofill(e) {
        let newState = { ...this.state, ...e }
        this.setState(newState)
    }




    render() {
        return (
            <form onSubmit={this.handleSubmit}>
                <div>
                    <Header title='Details' top={true} />
                    <Spacer space='20px' />
                    <ContainerFlex rowGap='0px' style={STYLES.flexContainer}>
                        <Text
                            autoFocus={true}
                            title='Customer Name'
                            onChange={e => this.setState({ name: e.target.value, shipment: null, shipmentLoading: false })}
                            value={this.state.name}
                            stylescontainer={STYLES.input}
                            stylesinput={STYLES.textInput}
                            styleslabel={STYLES.label}
                        />
                        <Text
                            title='Reference'
                            onChange={e => this.setState({ reference: e.target.value, shipment: null, shipmentLoading: false })}
                            value={this.state.reference}
                            stylescontainer={STYLES.input}
                            stylesinput={STYLES.textInput}
                            styleslabel={STYLES.label}
                        />
                        <Text
                            title='Company'
                            onChange={e => this.setState({ company: e.target.value, shipment: null, shipmentLoading: false })}
                            value={this.state.company}
                            stylescontainer={STYLES.input}
                            stylesinput={STYLES.textInput}
                            styleslabel={STYLES.label}
                        />
                        <Text
                            title='Email'
                            onChange={e => this.setState({ email: e.target.value, shipment: null, shipmentLoading: false })}
                            value={this.state.email}
                            stylescontainer={STYLES.input}
                            stylesinput={STYLES.textInput}
                            styleslabel={STYLES.label}
                        />
                        <Text
                            title='Phone'
                            onChange={e => this.setState({ phone: e.target.value, shipment: null, shipmentLoading: false })}
                            value={this.state.phone}
                            stylescontainer={STYLES.input}
                            stylesinput={STYLES.textInput}
                            styleslabel={STYLES.label}
                        />
                    </ContainerFlex>
                    <Spacer space='20px' />
                    <Header title='Address' />
                    <Spacer space='20px' />

                    <ContainerFlex rowGap='0px' style={STYLES.flexContainer}>
                        <StreetAddress
                            title='Street 1'
                            stylescontainer={STYLES.input}
                            stylesinput={STYLES.textInput}
                            value={this.state.street_1}
                            handleChange={e => this.setState({ street_1: e, shipment: null, shipmentLoading: false })}
                            handleAutofill={e => this.handleAutofill(e)}
                            styleslabel={STYLES.label}
                        />
                        <Text
                            title='Street 2'
                            onChange={e => this.setState({ street_2: e.target.value, shipment: null, shipmentLoading: false })}
                            value={this.state.street_2}
                            stylescontainer={STYLES.input}
                            stylesinput={STYLES.textInput}
                            styleslabel={STYLES.label}
                        />
                        <Text
                            title='City'
                            onChange={e => this.setState({ city: e.target.value, shipment: null, shipmentLoading: false })}
                            value={this.state.city}
                            stylescontainer={STYLES.input}
                            stylesinput={STYLES.textInput}
                            styleslabel={STYLES.label}
                        />
                        <StateSelect
                            value={this.state.state}
                            handleChange={e => this.setState({ state: e.target.value, shipment: null, shipmentLoading: false })}
                            styleslabel={STYLES.label}
                            stylesselect={STYLES.selectInput}
                        />
                        <Text
                            title='Postal'
                            onChange={e => this.setState({ postal: e.target.value, shipment: null, shipmentLoading: false })}
                            value={this.state.postal}
                            stylescontainer={STYLES.input}
                            stylesinput={STYLES.textInput}
                            styleslabel={STYLES.label}
                        />
                    </ContainerFlex>

                    {/* From Address */}
                    <SelectFromAddress
                        onChange={x => this.setState({ address: x, shipment: null, shipmentLoading: false })}
                        stylesinput={STYLES.selectInput}
                        styleslabel={STYLES.label}
                    />

                    {/* Weight */}
                    <Weight
                        value={this.state.weight}
                        onChange={value => this.setState({ weight: value, shipment: null, shipmentLoading: false })}
                        stylesinput={STYLES.textInput}
                        styleslabel={STYLES.label}
                    />

                    {/* Package */}
                    <SelectPackage
                        onChange={x => this.setState({ package: x, shipment: null, shipmentLoading: false })}
                        stylestext={STYLES.textInput}
                        stylesselect={STYLES.selectInput}
                        styleslabel={STYLES.label}
                    />

                    {/* Service */}
                    {
                        this.state.shipmentLoading || this.state.shipment ?
                            <ComponentLoading loading={this.state.shipmentLoading}>
                                <React.Fragment>
                                    <SelectModel
                                        placeholderLoading='Loading Rates'
                                        placeholder={this.state.shipment ? 'Select Rate' : 'Fill out all required fields to get rates'}
                                        disabled={!this.state.shipment}
                                        loading={this.state.shipmentLoading}
                                        title='Rate'
                                        models={this.state.rates}
                                        value={this.state.rate}
                                        onChange={x => this.setState({ rate: x })}
                                        stylesselect={STYLES.selectInput}
                                        styleslabel={STYLES.label}
                                    />
                                    <ComponentLoading loading={this.state.purchasing}>
                                        <Button
                                            props={{ type: 'submit'}}
                                            color={GoaBrand.getPrimaryColor()}
                                            stylesbutton={STYLES.button}
                                            stylesbuttonhover={STYLES.buttonHover}
                                        >
                                            Purchase Label
                                        </Button>
                                    </ComponentLoading>
                                    
                                </React.Fragment>
                            </ComponentLoading> :
                            <Button
                                props={{
                                    type: 'button',
                                    onClick: this.getRate
                                }}
                                color={GoaBrand.getPrimaryColor()}
                                stylesbutton={STYLES.button}
                                stylesbuttonhover={STYLES.buttonHover}
                            >
                                Rate
                            </Button>
                    }
                </div>
            </form>
        )
    }
}

const STYLES = {
    flexContainer: {
        maxWidth: '650px'
    },
    input: {
        flex: 1
    },
    numberinput: {
        flex: 1,
    },
    label: {
        marginBottom: '2px',
        fontSize: '16px',
        fontWeight: 'bold'
    },
    icon: {
        cursor: 'pointer',
        marginLeft: '10px',
        fontSize: '18px',
        marginTop: '10px'
    },
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
    button: {
        height: '50px',
        borderRadius: '20px',
        color: 'white',
        backgroundColor: GoaBrand.getPrimaryColor()
    },
    buttonHover: {
        backgroundColor: GoaBrand.getPrimaryHoverColor()
    }
}