
import React from 'react';
import Button from '../../../../common/inputs/button';
import Spacer from '../../../../common/components/spacer';
import toastr from 'toastr';
import Text from '../../../../common/inputs/text';
import GoaBrand from '../../../../common/brand';
import Header from '../../../../common/fields/header';

export default class InviteReferral extends React.Component {

    constructor(props) {
        super(props);
        this.state = {
            name: '',
            email: ''
        };

        this.handleSubmit = this.handleSubmit.bind(this);
    }

    handleSubmit(e) {
        e.preventDefault();
        if (this.loading) return;
        this.loading = true;

        GoaApi.Referral.invite(this.state, success => {
            toastr.success('Invite sent to ' + this.state.email);
            this.props.onCancel()
        }, failure => {
            toastr.error(failure.message);
            this.loading = false;
        })
    }
    
    render() {
        return (
            <form onSubmit={this.handleSubmit}>
                <div>
                    <Header title='Details' top={true}/>
                    <Spacer space='20px'/>
                    
                    <div style={STYLES.flexRow}>
                        <Text
                            autoFocus={true}
                            title='Name'
                            onChange={e => this.setState({name: e.target.value})}
                            value={this.state.name}
                            stylescontainer={STYLES.input}
                        />
                    </div>

                    <div style={STYLES.flexRow}>
                        <Text
                            title='Email'
                            onChange={e => this.setState({email: e.target.value})}
                            value={this.state.email}
                            stylescontainer={STYLES.input}
                        />
                    </div>
                    
                    <div style={STYLES.flexRow}>
                        <Button 
                            props={{type: 'submit'}}
                            color={GoaBrand.getPrimaryColor()}
                        >
                            Send Invite
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
                </div>
            </form>
        )
    }
}

const STYLES = {
    flexRow: {
        display: 'flex',
        alignItems: 'center',
        minWidth: '300px'
    },
    input: {
        flex: 1
    }
}