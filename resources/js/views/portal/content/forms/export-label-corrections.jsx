import React from 'react';

import GoaBrand from '../../../../common/brand';
import Functions from '../../../../common/functions';

import InputDateTime from '../../../../common/inputs/datetime';
import InputButton from '../../../../common/inputs/button';


export default class LabelCorrectionsExport extends React.Component {


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

        GoaApi.LabelCorrection.export({
            start: Functions.convertDateToMysql(this.state.start),
            end: Functions.convertDateToMysql(this.state.end)
        })

        this.props.onExport();
    }

    render() {

        return (
            <form onSubmit={this.handleSubmit}>
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

                <InputButton
                    props={{ type: 'submit' }}
                    color={GoaBrand.getPrimaryColor()}
                    stylesbutton={STYLES.button}
                    stylesbuttonhover={STYLES.buttonHover}
                >
                    Export
                </InputButton>
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