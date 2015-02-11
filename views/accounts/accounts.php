<style type="text/css">
	.wp-list-table .column-username {
		width: 55%;
	}
	.wp-list-table .column-is_active {
		width: 15%;
		text-align: center;
	}
	.wp-list-table .column-created_at {
		width: 15%;
		text-align: center;
	}
	.wp-list-table .column-updated_at {
		width: 15%;
		text-align: center;
	}
	.row-actions {
		visibility : visible !important;
	}
</style>
<div class="wrap">
	<?php require_once( WORDPRESSTOINSTAGRAM_PLUGIN_DIR . 'views/menus/tab.menus.php' ); ?>
	<div class="welcome-panel">
		<div class="welcome-panel-content">
			<h2><span class="dashicons dashicons-groups"></span> Instagram Accounts <a href="?page=<?php echo WORDPRESSTOINSTAGRAM_SLUG; ?>&tab=accounts&node=account" class="add-new-h2">Add New</a></h2>
			<p class="about-description">Created instagram accounts, you can choose to activate or deactivate the account here.</p>
			<div class="welcome-panel-column-container">
				<p>
					<form action="" method="POST">
						<?php
							$dash = new InstagramAccountsTable();
							$dash->prepare_items();
							$dash->search_box( 'search', 'search_id' );
							$dash->display();
						?>
					</form>
				</p>
			</div>
		</div>
	</div>
</div>