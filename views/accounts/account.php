<?php
	global $wpdb;
	$tableInstagramAccounts = $wpdb->prefix . 'wpinstagram_accounts';
	$idAccount = '';
	$username = '';
	$password = '';
	$buttonText = 'Add Account';
	$suggestionText = '';

	if ( isset( $_GET['account'] ) ) {
		$idAccount = sanitize_text_field( $_GET['account'] );
		$instagram = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $tableInstagramAccounts WHERE id = %d", $idAccount ) );
		if ( !empty( $instagram ) ) {
			$username = $instagram->username;
			$password = $instagram->password;
			$buttonText = 'Update Account';
			$suggestionText = '<a href="?page=' . WORDPRESSTOINSTAGRAM_SLUG . '&tab=accounts&node=account" class="add-new-h2">Add New</a>';
		}
	}
?>
<div class="wrap">
	<div class="welcome-panel">
		<div class="welcome-panel-content">
			<h2>
				<span class="dashicons dashicons-plus-alt"></span> Add Account 
				<a href="?page=<?php echo WORDPRESSTOINSTAGRAM_SLUG; ?>&tab=accounts" class="add-new-h2">Back</a>
				<?php echo $suggestionText; ?>
			</h2>
			<p class="about-description">Add new Instagram account.</p>
			<div class="welcome-panel-column-container">
				<p>
					<form action="" method="POST">
						<table style="border:1px groove #eeeeee;width: 100%;padding: 5px;margin-bottom: 5px;">
							<tr>
								<td style="width:250px;">Username</td>
								<td>
									<input type="text" name="instagram_username" style="width:35%" value="<?php echo $username; ?>"/>
								</td>
							</tr>
							<tr>
								<td >Password</td>
								<td>
									<input type="text" name="instagram_password" style="width:35%" value="<?php echo $password; ?>"/>
								</td>
							</tr>
						</table>
						<p/>
						<?php wp_nonce_field( 'wordpresstoinstagram_account_form', 'wordpresstoinstagram_account_form_nonce' ); ?>
						<input type="hidden" name="wordpresstoinstagram_account_hidden_id" value="<?php echo $idAccount; ?>"/>
						<input type="submit" name="submit_wordpresstoinstagram_account" class="button-primary" value="<?php echo $buttonText; ?>"/>
					</form>
				</p>
			</div>
		</div>
	</div>
</div>