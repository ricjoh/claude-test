//               _ __     __    _
//     __ _  ___| |\ \   / /_ _| |_ ___
//    / _` |/ _ \ __\ \ / / _` | __/ __|
//   | (_| |  __/ |_ \ V / (_| | |_\__ \
//    \__, |\___|\__| \_/ \__,_|\__|___/
//    |___/

function getVats(callback)
{
	$.ajax({
		url: getVatsUrl,
		type: "GET",
		dataType: "html",
		success: function(vatHtml) {

			// set item count in form
			var re = /--LOTTOTALS=(\{.*\})--/i;
			var counts = vatHtml.match(re);
			// console.log( counts[1] );
			var countHash = JSON.parse( counts[1] );
			// console.log( countHash );

			fillArray = [ "lotPieces", "lotWeight", "lotAvailPieces", "lotAvailWeight" ];

			for ( i = 0; i < fillArray.length; i++ )
			{
				f = fillArray[ i ];
				$( '#' + f ).text( commatize( countHash[ f ], f.indexOf( 'Weight' ) > 0 ? 2 : 0 ) );
			}

			$('#vatHtml').html(vatHtml);

			$(".tablesorter").tablesorter({
				// pass the headers argument and assing a object
				headers: {
					10: {
						// disable it by setting the property sorter to false
						sorter: false
					},
				},
				// define a custom text extraction function
				// textExtraction: function(node) {
					// return node.childNodes[1].innerHTML;
				// }

			});

			$(".tablesorter th").click( function(){
				$( ".vatNote, .vatNoteDivider" ).hide();
				setTimeout( function() {
					$( ".vatDetails" ).each( function(){
						var vid = $( this ).attr( 'rel' );
						$( this ).after( $("[rel='" + vid + "'].vatNote"), $("[rel='" + vid + "'].vatNoteDivider") );
					});
					$( ".vatNote, .vatNoteDivider" ).fadeIn();
				}, 300 );
				return false;
			} );

			$('.vatNoteText').editable({
				mode: 'inline',
				success: reportErr
			});


			$('.editdate').editable({
				format: 'mm/dd/yyyy',
				viewformat: 'mm/dd/yyyy',
				mode: 'popup',
				datepicker: {
						weekStart: 1
				},
				validate: function( value ) {
					if ( value === null )
					{
						return 'Must be a valid date.';
					}
				},
				success: reportErr
			});

			$('.editnum').editable({
				placement: 'right',
				validate: function( value ) {
					if ( value === null )
					{
						return 'Must be number';
					}
				},
				success: function( response ){
					getLotTotals();
					reportErr( response );

					if (typeof response.avail != 'undefined') {
						var name = $(this).data('name');
						var $availTd = $(this).closest('tr.vatDetails').children('td.avail.'+name);

						$availTd.text(response.avail);
					}
				}
			}).on( "shown", function(){

				if ( $( this ).data( 'warn' ) === 'TRUE' )
				{
					bootbox.alert( 'You are about to edit a vat that has offers or sales against it.' );
				}
			} );

			if ( callback ) {
				callback();
			}
		}
	});
}

//               _   _          _  _____     _        _
//     __ _  ___| |_| |    ___ | ||_   _|__ | |_ __ _| |___
//    / _` |/ _ \ __| |   / _ \| __|| |/ _ \| __/ _` | / __|
//   | (_| |  __/ |_| |__| (_) | |_ | | (_) | || (_| | \__ \
//    \__, |\___|\__|_____\___/ \__||_|\___/ \__\__,_|_|___/
//    |___/

function getLotTotals()
{
	getLotTotalsUrl = getVatsUrl.replace( 'getvats', 'getlottotals' );

	$.ajax({
		url: getLotTotalsUrl,
		type: "GET",
		dataType: "html",
		success: function( data ) {
			var countHash = JSON.parse( data );
			console.log( countHash );

			fillArray = [ "lotPieces", "lotWeight", "lotAvailPieces", "lotAvailWeight" ];

			for ( i = 0; i < fillArray.length; i++ )
			{
				f = fillArray[ i ];
				$( '#' + f ).text( commatize( countHash[ f ], f.indexOf( 'Weight' ) > 0 ? 2 : 0 ) );
			}
		}
	});
}


