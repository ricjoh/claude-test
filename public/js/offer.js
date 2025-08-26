/* JS Functions for the Offer Edit Page app/views/offer/edit.phtml */

//                 o  o   o
//                 8  8   8
// .oPYo. .oPYo.  o8P 8  o8P .oPYo. ooYoYo. .oPYo.
// 8    8 8oooo8   8  8   8  8oooo8 8' 8  8 Yb..
// 8    8 8.       8  8   8  8.     8  8  8   'Yb.
// `YooP8 `Yooo'   8  8   8  `Yooo' 8  8  8 `YooP'
// :....8 :.....:::..:..::..::.....:..:..:..:.....:
// ::ooP'.:::::::::::::::::::::::::::::::::::::::::
// ::...:::::::::::::::::::::::::::::::::::::::::::

function getItems(callback) {
	$.ajax({
		url: getItemsUrl,
		type: "GET",
		dataType: "html",
		success: function (itemHtml) {

			// set item count in form
			var re = /ITEMCOUNT=(\d*)/i;
			var counts = itemHtml.match(re);
			$('#ItemCount').val(counts[1]);

			$('#itemHtml').html(itemHtml);

			$("#itemTable.tablesorter").tablesorter({
				// pass the headers argument and assign a object
				headers: {
					7: {
						// disable it by setting the property sorter to false
						sorter: false
					},
				},
			});

			// hide notes, sort table, reattach notes to the right places
			$("#itemTable th").click(function () {
				$(".itemNote").hide();
				setTimeout(function () {
					$(".itemDetails").each(function () {
						var vid = $(this).attr('rel');
						$(this).after($("[rel='" + vid + "'].itemNote"));
					});
					$(".itemNote").fadeIn();
				}, 300);
				return false;
			});

			$('#itemTable .itemNoteText').editable({
				mode: 'inline',
				success: reportErr
			});

			$('#itemTable .editnum').editable({
				placement: 'right',
				validate: function (value) {
					if (value === null) {
						return 'Must be number';
					}
				},
				success: reportErr
			});

			if (counts[1] > 0 && $('#StatusSOLD').val() != 1 && $('#StatusEXPIRED').val() != 1) {
				$('#sellButtonBlock').fadeIn();
			}
			else {
				$('#sellButtonBlock').fadeOut();
			}

			if ( $('#StatusEXPIRED').val() != 1) {
				$('#addItem').fadeIn();
			}
			else {
				$('#addItem').fadeOut();
			}

			checkSingleSkuStatus();

			if (callback) {
				callback();
			}
		}
	});
}

//                            8
//                            8
// oPYo. .oPYo. .oPYo. .oPYo. 8 .oPYo.
// 8  `' 8oooo8 8    ' .oooo8 8 8    '
// 8     8.     8    . 8    8 8 8    .
// 8     `Yooo' `YooP' `YooP8 8 `YooP'
// ..:::::.....::.....::.....:..:.....:
// ::::::::::::::::::::::::::::::::::::
// ::::::::::::::::::::::::::::::::::::

function recalcOfferItem() {

	console.log('Recalcuating...');

	costOut = parseFloat($('#CostOut').text());
	$('#CostOut').text(costOut.toFixed(4));
	totPrice = 0;
	totPieces = 0;
	totWeight = 0;

	// for each row in table
	// line price computation on weight edit
	$('.vatDetails').each(function () {
		weight = parseFloat($(this).find('a.offerWeight').text());
		$(this).find('a.offerWeight').text(weight.toFixed(2));
		price = costOut * weight;
		$(this).find('.priceField').text(price.toFixed(2));
		totPrice += price;
		totWeight += weight;
		totPieces += parseFloat($(this).find('a.offerPieces').text());
	});

	// Offer totals update (at bottom) on any edit
	$('#totCost').text(commatize(totPrice.toFixed(2)));
	$('#totWeight').text(commatize(totWeight.toFixed(2)));
	$('#totPieces').text(commatize(totPieces.toFixed(0)));
	return false;

}


//                             o   o                 .oPYo.   o           o
//                             8   8                 8        8           8
// .oPYo. .oPYo. o    o .oPYo. 8  o8P .oPYo. ooYoYo. `Yooo.  o8P .oPYo.  o8P .oPYo.
// Yb..   .oooo8 Y.  .P 8oooo8 8   8  8oooo8 8' 8  8     `8   8  .oooo8   8  8oooo8
//   'Yb. 8    8 `b..d' 8.     8   8  8.     8  8  8      8   8  8    8   8  8.
// `YooP' `YooP8  `YP'  `Yooo' 8   8  `Yooo' 8  8  8 `YooP'   8  `YooP8   8  `Yooo'
// :.....::.....:::...:::.....:..::..::.....:..:..:..:.....:::..::.....:::..::.....:
// :::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
// :::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::

var _ITEMSTATE = { costOut: 0, lines: [], notes: '' };

