import React from 'react';
import StyledComponent from '../../components/styled-component';
import Loading from '../../components/loading';
import Panel from '../panel/index';
import GoaState from '../../goa-state';
import Select from '../../inputs/select';
import CommonFunctions from '../../functions';
import { SortableContainer, SortableElement } from "react-sortable-hoc";
import { CONFIRM } from '../modal/index';
import GoaBrand from '../../brand';
import toastr from 'toastr';
import FlexContainer from '../../components/flex-container';
import { camelCase } from 'lodash';

const OPTIONS_TAKE = [
    {
        label: '10',
        value: 10
    },
    {
        label: '25',
        value: 25
    },
    {
        label: '50',
        value: 50
    },
    {
        label: '100',
        value: 100
    }
]

class PanelTable extends React.Component {

    constructor(props) {
        super(props)

        let activeState = GoaState.get('active-model');

        this.state = {
            activeProperties: [],
            activeModel: activeState ? activeState.model : undefined,
            activeModels: []
        }

        this.handleOnSortEnd = this.handleOnSortEnd.bind(this);
        this.handleActiveColumnSelect = this.handleActiveColumnSelect.bind(this);
        this.handleSelectMass = this.handleSelectMass.bind(this);
        this.handleSelectModel = this.handleSelectModel.bind(this);
        this.handleSelectAll = this.handleSelectAll.bind(this);
        this.handleClearModels = this.handleClearModels.bind(this);
    }

    componentDidMount() {

        GoaState.subscribe('active-model', state => {
            this.setState({ activeModel: state.model })
        })

        let propKeys = Object.keys(this.props.properties)
        let defaultKeys = propKeys.map(x => {
            if ((this.props.properties[x]).hasOwnProperty('default')) { return x }
        })
        GoaUser.getPreference(this.props.tableKey + '_activeColumns',
            (success) => {
                this.setState({ activeProperties: success.activeColumns })
            },
            { activeColumns: defaultKeys }
        )
    }

    handleOnSortEnd({ oldIndex, newIndex }) {
        let newPositions = CommonFunctions.array_move(this.state.activeProperties, oldIndex, newIndex)
        this.setState({ activeProperties: newPositions }, success => {
            GoaUser.setPreference(this.props.tableKey + '_activeColumns', { activeColumns: newPositions })
        })
    };

    handleActiveColumnSelect(event) {
        let activeColumns = this.state.activeProperties;
        event.map(x => {
            if (!((this.state.activeProperties).includes(x))) {
                activeColumns.push(x)
            }
        })
        activeColumns.map(x => {
            if (!event.includes(x)) {
                activeColumns.splice(activeColumns.indexOf(x), 1);
            }
        })
        this.setState({ activeProperties: activeColumns }, success => {
            GoaUser.setPreference(this.props.tableKey + '_activeColumns', { activeColumns: activeColumns })
        })
    }

    handleSelectMass(model) {
        let activeModels = this.state.activeModels;
        if (activeModels.find(x => model.id == x.id)) activeModels = activeModels.filter(x => x.id != model.id);
        else activeModels.push(model);
        this.setState({ activeModels: activeModels });
        if (this.props.onSelectModels) this.props.onSelectModels(activeModels);
    }

    handleSelectModel(model) {
        this.setState({
            activeModels: [model]
        }, () => {
            if (this.props.onSelectModels) this.props.onSelectModels([]);
            if (this.props.onSelectModel) this.props.onSelectModel(model);
        })
    }

    handleClearModels() {
        this.setState({
            activeModels: []
        }, () => {
            if (this.props.onSelectModels) this.props.onSelectModels(this.state.activeModels);
        })
    }

    handleSelectAll() {


        let allSelected = true;

        this.props.models.forEach(x => {
            let selected = false;
            this.state.activeModels.forEach(y => {
                if (x.id == y.id) selected = true;
            })
            allSelected = selected && allSelected;
        })

        // if all selected then remove them all that are listed
        if (allSelected) {
            this.props.models.forEach(x => {
                this.state.activeModels = this.state.activeModels.filter(y => x.id != y.id);
            });
        }
        else { // if they are not all selected then add the ones that are not currently selected
            this.props.models.forEach(x => {
                let selected = false;
                this.state.activeModels.forEach(y => {
                    if (x.id == y.id) selected = true;
                })
                if (!selected) this.state.activeModels.push(x);
            })
        }

        if (this.props.onSelectModels) this.props.onSelectModels(this.state.activeModels);
        this.forceUpdate();
    }


