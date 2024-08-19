
import React from 'react';
import ReactDOM from 'react-dom';

import InputText from '../../common/inputs/text';
import InputButton from '../../common/inputs/button';
import InputSelect from '../../common/inputs/select';
import InputWeight from '../../common/inputs/weight';

import ComponentLoading from '../../common/components/loading';
import ComponentFlexContainer from '../../common/components/flex-container';
import ComponentSpacer from '../../common/components/spacer';

import Functions from '../../common/functions';
import ApiRestApi from '../../common/api/restapi';
import toastr from 'toastr';

const PACKAGE_TYPES = [
    {
        label: 'Parcel',
        value: 'Parcel',
    },
    {
        label: 'Soft Pack',
        value: 'SoftPack'
    },
    {
        label: 'USPS - Flat Rate Padded Envelope',
        value: 'UspsFlatRatePaddedEnvelope'
    },
    {
        label: 'USPS - Flat Rate Legal Envelope',
        value: 'UspsFlatRateLegalEnvelope'
    },
    {
        label: 'USPS - Small Flat Rate Envelope',
        value: 'UspsSmallFlatRateEnvelope'
    },
    {
        label: 'USPS - Flat Rate Envelope',
        value: 'UspsFlatRateEnvelope'
    },
    {
        label: 'USPS - Small Flat Rate Box',
        value: 'UspsSmallFlatRateBox'
    },
    {
        label: 'USPS - Medium Flat Rate Box (Top Loading)',
        value: 'UspsMediumFlatRateBoxTopLoading'
    },
    {
        label: 'USPS - Medium Flat Rate Box (Side Loading)',
        value: 'UspsMediumFlatRateBoxSideLoading'
    },
    {
        label: 'USPS - Large Flat Rate Box',
        value: 'UspsLargeFlatRateBox'
    },
    {
        label: 'Regional Rate Box A Top Loading',
        value: 'UspsRegionalRateBoxATopLoading'
    },
    {
        label: 'Regional Rate Box A Side Loading',
        value: 'UspsRegionalRateBoxASideLoading'
    },
    {
        label: 'Regional Rate Box B Top Loading',
        value: 'UspsRegionalRateBoxBTopLoading'
    },
    {
        label: 'Regional Rate Box B Side Loading',
        value: 'UspsRegionalRateBoxBSideLoading'
    }
]


class Calcualator extends React.Component {

    constructor(props) {
        super(props);

        this.state = {
            length: '',
            package: 'Parcel',
            width: '',
            height: '',
            zipcodefrom: '',
            zipcodeto: '',
            weight: '',
            loading: false,
            rates: []
        }
        this.handleCalculateRates = this.handleCalculateRates.bind(this)
    }

    handleCalculateRates(e) {
        e.preventDefault();
        console.log(this.state);

        this.setState({
            loading: true
        }, () => {
            ApiRestApi.Tokens.generate({}, success => {
                let token = success.data.model.access.token;

                ApiRestApi.Shipment.rateOnly({
                    from_postal: this.state.zipcodefrom,
                    to_postal: this.state.zipcodeto,
                    package: {
                        type: this.state.package,
                        height: this.state.height,
                        width: this.state.width,
                        length: this.state.length
                    },
                    weight: this.state.weight
                }, successRate => {
                    this.setState({ loading: false, rates: successRate.data.model.rates })
                }, failureRate => {
                    this.setState({ loading: false })
                    toastr.error(failureRate.message);
                }, token)
            }, failure => {
                this.setState({ loading: false })
                toastr.error(failure.message);

            })
        })
    }

