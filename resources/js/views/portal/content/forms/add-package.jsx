
import React from 'react';
import Button from '../../../../common/inputs/button';
import Spacer from '../../../../common/components/spacer';
import toastr from 'toastr';
import Select from '../../../../common/inputs/select';
import Text from '../../../../common/inputs/text';
import NumberInput from '../../../../common/inputs/number';
import Header from '../../../../common/fields/header';
import GoaBrand from '../../../../common/brand';
import Functions from '../../../../common/functions';

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

const PACKAGE_DICTIONARY = {
    UspsFlatRatePaddedEnvelope: {
        length: 12.5,
        width: .5,
        height: 9.5,
    },
    UspsFlatRateLegalEnvelope: {
        length: 15,
        width: .5,
        height: 9.5,
    },
    UspsSmallFlatRateEnvelope: {
        length: 10,
        width: .5,
        height: 6,
    },
    UspsFlatRateEnvelope: {
        length: 12.5,
        width: .5,
        height: 9.5,
    },
    UspsSmallFlatRateBox: {
        length: 8.62,
        width: 5.37,
        height: 1.62,
    },
    UspsMediumFlatRateBoxTopLoading: {
        length: 11,
        width: 8.5,
        height: 5.5,
    },
    UspsMediumFlatRateBoxSideLoading: {
        length: 13.62,
        width: 11.87,
        height: 3.37,
    },
    UspsLargeFlatRateBox: {
        length: 12,
        width: 12,
        height: 5.5,
    },
    UspsRegionalRateBoxATopLoading: {
        length: 10,
        width: 7,
        height: 4.75
    },
    UspsRegionalRateBoxASideLoading: {
        length: 10.9,
        width: 2.75,
        height: 12.81
    },
    UspsRegionalRateBoxBTopLoading: {
        length: 12,
        width: 10.25,
        height: 5
    },
    UspsRegionalRateBoxBSideLoading: {
        length: 14.75,
        width: 2.82,
        height: 15.82
    }
}


export default class AddPackage extends React.Component {

    constructor(props) {
        super(props);
        this.state = {
            name: '',
            type: 'Parcel',
            length: '',
            width: '',
            height: '',
            saved: 1,
            disableInputs: false
        };

        this.handleSubmit = this.handleSubmit.bind(this);
        this.handleSelect = this.handleSelect.bind(this);
    }

    componentDidMount() {
        if (this.props.tour) {
            if (this.props.tour.isActive()) {
                this.props.tour.next()
            }
        }
    }


    handleSubmit(e) {
        e.preventDefault();
        if (this.loading) return;
        this.loading = true;


        GoaApi.Package.add(this.state, success => {
            this.props.onAdd(success.data.package);
        }, failure => {
            toastr.error(failure.message);
            this.loading = false;
        })
    }

    handleSelect(value) {
        let option = PACKAGE_TYPES.find(x => x.value == value);

        let isDictionary = PACKAGE_DICTIONARY.hasOwnProperty(value);
        let dimensions = (isDictionary) ? PACKAGE_DICTIONARY[value] : { length: '', width: '', height: '' };
        this.setState({
            type: value,
            length: dimensions.length,
            width: dimensions.width,
            height: dimensions.height,
            disableInputs: isDictionary,
            name: isDictionary ? option.label : this.state.name
        })
    }


    render() {
        return (
            <form onSubmit={this.handleSubmit}>
                <div>
                    <Header title='Details' top={true} />
                    <Spacer space='20px' />
                    <Select
                        autoFocus={true}
                        title='Type'
                        onChange={e => this.handleSelect(e.target.value)}
                        value={this.state.type}
                        stylesselect={STYLES.selectInput}
                        styleslabel={STYLES.label}
                        options={PACKAGE_TYPES}
                    />
                    <Text
                        title='Name'
                        onChange={e => this.setState({ name: e.target.value })}
                        value={this.state.name}
                        stylescontainer={STYLES.input}
                        stylesinput={STYLES.textInput}
                        styleslabel={STYLES.label}
                    />
                    <div style={STYLES.flexRow}>
                        <Text
                            stylescontainer={STYLES.input}
                            stylesinput={STYLES.textInput}
                            styleslabel={STYLES.label}
                            onChange={(e) => {
                                this.setState({ length: e.target.value })
                            }}
                            value={this.state.length}
                            title='Length (in)'
                            disabled={this.state.disableInputs}
                        />
                        <Spacer space='10px' />
                        <Text
                            stylescontainer={STYLES.input}
                            stylesinput={STYLES.textInput}
                            styleslabel={STYLES.label}
                            onChange={(e) => {
                                this.setState({ width: e.target.value })
                            }}
                            value={this.state.width}
                            title='Width (in)'
                            disabled={this.state.disableInputs}
                        />
                        <Spacer space='10px' />
                        <Text
                            stylescontainer={STYLES.input}
                            stylesinput={STYLES.textInput}
                            styleslabel={STYLES.label}
                            onChange={(e) => {
                                this.setState({ height: e.target.value })
                            }}
                            value={this.state.height}
                            title='Height (in)'
                            disabled={this.state.disableInputs}
                        />
                    </div>

                    <div style={STYLES.flexRow}>
                        <Button
                            props={{ type: 'submit' }}
                            stylesbutton={STYLES.button}
                            stylesbuttonhover={STYLES.buttonHover}
                        >
                            Add Parcel
                        </Button>
                    </div>
                </div>
            </form>
        )
    }
}

const STYLES = {
    flexRow: {
        display: 'flex',
        alignItems: 'center'
    },
    input: {
        flex: '1'
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