//             _   ____                          _       _     _ _     _
//    ___  ___| |_|  _ \ ___   ___  _ __ ___    / \   __| | __| | |   | | __
//   / __|/ _ \ __| |_) / _ \ / _ \| '_ ` _ \  / _ \ / _` |/ _` | |   | |/ /
//   \__ \  __/ |_|  _ < (_) | (_) | | | | | |/ ___ \ (_| | (_| | |___|   <
//   |___/\___|\__|_| \_\___/ \___/|_| |_| |_/_/   \_\__,_|\__,_|_____|_|\_\

// controls adding/hiding/showing of "+" icon for adding rooms
function setRoomAddLink()
{
	$('.addLocationLinkContainer').remove();

	var roomSiteContainers = $('.roomSiteContainer');

	if ( roomSiteContainers.length < 3 )
	{
		var roomSiteContainer = $('.roomSiteContainer').last();

		$(
			'<div class="pull-right fa-icon-right addLocationLinkContainer">' +
				'<a href="#" id="addLocationLink" title="Add New Room/Site"><i class="fa fa-plus fa-lg fa-fw"></i></a>' +
			'</div>'
		).insertAfter(
			roomSiteContainer.find('.siteTxtInput')
		);
	}

	roomSiteContainers.each(function() {
		var t = $(this);
		var iconCount = t.find('.fa-icon-right').length;

		t.find('.siteTxtInput').removeClass(
			"fa-icon-left fa-icon-left-two fa-icon-left-full-width"
		).addClass(
			(iconCount == 1) ? 'fa-icon-left' :
			(iconCount == 2) ? 'fa-icon-left-two' :
				'fa-icon-left-full-width'
		);
	});
}

//        _                                 _        ____
//     __| | ___   ___   _ __ ___  __ _  __| |_   _ / /\ \
//    / _` |/ _ \ / __| | '__/ _ \/ _` |/ _` | | | | |  | |
//   | (_| | (_) | (__ _| | |  __/ (_| | (_| | |_| | |  | |
//    \__,_|\___/ \___(_)_|  \___|\__,_|\__,_|\__, | |  | |
//                                            |___/ \_\/_/

