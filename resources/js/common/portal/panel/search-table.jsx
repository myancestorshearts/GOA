

import React from 'react'
import Functions from '../../functions';
import GoaState from '../../goa-state';

import PanelTable from './table';

const DEFAULT_PREFERENCE = {}

export default class PanelSearchTable extends React.Component {

    constructor(props) {
        super(props)

        this.state = {
            loading: true,
            page: 1,
            take: 25,
            count: 0,
            models: [],
            query: '',
            sort: DEFAULT_PREFERENCE
        }

        this.handleSearch = this.handleSearch.bind(this);
        this.handleTake = this.handleTake.bind(this);
        this.handlePage = this.handlePage.bind(this);
        this.handleSearchTimeout = this.handleSearchTimeout.bind(this);
        this.handleSort = this.handleSort.bind(this);
    }

    componentDidMount() {

        // get user preference for column that is sorted        
        GoaUser.getPreference(this.props.tableKey + '_sort',
            preference => this.setState({
                sort: preference
            }, this.handleSearch),
            DEFAULT_PREFERENCE
        )

        this.subscribeSearch = GoaState.subscribe('GLOBAL_SEARCH', (x => this.setState({ query: x.query }, this.handleSearchTimeout)))
    }

    handleTake(take) {
        this.setState({
            take: take,
            page: 1,
            loading: true
        }, () => this.handleSearch());
    }

    handlePage(page) {
        this.setState({
            page: page,
            loading: true
        }, () => this.handleSearch())
    }

    handleSearch() {
        this.setState({
            loading: true
        }, () => {

            let args = {
                take: this.state.take,
                page: this.state.page,
                include_meta: true,
                ...(this.props.searchArgs ? this.props.searchArgs : {})
            }

            if (!Functions.isEmpty(this.state.sort.column)) args.order_by = JSON.stringify([this.state.sort]);

            if (!Functions.isEmpty(this.state.query)) args.query = this.state.query;

            this.props.searchMethod(args, success => {
                this.setState({
                    loading: false,
                    models: success.data.models,
                    count: success.data.total_count
                })
            });
        })
    }

    handleSearchTimeout() {
        this.setState({
            page: 1,
            loading: true
        }, () => {
            if (this.loadingTimeout) clearTimeout(this.loadingTimeout);
            this.loadingTimeout = setTimeout(this.handleSearch, 750);
        })
    }

    componentWillUnmount() {
        if (this.loadingTimeout) clearTimeout(this.loadingTimeout);
        if (this.subscribeSearch) GoaState.unsubscribe('GLOBAL_SEARCH', this.subscribeSearch);
    }

    handleSort(x) {
        let sort = this.state.sort
        if (sort.column == x.sortableColumn && sort.direction == 'ASC') {
            sort.direction = 'DESC';
        }
        else if (sort.column == x.sortableColumn && sort.direction == 'DESC') {
            sort = DEFAULT_PREFERENCE
        }
        else {
            sort = {
                column: x.sortableColumn,
                direction: 'ASC'
            }
        }
        this.setState({
            sort: sort
        }, () => {
            this.handleSearch();
            GoaUser.setPreference(this.props.tableKey + '_sort', sort)
        })
    }

    render() {

        if (this.props.hideIfEmpty && this.state.models.length == 0) return null;


        return (
            <PanelTable
                ref={e => this.table = e}
                style={this.props.style}
                onSort={this.handleSort}
                sort={this.state.sort}
                onRefresh={this.props.onRefresh}
                onPrint={this.props.onPrint}
                onExport={this.props.onExport}
                onDelete={this.props.onDelete}
                onUndo={this.props.onUndo}
                onCancel={this.props.onCancel}
                onApprove={this.props.onApprove}
                properties={this.props.properties}
                take={this.state.take}
                page={this.state.page}
                count={this.state.count}
                onPage={this.handlePage}
                onTake={this.handleTake}
                models={this.state.models}
                loading={this.state.loading}
                onSelectModel={this.props.onSelectModel}
                tableKey={this.props.tableKey}
                headerTitle={this.props.tableTitle}
                headerIcon={this.props.tableIcon}
                onRefreshSearch={this.handleSearch}
                onAdd={this.props.onAdd}
                searchQuery={this.state.query}
                onSelectModels={this.props.onSelectModels}
            />
        )
    }
}