    render() {
        let rangeLow = (this.props.take * (this.props.page - 1)) + 1;
        let rangeHigh = Math.min(this.props.count, this.props.take * this.props.page);
        let showPrevPage = this.props.page > 1;
        let showNextPage = (this.props.take * this.props.page) < this.props.count;

        let allSelected = true;

        this.props.models.forEach(x => {
            let selected = false;
            this.state.activeModels.forEach(y => {
                if (x.id == y.id) selected = true;
            })
            allSelected = selected && allSelected;
        })

        let rows = this.props.models.map(x => <Row
            onUndo={this.props.onUndo}
            onDelete={this.props.onDelete}
            onApprove={this.props.onApprove}
            onRefresh={this.props.onRefresh}
            onCancel={this.props.onCancel}
            active={this.state.activeModel && this.state.activeModel.id == x.id}
            activeProperties={this.state.activeProperties}
            key={x.id}
            model={x}
            properties={this.props.properties}
            onSelectModel={this.handleSelectModel}
            onSelectModels={this.props.onSelectModels}
            activeModels={this.state.activeModels}
            handleSelectMass={this.handleSelectMass}
        />)
        return (
            <div>
                <Panel
                    style={this.props.style}
                    headerTitle={this.props.headerTitle}
                    headerIcon={this.props.headerIcon}
                    buttonClassName={this.props.buttonClassName}
                    buttonOnClick={this.props.buttonOnClick}
                    onRefreshSearch={this.props.onRefreshSearch}
                    onAdd={this.props.onAdd}
                    onExport={this.props.onExport}
                    onPrint={this.props.onPrint}
                    onColumnSelect={this.handleActiveColumnSelect}
                    tablePreferences={this.state.activeProperties}
                    tableColumns={this.props.properties}
                    searchQuery={this.props.searchQuery}
                >
                    <div style={STYLES.tableContainer}>
                        <Loading loading={(this.props.loading) ? this.props.loading : false}>
                            <table
                                style={STYLES.table}
                                cellSpacing="0"
                            >
                                <thead>
                                    <HeaderComponent
                                        sort={this.props.sort}
                                        onSort={this.props.onSort}
                                        onDelete={this.props.onDelete}
                                        onUndo={this.props.onUndo}
                                        onApprove={this.props.onApprove}
                                        onRefresh={this.props.onRefresh}
                                        onCancel={this.props.onCancel}
                                        axis="x"
                                        pressDelay={100}
                                        properties={this.props.properties}
                                        activeProperties={this.state.activeProperties}
                                        onSortEnd={this.handleOnSortEnd}
                                        onSelectModels={this.props.onSelectModels}
                                        allSelected={allSelected}
                                        onSelectAll={this.handleSelectAll}
                                    />
                                </thead>
                                <tbody>
                                    {rows}
                                </tbody>
                            </table>
                        </Loading>
                    </div>

                    <div style={STYLES.paginationContainer}>

                        {/*Rows Per Page AKA: Take*/}
                        <FlexContainer style={STYLES.paginationTakeContainer}>
                            <div style={STYLES.rowsPerContainer}>
                                <span>Rows Per Page</span>
                                <Select
                                    stylescontainer={STYLES.paginationTakeSelectContainer}
                                    stylesselect={STYLES.paginationTakeSelect}
                                    color='#aaa'
                                    options={OPTIONS_TAKE}
                                    value={this.props.take}
                                    onChange={e => this.props.onTake(e.target.value)}
                                />
                            </div>
                            <div>{rangeLow}-{rangeHigh} of {this.props.count}</div>

                            {/* Pages and next prev */}
                            <div style={STYLES.paginationNextPrevContainer}>
                                {
                                    showPrevPage ?
                                        <StyledComponent
                                            tagName='div'
                                            style={STYLES.paginationCaret}
                                            styleHover={STYLES.paginationCaretHover}
                                            props={{
                                                onClick: () => this.props.onPage(this.props.page - 1)
                                            }}
                                        >
                                            <i className='fa fa-chevron-left' />
                                        </StyledComponent> : <div
                                            style={STYLES.paginationCaret}
                                        />
                                }
                                {
                                    showNextPage ?
                                        <StyledComponent
                                            tagName='div'
                                            style={STYLES.paginationCaret}
                                            styleHover={STYLES.paginationCaretHover}
                                            props={{
                                                onClick: () => this.props.onPage(this.props.page + 1)
                                            }}
                                        >
                                            <i className='fa fa-chevron-right' />
                                        </StyledComponent> : <div
                                            style={STYLES.paginationCaret}
                                        />
                                }
                            </div>
                        </FlexContainer>

                    </div>
                </Panel>
            </div>
        )
    }
}

