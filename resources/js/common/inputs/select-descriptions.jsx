import React from 'react';

export default class SelectDescription extends React.Component {
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


        let containerStyles = { ...STYLES.container }
        containerStyles = { ...containerStyles, ...(this.props.stylescontainer ? this.props.stylescontainer : {}) };

        let selectedOption = this.props.options.find(x => x.value == this.props.value)
        let remainingOptions = this.props.options.filter(x => x.value != this.props.value).map(x => <Option
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
                {this.state.opened ? remainingOptions : null}

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
            {/* image */}
            <img style={STYLES.optionImage} src={props.option.image} />
            <div style={STYLES.optionInfoContainer}>
                {/* title */}
                <div style={STYLES.optionTitle}>{props.option.title}</div>
                {/* description */}
                <div style={STYLES.optionDescription}>{props.option.description}</div>
            </div>
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
        right: '35px',
        top: '35px',
        fontSize: '26px',
        color: '#273240'
    },
    optionContainer: {
        display: 'flex',
        paddingLeft: '20px',
        paddingTop: '10px',
        paddingBottom: '10px',
        alignItems: 'center',
        paddingRight: '85px'
    },
    optionContainerDropdown: {
        borderTop: 'solid 1px #F2F6FC'
    },
    optionInfoContainer: {
        display: 'flex',
        flexDirection: 'column',
        paddingLeft: '25px'
    },
    optionImage: {
        width: '75px'
    },
    optionTitle: {
        fontFamily: 'poppins',
        fontWeight: '500',
        fontSize: '18px',
        lineHeight: '24px',
        color: '#273240'
    },
    optionDescription: {
        fontFamily: 'poppins',
        fontWeight: '600',
        fontSize: '16px',
        lineHeight: '24px',
        color: '#96A0AF'
    }
}