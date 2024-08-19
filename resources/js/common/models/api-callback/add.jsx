
import React from 'react';
import Button from '../../inputs/button';
import InputText from '../../inputs/text';
import Spacer from '../../components/spacer';
import Header from '../../fields/header';
import GoaBrand from '../../brand';


import toastr from 'toastr';
import Functions from '../../functions';

export default class FormPurchaseLabel extends React.Component {

    constructor(props) {
        super(props);

        this.state = {
            key: '',
            value: ''
        };

        this.handleSubmit = this.handleSubmit.bind(this);
    }


    handleSubmit(e) {
        if (e) e.preventDefault();

        if (Functions.isEmpty(this.state.key.trim())) {
            toastr.error('Key cannot be empty');
            return;
        }
        if (Functions.isEmpty(this.state.value.trim())) {
            toastr.error('Key cannot be empty');
            return;
        }

        this.props.onAdd(this.state.key, this.state.value);
        this.props.onClose();
    }

    render() {
        return (
            <form onSubmit={this.handleSubmit}>
                <div>
                    <Header title='Add Header' top={true} />

                    <Spacer space='10px' />

                    <InputText
                        title='Key'
                        onChange={e => this.setState({ key: e.target.value })}
                        value={this.state.key}
                        stylesinput={STYLES.textInput}
                        styleslabel={STYLES.label}
                    />

                    <InputText
                        title='Value'
                        onChange={e => this.setState({ value: e.target.value })}
                        value={this.state.value}
                        stylesinput={STYLES.textInput}
                        styleslabel={STYLES.label}
                    />

                    <Button
                        props={{ type: 'submit' }}
                        color={GoaBrand.getPrimaryColor()}
                        stylesbutton={STYLES.button}
                        stylesbuttonhover={STYLES.buttonHover}
                    >
                        Add Header
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