const GET_VALUE = (model, property) => {
    if (property.type == 'METHOD') return property.method(model)
    let value = CommonFunctions.deepGetFromString(model, property.property);
    if (CommonFunctions.isEmpty(value)) return '-';
    if (property.type == 'DATE') value = CommonFunctions.convertMysqlToDate(value).formatDate('n/d/y');
    else if (property.type == 'DATETIME') value = CommonFunctions.convertMysqlToDate(value).formatDate('n/d/y H:m A')
    else if (property.type == 'CURRENCY') value = CommonFunctions.convertToMoney(value);
    else if (property.type == 'ADDRESS') value = CommonFunctions.formatAddress(value);
    else if (property.type == 'BOOLEAN') value = CommonFunctions.inputToBool(value) ? property.boolTrue : property.boolFalse;
    else if (property.type == 'WEIGHT') value += 'oz';
    else if (property.type == 'LENGTH') value += '"';
    return value;
}

const Row = (props) => {


    let cellStyles = { ...STYLES.tableRowCell }
    if (props.activeModels.find(x => props.model.id == x.id)) cellStyles = { ...cellStyles, ...STYLES.tableRowActive }
    if (props.active) cellStyles = { ...cellStyles, ...STYLES.tableRowActive }


    let columns = props.activeProperties ? props.activeProperties.map(key => {
        if (!props.properties[key]) return null;

        let contents = GET_VALUE(props.model, props.properties[key])
        if (props.properties[key].type == 'LINK') {
            contents = (<a href='#' onClick={(e) => {
                window.open(props.properties[key].linkMethod(props.model), '_blank')
                e.stopPropagation();
            }}> {contents}</a>);
        }
        return (
            <td
                style={cellStyles}
                key={key}
            >
                {contents}
            </td>
        )
    }) : null

    if (props.onSelectModels) {

        let active = props.activeModels.find(x => props.model.id == x.id);
        let icon = active ? 'fa fa-check-square-o' : 'fa fa-square-o';
        let color = active ? GoaBrand.getPrimaryColor() : '#555';
        color = (props.active || active) ? 'white' : color;

        columns.unshift(<td
            onClick={(e) => {
                props.handleSelectMass(props.model);
                e.stopPropagation();
            }}
            style={cellStyles}
            key='mass'
        >
            <i className={icon} style={{ color: color }} />
        </td>)
    }

    // add approve icon if we have the approve event
    if (props.onApprove || props.onRefresh || props.onDelete || props.onCancel || props.onUndo) {
        let actionIcons = [];
        if (props.onApprove) actionIcons.push(<i
            style={STYLES.actionIcon}
            key='approve'
            onClick={(e) => {
                e.stopPropagation();
                CONFIRM('Are you sure you want to approve this?', () => props.onApprove(props.model))
            }}
            className='fa fa-check'
        />)
        if (props.onRefresh) actionIcons.push(<i
            style={STYLES.actionIcon}
            key='refresh'
            onClick={(e) => {
                e.stopPropagation();
                props.onRefresh(props.model)
            }}
            className='fa fa-refresh'
        />)
        if (props.onDelete) actionIcons.push(<i
            style={STYLES.actionIcon}
            key='delete'
            onClick={(e) => {
                e.stopPropagation();
                CONFIRM('Are you sure you want to delete this?', () => props.onDelete(props.model))
            }}
            className='fa fa-trash'
        />)
        if (props.onUndo) actionIcons.push(<i
            style={STYLES.actionIcon}
            key='undo'
            onClick={(e) => {
                e.stopPropagation();
                CONFIRM('Are you sure you want to print return label?', () => props.onUndo(props.model))
            }}
            className='fa fa-undo'
        />)
        if (props.onCancel) actionIcons.push(<i
            style={STYLES.actionIcon}
            key='cancel'
            onClick={(e) => {
                e.stopPropagation();
                CONFIRM('Are you sure you want to cancel this?', () => props.onCancel(props.model))
            }}
            className='fa fa-ban'
        />)

        columns.push(<td
            style={cellStyles}
            key='actions'
        >
            {actionIcons}
        </td>)
    }

    let cursorType = (props.onSelectModel ? 'pointer' : 'auto')

    return (
        <tr
            style={{ cursor: cursorType }}
            onClick={(props.onSelectModel ? () => props.onSelectModel(props.model) : null)}
        >
            {columns}
        </tr>
    )
}