function saveItemState() {

	console.log('Saving Item State (Vats)...');

	_ITEMSTATE.costOut = parseFloat($('#CostOut').text()).toFixed(4);

	// for each row in table
	// line price computation on weight edit
	$('.vatDetails').each(function () {
		lineData = {};
		lineData.vatNumber = $(this).data('vatnumber');
		lineData.weight = parseFloat($(this).find('a.offerWeight').text());
		lineData.price = _ITEMSTATE.costOut * lineData.weight
		lineData.pieces = parseInt($(this).find('a.offerPieces').text());
		_ITEMSTATE.lines.push(lineData);
	});

	_ITEMSTATE.notes = $('#itemNotes').text();

	return false;

}


//                8             o    o          o
//                8             8b   8          8
// ooYoYo. .oPYo. 8  .o  .oPYo. 8`b  8 .oPYo.  o8P .oPYo. .oPYo.
// 8' 8  8 .oooo8 8oP'   8oooo8 8 `b 8 8    8   8  8oooo8 Yb..
// 8  8  8 8    8 8 `b.  8.     8  `b8 8    8   8  8.       'Yb.
// 8  8  8 `YooP8 8  `o. `Yooo' 8   `8 `YooP'   8  `Yooo' `YooP'
// ..:..:..:.....:..::...:.....:..:::..:.....:::..::.....::.....:
// ::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
// ::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::

function makeNotes(newLine, oldLine) {
	note = '';
	if (newLine.pieces !== oldLine.pieces) note += ' - revised Pieces on Vat ' + oldLine.vatNumber + ' from ' + oldLine.pieces + ' to ' + newLine.pieces + '\n';
	if (newLine.weight !== oldLine.weight) note += ' - revised Weight on Vat ' + oldLine.vatNumber + ' from ' + oldLine.weight + ' to ' + newLine.weight + '\n';

	return note;
}

// Ajax contacts for Customer
function updateContacts(id) {
	// var custid = $(':selected', this).val();
	var custid = $('#CustomerID option:selected').val();

	if (custid) {
		$.ajax({
			url: "/customer/getcontactlist/" + custid,
			type: "GET",
			dataType: "json",
			success: function (data) {
				$('#Attention').find('option').remove();
				$.each(data, function (key, value) {
					$('#Attention')
						.append($("<option></option>")
							.attr("value", key)
							.text(value));
				});

				if ($("#CustomerPhoneNumber").data('sacred') !== 'SACRED') {
					$("#CustomerPhoneNumber").val('');
				}

				if ($("#CustomerFaxNumber").data('sacred') !== 'SACRED') {
					$("#CustomerFaxNumber").val('');
				}

				if ($("#CustomerEmail").data('sacred') !== 'SACRED') {
					$("#CustomerEmail").val('');
				}

				if (typeof id !== undefined) {
					$('#Attention option[value="' + id + '"]')
						.prop('selected', true)
						.attr('selected', 'selected');
				}

			},
			error: function (e) {
				console.log('Error in Customer AJAX');
				console.log(JSON.stringify(e));
			}
		});

		var termsPID = $('#CustomerID option:selected').data('terms-pid');
		if (termsPID && termsPID != '00000000-0000-0000-0000-000000000000') {
			var $termsSelect = $('#TermsPID');

			if ($termsSelect.data('sacred') !== 'SACRED') {
				$termsSelect.children('option[value="' + termsPID + '"]').prop('selected', true);
			}
		}
	}

};



//                 o  o   o                 o     o          o
//                 8  8   8                 8     8          8
// .oPYo. .oPYo.  o8P 8  o8P .oPYo. ooYoYo. 8     8 .oPYo.  o8P .oPYo.
// 8    8 8oooo8   8  8   8  8oooo8 8' 8  8 `b   d' .oooo8   8  Yb..
// 8    8 8.       8  8   8  8.     8  8  8  `b d'  8    8   8    'Yb.
// `YooP8 `Yooo'   8  8   8  `Yooo' 8  8  8   `8'   `YooP8   8  `YooP'
// :....8 :.....:::..:..::..::.....:..:..:..:::..::::.....:::..::.....:
// ::ooP'.:::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
// ::...:::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::

