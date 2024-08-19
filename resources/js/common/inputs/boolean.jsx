import React from 'react';
import Switch from "react-switch";
import CommonFunctions from '../functions';
import GoaBrand from '../brand';
export default class Boolean extends React.Component {
  constructor(props) {
    super(props);
    this.state = {};
    this.handleChange = this.handleChange.bind(this);
  }

  handleChange(value) {
    let active = CommonFunctions.inputToBool(CommonFunctions.deepGetFromString(this.props.model, this.props.property, false))
    CommonFunctions.deepSetFromString(this.props.model, this.props.property, !active);
    if (this.props.onChange) this.props.onChange(value);
    this.forceUpdate()
  }

  render() {
    let containerStyles = { ...STYLES.label, ...(this.props.stylescontainer ? this.props.stylescontainer : {}) }
    let labelStyles = { ...STYLES.span, ...(this.props.styleslabel ? this.props.styleslabel : {}) };
    let active = CommonFunctions.inputToBool(CommonFunctions.deepGetFromString(this.props.model, this.props.property, false))
    return (
      <div style={containerStyles}>
        <span style={labelStyles}>{this.props.title}</span>
        <Switch
          onChange={this.handleChange}
          checked={active}
          offColor='#aaa'
          onColor={GoaBrand.getPrimaryColor()}
          uncheckedIcon={false}
          checkedIcon={false}
          activeBoxShadow={'0 0 0px 0px #bdb9a6'}
          boxShadow={'0 0 0px 0px #bdb9a6'}
          width={48}
          height={25}
        />
      </div>
    );
  }
}
const STYLES = {
  label: {
    display: 'flex',
    flexDirection: 'column',
    marginBottom: '10px'
  },
  span: {
    fontSize: '18px',
    marginRight: '190px',
    marginBottom: '5px'
  }
}