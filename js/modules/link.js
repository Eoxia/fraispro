/**
 * @namespace fraispro.link
 * @description Manages the UI for linking expense report lines to invoice lines
 */
window.fraispro = window.fraispro || {};
window.fraispro.link = {};

/**
 * Init method — called automatically by saturne.js on document ready
 */
window.fraispro.link.init = function() {
	window.fraispro.link.event();
};

/**
 * Event bindings
 */
window.fraispro.link.event = function() {
	$(document).on('click', '.fraispro-link-open', window.fraispro.link.openLinkModal);
	$(document).on('change', '#fraispro-invoice-select', window.fraispro.link.loadInvoiceLines);
	$(document).on('click', '#fraispro-confirm-link', window.fraispro.link.confirmLink);
	$(document).on('change', '#fraispro-invoice-status', window.fraispro.link.loadInvoices);
	
	$(document).on('change', 'select[name="billing_product_id"], #billing_product_id, select[name="selected_invoice_id"]', window.fraispro.link.onDropdownChange);
};

/**
 * Open the link modal and load draft invoices
 *
 * @param {Event} event
 */
window.fraispro.link.openLinkModal = function(event) {
	event.preventDefault();

	var expdetId = $(event.target).closest('.fraispro-link-open').data('expdet-id') || $(this).data('expdet-id');
	$('#fraispro-link-expdet-id').val(expdetId);

	// Reset selects
	$('#fraispro-invoice-select').empty().append('<option value="">' + window.fraispro.link.selectInvoiceLabel + '</option>');
	$('#fraispro-invoiceline-select').empty().append('<option value="">' + window.fraispro.link.selectLineLabel + '</option>');
	$('#fraispro-line-preview').hide();

	// Load invoices
	window.fraispro.link.loadInvoices();

	// Open the Dolibarr dialog
	$('#fraispro-link-dialog').dialog({
		title: window.fraispro.link.modalTitle || 'Lier à une ligne de facture',
		modal: true,
		width: 600,
		resizable: false,
		close: function() {
			$(this).dialog('destroy');
		}
	});
};

/**
 * Load invoices based on selected status filter
 */
window.fraispro.link.loadInvoices = function() {
	var status = $('#fraispro-invoice-status').val();
	if (status === undefined || status === null) {
		status = -1; // All statuses
	}

	$('#fraispro-invoice-select').empty().append('<option value="">Chargement...</option>');
	$('#fraispro-invoiceline-select').empty().append('<option value="">' + window.fraispro.link.selectLineLabel + '</option>');

	$.ajax({
		url: window.fraispro.link.ajaxUrl,
		type: 'GET',
		dataType: 'json',
		data: {
			action: 'getinvoices',
			status: status,
			token: window.fraispro.link.token
		},
		success: function(response) {
			$('#fraispro-invoice-select').empty().append('<option value="">' + window.fraispro.link.selectInvoiceLabel + '</option>');
			if (response.success && response.data.length > 0) {
				$.each(response.data, function(i, invoice) {
					var label = invoice.ref;
					if (invoice.thirdparty) {
						label += ' - ' + invoice.thirdparty;
					}
					label += ' (' + invoice.total_ht.toFixed(2) + ' HT)';
					$('#fraispro-invoice-select').append('<option value="' + invoice.id + '">' + label + '</option>');
				});
			}
		},
		error: function() {
			$('#fraispro-invoice-select').empty().append('<option value="">Erreur de chargement</option>');
		}
	});
};

/**
 * Load invoice lines when an invoice is selected
 */
window.fraispro.link.loadInvoiceLines = function() {
	var factureId = $(this).val();

	$('#fraispro-invoiceline-select').empty().append('<option value="">' + window.fraispro.link.selectLineLabel + '</option>');
	$('#fraispro-line-preview').hide();

	if (!factureId) {
		return;
	}

	$('#fraispro-invoiceline-select').empty().append('<option value="">Chargement...</option>');

	$.ajax({
		url: window.fraispro.link.ajaxUrl,
		type: 'GET',
		dataType: 'json',
		data: {
			action: 'getinvoicelines',
			fk_facture: factureId,
			token: window.fraispro.link.token
		},
		success: function(response) {
			$('#fraispro-invoiceline-select').empty().append('<option value="">' + window.fraispro.link.selectLineLabel + '</option>');
			if (response.success && response.data.length > 0) {
				$.each(response.data, function(i, line) {
					var label = line.label + ' — ' + line.total_ht.toFixed(2) + ' HT / ' + line.total_ttc.toFixed(2) + ' TTC';
					$('#fraispro-invoiceline-select').append('<option value="' + line.id + '">' + label + '</option>');
				});
			} else {
				$('#fraispro-invoiceline-select').append('<option value="">Aucune ligne</option>');
			}
		},
		error: function() {
			$('#fraispro-invoiceline-select').empty().append('<option value="">Erreur de chargement</option>');
		}
	});
};

/**
 * Confirm the link — submit the form
 */
window.fraispro.link.confirmLink = function(event) {
	event.preventDefault();

	var expdetId = $('#fraispro-link-expdet-id').val();
	var facturedetId = $('#fraispro-invoiceline-select').val();

	if (!expdetId || !facturedetId) {
		setEventMessage('Veuillez sélectionner une ligne de facture', 'errors');
		return;
	}

	// Submit via hidden form
	$('#fraispro-link-form-expdet').val(expdetId);
	$('#fraispro-link-form-facturedet').val(facturedetId);
	$('#fraispro-link-form').submit();
};



/**
 * Handle change on the product/service billing selection dropdown or invoice dropdown
 */
window.fraispro.link.onDropdownChange = function() {
	var productVal = $('select[name="billing_product_id"]').val();
	var invoiceVal = $('select[name="selected_invoice_id"]').val();
	
	var isProductValid = (productVal && parseInt(productVal) > 0);
	var isInvoiceValid = (invoiceVal && parseInt(invoiceVal) > 0);
	
	var btn = $('#fraispro-add-to-invoice-btn');
	var productWarning = $('.fraispro-default-product-warning');
	var invoiceWarning = $('.fraispro-target-invoice-warning');
	
	if (isProductValid) {
		productWarning.hide();
	} else {
		productWarning.show();
	}
	
	if (isInvoiceValid) {
		invoiceWarning.hide();
	} else {
		invoiceWarning.show();
	}
	
	if (isProductValid && isInvoiceValid) {
		btn.prop('disabled', false).removeClass('butActionRefused').addClass('button');
	} else {
		btn.prop('disabled', true).addClass('butActionRefused').removeClass('button');
	}
};

// Initialize on document ready
$(document).ready(function() {
	if (typeof window.fraispro !== 'undefined' && typeof window.fraispro.link !== 'undefined' && typeof window.fraispro.link.init === 'function') {
		window.fraispro.link.init();
	}
});
