
import React from 'react';

import Button from '../../../inputs/button';
import Spacer from '../../../components/spacer';
import GoaBrand from '../../../brand';
import InputText from '../../../inputs/text';
import InputSelect from '../../../inputs/select';

import toastr from 'toastr';


const CALLBACK_TYPES = [
    {
        value: 'CANCELLED_LABEL_USED',
        label: 'Cancelled Label Used'
    },
    {
        value: 'PRICE_CORRECTION',
        label: 'Price Correction'
    },
    {
        value: 'RETURN_LABEL_USED',
        label: 'Return Label Used'
    }, 
    {
        value: 'TRACKING', 
        label: 'Tracking'
    }
]

export default class AddApiCallback extends React.Component {

    constructor(props) {
        super(props);
        this.state = {
            callback_url: '',
            type: 'CANCELLED_LABEL_USED'
        };

        this.handleSubmit = this.handleSubmit.bind(this);
    }

    handleSubmit(e) {
        e.preventDefault();
        GoaApi.ApiCallback.add({
            type: this.state.type,
            callback_url: this.state.callback_url
        }, success => {
            this.props.onAdd(success.data.model);
        }, failure => {
            toastr.error(failure.message);
        })
    }

    render() {

        return (
            <form onSubmit={this.handleSubmit}>
                <InputSelect
                    options={CALLBACK_TYPES}
                    value={this.state.type}
                    onChange={e => this.setState({type: e.target.value})}
                />
                <InputText
                    autoFocus={true}
                    title='Callback Url'
                    value={this.state.callback_url}
                    onChange={e => this.setState({callback_url: e.target.value})}
                /> 
                <div style={STYLES.confirmButtonsContainer}>
                    <Button 
                        props={{type: 'submit'}}
                        color={GoaBrand.getPrimaryColor()}
                    >
                        Add Callback
                    </Button>
                    <Spacer space='20px'/>
                    <Button 
                        props={{
                            type: 'button',
                            onClick: this.props.onCancel
                        }}
                        color={GoaBrand.getPrimaryColor()}
                    >
                        Cancel
                    </Button>
                </div>
            </form>
        )
    }
}

const STYLES = {
    confirmButtonsContainer: {
        display: 'flex',
        minWidth: '330px'
    }
}