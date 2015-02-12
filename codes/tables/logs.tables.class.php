<?php
if ( !class_exists( 'WP_List_Table' ) ) {
  require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class InstagramLogsTable extends WP_List_Table {
	public function __construct() {
		global $status, $page;
		parent::__construct( array(
			'singular' => 'instagramlog',
			'plural' => 'instagramlogs',
			'ajax' => false
		));
  }

	private function get_related_datas() {
		global $wpdb;
		$tableInstagramAccounts = $wpdb->prefix . 'wpinstagram_accounts';
		$tableInstagramLogs = $wpdb->prefix . 'wpinstagram_logs';
		$tablePosts = $wpdb->prefix . 'posts';

		if ( isset( $_POST['s'] ) ) {
			$searchString = sanitize_text_field( $_POST['s'] );
		} else {
			$searchString = '';
		}

		if ( empty( $searchString ) ) {
			$q = "SELECT iglogs.id, wppost.post_title, igaccounts.username, iglogs.response_message, iglogs.logged_at
				FROM $tableInstagramLogs AS iglogs
				LEFT JOIN $tablePosts AS wppost ON
				iglogs.id_post = wppost.ID
				LEFT JOIN $tableInstagramAccounts AS igaccounts ON
				iglogs.id_account = igaccounts.id";
			$orderby 	= !empty( $_GET["orderby"] ) ? sanitize_text_field( $_GET["orderby"] ) : 'logged_at';
			$order 		= !empty( $_GET["order"] ) ? sanitize_text_field( $_GET["order"] ) : 'DESC';
			if ( !empty( $orderby ) && !empty( $order ) ) { $q.=' ORDER BY ' . $orderby . ' ' . $order; }
			$getDatas = $wpdb->get_results( $q );
		} else {
			$q = "SELECT iglogs.id, wppost.post_title, igaccounts.username, iglogs.response_message, iglogs.logged_at
				FROM $tableInstagramLogs AS iglogs
				LEFT JOIN $tablePosts AS wppost ON
				iglogs.id_post = wppost.ID
				LEFT JOIN $tableInstagramAccounts AS igaccounts ON
				iglogs.id_account = igaccounts.id
				WHERE wppost.post_title LIKE '%$searchString%' OR igaccounts.username LIKE '%$searchString%' OR iglogs.response_message LIKE '%$searchString%'
				ORDER BY iglogs.logged_at DESC";
			$getDatas = $wpdb->get_results( $q );
		}

		if ( !empty( $getDatas ) ) {
			foreach ( $getDatas as $gData ) {
				$tableFields[] = array(
					'id' => $gData->id,
					'post_title' => $gData->post_title,
					'username' => $gData->username,
					'response_message' => $gData->response_message,
					'logged_at' => $this->relative_time( strtotime( $gData->logged_at ) )
				);
			}
		} else {
			$tableFields = array();
		}

		return $tableFields;
	}
	
	private function relative_time( $ptime ) {
		$etime = time() - $ptime;

		if ( $etime < 1 ) {
			return 'just now';
		}

		$a = array( 12 * 30 * 24 * 60 * 60 => 'year',
		30 * 24 * 60 * 60 => 'month',
		24 * 60 * 60 => 'day',
		60 * 60 => 'hour',
		60 => 'minute',
		1 => 'second'
		);

		foreach ( $a as $secs => $str ) {
			$d = $etime / $secs;
			if ( $d >= 1 ) {
			$r = round( $d );
			return $r . ' ' . $str . ( $r > 1 ? 's' : '' ) . ' ago';
			}
		}
	}
	
	public function get_columns() {
		$columns = array(
			'post_title' => 'Post Title',
			'username' => 'Username',
			'response_message' => 'Response',
			'logged_at' => 'Logged At',
		);
		return $columns;
	}

	public function get_sortable_columns() {
		$sortable_columns = array(
			'post_title' => array( 'post_title', false ),
			'username' => array( 'username', false ),
			'response_message' => array( 'response_message', false )
		);
		return $sortable_columns;
	}
	
	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'post_title':
			case 'username':
			case 'response_message':
			case 'logged_at':
				return $item[$column_name];
			default:
				return print_r( $item, true );
		}
	}

	public function prepare_items() {
		$columns = $this->get_columns();
		$hidden = array();
		$sortable = $this->get_sortable_columns();
		$this->_column_headers = array( $columns, $hidden, $sortable );
		$per_page = 10;
		$current_page = $this->get_pagenum();
		$total_items = count( $this->get_related_datas() );
		$this->found_data = array_slice( $this->get_related_datas(), ( ( $current_page - 1 ) * $per_page ), $per_page );
		$this->set_pagination_args( array(
			'total_items' => $total_items, 
			'per_page' => $per_page
		) );
		$this->items = $this->found_data;
	}
}