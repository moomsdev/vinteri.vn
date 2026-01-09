/**
 * Custom Post Order - Vanilla JavaScript
 * Drag & Drop ordering for posts, pages, and taxonomies without jQuery.
 */

const postOrderData = typeof window.lacaPostOrder !== 'undefined' ? window.lacaPostOrder : null;

const state = {
	draggingRow: null,
};

const selectors = {
	tableBody: 'table.wp-list-table tbody#the-list',
	inlineRow: '.inline-edit-row',
	noItemsRow: 'tr.no-items',
};

const isTaxonomyScreen = () => Boolean( postOrderData?.taxonomy );

const toggleSpinner = ( show = false ) => {
	const actions = document.querySelector( '.tablenav .actions' );
	if ( ! actions ) {
		return;
	}

	let spinner = actions.querySelector( '.spinner' );

	if ( show ) {
		if ( ! spinner ) {
			spinner = document.createElement( 'span' );
			spinner.className = 'spinner is-active';
			actions.prepend( spinner );
		}
		spinner.classList.add( 'is-active' );
	} else if ( spinner ) {
		spinner.classList.remove( 'is-active' );
	}
};

const serializeOrder = ( rows ) => {
	const params = new URLSearchParams();

	rows.forEach( ( row ) => {
		if ( ! row.id ) {
			return;
		}
		const [ prefix, id ] = row.id.split( '-' );
		if ( prefix && id ) {
			params.append( `${ prefix }[]`, id );
		}
	} );

	return params.toString();
};

const sendOrder = ( serializedOrder ) => {
	if ( ! postOrderData ) {
		return;
	}

	const body = new URLSearchParams();
	body.set( 'action', isTaxonomyScreen() ? 'update-menu-order-tags' : 'update-menu-order' );
	body.set( 'nonce', postOrderData.nonce || '' );
	body.set( 'order', serializedOrder );

	toggleSpinner( true );

	fetch( postOrderData.ajaxUrl, {
		method: 'POST',
		headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
		body,
	} ).finally( () => {
		toggleSpinner( false );
	} );
};

const applyDragAndDrop = () => {
	const tableBody = document.querySelector( selectors.tableBody );

	if ( ! tableBody ) {
		return;
	}

	const rows = Array.from( tableBody.querySelectorAll( 'tr' ) ).filter(
		( row ) => ! row.matches( selectors.inlineRow )
	);

	rows.forEach( ( row ) => {
		row.classList.add( 'is-draggable' );
		row.setAttribute( 'draggable', 'true' );

		row.addEventListener( 'dragstart', ( event ) => {
			state.draggingRow = row;
			row.classList.add( 'is-dragging' );
			event.dataTransfer.effectAllowed = 'move';
			event.dataTransfer.setData( 'text/plain', row.id );
		} );

		row.addEventListener( 'dragend', () => {
			row.classList.remove( 'is-dragging' );
			state.draggingRow = null;
		} );
	} );

	tableBody.addEventListener( 'dragover', ( event ) => {
		event.preventDefault();
		const targetRow = event.target.closest( 'tr' );

		if ( ! state.draggingRow || ! targetRow || targetRow === state.draggingRow || targetRow.matches( selectors.inlineRow ) ) {
			return;
		}

		const rect = targetRow.getBoundingClientRect();
		const shouldMoveAfter = event.clientY - rect.top > rect.height / 2;

		if ( shouldMoveAfter ) {
			targetRow.after( state.draggingRow );
		} else {
			targetRow.before( state.draggingRow );
		}
	} );

	tableBody.addEventListener( 'drop', ( event ) => {
		event.preventDefault();
		const orderedRows = Array.from( tableBody.querySelectorAll( 'tr' ) ).filter(
			( row ) => ! row.matches( selectors.inlineRow ) && ! row.matches( selectors.noItemsRow )
		);
		const serializedOrder = serializeOrder( orderedRows );
		if ( serializedOrder ) {
			sendOrder( serializedOrder );
		}
	} );
};

const fixTableWidths = () => {
	const tableBody = document.querySelector( selectors.tableBody );
	if ( ! tableBody ) {
		return;
	}

	const firstRow = tableBody.querySelector( 'tr' );
	if ( ! firstRow ) {
		return;
	}

	const baseWidths = Array.from( firstRow.children ).map( ( cell ) => cell.getBoundingClientRect().width );

	const applyWidths = ( elements ) => {
		elements.forEach( ( row ) => {
			Array.from( row.children ).forEach( ( cell, index ) => {
				const width = baseWidths[ index ];
				if ( typeof width === 'undefined' ) {
					return;
				}
				const styles = window.getComputedStyle( cell );
				const padding =
					parseFloat( styles.paddingLeft || '0' ) + parseFloat( styles.paddingRight || '0' );
				cell.style.width = `${ Math.max( width - padding, 0 ) }px`;
			} );
		} );
	};

	applyWidths( Array.from( tableBody.querySelectorAll( 'tr' ) ) );

	const table = tableBody.closest( '.wp-list-table' );
	if ( table ) {
		applyWidths( Array.from( table.querySelectorAll( 'thead tr, tfoot tr' ) ) );
	}
};

const bindCheckAll = ( triggerSelector, targetWrapperSelector ) => {
	const trigger = document.querySelector( triggerSelector );
	const wrapper = document.querySelector( targetWrapperSelector );

	if ( ! trigger || ! wrapper ) {
		return;
	}

	trigger.addEventListener( 'change', () => {
		const inputs = wrapper.querySelectorAll( 'input[type="checkbox"]' );
		inputs.forEach( ( input ) => {
			input.checked = trigger.checked;
		} );
	} );
};

const handleResetOrder = () => {
	const resetButton = document.querySelector( '#reset-scp-order' );
	const response = document.querySelector( '.scpo-reset-response' );

	if ( ! resetButton || ! postOrderData ) {
		return;
	}

	resetButton.addEventListener( 'click', ( event ) => {
		event.preventDefault();

		const checked = document.querySelectorAll( '.scpo-reset-order input[type="checkbox"]:checked' );
		if ( checked.length === 0 ) {
			if ( response ) {
				response.textContent = 'Please select at least one post type.';
			}
			return;
		}

		const body = new URLSearchParams();
		body.set( 'action', 'scpo_reset_order' );
		body.set( 'scpo_security', postOrderData.resetNonce || '' );
		checked.forEach( ( input ) => {
			body.append( 'items[]', input.name );
		} );

		fetch( postOrderData.ajaxUrl, {
			method: 'POST',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
			body,
		} )
			.then( ( res ) => res.json() )
			.then( ( res ) => {
				if ( response ) {
					response.textContent = res?.data || res?.message || 'Done';
				}
				setTimeout( () => window.location.reload(), 1200 );
			} )
			.catch( () => {
				if ( response ) {
					response.textContent = 'Reset failed.';
				}
			} );
	} );
};

const initSettingsPage = () => {
	bindCheckAll( '#scporder_allcheck_objects', '#scporder_select_objects' );
	bindCheckAll( '#scporder_allcheck_tags', '#scporder_select_tags' );
	handleResetOrder();
};

const initPostOrder = () => {
	if ( ! postOrderData ) {
		return;
	}

	if ( postOrderData.isSettingsPage ) {
		initSettingsPage();
	}

	if ( postOrderData.isSortableScreen ) {
		applyDragAndDrop();
		window.addEventListener( 'load', fixTableWidths );
	}
};

document.addEventListener( 'DOMContentLoaded', initPostOrder );