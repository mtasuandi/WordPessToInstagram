<style type="text/css">
	.wp-list-table .column-post_title {
		width: 25%;
	}
	.wp-list-table .column-username {
		width: 15%;
	}
	.wp-list-table .column-response_message {
		width: 45%;
	}
	.wp-list-table .column-logged_at {
		width: 15%;
		text-align: center;
	}
</style>
<div class="wrap">
	<?php require_once( WORDPRESSTOINSTAGRAM_PLUGIN_DIR . 'views/menus/tab.menus.php' ); ?>
	<div class="welcome-panel">
		<div class="welcome-panel-content">
			<h2><span class="dashicons dashicons-index-card"></span> Logs</h2>
			<p class="about-description">Everytime there's API call to Instagram, we save the response to the log table.</p>
			<div class="welcome-panel-column-container">
				<p>
					<form action="" method="POST">
						<?php
							$dash = new InstagramLogsTable();
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