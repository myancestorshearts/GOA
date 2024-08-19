import React from 'react';
import Button from '../../../common/inputs/button';
import GoaBrand from '../../../common/brand';

export default class Verify extends React.Component {
    constructor(props) {
        super(props)

        this.state = {
        }

        this.handleProceedToLogin = this.handleProceedToLogin.bind(this);
    }

    handleProceedToLogin(e) {
        e.preventDefault()
        this.props.history.push('/');
    }

    render() {

        return (
            <form onSubmit={this.handleProceedToLogin}>
                <div style={STYLES.text}>
                    Thank you for signing up. An email has been sent to your inbox to verify your email.
                </div>
                <Button
                    stylesbuttonhover={STYLES.buttonHover}
                    stylesbutton={STYLES.button}
                    props={{ type: 'submit' }}
                    color='white'
                >
                    Back To Login
                </Button>
            </form>
        )
    }
}

const STYLES = {
    text: {
        color: 'black',
        fontSize: '20px',
        textAlign: 'center',
        fontFamily: 'poppins',
        fontWeight: '600'
    },
    buttonHover: {
        backgroundColor: GoaBrand.getPrimaryHoverColor(),
        color: 'white'
    },
    button: {
        backgroundColor: GoaBrand.getPrimaryColor(),
        borderColor: '#04009a',
        borderRadius: '15px',
        height: '40px',
        fontSize: '18px',
        fontWeight: '600',
        marginBottom: '20px'

    },
}

