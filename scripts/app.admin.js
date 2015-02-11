console.log('app.admin.js');

jQuery(document).ready(function($){
	Messenger.options = {
    extraClasses: 'messenger-fixed messenger-on-top messenger-on-right',
    theme: 'flat'
	}

	/**
	 * Activate Instagram account
	 */
	$(document).on('click', '.wordpresstoinstagram_activate_account', function(e){
		e.preventDefault();
		var el = $(this);
		var id = $(this).data('id');
		var username = $(this).data('username');
		var activateUrl = $(this).attr('href');
		var deactivateUrl = $(this).data('deactivateurl');

		msgActivateAccount = Messenger().post({
			message: "Activate account <strong>" + username + "</strong>?",
			type: 'info',
			actions: {
				deactivate: {
					label: "Activate",
					action: function(){
						$.ajax({
							url : activateUrl,
							type: 'GET',
							dataType: 'HTML',
							beforeSend: function(){
								Messenger().hideAll();
								Messenger().post({
									message: 'Processing ...',
									type: 'info',
									hideAfter: 50
								});
							},
							complete: function(){
								Messenger().hideAll();
							},
							success: function(res){
								if(res == 'ACCOUNT_ACTIVATED'){
									$('.wordpresstoinstagram_status_' + id).text('Active');
									
									el.text('Deactivate');
									el.attr('href', deactivateUrl);
									el.removeAttr('data-deactivateurl');
									el.attr('data-activateurl', activateUrl);

									el.addClass('wordpresstoinstagram_deactivate_account');
									el.removeClass('wordpresstoinstagram_activate_account');

									Messenger().hideAll();
								}else{
									Messenger().post({
										message: 'Error while activating account, please try again.',
										type: 'error',
										hideAfter: 5
									});
									return false;
								}
							}
						});
					}
				},
				cancel: {
					action: function(){
						msgActivateAccount.hide()
					}
				}
			}
		});
		return false;
	});

	/**
	 * Deactivate Instagram account
	 */
	$(document).on('click', '.wordpresstoinstagram_deactivate_account', function(e){
		e.preventDefault();
		var el = $(this);
		var id = $(this).data('id');
		var username = $(this).data('username');
		var deactivateUrl = $(this).attr('href');
		var activateUrl = $(this).data('activateurl');

		msgDeactivateAccount = Messenger().post({
			message: "Deactivate account <strong>" + username + "</strong>?",
			type: 'info',
			actions: {
				deactivate: {
					label: "Deactivate",
					action: function(){
						$.ajax({
							url : deactivateUrl,
							type: 'GET',
							dataType: 'HTML',
							beforeSend: function(){
								Messenger().hideAll();
								Messenger().post({
									message: 'Processing ...',
									type: 'info',
									hideAfter: 50
								});
							},
							complete: function(){
								Messenger().hideAll();
							},
							success: function(res){
								if(res == 'ACCOUNT_DEACTIVATED'){
									$('.wordpresstoinstagram_status_' + id).text('Inactive');

									el.text('Activate');
									el.attr('href', activateUrl);
									el.removeAttr('data-activateurl');
									el.attr('data-deactivateurl', deactivateUrl);

									el.addClass('wordpresstoinstagram_activate_account');
									el.removeClass('wordpresstoinstagram_deactivate_account');

									Messenger().hideAll();
								}else{
									Messenger().post({
										message: 'Error while deactivating account, please try again.',
										type: 'error',
										hideAfter: 5
									});
									return false;
								}
							}
						});
					}
				},
				cancel: {
					action: function(){
						msgDeactivateAccount.hide()
					}
				}
			}
		});
		return false;
	});

	/**
	 * Trash Instagram account
	 */
	$(document).on('click', '.wordpresstoinstagram_trash_account', function(e){
		e.preventDefault();
		var username = $(this).data('username');
		var trashUrl = $(this).attr('href');

		msgTrashAccount = Messenger().post({
			message: "Trash account <strong>" + username + "</strong>?",
			type: 'info',
			actions: {
				deactivate: {
					label: "Trash",
					action: function(){
						$.ajax({
							url : trashUrl,
							type: 'GET',
							dataType: 'HTML',
							beforeSend: function(){
								Messenger().hideAll();
								Messenger().post({
									message: 'Processing ...',
									type: 'info',
									hideAfter: 50
								});
							},
							complete: function(){
								Messenger().hideAll();
							},
							success: function(res){
								if(res == 'ACCOUNT_TRASHED'){
									var tr = $(e.target).closest("tr");
									$(tr).hide('slow', function(){$(tr).remove();});
									Messenger().hideAll();
								}else{
									Messenger().post({
										message: 'Error while deleting account, please try again.',
										type: 'error',
										hideAfter: 5
									});
									return false;
								}
							}
						});
					}
				},
				cancel: {
					action: function(){
						msgTrashAccount.hide()
					}
				}
			}
		});
		return false;
	});
});