function getItemsVats(offerItemID, callback) {
	_ITEMSTATE = { costOut: 0, lines: [] };

	console.log('getVats OIID: ' + offerItemID);

	if (typeof offerItemID == undefined || !offerItemID) {
		offerItemID = '00000000-0000-0000-0000-000000000000';
	}

	getItemVatsUrl = '/offer/getitemvats/' + offerItemID;

	$.ajax({
		url: getItemVatsUrl,
		type: "GET",
		dataType: "html",
		success: function (offerItemDetailContent) {
			$("#offerItemDetailContent").html(offerItemDetailContent);
			$('#itemModal').modal('show');

			$("#vatTable.tablesorter").tablesorter({
				// pass the headers argument and passing an object
				headers: {
					999: {
						// disable it by setting the property sorter to false
						sorter: false
					},
				}
			});

			// Test for numeric
			$('#vatTable .editnum').editable({
				placement: 'right',
				validate: function (value) {
					if (value === null) {
						return 'Must be number';
					}
					else {
						setTimeout(recalcOfferItem, 100);
					}

					// if (value > 0) {
					var $tr = $(this).closest('tr.vatDetails');
					var $offerWeight = $tr.find('a.offerWeight.editnum');
					// var offWeight = parseFloat($offerWeight.text().replace(',', ''));

					var approxWeight = 0.0;

					var invWeight = parseFloat($tr.children('td.InvWeight').text().replace(',', ''));
					var invPieces = parseInt($tr.children('td.InvPieces').text().replace(',', ''));

					if (value == invPieces) {
						approxWeight = invWeight;
					} else {
						var enteredWeight = parseFloat($tr.children('td.InvWeight').first().data('entered'));
						var enteredPieces = parseInt($tr.children('td.InvPieces').first().data('entered'));

						var avgWeight = enteredWeight / enteredPieces;

						approxWeight = avgWeight * value;
					}

					$offerWeight.editable('setValue', approxWeight.toFixed(2));
					// }
				}
			});

			// select number as soon as the input is focused
			$('#itemModal').on('focus', 'div.popover div.popover-content input.form-control', function (e) { // used to just be #vatTable
				var input = this;

				setTimeout(function () {
					input.select();
				}, 0);
			});

			// go forwards and backwards in the table using tab and shift+tab
			$('#vatTable').on('keydown', 'div.popover div.popover-content input.form-control', function (e) {
				if (e.which == 9) {
					e.preventDefault();

					let valid = true;
					let input = $( this );
					let form = input.closest('form.editableform');
					this.addEventListener('invalid', () => {valid = false;} );
					form[0].checkValidity();
					form[0].reportValidity();
					if ( valid ) {
						if (e.shiftKey) {
							var $next = $(this).closest('td').prevAll('td:has(.editnum)').first().find('.editnum');
							if ($next.length == 0) {
								$next = $(this).closest('tr.vatDetails').prev('tr.vatDetails').find('.editnum').last();
							}
						} else {
							var $next = $(this).closest('td').nextAll('td:has(.editnum)').first().find('.editnum');
							if ($next.length == 0) {
								$next = $(this).closest('tr.vatDetails').next('tr.vatDetails').find('.editnum').first();
							}
						}

						form.submit();
						$next.click();
					}
				}
			});

			// CostOut
			$('#CostOut').editable({
				placement: 'right',
				validate: function (value) {
					if (value === null || value < 0) {
						return 'Must be number greater than or equal to zero';
					}
					else {
						setTimeout(recalcOfferItem, 100);
					}
				}
			});

			$('#itemNotes').editable({
				mode: 'inline',
				inputclass: 'mytextarea',
				success: reportErr
			});

			$("#itemModal #lotSelection").autocomplete({
				source: function (request, response) {
					$.ajax({
						url: "/search/lotnumber",
						type: "POST",
						dataType: "json",
						data: { searchVal: request.term },
						success: function (data) {
							response($.map(data, function (item) {
								return {
									label: item.name,
									id: item.id,
									abbrev: item.name
								};
							}));
						}
					});
				},
				minLength: 2,
				autoFocus: true,
				select: function (event, ui) {
					// ajax and get offeritem id, on success pass to getItemVats
					lotID = ui.item.id;
					console.log('Success: ' + lotID);
					$.ajax({
						url: "/offer/newitem/" + $('#OfferID').val() + '/' + lotID,
						type: "GET",
						dataType: "json",
						success: function (data) {
							console.log('Ajax Success: ' + data.offeritemid);
							var cb = function () { return; };
							if (data.type === 'existing') {
								cb = function () { bootbox.alert('Editing existing offer item for lot ' + ui.item.label + '.'); };
							}
							getItemsVats(data.offeritemid, cb);
							checkSingleSkuStatus();
						}
					});
				}
			});

			if ($('#StatusSOLD').val() != 1) {
				$('#soldMessage').hide();
			}
			else {
				saveItemState();
				console.log(_ITEMSTATE);
			}

			if (callback) {
				callback();
			}
		}
	});
}

function setButton(button) {
	buttontext = button.innerHTML;
	var b = document.getElementById('mainbtn');
	b.innerHTML = buttontext;
	b.dataset.action = button.dataset.action;
}

function checkSingleSkuStatus() {
	const transferButton = $('#ship-sell-button #transferButton');
	if (!transferButton.length) return;

	$.ajax({
		url: "/offer/issinglesku/" + $('#OfferID').val(),
		type: "GET",
		dataType: "json",
		success: function (data) {
			transferButton.attr('class', data.isSingleSku ? 'button-option' : 'button-option disabled-button-option');

			if (data.isSingleSku) {
				transferButton.removeAttr('disabled');
				transferButton.removeAttr('title')
			} else {
				transferButton.attr('disabled', '');
				transferButton.attr('title', 'Cannot transfer this offer. All offer items must be the same product.');
				if ($('#mainbtn').data('action') == 'transfer') {
					setButton(document.getElementById('markSoldButton'));
				}
			}
		}
	});
}

