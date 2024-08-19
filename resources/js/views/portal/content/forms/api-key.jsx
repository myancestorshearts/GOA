
import React from 'react';

import Button from '../../../../common/inputs/button';
import Spacer from '../../../../common/components/spacer';
import toastr from 'toastr';
import Text from '../../../../common/inputs/text';
import GoaBrand from '../../../../common/brand';


export default class AddApiKey extends React.Component {

    constructor(props) {
        super(props);
        this.state = {
            name: ''
        };

        this.handleSubmit = this.handleSubmit.bind(this);
    }

    handleSubmit(e) {
        e.preventDefault();

        GoaApi.ApiKey.add({
            name: this.state.name
        }, success => {
            this.props.onAdd(success.data.api_key);
        })
    
    }

    render() {

        return (
            <form onSubmit={this.handleSubmit}>
                <Text
                    autofocus={true}
                    title='Label'
                    value={this.state.name}
                    onChange={e => this.setState({name: e.target.value})}
                /> 
                <Spacer space='10px'/>
                <div style={STYLES.confirmButtonsContainer}>
                    <Button 
                        props={{type: 'submit'}}
                        color={GoaBrand.getPrimaryColor()}
                    >
                        Add Key
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
        display: 'flex'
    }
}