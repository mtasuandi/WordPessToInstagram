<?php
if ( !class_exists( 'WP_List_Table' ) ) {
  require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class InstagramAccountsTable extends WP_List_Table {
	public function __construct() {
		global $status, $page;
		parent::__construct( array(
			'singular' => 'instagramaccount',
			'plural' => 'instagramaccounts',
			'ajax' => false
		));
  }

	private function get_related_datas() {
		global $wpdb;
		$tableInstagramAccounts = $wpdb->prefix . 'wpinstagram_accounts';

		if ( isset( $_POST['s'] ) ) {
			$searchString = sanitize_text_field( $_POST['s'] );
		} else {
			$searchString = '';
		}

		if ( empty( $searchString ) ) {
			$q = "SELECT * FROM $tableInstagramAccounts WHERE deleted_at IS NULL";
			$orderby 	= !empty( $_GET["orderby"] ) ? sanitize_text_field( $_GET["orderby"] ) : 'updated_at';
			$order 		= !empty( $_GET["order"] ) ? sanitize_text_field( $_GET["order"] ) : 'DESC';
			if ( !empty( $orderby ) && !empty( $order ) ) { $q.=' ORDER BY ' . $orderby . ' ' . $order; }
			$getDatas = $wpdb->get_results( $q );
		} else {
			$q = "SELECT * FROM $tableInstagramAccounts WHERE username LIKE '%$searchString%' AND deleted_at IS NULL ORDER BY updated_at DESC";
			$getDatas = $wpdb->get_results( $q );
		}

		if ( !empty( $getDatas ) ) {
			foreach ( $getDatas as $gData ) {
				$isActive = 'Inactive';
				if ( $gData->is_active == 1 ) {
					$isActive = 'Active';
				}
				$tableFields[] = array(
					'id' => $gData->id,
					'username' => $gData->username,
					'is_active_int' => $gData->is_active,
					'is_active' => '<a class="wordpresstoinstagram_status_' . $gData->id . '" title="' . $isActive . '">' . $isActive . '</a>',
					'created_at' => $this->relative_time( strtotime( $gData->created_at ) ),
					'updated_at' => $this->relative_time( strtotime( $gData->updated_at ) )
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
			'cb' => '<input type="checkbox" />',
			'username' => 'Username',
			'is_active' => 'Status',
			'updated_at' => 'Created At',
			'created_at' => 'Updated At',
		);
		return $columns;
	}

	public function column_username( $item ) {
		$actions = array(
			'edit' => sprintf( '<span class="dashicons dashicons-edit"></span> <a href="?page=%s&tab=%s&node=%s&account=%d">Edit</a>',
				WORDPRESSTOINSTAGRAM_SLUG,
				'accounts',
				'account',
				$item['id']
			),
			'trash' => sprintf( '<span class="dashicons dashicons-trash"></span><a href="%s" class="%s" data-username="%s">Trash</a>',
				wp_nonce_url( admin_url() . 'admin.php?page=' . WORDPRESSTOINSTAGRAM_SLUG . '&tab=accounts&node=trash&account=' . $item['id'], 'wordpresstoinstagram_trash_account', 'wordpresstoinstagram_trash_account_nonce' ),
				'wordpresstoinstagram_trash_account',
				$item['username']
			)
		);
		
		if ( $item['is_active_int'] == 1 ) {
			$actionStatus = array(
				'deactivate' => sprintf( '<span class="dashicons dashicons-lock"></span><a href="%s" class="%s" data-username="%s" data-id="%d" data-activateurl="%s">Deactivate</a>',
					wp_nonce_url( admin_url() . 'admin.php?page=' . WORDPRESSTOINSTAGRAM_SLUG . '&tab=accounts&node=deactivate&account=' . $item['id'], 'wordpresstoinstagram_deactivate_account', 'wordpresstoinstagram_deactivate_account_nonce' ),
					'wordpresstoinstagram_deactivate_account',
					$item['username'],
					$item['id'],
					wp_nonce_url( admin_url() . 'admin.php?page=' . WORDPRESSTOINSTAGRAM_SLUG . '&tab=accounts&node=activate&account=' . $item['id'], 'wordpresstoinstagram_activate_account', 'wordpresstoinstagram_activate_account_nonce' )
				)
			);
		} else {
			$actionStatus = array(
				'activate' => sprintf( '<span class="dashicons dashicons-lock"></span><a href="%s" class="%s" data-username="%s" data-id="%d" data-deactivateurl="%s">Activate</a>',
					wp_nonce_url( admin_url() . 'admin.php?page=' . WORDPRESSTOINSTAGRAM_SLUG . '&tab=accounts&node=activate&account=' . $item['id'], 'wordpresstoinstagram_activate_account', 'wordpresstoinstagram_activate_account_nonce' ),
					'wordpresstoinstagram_activate_account',
					$item['username'],
					$item['id'],
					wp_nonce_url( admin_url() . 'admin.php?page=' . WORDPRESSTOINSTAGRAM_SLUG . '&tab=accounts&node=deactivate&account=' . $item['id'], 'wordpresstoinstagram_deactivate_account', 'wordpresstoinstagram_deactivate_account_nonce' )
				)
			);
		}

		$mergeActions = array_merge( $actions, $actionStatus );
		return sprintf( '%1$s %2$s', $item['username'], $this->row_actions( $mergeActions ) );
	}

	public function get_bulk_actions(){
		$actions = array(
			'trash' => 'Trash'
		);
		return $actions;
	}

	public function column_cb($item){
    return sprintf(
     	'<input type="checkbox" name="%1$s[]" value="%2$s" />',
      $this->_args['singular'],
      $item['id']
    );
  }

  public function process_bulk_action() {
    if ( 'trash' === $this->current_action() ) {
      if ( !empty( $_POST['instagramaccount'] ) ) {
				foreach ( $_POST['instagramaccount'] as $idAccount ) {
					global $wpdb;
					$tableInstagramAccounts = $wpdb->prefix . 'wpinstagram_accounts';
					$wpdb->query( $wpdb->prepare( "UPDATE $tableInstagramAccounts SET deleted_at = NOW() WHERE id = %d", $idAccount ) );
				}
			}
    }
	}

	public function get_sortable_columns() {
		$sortable_columns = array(
			'username' => array( 'username', false ),
		);
		return $sortable_columns;
	}
	
	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'username':
			case 'is_active':
			case 'created_at':
			case 'updated_at':
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
		$this->process_bulk_action();
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