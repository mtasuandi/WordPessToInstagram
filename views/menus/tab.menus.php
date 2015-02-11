<?php
	$tab = isset($_GET['tab']) ? $_GET['tab'] : 'dashboard';
?>
<h2 class="nav-tab-wrapper" style="padding-left:0px !important;">
	<a class="nav-tab <?php if($tab == 'dashboard') { echo 'nav-tab-active'; }?>" href="?page=<?php echo WORDPRESSTOINSTAGRAM_SLUG; ?>&tab=dashboard"><span class="dashicons dashicons-dashboard"></span> Dashboard</a>
	<a class="nav-tab <?php if($tab == 'accounts') { echo 'nav-tab-active'; }?>" href="?page=<?php echo WORDPRESSTOINSTAGRAM_SLUG; ?>&tab=accounts"><span class="dashicons dashicons-groups"></span> Accounts</a>
</h2>