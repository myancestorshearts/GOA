
import React from 'react';

import Button from '../../../../../../common/inputs/button';
import Spacer from '../../../../../../common/components/spacer';
import toastr from 'toastr';
import Text from '../../../../../../common/inputs/text';
import GoaBrand from '../../../../../../common/brand';


export default class ShopifyConnect extends React.Component {

    constructor(props) {
        super(props);
        this.state = {
            name: ''
        };

        this.handleSubmit = this.handleSubmit.bind(this);
    }

    handleSubmit(e) {
        e.preventDefault();

        GoaApi.Integration.connect({
            type: 'ETSY',
            name: this.state.name
        }, success => {
            console.log(success);
            window.open(success.data.link)
        }, failure => {
            toastr.error(failure.message)
        })
    }

    render() {

        return (
            <form onSubmit={this.handleSubmit}>
                <p>The term 'Etsy' is a trademark of Etsy, Inc. This application uses the Etsy API but is not endorsed or certified by Etsy, Inc.</p>
                <Text
                    autoFocus={true}
                    title='Store Name'
                    value={this.state.name}
                    onChange={e => this.setState({name: e.target.value})}
                /> 
                <Spacer space='10px'/>
                <div style={STYLES.confirmButtonsContainer}>
                    <Button 
                        props={{type: 'submit'}}
                        color={GoaBrand.getPrimaryColor()}
                    >
                        Connect
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