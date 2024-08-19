import React from 'react';
import GoaBrand from '../brand';
export default class ColumnSelection extends React.Component {

  constructor(props) {
    super(props)

    this.state = {
      open: false
    }

    this.handleDocumentClick = this.handleDocumentClick.bind(this);
  }

  componentDidMount() {
    // listen for click so the dropdown can be closed if the click is outside the dropdown
    document.addEventListener("click", this.handleDocumentClick);
  }

  componentWillUnmount() {
    document.removeEventListener("click", this.handleDocumentClick);
  };

  handleDocumentClick(e) {
    if (this.node.contains(e.target)) return;
    // the click is outside, so close the dropdown
    this.setState({ open: false });
  };

  handleCheckedToggle(columnKey, isChecked) {
    if (isChecked && !this.props.tablePreferences.includes(columnKey)) {
      let tablePreferences = this.props.tablePreferences;
      tablePreferences.push(columnKey);
      this.props.onUpdateTablePreferences(tablePreferences)
    }
    else if (!isChecked && this.props.tablePreferences.includes(columnKey)) {
      let tablePreferences = this.props.tablePreferences.filter(x => x !== columnKey);
      this.props.onUpdateTablePreferences(tablePreferences);
    }
  };

  render() {
    if (this.props.tablePreferences === undefined) return null;

    let countSelected = this.props.tablePreferences.filter(key => this.props.tableColumns[key]).length;

    let openClass = this.state.open ? ' open' : '';
    let buttonTitle = `Columns (${countSelected}/${Object.keys(this.props.tableColumns).length})` 
    let dropdownItems = Object.keys(this.props.tableColumns).map(key => {
        let isChecked = this.props.tablePreferences.includes(key);
        return (
          <li key={key} style={{...STYLES.eliteCheckbox}}>
            <input style={STYLES.eliteCheckbox.input} type="checkbox" checked={isChecked} onChange={e => this.handleCheckedToggle(key, e.target.checked)} />
            <div style={{...STYLES.eliteCheckbox.checkmark}}>
              <span style={{...isChecked ? STYLES.eliteCheckbox.checkmark.after : {}}} />
            </div>
            <div style={{marginTop: '2px', color: '#AAAAAA', fontWeight: '700', marginLeft: '15px',}}>
              {this.props.tableColumns[key].title}
            </div>
          </li>
        );
    })

    return (
      <div style={{...STYLES.eliteDropdown, ...this.state.open ? STYLES.eliteOpen : null}} ref={node => (this.node = node)}>
        <button style={STYLES.eliteDropdown.button} type="button" aria-expanded={this.state.open} onClick={() => this.setState({open: !this.state.open})}>
          <div style={{fontSize: '14px', fontWeight: '700', color: (GoaBrand.getPrimaryColor())}}>
          {buttonTitle}
          </div>
          <span style={STYLES.eliteDropdown.caret} />
        </button>
        <ul style={{...STYLES.eliteDropdown.menu, ...this.state.open ? {display: 'block'} : {} }}>
          {dropdownItems}
        </ul>
      </div>
    );
  }
}

const STYLES = {
  eliteDropdown:{
    position: 'relative',
    display: 'inline-block',
    button:{
      display: 'flex',
      alignItems: 'center',
      justifyContent: 'space-evenly',
      fontSize: '13px',
      fontWeight: '400',
      lineHeight: '1',
      marginBottom: '0',
      padding: '9px 15px',
      border: '2px solid ' + GoaBrand.getPrimaryColor(),
      borderRadius: '5px',
      color: '#333',
      backgroundColor: '#fff',
      height: '40px',
      width: '150px',
      cursor: 'pointer',
      WebkitUserSelect: 'none',
      MozUserSelect: 'none',
      msUserSelect: 'none',
      userSelect: 'none',
      textAlign: 'center',
      verticalAlign: 'middle',
      whiteSpace: 'nowrap',
      backgroundImage: 'none',
      touchAction: 'manipulation',
      focus:{
        // color: #fff,
        backgroundColor: '#f2f2f2',
        outline: '0 !important',
      }
    },
    caret:{
      color: GoaBrand.getPrimaryColor(),
      display: 'inline-block',
      width: '0',
      height: '0',
      margin: '-1px -5px 0 5px',
      verticalAlign: 'middle',
      borderTop: '4px solid',
      borderRight: '4px solid transparent',
      borderLeft: '4px solid transparent',
    },
    menu:{
      right: '0',
      boxShadow: '0 2px 9px -3px #9e9e9e',
      fontSize: '13px',
      position: 'absolute',
      zIndex: '1000',
      display: 'none',
      width: 'max-content',
      minWidth: '160px',
      margin: '2px 0 0',
      padding: '10px 0',
      listStyle: 'none',
      textAlign: 'left',
      border: '1px solid #ddd',
      borderRadius: '0',
      backgroundColor: '#fff',
      backgroundClip: 'padding-box',
      boxShadow: '0 6px 12px rgba(0, 0, 0, 0.175)',
    },
  },

  eliteOpen:{ 
      menu:{
        display: 'block',
        maxHeight: '200px',
        overflowY: 'auto',
      },
      caret:{
        borderTop: 'none',
        borderBottom: '4px solid',
      }
    },

  eliteCheckbox:{
    display: 'block',
    position: 'relative',
    padding: '5px 40px',
    cursor: 'pointer',
    WebkitUserSelect: 'none',
    MozUserSelect: 'none',
    msUserSelect: 'none',
    userSelect: 'none',
    hover:{
      'backgroundColor': '#f5f5f5',
    },
    /* Hide the browser's default checkbox */
    input:{
      position: 'absolute',
      opacity: '0',
      cursor: 'pointer',
      width: '100%',
      left: '0',
      top: '0',
      height: '100%',
      zIndex: '2',
      /* Show the checkmark when checked */
      '&:checked ~ .checkmark:after':{
        display: 'block',
      }
    },
    /* Create a custom checkbox */
    checkmark:{
      position: 'absolute',
      top: '5px',
      left: '16px', 
      height: '15px',
      width: '15px',
      background: '#fff',
      border: '2px solid ' + GoaBrand.getPrimaryColor(),
      /* Create the checkmark/indicator (hidden when not checked) */
      after: {
        content: "",
        position: 'absolute',
        left: '4px',
        top: '1px',
        width: '4px',
        height: '7px',
        border: 'solid ' + GoaBrand.getPrimaryColor(),
        borderWidth: '0 2px 2px 0',
        WebkitTransform: 'rotate(45deg)',
        MsTransform: 'rotate(45deg)',
        transform: 'rotate(45deg)',
      }
    }
  }
}