$(document).ready( function() {

	// check if transferred param is present
	const params = new URLSearchParams(window.location.search);
	if (params.has('transferred')) {
		// remove param from url
		window.history.replaceState({}, '', window.location.href.replace(/[?&]transferred=[^?&]*/i, ''));
	}

	setRoomAddLink();

	var roomTempOnLoad = {};

	$('.room-control select').change(function() {

		var InputID	= this.name.replace('RoomPID', 'RoomTemp');

		if (this.value.trim().length > 0)
		{
			// Prevents stored temperature values
			// in Lot from being overwritten when
			// the page first loads.

			if (!roomTempOnLoad[InputID] && $('#' + InputID).val().length > 0)
			{
				roomTempOnLoad[InputID] = true;
				return;
			}

			// Update the room temperature value
			// when a new room is selected.

			var RoomID	= $(this).val();
			var Detail	= getRoomDetail(RoomID, function(Detail) {
				$('#' + InputID).val(Detail.temperature);
			});
		}
		else
		{
			$('#' + InputID).val('');
		}
	}).change();

	// Reverts the temperature back if none provided.
	$('.temp-control input').change(function() {
		if (this.value.trim().length == 0)
		{
			var RoomInputID	= this.name.replace(/RoomTemp/, 'RoomPID');

			$('#' + RoomInputID).change();
		}
	});

	$( "body" ).on( "click", "#SaveAddButton", function() {
		$( "#vatFormReload" ).val( 1 );
		$( "#vatForm" ).submit();
	});

	$( "body" ).on( "click", "#addLocationLink", function() {

		var cloned = $('.roomSiteContainer').first().clone(true);

		cloned.insertAfter($('.roomSiteContainer').last());

		var numLocations = parseInt($('.roomSiteContainer').length);

		var roomPIDName	= 'RoomPID' + numLocations;
		var tempName	= 'RoomTemp' + numLocations;
		var siteName	= 'Site' + numLocations;

		var roomSiteContainer = $('.roomSiteContainer').last();

		roomSiteContainer.find('select[name="RoomPID"]').attr({
			'name': roomPIDName,
			'id': roomPIDName
		}).val('');

		roomSiteContainer.find('input[name="RoomTemp"]').attr({
			'name': tempName,
			'id': tempName
		}).val('');

		var siteInput = roomSiteContainer.find('input[name="Site"]');
		siteInput
			.removeClass('fa-icon-left')
			.addClass('fa-icon-left-two')
			.attr({
				'name': siteName,
				'id': siteName
			}).val('');

		$(
			'<div class="pull-right fa-icon-right">' +
				'<a href="#" class="removeRoomSite" title="Remove This Room/Site">' +
					'<i class="fa fa-trash fa-lg fa-fw"></i>' +
				'</a>' +
			'</div>'
		).insertAfter(
			siteInput
		); // roomSiteContainer.find('select[name="' + roomPIDName + '"]')

		setRoomAddLink();

		return false;
	});

	$( "body" ).on( "click", ".removeRoomSite", function() {
		var removeIndex = $('.removeRoomSite').index(this) + 1;
		$( ".roomSiteContainer" ).eq( removeIndex ).remove();
		setRoomAddLink();
		return false;
	});

	getVats();

	$.fn.editable.defaults.mode = 'popup';
	$.fn.popover.Constructor.DEFAULTS.placement = 'left';

	$( ".datepicker" ).datepicker();

	reportErr = function( response ){
		if (response.status == 'error') return response.msg; // msg will be shown in editable form
	};

	$( "body" ).on( "mouseover", "#vatTable .editVatNote i", function() {
		$( this ).removeClass( 'fa-file-o' ).addClass( 'fa-file' );
	});

	$( "body" ).on( "mouseout", "#vatTable .editVatNote i", function() {
		$( this ).removeClass( 'fa-file' ).addClass( 'fa-file-o' );
	});

	$( "body" ).on( "mouseover", "#vatTable .deleteVat i", function() {
		$( this ).removeClass( 'fa-trash-o' ).addClass( 'fa-trash' );
	});

	$( "body" ).on( "mouseout", "#vatTable .deleteVat i", function() {
		$( this ).removeClass( 'fa-trash' ).addClass( 'fa-trash-o' );
	});

	$( "body" ).on( "click", "#addVat", function() {
		$('#vatForm input[type="text"], #vatFormReload, #vatForm input[type="number"], #vatForm textarea').val('');
	});

	$( "body" ).on( "click", "#vatTable .deleteVat", function() {
		var vid = $( this ).data( 'vatid' );
		bootbox.confirm( 'Are you sure you want to delete this vat?', function(result){
			if ( result )
			{
				$.ajax({
					url: "/vat/delete",
					type: "POST",
					data: {
						VatID : vid
					},
					dataType: "json",
					success: function(data) {
						if ( data.success == 1 )
						{
							var mycallback = function() {
								// bootbox.alert('Vat deleted sucessfully');
							};

							getVats(mycallback);
						}
						else
						{
							bootbox.alert( data.msg );
						}
					}
				});
			}
		});
		return false;
	});

	$( "body" ).on( "click", "#vatTable .editVatNoteButton", function() {
		var vid = $( this ).data( 'vatid' );

		$( "[rel='" + vid + "'].hide").removeClass( 'hide' );
		$( "[data-pk='" + vid + "'].vatNoteText" ).click();
		return false;
	});


	$("#FactoryID").on( 'change', function() {
		// console.log( $(this).find(":selected").data( 'fnum' ) );
		$( "#FactoryNumber" ).val( $(this).find(":selected").data( 'fnum' ) );
	});

	function inventoryTypeChanged() {
		var type = $('#InventoryTypePID :selected').text();
		if (type == 'Select...') return;

		$('.show-on-'+type).show();
		$('.hide-on-'+type).hide();

		var $enabled = $('.enabled-on-'+type);
		var $disabled = $('.disabled-on-'+type);

		$enabled.find('label').removeClass('disabled');
		$enabled.find('.form-link').removeClass('disabled');
		$enabled.find('input, select').prop('disabled', false);
		$enabled.find('input, select').removeAttr('disabled');

		$disabled.find('label').addClass('disabled');
		$enabled.find('.form-link').addClass('disabled');
		$disabled.find('input, select').prop('disabled', true);
	}

	$("#InventoryTypePID").on('change', inventoryTypeChanged);

	inventoryTypeChanged();

	// transfer

	function transferLot() {
		bootbox.confirm('<p>You are about to TRANSFER this lot to a new draft lot! Saving the draft lot will deplete this lot\'s inventory.</p><p>If you create a draft lot by mistake, you can delete that lot to undo this operation.</p><p><strong>Are you sure?</strong></p>', function (result) {
			if (result) {
				$.ajax({
					url: '/lot/transfertolot/' + $('#LotID').val(),
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

	$("#lotTransferButton").on('click', transferLot);

	// $('.form-link a').click(function() {
	// 	if ($(this).parents('.form-link.disabled').length) return false;
	// });
//                        _       _
//    _ __ ___   ___   __| | __ _| |___
//   | '_ ` _ \ / _ \ / _` |/ _` | / __|
//   | | | | | | (_) | (_| | (_| | \__ \
//   |_| |_| |_|\___/ \__,_|\__,_|_|___/

	$("#activityModal").on("show.bs.modal", function(e) {
		var url = '/lot/getactivity/' + LOT_ID;

		$.get(url, function(data) {
			$('#activityModal .modal-body').html(data);
			$('#activity-table').tablesorter({
				// pass the headers argument and assing a object
				headers: {
					// 10: {
						// disable it by setting the property sorter to false
					// 	sorter: false
					// },
				},
				// define a custom text extraction function
				// textExtraction: function(node) {
					// return node.childNodes[1].innerHTML;
				// }

			});
		});
	});


	$("#testsModal").on("show.bs.modal", function(e) {
		var url = '/contaminenttest/edit/' + LOT_ID;

		$.get(url, function(data) {
			$('#testsModal .modal-body').html(data);

			$( "#testForm .datepicker" ).datepicker();

			$("#testForm input, #testForm select, #testForm textarea").not("[type=submit]").jqBootstrapValidation({
				sniffHtml: false,
				preventSubmit: true,
				submitError: function($form, event, errors) {
					// Here I do nothing, but you could do something like display
					// the error messages to the customer, log, etc.
					console.log( 'There was an error in the form' + JSON.stringify(errors));
				},
				submitSuccess: function($form, event) {

					// form has been submitted
					if ( $form[0].id == 'testForm' )
					{
						$.ajax({
							url: "/contaminenttest/save/" + LOT_ID,
							type: "POST",
							data: $('#testForm').serialize(),
							dataType: "json",
							success: function(data) {
								if ( data.success == 1 ) {
									console.log( 'saved' );

									$('#testsModal').modal('hide');
								}
								else {
									console.log('fail');
									bootbox.alert( data.msg );
								}
							}
						});
					}
				},
				filter: function() {
					return $(this).is(":visible");
				}
			});
		});
	});
	$('#save-tests-button').click(function() {
		$('#testForm').submit();
	});

	$("#vatModal").on("show.bs.modal", function(e) {
		$('nav.navbar, .subnav, #pageTitle, .hide-on-vat-entry:not(td,th)').slideUp(500);
		// $('td.hide-on-vat-entry, th.hide-on-vat-entry').hide();
		$('#vatHtml').addClass('vat-entry-active');

		$('#vatHtml').animate({
			marginLeft: '50%'
		}, 500);

		window.scrollTo(0, 0);
	});

	$("#vatModal").on("hide.bs.modal", function(e) {
		$('nav.navbar, .subnav, #pageTitle, .hide-on-vat-entry:not(td,th)').slideDown(500);
		// $('td.hide-on-vat-entry, th.hide-on-vat-entry').show();
		$('#vatHtml').removeClass('vat-entry-active');

		$('#vatHtml').animate({
			marginLeft: '0%'
		}, 500);
	});
});

//               _ _     _       _   _
//   __   ____ _| (_) __| | __ _| |_(_) ___  _ __
//   \ \ / / _` | | |/ _` |/ _` | __| |/ _ \| '_ \
//    \ V / (_| | | | (_| | (_| | |_| | (_) | | | |
//     \_/ \__,_|_|_|\__,_|\__,_|\__|_|\___/|_| |_|

function showLotSavedFeedback() {
	$( "#lotSavedFeedback" ).fadeIn( 1000, function() {
		setTimeout( function() {
			$( "#lotSavedFeedback" ).fadeOut( 1000 );
		}, 1000);
	});
}

jQuery(function () {
	// check for saved query string
	if (window.location.href.indexOf('saved=1') > -1) {
		window.history.replaceState({}, '', window.location.href.replace(/[?&]saved=[^?&]*/i, ''));
		$('#lotSaveButton')[0].scrollIntoView()
		showLotSavedFeedback();
	}

	jQuery("#lotForm input, #lotForm select, #lotForm textarea, #vatForm input").not("[type=submit]").jqBootstrapValidation({
		sniffHtml: false,
		preventSubmit: true,
		submitError: function($form, event, errors) {
			// Here I do nothing, but you could do something like display
			// the error messages to the customer, log, etc.
			// console.log( 'There was an error in the form' );
		},
		submitSuccess: function($form, event) {
			// form has been submitted
			if ( $form[0].id == 'lotForm' )
			{
				var RoomNums = ['', '2', '3'];
				for (var a = 0; a < RoomNums.length; a++)
				{
					var RoomPID		= $('#RoomPID' + RoomNums[a]),
						RoomTemp	= $('#RoomTemp' + RoomNums[a]),
						matches;

					// If there's a room, look for a number as temperature,
					// then format it as "50*" as Diane had formatted it.

					if (RoomPID.length > 0 && RoomPID.val().length > 0)
					{
						var RoomTempDeg = RoomTemp.val().trim();

						if ( (matches = RoomTempDeg.match(/^(\d+)/)) )
						{
							RoomTemp.val(matches[1] + '*');
						}
						else
						{
							// Throw an error alert and bail if bad temp value
						        RoomTemp.val('');
							/* var msg = [
								'The temperature',
								'\'' + RoomTempDeg + '\'',
								'entered for Room',
								'#' + ((RoomNums[a] != '') ? RoomNums[a] : '1'),
								RoomPID.find('option:selected').text(),
								'doesn\'t appear to be valid.'
							];

							bootbox.alert(msg.join(' '));

							return;*/
						}
					}
				}

				$.ajax({
					url: "/lot/save",
					type: "POST",
					data: $('#lotForm').serialize(),
					dataType: "json",
					success: function(data) {
						console.log(data);
						if ( data.success == 1 )
						{
							$('#lotForm').data("changed", false);
							if ( data.newLotID != '' )
							{
								window.location.href = '/lot/edit/'+data.newLotID;
							}
							else if ( data.reload )
							{
								window.location.href += (window.location.href.indexOf('?') > -1 ? '&' : '?') + 'saved=1';
							}
							else
							{
								showLotSavedFeedback();

								// Commented out because it moves the user to the top of the page
								// $( "#quickSearch" ).focus();
							}
						}
						else
						{
							bootbox.alert( data.msg );
						}
					}
				});
			}
			else if ( $form[0].id == 'vatForm' )
			{
				$.ajax({
					url: "/vat/addvat",
					type: "POST",
					data: $('#vatForm').serialize(),
					dataType: "json",
					success: function(data) {
						if ( data.success == 1 )
						{
							var mycallback;
							console.log( JSON.stringify( data ) );

							if ( data.reload == 1 )
							{
								mycallback = function() {
									$('#vatForm input[type="text"], #vatForm input[type="number"], #vatFormReload, #vatForm textarea').val('');

									$( "#vatSavedFeedback" ).fadeIn( 250, function() {
										setTimeout( function() {
											$( "#vatSavedFeedback" ).fadeOut( 1000 );
										}, 1000);
									});
								};
							}
							else
							{
								mycallback = function() {
									$('#vatModal').modal('hide');
								};
							}

							getVats(mycallback);
						}
						else
						{
							bootbox.alert( data.msg );
						}
					}
				});
			}
		},
		filter: function() {
			return jQuery(this).is(":visible");
		}
	});
});

function getParameter(ParameterID, callback)
{
	$.ajax({
		url: '/parameter/getsingleparameter/' + ParameterID,
		type: 'POST',
		data: { id : ParameterID },
		dataType: 'json',
		success: function(Parameter) {
			if (Parameter)
			{
				if (callback)
					callback(Parameter);
			}
			else
			{
				console.log('Failed to get Parameter: ' + ParameterID);
			}
		}
	});
}

function getRoomDetail(RoomParamID, CallBack)
{
	if (RoomParamID && CallBack)
	{
		// TODO - Would be nice to piggy-back
		// multiple RoomParamIDs for a single
		// ajax request.

		getParameter(RoomParamID, function(RoomParam) {
			var Room = RoomParam.Value1;
			var Temp = RoomParam.Value2;

			var Detail = {
				'id' : RoomParamID,
				'room' : '',
				'temperature' : ''
			};

			if (Room.length > 0)
			{
				Detail['room'] = Room;
				Detail['temperature'] = Temp;
			}

			CallBack(Detail);
		});
	}

	return;
}

 // #     #                                                            ######
 // #  #  #   ##   #####  ###### #    #  ####  #    #  ####  ######    #     # ######  ####  ###### # #####  #####
 // #  #  #  #  #  #    # #      #    # #    # #    # #      #         #     # #      #    # #      # #    #   #
 // #  #  # #    # #    # #####  ###### #    # #    #  ####  #####     ######  #####  #      #####  # #    #   #
 // #  #  # ###### #####  #      #    # #    # #    #      # #         #   #   #      #      #      # #####    #
 // #  #  # #    # #   #  #      #    # #    # #    # #    # #         #    #  #      #    # #      # #        #
 //  ## ##  #    # #    # ###### #    #  ####   ####   ####  ######    #     # ######  ####  ###### # #        #


var $wrForm;
var $wrPopover;

$(document).ready(function() {
	$wrPopover = $('#AddToWR').popover({
		html: true,
		content: getWRPopoverContent,
		placement: 'bottom',
		container: '#WRPopoverContainer'
	});
});

/**
 * Generates and returns the Warehouse Receipt popover form content.
 *
 * This function creates and configures a form that allows users to submit
 * data to save a warehouse receipt. It handles form detachment, AJAX submission,
 * and success/error handling. It also manages hiding the popover on dismissal
 * or after a successful submission.
 *
 * @returns {jQuery} The form element to be displayed in the popover.
 */
function getWRPopoverContent() {
	var $popoverTarget = $(this);
	var lotId = $popoverTarget.data('lotid');

	var $popoverButton = $('#AddToWR');

	// var $form = $('<form id="WRForm" class="form-inline editableform"></form>');
	if (!$wrForm) {
		$wrForm = $('#WRForm').detach().removeClass('hide');

		$wrForm.submit(function() {
			$.ajax({
				url: "/warehousereceipt/save",
				type: "POST",
				data: $wrForm.serialize(),
				dataType: "json",
				success: function(data) {
					if ( data.success == 1 ) {
						if ( data.ReceiptID != '' ) {
							$popoverButton.prev('a.wr-link').remove();
							$popoverButton.parent().prepend('<a href="/warehousereceipt/print/'+data.ReceiptID+'" target="_blank" class="wr-link">'+data.ReceiptNumber+'</a>');

							$wrPopover.popover('hide');
							// $popoverButton.prev('a.wr-link')[0].click();

							// window.location.href = '/warehousereceipt/print/'+data.ReceiptID;
							window.open('/warehousereceipt/print/'+data.ReceiptID, '_blank');
						} else {
							bootbox.alert( data.msg );
						}
					}
					else {
						bootbox.alert( data.msg );
					}
				}
			});

			return false;
		});

		$wrForm.find('.dismiss-popover').click(function() {
			$wrPopover.popover('hide');
		});
	}

	return $wrForm;
}
