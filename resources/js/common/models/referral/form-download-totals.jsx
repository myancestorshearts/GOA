
import React from 'react';
import Button from '../../inputs/button';
import InputDateTime from '../../inputs/datetime';
import Spacer from '../../components/spacer';
import Header from '../../fields/header';
import GoaBrand from '../../brand';


import ApiReferral from '../../api/referral';
import Functions from '../../functions';

export default class FormPurchaseLabel extends React.Component {

    constructor(props) {
        super(props);

        let currentDate = new Date;
        let endOfDay = currentDate.getEndOfDay();
        let startOfMonth = currentDate.getStartOfMonth();

        this.state = {
            start: startOfMonth,
            end: endOfDay
        };

        this.handleSubmit = this.handleSubmit.bind(this);
    }


    handleSubmit(e) {
        if (e) e.preventDefault();

        ApiReferral.Referral.totalsExport({
            start: Functions.convertDateToMysql(this.state.start),
            end: Functions.convertDateToMysql(this.state.end),
            user_id: this.props.model.referred_user_id
        })

        this.props.onClose();
    }

    render() {
        return (
            <form onSubmit={this.handleSubmit}>
                <div>
                    <Header title='Export labels' top={true} />
                    <Spacer space='10px' />

                    <InputDateTime
                        title='Start'
                        onChange={value => this.setState({ start: value })}
                        value={this.state.start}
                        stylesinput={STYLES.textInput}
                        styleslabel={STYLES.label}
                    />

                    <InputDateTime
                        title='End'
                        onChange={value => this.setState({ end: value })}
                        value={this.state.end}
                        stylesinput={STYLES.textInput}
                        styleslabel={STYLES.label}
                    />

                    <Button
                        props={{ type: 'submit' }}
                        color={GoaBrand.getPrimaryColor()}
                        stylesbutton={STYLES.button}
                        stylesbuttonhover={STYLES.buttonHover}
                    >
                        Export
                    </Button>
                </div>
            </form>
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