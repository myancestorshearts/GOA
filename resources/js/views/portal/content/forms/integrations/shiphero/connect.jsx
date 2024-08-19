
import React from 'react';

import Button from '../../../../../../common/inputs/button';
import Spacer from '../../../../../../common/components/spacer';
import toastr from 'toastr';
import Text from '../../../../../../common/inputs/text';
import GoaBrand from '../../../../../../common/brand';


export default class ShipHeroConnect extends React.Component {

    constructor(props) {
        super(props);
        this.state = {
            name: '',
            accountId: ''
        };

        this.handleSubmit = this.handleSubmit.bind(this);
    }

    handleSubmit(e) {
        e.preventDefault();

        GoaApi.Integration.connect({
            type: 'SHIPHERO',
            name: this.state.name,
            account_id: this.state.accountId
        }, success => {
            this.props.onConnect();
        }, failure => {
            toastr.error(failure.message)
        })
    }

    render() {

        return (
            <form onSubmit={this.handleSubmit}>
                <p>The term 'ShipHero' is a trademark of ShipHero, Inc. This application uses the ShipHero api but is not endorsed or certified by ShipHero, Inc.</p>
                <Text
                    autoFocus={true}
                    title='Store Name'
                    value={this.state.name}
                    onChange={e => this.setState({ name: e.target.value })}
                />
                <Text
                    autoFocus={true}
                    title='ShipHero Account ID'
                    value={this.state.accountId}
                    onChange={e => this.setState({ accountId: e.target.value })}
                />
                <Spacer space='10px' />
                <div style={STYLES.confirmButtonsContainer}>
                    <Button
                        props={{ type: 'submit' }}
                        color={GoaBrand.getPrimaryColor()}
                    >
                        Connect
                    </Button>
                    <Spacer space='20px' />
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