const HeaderComponent = SortableContainer((props) => {

    let headers = (props.activeProperties) ? props.activeProperties.map((key, index) => {
        if (!props.properties[key]) return null;
        return <HeaderTitle
            onSort={() => {
                if (props.onSort) props.onSort(props.properties[key])
            }}
            key={key}
            index={index}
            title={props.properties[key].title}
            sortableColumn={props.properties[key].sortableColumn}
            sort={props.sort}
        />
    }) : null


    // if on select models then we need to show an empty header at the beginning
    if (props.onSelectModels) {

        let icon = props.allSelected ? 'fa fa-check-square-o' : 'fa fa-square-o';

        headers.unshift(
            <th
                key='massselection'
                style={STYLES.tableHeader}
                onClick={props.onSelectAll}
            >
                <i className={icon} />
            </th>
        )
    }

    // add delete header if we have the delete event
    if (props.onDelete || props.onRefresh || props.onApprove || props.onCancel || props.onUndo) {
        headers.push(<th
            key='actions'
            style={STYLES.tableHeader}
        />)
    }

    return (
        <tr>
            {headers}
        </tr>
    )
})

const HeaderTitle = SortableElement((props) => {

    let carat = undefined;
    if (props.sortableColumn && props.sort.column == props.sortableColumn) {
        let caretDirection = props.sort.direction == 'ASC' ? 'fa fa-caret-down' : 'fa fa-caret-up';
        carat = <i className={caretDirection} style={STYLES.tableHeaderIcon} />
    }

    return (
        <th
            key={props.key}
            style={STYLES.tableHeader}
            onClick={props.sortableColumn ? props.onSort : () => toastr.warning('Unable to sort by ' + props.title)}
        >
            {props.title} {carat}
        </th>
    )
})


export default PanelTable;

const STYLES = {
    tableContainer: {
        width: 'calc(100% + 40px)',
        marginTop: '-10px',
        marginLeft: '-22px',
        marginRight: '-22px',
        overflowY: 'auto'
    },
    table: {
        width: '100%',
        textAlign: 'center',
        cellspacing: 0
    },
    tableHeader: {
        paddingLeft: '5px',
        paddingRight: '5px',
        color: '#aaa',
        borderBottom: '2px solid #f1f4f9',
        paddingBottom: '10px',
        fontSize: '12px',
        cursor: 'pointer',
        userSelect: 'none'
    },
    tableHeaderIcon: {
        fontSize: '14px',
    },
    tableRowCell: {
        padding: '6px',
        borderBottom: '1px solid #f1f4f9',
        color: '#555',
        fontWeight: 'bold',
        paddingLeft: '15px',
        paddingRight: '15px',
        fontSize: '12px'
    },
    tableRowActive: {
        color: 'white',
        background: GoaBrand.getPrimaryColor()
    },
    paginationContainer: {
        display: 'flex',
        paddingTop: '10px',
        alignItems: 'center',
        justifyContent: 'center',
        marginBottom: '-10px',
        textAlign: 'center',
        color: '#aaa',
        fontSize: '12px'
    },
    paginationSpacer: {
        flex: 1
    },
    paginationNextPrevContainer: {
        flex: 1,
        display: 'flex'
    },
    paginationTakeContainer: {
        alignItems: 'center'
    },
    rowsPerContainer: {
        display: 'flex',
        alignItems: 'center'
    },
    paginationTakeSelectContainer: {
        marginBottom: '0px',
        marginLeft: '10px'
    },
    paginationTakeSelect: {
        fontSize: '12px',
        height: '24px',
        minHeight: '20px'
    },
    paginationCaret: {
        fontSize: '15px',
        minWidth: '30px',
        cursor: 'pointer'
    },
    paginationCaretHover: {
        color: GoaBrand.getPrimaryColor()
    },
    loading: {
        display: 'table-cell',
        verticalAlign: 'middle'
    },
    actionIcon: {
        width: '20px',
        padding: '5px',
        cursor: 'pointer'
    }
}
