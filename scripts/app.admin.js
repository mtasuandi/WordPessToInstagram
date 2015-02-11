console.log('app.admin.js');

jQuery(document).ready(function($){
	Messenger.options = {
    extraClasses: 'messenger-fixed messenger-on-top messenger-on-right',
    theme: 'flat'
	}

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