//      8                .oPYo.                    8
//      8                8   `8                    8
// .oPYo8 .oPYo. .oPYo. o8YooP' .oPYo. .oPYo. .oPYo8 o    o
// 8    8 8    8 8    '  8   `b 8oooo8 .oooo8 8    8 8    8
// 8    8 8    8 8    .  8    8 8.     8    8 8    8 8    8
// `YooP' `YooP' `YooP'  8    8 `Yooo' `YooP8 `YooP' `YooP8
// :.....::.....::.....::..:::..:.....::.....::.....::....8
// ::::::::::::::::::::::::::::::::::::::::::::::::::::ooP'.
// ::::::::::::::::::::::::::::::::::::::::::::::::::::...::

$(document).ready(function () {

	getItems();

	$.fn.editable.defaults.mode = 'popup';
	$.fn.popover.Constructor.DEFAULTS.placement = 'left';

	$(".datepicker").datepicker({ dateFormat: "mm/dd/y" });

	reportErr = function (response) {
		if (response.status == 'error') return response.msg; // msg will be shown in editable form
	};



//                 o  ooooo        8      8
//                 8    8          8      8
// o    o .oPYo.  o8P   8   .oPYo. 8oPYo. 8 .oPYo.
// Y.  .P .oooo8   8    8   .oooo8 8    8 8 8oooo8
// `b..d' 8    8   8    8   8    8 8    8 8 8.
//  `YP'  `YooP8   8    8   `YooP8 `YooP' 8 `Yooo'
// ::...:::.....:::..:::..:::.....::.....:..:.....:
// ::::::::::::::::::::::::::::::::::::::::::::::::
// ::::::::::::::::::::::::::::::::::::::::::::::::

	$(document).on('click', '#offerAll', function () {
		// for each row in table
		// line price computation on weight edit
		$('.vatDetails').each(function () {
			$(this).find('a.offerWeight').text(parseFloat($(this).find('a.offerWeight').data('max')).toFixed(2));
			$(this).find('a.offerPieces').text(parseInt($(this).find('a.offerPieces').data('max')));
		});
		recalcOfferItem();
		return false;
	});

	/******************************************************************************* */
	// On submit, traverse form and post ajax to update OIitemVat, OItem, and Inventory
	// .removeClass( 'editable-unsaved' ); // on "Apply"?
	// refresh items list on save or on cancel. (Plus totals -- just getItems again?)
	// delete if no offer lines on cancel?
	/******************************************************************************* */

	$(document).on('click', '#saveItemButton', function () {

		actualWorkings = function () {
			costOut = parseFloat($('#CostOut').text()).toFixed(4);

			oldnotes = $('#itemNotes').text(); // -- get form notes.
			if (oldnotes === 'Empty') oldnotes = '';

			console.log('Old Notes: >' + oldnotes + '<');
			newnotes = '';
			var jsonData = {};
			var totals = { 'price': 0.0, 'pieces': 0, 'weight': 0.0 };
			var lineCount = 0;
			var lines = [];

			// for each row in table
			// line price computation on weight edit
			$('.vatDetails').each(function () {
				lineData = {};
				lineData.VatID = $(this).data('vatid');
				OfferItemID = $(this).data('offeritemid');
				lineData.OfferItemVatID = $(this).data('offeritemvatid');
				lineData.weight = parseFloat($(this).find('a.offerWeight').text());
				price = costOut * lineData.weight
				lineData.price = price.toFixed(2);
				lineData.pieces = parseInt($(this).find('a.offerPieces').text());
				lines.push(lineData);

				if ($('#StatusSOLD').val() == 1) {
					newnotes += makeNotes(lineData, _ITEMSTATE.lines[lineCount]);
				}

				totals.price += lineData.price;
				totals.weight += lineData.weight;
				totals.pieces += lineData.pieces;
				lineCount++;
			});

			var pallets = 0;

			if ($('#palletInput').val()) {
				pallets = $('#palletInput').val();
			}
			totals.price = parseFloat(totals.price).toFixed(2);

			jsonData.totals = totals;
			jsonData.lines = lines;
			jsonData.OfferItemID = OfferItemID;
			jsonData.CostOut = costOut;
			jsonData.Pallets = pallets;

			if ($('#StatusSOLD').val() == 1) {
				if (_ITEMSTATE.costOut !== costOut) newnotes = ' - revised Cost Out' + ' from ' + _ITEMSTATE.costOut + ' to ' + costOut + '\n' + newnotes;

				if (newnotes) {
					who = $('#currentUser').val();
					when = $('#currentTime').val();
					if ( oldnotes ) newnotes += "\n";
					newnotes = who + ' updated this on ' + when + ' with the following changes:\n' + newnotes;
				}
			}

			jsonData.NoteText = oldnotes + newnotes;
			console.log(JSON.stringify(jsonData.NoteText));

			$.ajax({
				url: "/offer/saveitemvats",
				type: "POST",
				data: { 'jsondata': jsonData },
				dataType: "json",
				success: function (data) {
					if (data.success == 1) {
						var mycallback = function () {
							$("#itemSavedFeedback").fadeIn(1000, function () {
								setTimeout(function () {
									$("#itemSavedFeedback").fadeOut(1000);
								}, 1000);
							});

						};
						$('#itemModal').modal('hide');
						getItems(mycallback);
					}
					else {
						bootbox.alert(data.msg);
					}
				}
			});
		}; // end function actualWorkings

		recalcOfferItem();

		if ($('#StatusSOLD').val() == 1) {
			bootbox.confirm('You are about to change values on a SOLD offer. Are you sure?', function (result) { if (result) actualWorkings(); }); // confirm callback
		}
		else {
			actualWorkings();
		}

	});

	// Function "offer whole lot"


//  o   o                 ooooo        8      8
//      8                   8          8      8
// o8  o8P .oPYo. ooYoYo.   8   .oPYo. 8oPYo. 8 .oPYo.
//  8   8  8oooo8 8' 8  8   8   .oooo8 8    8 8 8oooo8
//  8   8  8.     8  8  8   8   8    8 8    8 8 8.
//  8   8  `Yooo' 8  8  8   8   `YooP8 `YooP' 8 `Yooo'
// :..::..::.....:..:..:..::..:::.....::.....:..:.....:
// ::::::::::::::::::::::::::::::::::::::::::::::::::::
// ::::::::::::::::::::::::::::::::::::::::::::::::::::

	$('#itemTable .itemNoteText').editable({
		mode: 'inline',
		success: reportErr
	});

	$('#itemTable .editnum').editable({
		placement: 'right',
		validate: function (value) {
			if (value === null) {
				return 'Must be number';
			}
		},
		success: reportErr
	});

	$("body").on("mouseover", "#itemTable .editItemNote i", function () {
		$(this).removeClass('fa-file-o').addClass('fa-file');
	});

	$("body").on("mouseout", "#itemTable .editItemNote i", function () {
		$(this).removeClass('fa-file').addClass('fa-file-o');
	});

	$("body").on("mouseover", "#itemTable .deleteItem i", function () {
		$(this).removeClass('fa-trash-o').addClass('fa-trash');
	});

	$("body").on("mouseout", "#itemTable .deleteItem i", function () {
		$(this).removeClass('fa-trash').addClass('fa-trash-o');
	});

	$("body").on("click", "#addItem", function () {
		// set offer item ID to '00000000-0000-0000-0000-000000000000'
		// refresh screen

		// if ( $( '#StatusSOLD' ).val() == 1 ) {
		//     bootbox.confirm( 'You are about to add an item to a SOLD offer. Are you sure?', function(result){ if ( result ) getItemsVats(); });
		// } else if ( $( '#StatusEXPIRED' ).val() != 1 ) {
		//     getItemsVats();
		// }

		getItemsVats();

		$('#itemForm input[type="text"], #itemForm input[type="number"], #itemForm textarea').val('');
	});

	$("body").on("click", "#itemTable .deleteItem", function () {
		var iid = $(this).data('itemid');

		bootbox.confirm('Are you sure you want to delete this offer item?', function (result) {
			if (result) {
				$.ajax({
					url: "/offer/deleteitem",
					type: "POST",
					data: {
						OfferItemID: iid
					},
					dataType: "json",
					success: function (data) {
						if (data.success == 1) {
							var mycallback = function () {
								// bootbox.alert('Offer item deleted successfully');
							};

							getItems(mycallback);
						}
						else {
							bootbox.alert(data.msg);
						}
					}
				});
			}
		}); // confirm callback

		return false;
	});


	$("body").on("click", "#itemTable .editItemButton", function () {
		var iid = $(this).data('itemid');

		// ajax in item details, upon success, open window

		getItemsVats(iid);
		return false;
	});

	$("body").on("click", "#itemTable .editItemNoteButton", function () {
		var iid = $(this).data('itemid');

		$("[rel='" + iid + "'].hide").removeClass('hide');
		$("[data-pk='" + iid + "'].itemNoteText").click();
		return false;
	});


// 8                         8               ooooo
// 8                         8               8
// 8oPYo. .oPYo. .oPYo. .oPYo8 .oPYo. oPYo. o8oo   .oPYo. oPYo. ooYoYo.
// 8    8 8oooo8 .oooo8 8    8 8oooo8 8  `'  8     8    8 8  `' 8' 8  8
// 8    8 8.     8    8 8    8 8.     8      8     8    8 8     8  8  8
// 8    8 `Yooo' `YooP8 `YooP' `Yooo' 8      8     `YooP' 8     8  8  8
// ..:::..:.....::.....::.....::.....:..:::::..:::::.....:..::::..:..:..
// :::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
// :::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::

	// Listen on Email field and update icon
	function fixEmailIcons() {
		$('.email-icon').each(function () {
			id = '#' + $(this).attr('rel');
			$(this).attr('href', "mailto:" + $(id).val());
		});
	}
	$(".email-field").on('blur', function () { fixEmailIcons(); });

	$("#OfferDate").on('change', autoSetExpiration);

	// fire that change listener right away
	autoSetExpiration();

	function autoSetExpiration() {
		if ($("#OfferExpiration").val() === '') {
			odate = $("#OfferDate").val();
			var expDate = new Date(Date.parse(odate) + (10 * 24 * 60 * 60 * 1000));
			var dd = '0' + expDate.getDate().toString();
			var mm = '0' + (expDate.getMonth() + 1).toString();
			var yy = expDate.getFullYear().toString();
			$("#OfferExpiration").val(mm.substr(-2) + '/' + dd.substr(-2) + '/' + yy.substr(-2))
		}
	}

	// Listen on .protect and turn data( 'sacred', 'SACRED' )
	$(".protect").on('change', function () {
		if ($.trim($(this).val())) {
			$(this).data('sacred', 'SACRED');
		}
		else {
			$(this).data('sacred', 'MALLIABLE');
		}

	});

	// Status IDs
	var statii = {
		'EXPIRED': '319FB16C-19F5-4364-82E3-93AD7627AF38',
		'CONTRACT': '7001E6DC-0378-4B4A-9FC7-7AD78805884B',
		'OPEN': '9A085965-75B4-4EE2-85A8-02D61924DCC8',
		'SOLD': 'C5F3B7B9-9340-46B4-820A-504AC2A98A00'
	};

	// Do things when offer status changes
	$("#OfferStatusPID").on('change', function () {
		var stat = $(':selected', this).val();
		// console.log( 'changed to: ' + stat );
		itemcount = $('#ItemCount').val();
		// console.log( 'Item Count: ' + itemcount );
	});


	//               8 8  .oPYo.          o    o
	//               8 8  8   `8          8    8
	// .oPYo. .oPYo. 8 8 o8YooP' o    o  o8P  o8P .oPYo. odYo.
	// Yb..   8oooo8 8 8  8   `b 8    8   8    8  8    8 8' `8
	//   'Yb. 8.     8 8  8    8 8    8   8    8  8    8 8   8
	// `YooP' `Yooo' 8 8  8oooP' `YooP'   8    8  `YooP' 8   8
	// :.....::.....:....:......::.....:::..:::..::.....:..::..
	// ::::::::::::::::::::::::::::::::::::::::::::::::::::::::
	// ::::::::::::::::::::::::::::::::::::::::::::::::::::::::

	function showMismatches(mismatches) {
		$('.itemDetails').each(function (i, node) {
			const element = $(node);
			const productCode = element.data('product-code');
			const productDesc = element.data('product-desc');
			console.log(productCode);
			console.log(mismatches);
			if (productCode && mismatches[productCode]) {
				element.addClass('mismatch');
				element.attr('data-toggle', 'tooltip');
				// element.data('orderline', "refe");
				element.attr('data-orderline', JSON.stringify(mismatches[productCode]));
				element.tooltip({
					title: `Add <strong>${productDesc} (${productCode})</strong> to Customer Order.`,
					placement: 'top',
					html: true,
				});
			} else {
				element.removeClass('mismatch');
				element.tooltip('destroy');
				element.removeAttr('data-toggle');
			}
		});
	}

	// TODO: Add clicky to popup an "Add to Offer" dialog
	$( "#itemHtml" ).on( "click", ".mismatch" ,function() {
		const element = $( this );
		const thing = `${element.data( 'product-desc' )} (${element.data( 'product-code' )})`;
		bootbox.confirm(`<p>You are about to add <b>${thing}</b> to the original EDI Order!</p><p><strong>Are you sure?</strong></p>`, (result) => {
			if (result) {
				console.log(element);
				const orderline = element.data( 'orderline' );
				$.ajax({
					url: `/customerorder/addline/${orderline['CustomerOrderID']}/`,
					type: "POST",
					dataType: "json",
					data: orderline,
					success: (data) => {
						if (data.success == 1) {
							bootbox.alert('Added to Offer.');
							element.removeClass('mismatch');
							element.tooltip('destroy');
							element.removeAttr('data-toggle');
						} else {
							bootbox.alert(data.msg);
						}
					}
				});
			}
			else {
				console.log('mismatch click cancelled');
			}
		}); // confirm callback
	});

	function sellOffer(forceConfirm = false) {
		bootbox.confirm('<p>You are about to change this offer to SOLD. There is no going back!</p><p><strong>Are you sure?</strong></p>', function (result) {
			if (result) {
				$('#sellButtonBlock, #addItem').fadeOut(2000);
				$.ajax({
					url: "/offer/sell/" + $('#OfferID').val() + (forceConfirm ? '?confirm=1' : ''),
					type: "GET",
					dataType: "json",
					success: function (data) {
						if (data.success == 1) {
							$("#offerSoldFeedback").fadeIn(1000, function () {
								setTimeout(function () {
									$("#offerSoldFeedback").fadeOut(1000);
								}, 1000);
							});
							// console.log( 'enabling' );
							$('#BOLButton').removeClass('disabled');
							$('#BOLButton').prop('disabled', false);
							$('#BOLButton').removeAttr('disabled');
							document.location.href = '/offer/edit/' + $('#OfferID').val() + '?cache=' + Date.now();
						} else {
							bootbox.alert(data.msg, function() {
								$('#sellButtonBlock, #addItem').fadeIn(1000);
								showMismatches(data.mismatches);
							});
						}
					}
				});
			}
		}); // confirm callback
	}

	$("#sellButton").on('click', function () {console.log('sellButton');sellOffer();});

	function sellAndShipOffer() {
		bootbox.confirm('<p>You are about to SELL this offer and mark it SHIPPED! There is no going back!</p><p><strong>Are you sure?</strong></p>', function (result) {
			if (result) {
				$('#sellButtonBlock, #addItem').fadeOut(2000);
				$.ajax({
					url: "/offer/quickship/" + $('#OfferID').val(),
					type: "GET",
					dataType: "json",
					success: function (data) {
						if (data.success == 1) {
							$("#offerShippedFeedback").fadeIn(1000, function () {
								setTimeout(function () {
									$("#offerShippedFeedback").fadeOut(1000);
								}, 1000);
							});
							$('#BOLButton').removeClass('disabled');
							$('#BOLButton').prop('disabled', false);
							$('#BOLButton').removeAttr('disabled');
							document.location.href = '/offer/edit/' + $('#OfferID').val() + '?cache=' + Date.now();
						}
						else {
							bootbox.alert(data.msg);
						}
					}
				});
			}
		}); // confirm callback
	}

	function transferOffer() {
		bootbox.confirm('<p>You are about to TRANSFER this offer to a new draft lot! Saving the lot will close this offer.</p><p>If you create a draft lot by mistake, you can delete that lot to undo this operation.</p><p><strong>Are you sure?</strong></p>', function (result) {
			if (result) {
				$('#sellButtonBlock, #addItem').fadeOut(2000);
				$.ajax({
					url: '/offer/transfertolot/' + $('#OfferID').val(),
					type: 'GET',
					dataType: 'json',
					complete: function (jqXHR, textStatus) {
						const data = JSON.parse(jqXHR.responseText);
						console.log(data);
						if (data.success == 1) {
							// send user to edit the new lot
							document.location.href = '/lot/edit/' + data.lotId + '?transferred=1';
						} else {
							bootbox.alert(data.msg);
						}
					}
				});
			}
		});
	}

	//               8 8     .o        8       o          8               o    o
	//               8 8    .o'        8                  8               8    8
	// .oPYo. .oPYo. 8 8   .o'  .oPYo. 8oPYo. o8 .oPYo.   8oPYo. o    o  o8P  o8P .oPYo. odYo.
	// Yb..   8oooo8 8 8  .o'   Yb..   8    8  8 8    8   8    8 8    8   8    8  8    8 8' `8
	//   'Yb. 8.     8 8 .o'      'Yb. 8    8  8 8    8   8    8 8    8   8    8  8    8 8   8
	// `YooP' `Yooo' 8 8 o'     `YooP' 8    8  8 8YooP'   `YooP' `YooP'   8    8  `YooP' 8   8
	// :.....::.....:......::::::.....:..:::..:..8 ....::::.....::.....:::..:::..::.....:..::..
	// ::::::::::::::::::::::::::::::::::::::::::8 ::::::::::::::::::::::::::::::::::::::::::::
	// ::::::::::::::::::::::::::::::::::::::::::..::::::::::::::::::::::::::::::::::::::::::::

	const specialarea = document.getElementById( 'ship-sell-button' );

	specialarea.onclick = function(event) {

		if (event.target.matches('.dropbtn, .fa-chevron-down')) {
			event.stopPropagation();
			document.getElementById("myDropdown").classList.toggle("show");
		}

		if (!event.target.matches('.dropbtn, .fa-chevron-down')) {
			var dropdowns = document.getElementsByClassName("dropdown-content");
			var i;
			for (i = 0; i < dropdowns.length; i++) {
				var openDropdown = dropdowns[i];
				if (openDropdown.classList.contains('show')) {
					openDropdown.classList.remove('show');
				}
			}
		}
		if (event.target.matches('.button-option')) {
			event.stopPropagation();
			if (!event.target.attributes.disabled) {
				setButton(event.target);
			}
		}

		if (event.target.matches('#mainbtn')) {
			event.stopPropagation();
			buttontext = event.target.innerHTML;
			if ( event.target.dataset.action === 'sell' ) {
				console.log('mainbtn');
				sellOffer();
			} else if ( event.target.dataset.action === 'ship' ) {
				sellAndShipOffer();
			} else if ( event.target.dataset.action === 'transfer' ) {
				transferOffer();
			}
		}

	}

	// re-open an expired offer
	$('#reopenButton').click(function() {
		var offerId = $('#OfferID').val();
		bootbox.confirm('Are you sure you want to re-open this offer?', function(result) {
			if (result) {
				$.ajax({
					url: "/offer/reopen/" + offerId,
					type: "GET",
					dataType: "json",
					success: function(data) {
						if (data.success == 1) {
							$("#offerReopenedFeedback").fadeIn(1000, function() {
								setTimeout(function() {
									$("#offerReopenedFeedback").fadeOut(1000);
								}, 1000);
							});
							$('#reopenButton').hide();
							document.location.href = '/offer/edit/' + $('#OfferID').val() + '?cache=' + Date.now();
						}
						else {
							bootbox.alert(data.msg);
						}
					}
				});
			}
		});
	});


	// onchange UserID AJAX: /user/detail/70D9A0D2-C073-4BD1-BF96-62F034600CDF to re-fill values unless edited
	$("#UserID").on('change', function () {
		var userid = $(':selected', this).val();

		if (userid) {
			$.ajax({
				url: "/user/detail/" + userid,
				type: "GET",
				dataType: "json",
				success: function (data) {
					if ($("#OCSContactPhoneNumber").data('sacred') !== 'SACRED') $("#OCSContactPhoneNumber").val(data.Phone);
					if ($("#OCSContactFaxNumber").data('sacred') !== 'SACRED') $("#OCSContactFaxNumber").val(data.Fax);
					if ($("#OCSContactEmail").data('sacred') !== 'SACRED') $("#OCSContactEmail").val(data.Email);
					fixEmailIcons();
				},
				error: function (e) {
					console.log('Error in User AJAX');
					console.log(JSON.stringify(e));
				}
			});
		}

	});

	$("#CustomerID").on('change', function () {
		updateContacts();
	});

	// Copy UserID ajax for #Attention
	$("#Attention").on('change', function () {
		var contactid = $(':selected', this).val();

		if (contactid) {
			$.ajax({
				url: "/customer/getcontactdetail/" + contactid,
				type: "GET",
				dataType: "json",
				success: function (data) {
					if ($("#CustomerPhoneNumber").data('sacred') !== 'SACRED') $("#CustomerPhoneNumber").val(data.BusPhone);
					if ($("#CustomerFaxNumber").data('sacred') !== 'SACRED') $("#CustomerFaxNumber").val(data.Fax);
					if ($("#CustomerEmail").data('sacred') !== 'SACRED') $("#CustomerEmail").val(data.BusEmail);
					fixEmailIcons();
				},
				error: function (e) {
					console.log('Error in Contact AJAX');
					console.log(JSON.stringify(e));
				}
			});
		}

	});

});