    render() {


        let rates = this.state.rates.map((x, i) => <Rate key={i} rate={x} />)

        return (
            <form onSubmit={this.handleCalculateRates} style={STYLES.form}>
                <InputSelect
                    stylesselect={STYLES.selectStyles}
                    stylescontainer={STYLES.inputContainer}
                    name='package type'
                    styleslabel={STYLES.inputTitles}
                    value={this.state.package}
                    options={PACKAGE_TYPES}
                    onChange={(e) => this.setState({ package: e.target.value })}
                />
                <ComponentFlexContainer gap='25px'>
                    <InputText
                        stylesinput={{ ...STYLES.inputText, ...STYLES.zipcodeInput }}
                        stylescontainer={STYLES.inputContainer}
                        name='zipcode from'
                        title='Zipcode From'
                        styleslabel={STYLES.inputTitles}
                        value={this.state.zipcodefrom}
                        onChange={(e) => this.setState({ zipcodefrom: e.target.value })}
                        styles={STYLES.zipcodes}
                    />
                    <InputText
                        stylesinput={{ ...STYLES.inputText, ...STYLES.zipcodeInput }}
                        stylescontainer={STYLES.inputContainer}
                        name='zipcode to'
                        title='Zipcode To'
                        styleslabel={STYLES.inputTitles}
                        value={this.state.zipcodeto}
                        onChange={(e) => this.setState({ zipcodeto: e.target.value })}
                    />
                </ComponentFlexContainer >

                <InputWeight
                    gap='25px'
                    stylesinput={STYLES.inputText}
                    stylescontainer={STYLES.inputContainer}
                    name='weight'
                    title='weight'
                    styleslabel={STYLES.inputTitles}
                    value={this.state.weight}
                    onChange={value => this.setState({ weight: value }, this.getRate)}
                />
                <ComponentFlexContainer gap='25px'>
                    <InputText
                        stylescontainer={STYLES.inputContainer}
                        stylesinput={STYLES.inputText}
                        name='length'
                        title='Length'
                        styleslabel={STYLES.inputTitles}
                        value={this.state.length}
                        onChange={(e) => this.setState({ length: e.target.value })}
                    />
                    <InputText
                        stylesinput={STYLES.inputText}
                        stylescontainer={STYLES.inputContainer}
                        name='width'
                        title='Width'
                        styleslabel={STYLES.inputTitles}
                        value={this.state.width}
                        onChange={(e) => this.setState({ width: e.target.value })}
                    />
                    <InputText
                        stylesinput={STYLES.inputText}
                        stylescontainer={STYLES.inputContainer}
                        name='height'
                        title='Height'
                        styleslabel={STYLES.inputTitles}
                        value={this.state.height}
                        onChange={(e) => this.setState({ height: e.target.value })}
                    />
                </ComponentFlexContainer>

                <InputButton
                    stylesbuttonhover={STYLES.buttonHover}
                    stylesbutton={STYLES.button}
                    props={{
                        onClick: this.handleCalculateRates
                    }}
                >
                    Get Shipping Rates
                </InputButton>

                <ComponentSpacer space="20px" />
                <ComponentLoading loading={this.state.loading}>
                    <ComponentFlexContainer gap='25px'>
                        {rates}
                    </ComponentFlexContainer>
                </ComponentLoading>
            </form>
        )
    }
}

const Rate = (props) => {
    return (
        <div style={STYLES.serviceContainer}>
            <div style={STYLES.rateService}>{props.rate.service}</div>
            <div style={STYLES.rateList}>{Functions.convertToMoney(props.rate.rate_list)}</div>
            <div><a style={STYLES.shipNow} href="http://app.shipdude.com" target="_blank">Ship Now</a></div>
        </div>
    )
}

const STYLES = {
    loadingContainer: {
        marginTop: '20px'
    },
    serviceContainer: {
        borderWidth: '2px',
        borderRadius: '20px',
        borderColor: 'white',
        borderStyle: 'solid',
        textAlign: 'center',
        fontSize: '45px',
        flex: 1,
        padding: '20px'
    },
    inputTitles: {
        fontFamily: 'arial',
        minWidth: '50px',
        fontSize: '20px',
        color: 'white',
        borderTop: 'none'

    },
    rateService: {
        color: 'white',
        fontSize: '20px',
        marginBottom: '5px',
        fontWeight: 'bold',
    },
    rateList: {
        fontSize: '70px',
        fontWeight: 'bold',
        marginTop: '5px',
        color: '#3B99F4'
    },
    inputContainer: {
        flex: 1,
        fontSize: '45px'
    },
    button: {
        color: '#FDB63E',
        height: '70px',
        fontSize: '35px',
        borderRadius: '15px',
        border: 'none'
    },
    buttonHover: {
        backgroundColor: '#0906b0'
    },
    inputText: {
        color: '#3B99F4',
        borderColor: 'white',
        fontSize: '70px',
        height: '100px',
        borderTop: 'none',
        borderLeft: 'none',
        borderRight: 'none',
        borderRadius: 'none',
        maxLength: 10,
        padding: '0px',
        paddingBottom: '40px',
        paddintTop: '20px',
        fontWeight: 'bold',
        width: '100%',
        marginBottom: '70px',
        height: '130px'
    },
    selectStyles: {
        color: '#3B99F4',
        borderColor: 'white',
        fontSize: '40px',
        border: 'none',
        borderRadius: '0px',
        height: '100px',
        backgroundColor: '#020052',
        marginBottom: '100px',
        paddingLeft: '20px',
        paddingRight: '20px'
    },
    form: {
        width: '100%'
    },
    shipNow: {
        fontSize: '30px',
        color: '#FDB63E',
        fontDecoration: 'none'
    },
    zipcodeInput: {
        minWidth: '200px'
    }
}



ReactDOM.render(<Calcualator />, document.getElementById('shipping-calculator'));

