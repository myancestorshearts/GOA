
import React from 'react';
import CommonFunctions from '../functions';
export default class NumberInput extends React.Component {

	constructor(props)
	{
		super(props)

		this.state = {
			focus: false
		}

		this.handleBlur = this.handleBlur.bind(this);
		this.handleKeyPress = this.handleKeyPress.bind(this);
		this.precision = CommonFunctions.isEmpty(this.props.precision) ? 2 : this.props.precision
		this.min = CommonFunctions.isEmpty(this.props.min) ? -999999999999 : this.props.min;
		this.max = CommonFunctions.isEmpty(this.props.max) ? 999999999999 : this.props.max;
		this.id = 'input-' + CommonFunctions.getRandomId();
	}
	
	handleBlur(e) {
		if (this.props.onBlur) this.props.onBlur(e.target.value);
		this.setState({focus: false})
	}

	handleKeyPress(e){

  		let value = this.props.value ? Number(this.props.value) : 0 
		if (!isNaN(e.key)) { 
			value = CommonFunctions.round(value * 10, this.precision);
			let addition = CommonFunctions.round(Number(e.key) / (Math.pow(10, this.precision)), this.precision) * (value < 0 ? -1 : 1)
			value += addition;
		}
		else if (e.keyCode == 8){
			let method = value > 0 ? CommonFunctions.floor : CommonFunctions.ceil
			value = method(value / 10, this.precision)
		}
		else if (e.keyCode == 189) value *= -1


		value = Math.max(this.min, value);
		value = Math.min(this.max, value);
  		if (this.props.onChange) this.props.onChange(value);
	}




	render() {

		let inputAttributes = {}

		let inputStyles = {...STYLES.input};
        let labelStyles = {...STYLES.label};
        let containerStyles = {...STYLES.container}

		inputStyles = {...inputStyles, ...(this.props.stylesinput ? this.props.stylesinput : {})};
        labelStyles = {...labelStyles, ...(this.props.styleslabel ? this.props.styleslabel : {})};
        containerStyles = {...containerStyles, ...(this.props.container ? this.props.container : {})};
		let focusStyles = {...inputStyles, ...STYLES.focus, ...(this.props.stylesFocus ? this.props.stylesFocus : {})}
		if (this.props.name) inputAttributes.name = this.props.name;
		if (this.props.disabled){
			inputAttributes.disabled = this.props.disabled;
			inputStyles = {...inputStyles, ...STYLES.disabled}
		}
		inputAttributes.placeholder = Number(0).toFixed(this.precision)
		return (			
			<div style={containerStyles}>
				<label style={labelStyles} htmlFor={this.id}>{this.props.title}</label>
				<input 
					style={(this.state.focus) ? focusStyles : inputStyles}
					onFocus={() => this.setState({focus: true})}
					onKeyDown={(e) => this.handleKeyPress(e)}
					onChange={() => {}}
					onBlur={this.handleBlur}
					id={this.id + 'input'}
					type="text"
					{...inputAttributes}
					value={CommonFunctions.isEmpty(this.props.value) ? (Number(0).toFixed(this.precision)) : (Number(this.props.value).toFixed(this.precision))}
					required={this.props.required}
					onSelect={(event) => event.selectionStart = event.selectionEnd}
				/>
				<style>{'#'+this.id+'input'+'::selection {color: black;background: transparent;}'}</style>
			</div>
		)
	}
}

const STYLES = {
    container: {
        display: 'flex',
        flexDirection: 'column',
        marginBottom: '10px'
    },
    input: {
        backgroundColor: 'transparent',
        height: '30px',
        borderRadius: '5px',
        border: '2px solid',
        paddingLeft: '10px',
        paddingRight: '10px',
        fontSize: '16px',
        minHeight: '41px',
        marginTop: '5px',
        caretColor: 'transparent'
    },
    focus: {
        border: '3px solid',
        '::MozSelection':{ /* Code for Firefox */
			color: 'black',
		  	background: 'transparent'
		},
		'::selection':{
			color: 'black',
			background: 'transparent'
		}
    },
    label: {
        marginBottom: '2px',
        fontSize: '16px',
        fontWeight: 'bold'
    },
    disabled: {
    	color: 'grey'
    }
}