//               8  o      8          o   o
//               8         8          8
// o    o .oPYo. 8 o8 .oPYo8 .oPYo.  o8P o8 .oPYo. odYo.
// Y.  .P .oooo8 8  8 8    8 .oooo8   8   8 8    8 8' `8
// `b..d' 8    8 8  8 8    8 8    8   8   8 8    8 8   8
//  `YP'  `YooP8 8  8 `YooP' `YooP8   8   8 `YooP' 8   8
// ::...:::.....:..:..:.....::.....:::..::..:.....:..::..
// ::::::::::::::::::::::::::::::::::::::::::::::::::::::
// ::::::::::::::::::::::::::::::::::::::::::::::::::::::

jQuery(function () {
	jQuery("#lotForm input, #lotForm select, #lotForm textarea, #vatForm input, #CostOut").not("[type=submit]")
		.jqBootstrapValidation({
		sniffHtml: false,
		preventSubmit: true,
		submitError: function ($form, event, errors) {
			// Here I do nothing, but you could do something like display
			// the error messages to the customer, log, etc.
			console.log('There was an error in the form');
			console.log(JSON.stringify(errors));
		},
		submitSuccess: function ($form, event) {
			// form has been submitted
			console.log( 'Submit Success: ' , $form[0].id );

			if ($form[0].id == 'lotForm') {
				console.log( 'Calling AJAX save' );
				$.ajax({
					url: "/offer/save",
					type: "POST",
					data: $('#lotForm').serialize(),
					dataType: "json",
					success: function (data) {
						if (data.success == 1) {
							$('#lotForm').data("changed", false);

							if (data.newOfferFlag == 1 && data.OfferID.length > 5) {
								window.location.href = '/offer/edit/' + data.OfferID;
							} else {

								$("#lotSavedFeedback").fadeIn(1000, function () {
									setTimeout(function () {
										$("#lotSavedFeedback").fadeOut(1000);
									}, 1000);
								});

								$('#saveOfferNote').hide();
								$('#itemHtml').show();
								$("#saveButton").html('Save Offer Info');
							}

						}
						else {
							bootbox.alert(data.msg);
						}
					}
				});
			}
		},
		filter: function () {
			return jQuery(this).is(":visible");
		}
	});
});
