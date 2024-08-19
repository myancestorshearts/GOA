import React from 'react';

export default class Filter extends React.Component {
    constructor(props) {
        super(props);

        this.state = {
            opened: false
        }

        this.handleSelect = this.handleSelect.bind(this);
    }

    handleSelect(option) {
        if (this.props.onChange) this.props.onChange(option.value);
    }

    render() {


        let containerStyles = { ...STYLES.inputContainer }
        containerStyles = { ...containerStyles, ...(this.props.stylescontainer ? this.props.stylescontainer : {}) };

        let selectedOption = this.props.options.find(x => x.value == this.props.value)
        let remainingOptions = this.props.options.map(x => <Option
            key={x.value}
            option={x}
            onClick={() => this.handleSelect(x)}
            dropdown={true}
        />)

        return (
            <div style={containerStyles} onClick={() => this.setState({ opened: !this.state.opened })}>

                <Option
                    option={selectedOption}
                />
                <div style={STYLES.optionsContainer}>{this.state.opened ? remainingOptions : null}</div>

                {/* Icon Arrow */}
                <i style={STYLES.iconArrow} className={this.state.opened ? 'fa fa-angle-up' : 'fa fa-angle-down'}></i>
            </div>
        )
    }
}


const Option = (props) => {

    let containerStyles = { ...STYLES.optionContainer };
    if (props.dropdown) containerStyles = { ...containerStyles, ...STYLES.optionContainerDropdown }

    return (
        <div style={containerStyles} onClick={props.onClick}>
            <div style={STYLES.optionDropdown}>{props.option.title}</div>
        </div>
    )
}


const STYLES = {
    container: {
        cursor: 'pointer',
        display: 'flex',
        flexDirection: 'column',
        marginBottom: '10px',
        position: 'relative'
    },
    iconArrow: {
        position: 'absolute',
        right: '20px',
        top: '20px',
        bottom: '12.5%',
        fontSize: '26px',
        color: '#273240'
    },
    optionContainer: {
        display: 'flex',
        paddingLeft: '20px',
        paddingTop: '10px',
        paddingBottom: '10px',
        alignItems: 'center',
        paddingRight: '85px',

    },
    optionContainerDropdown: {
        position: 'relative',
        background: '#FFFFFF',
        border: 'none',
        borderBottom: '1px solid #f1f4f9'
    },
    optionsContainer: {
        borderRadius: '20px',
        overflow: 'hidden',
        top: '10px',
        boxShadow: 'rgb(231, 228, 233) 2px 2px 5px',
        position: 'relative',
        userSelect: 'none',
        cursor: 'pointer',
        zIndex: 1
    },
    optionInfoContainer: {
        display: 'flex',
        flexDirection: 'column',
        paddingLeft: '25px',
    },

    optionTitle: {
        fontFamily: 'poppins',
        fontWeight: '500',
        fontSize: '18px',
        lineHeight: '10px',
        color: '#273240'
    },
    optionDropdown: {
        fontFamily: 'poppins',
        fontWeight: '600',
        fontSize: '16px',
        lineHeight: '24px',
        color: '#273240',
    },
    inputContainer: {
        cursor: 'pointer',
        userSelect: 'none',
        position: 'relative',
        paddingTop: '10px',
        height: '50px',
        background: '#FFFFFF',
        boxShadow: '0px 4px 0px 0px rgba(94, 132, 194, 0.06)',
        borderRadius: '20px'
    }
}