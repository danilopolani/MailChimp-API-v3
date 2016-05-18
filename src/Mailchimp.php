<?php namespace Grork;
/**
 * Simplify the Mailchimp API v3.0
 *
 * @author     Danilo Polani ( @Grork )
 * @license    GNU General Public License 3.0
 * @version    1.0
 */
 
class Mailchimp {

	private $api_key;
	private $url = 'https://<dc>.api.mailchimp.com/3.0';
	private $dc;
	
	private $tmp_res;
	private $last_chain;
	
	private $last_settings = [];
	
	private $fields = [];
	private $email = '';
	private $listId = '';
	private $campaignId = '';


	/**
	 * Constructor
	 *
	 * @param string $hook_name
	 */
	public function __construct( $api_key ) {
		
		$api_key = trim( $api_key );
		if ( empty( $api_key ) ) throw new \Exception( 'Mailchimp API Key cannot be empty.' );
		
		$this->api_key = $api_key;
		$this->dc = str_replace( '-', '', strstr( $this->api_key, '-' ) );
		
		$this->url = str_replace( '<dc>', $this->dc, $this->url );
		$this->tmp_res = (object) [ 'response' => '' ];
		
		$try_api = $this->_get( '/' );

		if ( $try_api->status == 401 ) throw new \Exception( 'Invalid Mailchimp API Key: ' . $try_api->detail );
		
	}


	/**
	 * Set list
	 *
	 * @param string $listId
	 *
	 * @return $this
	 */
	public function setList( $listId ) {
		
		if ( empty( $listId ) ) {
			
			$this->tmp_res = (object) [ 'status' => 'error', 'code' => '', 'details' => 'List ID not valid.' ];
			return $this;
				
		}
		
		$this->listId = $listId;
		$this->last_chain = 'list';
		
		return $this;
			
	}
	

	/**
	 * Set campaign
	 *
	 * @param string $listId
	 *
	 * @return $this
	 */
	public function setCampaign( $campaignId ) {
		
		if ( empty( $campaignId ) ) {
			
			$this->tmp_res = (object) [ 'status' => 'error', 'code' => '', 'details' => 'Campaign ID not valid.' ];
			return $this;
				
		}
		
		$this->campaignId = $campaignId;
		$this->last_chain = 'campaign';
		
		return $this;
			
	}


	/**
	 * Get current list
	 *
	 * @return $this
	 */
	public function getList() {
		
		return $this->listId;
			
	}
	

	/**
	 * Get current campaign
	 *
	 * @return $this
	 */
	public function getCampaign() {
		
		return $this->campaignId;
			
	}
	

	/**
	 * Get current campaign
	 *
	 * @return $this
	 */
	public function fetch() {
		
		return $this->tmp_res;
			
	}
	

	/**
	 * Delete current list / campaign.
	 * You can use only after setList() or setCampaign()
	 *
	 * @return $this
	 */
	public function delete() {
		
		if ( $this->last_chain == 'list' )
			$this->deleteList();
		else if ( $this->last_chain == 'campaign' )
			$this->deleteCampaign();
		else
			$this->tmp_res = (object) [ 'status' => 'error', 'code' => '', 'details' => 'Not chaining methods.' ];
		
		$this->last_chain = '';
		
		return $this;
			
	}
	

	/**
	 * Get lists
	 *
	 * @param integer $count (opt.) - Number of records to return
	 * @param array $settings (opt.) - Other settings of the query. Reference: http://developer.mailchimp.com/documentation/mailchimp/reference/lists/#read-get_lists
	 *
	 * @return $this
	 */
	public function lists( $count = -1, $settings = [] ) {
		
		$url = '/lists?count=' . $count;
		$params = '';
		
		foreach ( $settings as $setting => $value ) $params .= '&' . $setting . '=' . urlencode( $value );
		
		$res = $this->_get( $url . $params );
		$errors = $this->_errors( $res );
		if ( $errors ) return $errors;
		
		$this->tmp_res = (object) [ 'response' => 'success', 'text' => 'See details.', 'details' => $res ];
		
		return $this;
		
	}
	
	
	/**
	 * Create a list
	 * For fields required ( contact and campaign_defaults ) values check here: http://developer.mailchimp.com/documentation/mailchimp/reference/lists/#create-post_lists
	 *
	 * @param string $name
	 * @param array $contact
	 * @param array $campaign_defaults
	 * @param string $permission_reminder (opt.) 
	 * @param boolean $email_type_option (opt.) - Permit to user to choose if see HTML email or plain text email. Default: false
	 * @param array $other_fields (opt.) - Other fields you want to inject. See the documentation from the link above.
	 *
	 * @return $this
	 */
	public function createList( $name, $contact, $campaign_defaults, $permission_reminder = 'You are receiving this email for your subscription to the newsletter on the website', $email_type_option = false, $other_fields = [] ) {
		
		$name = trim( $name );
		if ( empty( $name ) ) {
			
			$this->tmp_res = (object) [ 'response' => 'error', 'code' => 'empty_name', 'details' => 'List name cannot be empty.' ];
			return $this;
			
		}
		if ( count( $contact ) == 0 ) {
			
			$this->tmp_res = (object) [ 'response' => 'error', 'code' => 'empty_contact', 'details' => 'contact cannot be empty.' ];
			return $this;
			
		}
		if ( count( $campaign_defaults ) == 0 ) {
			
			$this->tmp_res = (object) [ 'response' => 'error', 'code' => 'empty_campaign_defaults', 'details' => 'campaign_defaults cannot be empty.' ];
			return $this;
			
		}
		
		$res = $this->_post( '/lists', [
			'name' => $name,
			'email_type_option' => $email_type_option,
			'contact' => $contact,
			'campaign_defaults' => $campaign_defaults,
			'permission_reminder' => $permission_reminder
		] + $other_fields );
		
		$errors = $this->_errors( $res );
		if ( $errors ) {
			
			$this->tmp_res = $errors;
			return $this;
			
		}
		
		if ( !array_key_exists( 'id', $res ) ) {
			
			$this->tmp_res = (object) [ 'response' => 'error', 'code' => 'unknown', 'details' => 'Unknown error. See "more" and report it to the administrator.', 'more' => $res ];
			return $this;
			
	  } else {
			
			$this->last_settings = $campaign_defaults;
			$this->listId = $res->id;
			$this->tmp_res = (object) [ 'response' => 'success', 'id' => $res->id, 'text' => 'See details.', 'details' => $res ];
			
			return $this;
			
		}
		
	}
	
	
	/**
	 * Add members to a new list
	 *
	 * @param array $email ( see example )
	 * @param string $status (opt.) - subscribed (default, add to list) | pending ( need email verification ) | unsubscribed ( or cleaned, set as inactive e no email will be sent to it )
	 *
	 * @example with MERGE FIELDS : $email = array( 'email@example.com' => [ 'FNAME' => 'John', 'LNAME' => 'Doe' ] );
	 * @example just emails :       $email = array( 'email@example.com', 'email2@example.com' );
	 * @example with and without  : $email = array( 'email@example.com', 'email2@example.com' => [ 'FNAME' => 'John' ] );
	 *
	 */
	public function withMembers( $email, $status = 'subscribed' ) {
		
		if ( $this->tmp_res->response == 'error' ) return $this;
		
		$listId = $this->listId;
		if ( !is_array( $email ) ) $email = [ $email ];
		
		$processed = [];
		foreach ( $email as $addr => $info ) {
			
			if ( is_int( $addr ) ) { // Without merge fields
				$res = $this->subscribe( $info, $status );
			} else {
				$res = $this->subscribe( $addr, $status, $info );
			}

			$processed[] = ( is_int( $addr ) ? $info : $addr );
			
		}
		
		// Reset tmp
		$this->tmp_emails = [];
		$this->tmp_status = '';
		
		$res = $this->tmp_res;
		$res->members = $processed;
		
		$this->tmp_res = $res;
		
		return $this;
		
	}	
	
	
	/**
	 * Delete list
	 *
	 * @param string $list (opt.) - If empty, it will be the last used or set by setList() 
	 *
	 * @return $this
	 */
	public function deleteList( $listId = 0 ) {
	
		if ( $this->tmp_res->response == 'error' ) return $this;
		
		$listId = $this->_listId( $listId );
		
		$res = $this->_delete( '/lists/' . $listId );
		$errors = $this->_errors( $res );
		if ( $errors ) {
			
			$this->tmp_res = $errors;
			return $this;
			
		}
		
		$this->tmp_res = (object) [ 'response' => 'success', 'text' => 'See details.', 'details' => $res ];
		
		return $this;
		
	}



	/**
	 * Check if email is subscribed to a list
	 *
	 * @param string $email
	 * @param string $listId (opt.) - If empty, it will be the last used or set by setList() 
	 *
	 * @return boolean
	 */
	public function inList( $email = '', $listId = 0 ) {
		
		$listId = $this->_listId( $listId );
		$email = $this->_email( $email );
		
		if ( !filter_var( $email, FILTER_VALIDATE_EMAIL ) ) return (object) [ 'response' => 'error', 'code' => 'invalid_email', 'details' => 'Email address not valid.' ];
		
		$hash = md5(strtolower( $email ));
		
		$res = $this->_get( '/lists/' . $listId . '/members/' . $hash );
		
		$errors = $this->_errors( $res, true );
		if ( $errors ) return $errors;
		
		$this->listId = $listId; // Set as last list used
		$this->email = $email; // Set as last email used
		
		if ( $res->status === 404 ) return false;
		return true;		
			
	}
	
	
	/**
	 * Add fields for subscribers. You can use this instead of $fields param in subscribe() method.
	 *
	 * @param array $fields - Assocative array
	 
	 * @return $this
	 */
	public function fields( $fields ) {
		
		$this->fields = $fields;
		return $this;
		
	}


	/**
	 * Subscribe email to a list
	 *
	 * @param string $email (opt.) - If empty, it will be the last used
	 * @param string $status (opt.) - subscribed (default, add to list) | pending ( need email verification ) | unsubscribed ( or cleaned, set as inactive e no email will be sent to it )
	 * @param string $fields (opt.) - Additional fields of mailchimp, for example FNAME and LNAME. Must be an associative array. 
	 * @param string $listId (opt.) - If empty, it will be the last used or set by setList() 
	 *
	 * @return object
	 */
	public function subscribe( $email = '', $status = 'subscribed', array $fields = [], $listId = 0 ) {
		
		$listId = $this->_listId( $listId );
		$email = $this->_email( $email );
		$status = trim( $status );
		$fields = $fields + $this->fields;
		
		if ( !filter_var( $email, FILTER_VALIDATE_EMAIL ) ) {
			
			$this->tmp_res = (object) [ 'response' => 'error', 'code' => 'invalid_email', 'details' => 'Email address not valid.' ];
			return $this;
			
		}
		
		$status = ( !in_array( $status, [ 'subscribed', 'pending', 'unsubscribed', 'cleaned' ]) ? 'subscribed' : $status );
		
		if ( $this->inList( $email, $listId ) ) {
			
			$this->tmp_res = (object) [ 'response' => 'error', 'code' => 'already_in_list', 'details' => 'The email is already in the list.' ];
			return $this;
			
		}
		
		$res = $this->_post( '/lists/' . $listId . '/members/', [
			'email_address' => $email,
			'status' => $status,
			'merge_fields' => ( count( $fields ) == 0 ? (object)[] : $fields )
		]);
		
		$errors = $this->_errors( $res );
		if ( $errors ) return $errors;
		
		$this->listId = $listId; // Set as last list used
		$this->email = $email; // Set as last email used
		
		if ( !array_key_exists( 'id', $res ) )
			$this->tmp_res = (object) [ 'response' => 'error', 'code' => 'unknown', 'details' => 'Unknown error. See "more" and report it to the administrator.', 'more' => $res ];
		else
			$this->tmp_res = (object) [ 'response' => 'success', 'id' => $res->id, 'text' => 'See details.', 'details' => $res ];
		
		return $this;
			
	}


	/**
	 * Unsubscribe email to a list
	 *
	 * @param string $email (opt.) - If empty, it will be the last used
	 * @param string $listId (opt.) - If empty, it will be the last used or set by setList() 
	 *
	 * @return $this
	 */
	public function unsubscribe( $email = '', $listId = 0 ) {
		
		$listId = $this->_listId( $listId );
		$email = $this->_email( $email );
		
		if ( !filter_var( $email, FILTER_VALIDATE_EMAIL ) ) {
			
			$this->tmp_res = (object) [ 'response' => 'error', 'code' => 'invalid_email', 'details' => 'Email address not valid.' ];
			return $this;
			
		}
		if ( !$this->inList( $email, $listId ) ) {
			
			$this->tmp_res = (object) [ 'response' => 'error', 'code' => 'not_in_list', 'details' => 'The email is not in the list.' ];
			return $this;
			
		}
		
		$res = $this->_delete( '/lists/' . $listId . '/members/' . md5(strtolower( $email )) );
		
		$errors = $this->_errors( $res );
		if ( $errors ) return $errors;
		
		$this->listId = $listId; // Set as last list used
		$this->email = $email; // Set as last email used
				
		$this->tmp_res = (object) [ 'response' => 'success', 'id' => $res->id, 'text' => 'See details.', 'details' => $res ];
		
		return $this;
		
	}
	

	/**
	 * Get campaigns
	 *
	 * @param integer $count (opt.) - Number of records to return
	 * @param array $settings (opt.) - Other settings of the query. Reference: http://developer.mailchimp.com/documentation/mailchimp/reference/campaigns/#read-get_campaigns
	 *
	 * @return $this
	 */
	public function campaigns( $count = -1, $settings = [] ) {
		
		$url = '/campaigns?count=' . $count;
		$params = '';
		
		foreach ( $settings as $setting => $value ) $params .= '&' . $setting . '=' . urlencode( $value );
		
		$res = $this->_get( $url . $params );
		$errors = $this->_errors( $res );
		if ( $errors ) return $errors;
		
		$this->tmp_res = (object) [ 'response' => 'success', 'text' => 'See details.', 'details' => $res ];
		
		return $this;
		
	}


	/**
	 * Create a campaign
	 *
	 * @param array $settings (opt.) - Settings. If empty, those of the last list created will be used. You can also overwrite values of the last used. For the fields required: http://developer.mailchimp.com/documentation/mailchimp/reference/campaigns/#create-post_campaigns
	 * @param string $listId (opt.) - If empty, it will be the last used or set by setList() 
	 * @param string $type (opt.) - Type of the campaign. regular | plaintext | absplit | rss | variate. Default: regular
	 *
	 * @return $this
	 */
	public function createCampaign( $settings = [], $listId = 0, $type = 'regular', $other_fields = [] ) {
		
		$listId = $this->_listId( $listId );
		$type = trim( $type );
		
		$settings_tmp = $settings;
		
		if ( count( $this->last_settings ) > 0 ) $settings = $this->last_settings;
		$settings['subject_line'] = @$settings['subject'];
		// Check to overwrite last used with custom new
		$settings = array_merge( $settings, $settings_tmp );
		
		if ( count( $settings ) == 0 ) $this->tmp_res = (object) [ 'response' => 'error', 'code' => 'settings_error', 'details' => 'Empty settings.' ];
				
		// Check settings
		if ( !array_key_exists( 'subject_line', $settings ) )
			$this->tmp_res = (object) [ 'response' => 'error', 'code' => 'settings_error', 'details' => 'subject_line field not provided in settings.' ];
		if ( !array_key_exists( 'from_name', $settings ) )
			$this->tmp_res =  (object) [ 'response' => 'error', 'code' => 'settings_error', 'details' => 'from_name field not provided in settings.' ];
		if ( !array_key_exists( 'reply_to', $settings ) )
			$this->tmp_res = (object) [ 'response' => 'error', 'code' => 'settings_error', 'details' => 'reply_to field not provided in settings.' ];
		if ( array_key_exists( 'from_name', $settings ) && filter_var( $settings['from_name'], FILTER_VALIDATE_EMAIL ) ) 
			return (object) [ 'response' => 'error', 'code' => 'settings_error', 'details' => 'from_name in settings must be a string, not an email.' ];
		if ( array_key_exists( 'reply_to', $settings ) && !filter_var( $settings['reply_to'], FILTER_VALIDATE_EMAIL ) ) 
			$this->tmp_res = (object) [ 'response' => 'error', 'code' => 'settings_error', 'details' => 'reply_to in settings must be an email address.' ];

		// Check for errors
		if ( array_key_exists( 'response', $this->tmp_res ) && $this->tmp_res->response == 'error' ) return $this;
		
		$type = ( !in_array( $type, [ 'regular', 'plaintext', 'absplit', 'rss', 'variate' ]) ? 'regular' : $type );
		
		$res = $this->_post( '/campaigns', [
			'type' => $type,
			'recipients' => [
				'list_id' => $listId
			],
			'settings' => $settings
		] + $other_fields );
		
		$errors = $this->_errors( $res );
		if ( $errors ) {
			
			$this->tmp_res = $errors;
			return $this;
			
		}
		
		$this->listId = $listId; // Set as last list used
		$this->campaignId = $res->id;
		
		if ( !array_key_exists( 'id', $res ) )
			$tmp_res = (object) [ 'response' => 'error', 'code' => 'unknown', 'details' => 'Unknown error. See "more" and report it to the administrator.', 'more' => $res ];
		else
			$tmp_res = (object) [ 'response' => 'success', 'id' => $res->id, 'text' => 'See details.', 'details' => $res ];
		
		$this->tmp_res = $tmp_res;
		return $this;
		
	}
	
	
	/**
	 * Set content of a campaign
	 *
	 * @param string $content - Can be HTML or plain text.
	 * @param boolean $is_html (opt.) - True if $content is HTML and campaign is regular. Default: true
	 * @param boolean $plain_text (opt.) - Needed but optional only if campaign is regular and there is HTML text. If empty, plain text will be automatically generated.
	 *
	 * @return $this
	 */
	public function withContent( $content, $is_html = true, $plain_text = '', $other_fields = [] ) {
		
		if ( $this->tmp_res->response == 'error' ) return $this;
		
		$campaignId = $this->campaignId;
		
		$res = $this->_put( '/campaigns/' . $campaignId . '/content', [
			'plain_text' => ( $is_html ? $plain_text : $content ),
			'html' => ( $is_html ? $content : '' )
		] + $other_fields );
		
		$errors = $this->_errors( $res );
		if ( $errors ) {
			
			$this->tmp_res = $errors;
			return $this;
			
		}
		
		$this->campaignId = $campaignId;
		
		if ( !array_key_exists( 'plain_text', $res ) )
			$tmp_res = (object) [ 'response' => 'error', 'code' => 'unknown', 'details' => 'Unknown error. See "more" and report it to the administrator.', 'more' => $res ];
		else
			$tmp_res = (object) [ 'response' => 'success', 'text' => 'See details.', 'details' => $res ];
		
		$this->tmp_res = $tmp_res;
		return $this;
		
	}
	
	
	/**
	 * Send campaign
	 *
	 * @param string $campaignId (opt.) - If empty, it will be the last used or set by setCampaign() 
	 *
	 * @return $this
	 */
	public function send( $campaignId = 0 ) {
	
		if ( $this->tmp_res->response == 'error' ) return $this;
		
		$campaignId = $this->_campaignId( $campaignId );
		
		$res = $this->_post( '/campaigns/' . $campaignId . '/actions/send' );
		$errors = $this->_errors( $res );
		if ( $errors ) {
			
			$this->tmp_res = $errors;
			return $this;
			
		}
		
		$this->tmp_res = (object) [ 'response' => 'success', 'text' => 'See details.', 'details' => $res ];
		
		return $this;
		
	}
	
	
	/**
	 * Delete campaign
	 *
	 * @param string $campaignId (opt.) - If empty, it will be the last used or set by setCampaign() 
	 *
	 * @return $this
	 */
	public function deleteCampaign( $campaignId = 0 ) {
	
		if ( $this->tmp_res->response == 'error' ) return $this;
		
		$campaignId = $this->_campaignId( $campaignId );
		
		$res = $this->_delete( '/campaigns/' . $campaignId );

		$errors = $this->_errors( $res );
		if ( $errors ) {
			
			$this->tmp_res = $errors;
			return $this;
			
		}
		
		$this->tmp_res = (object) [ 'response' => 'success', 'text' => 'See details.', 'details' => $res ];
		
		return $this;
		
	}
	
	
	// -- PRIVATE METHODS
	
	
	/**
	 * Check current list ID
	 *
	 * @param string $listId
	 *
	 * @return string
	 */
	private function _listId( $listId ) {
		
		if ( ( empty( $listId ) || !$listId || is_null( $listId ) ) && empty( $this->listId ) ) throw new \Exception( 'No List ID provided.' );
		
		return ( empty( $listId ) ? $this->listId : $listId );
			
	}
	
	
	/**
	 * Check current campaign ID
	 *
	 * @param string $campaignId
	 *
	 * @return string
	 */
	private function _campaignId( $campaignId ) {
		
		if ( ( empty( $campaignId ) || !$campaignId || is_null( $campaignId ) ) && empty( $this->campaignId ) ) throw new \Exception( 'No Campaign ID provided.' );
		
		return ( empty( $campaignId ) ? $this->campaignId : $campaignId );
			
	}
	
	
	/**
	 * Check current email
	 *
	 * @param string $email
	 *
	 * @return string
	 */
	private function _email( $email ) {
		
		if ( ( empty( $email ) || !$email || is_null( $email ) ) && empty( $this->email ) ) throw new \Exception( 'No email provided.' );
		
		return ( empty( $email ) ? $this->email : $email );
			
	}
	
	
	/**
	 * Fetch errors
	 *
	 * @param object $result
	 */
	private function _errors( $result, $ignore_404 = false ) {
		
		$errors = [ 400, 401, 403, 405, 406, 422 ];
		if ( !$ignore_404 ) $errors[] = 404;
		
		if ( !in_array( @$result->status, $errors ) ) return false;
		
		$text = $result->detail;
		
		$output = [ 
			'response' => 'error',
			'code' => $result->status,
			'details' => $text,
			'complete' => $result
		];
		
		return (object) $output; 
		
	}
	
	
	/**
	 * Send a GET requst using cURL
	 *
	 * @param string $url - Request URL
	 * @param array $params - Values to send
	 *
	 * @return string
	 */
	public function _get( $url, array $params = [] ) {
		
		return $this->_call( $url, $params, 'get' );
			
	}


	/**
	 * Send a POST requst using cURL
	 *
	 * @param string $url - Request URL
	 * @param array $params - Values to send
	 *
	 * @return string
	 */
	public function _post( $url, array $params = [] ) {
		
		return $this->_call( $url, $params, 'post' );
		
	}


	/**
	 * Send a PATCH requst using cURL
	 *
	 * @param string $url - Request URL
	 * @param array $params - Values to send
	 *
	 * @return string
	 */
	private function _patch( $url, array $params = [] ) {
		
		return $this->_call( $url, $params, 'patch' );
		
	}


	/**
	 * Send a PUT requst using cURL
	 *
	 * @param string $url - Request URL
	 * @param array $post - Values to send
	 *
	 * @return string
	 */
	private function _put( $url, array $params = [] ) {
		
		return $this->_call( $url, $params, 'put' );
		
	}


	/**
	 * Send a DELETE requst using cURL
	 *
	 * @param string $url - Request URL
	 * @param array $post - Values to send
	 *
	 * @return string
	 */
	private function _delete( $url, array $params = [] ) {
		
		return $this->_call( $url, $params, 'delete' );
		
	}

	
	/**
	 * Perform a cURL call
	 *
	 * @param string $url - Request URL
	 * @param array $params - Parameters
	 * @param array $options - Options for cURL
	 *
	 * @return string
	 */
	private function _call( $url, array $params = [], $method = 'GET' ) {
		
		$method = strtoupper( $method );
		
		// Headers
		$headers = [
			'Content-type: application/json',
			'Authorization: OAuth ' . $this->api_key
		];
		
		$url = $this->url . $url;
		
		// Options
		$options = [
			CURLOPT_URL => $url,
			CURLOPT_HEADER => 0,
			CURLOPT_RETURNTRANSFER => TRUE,
			CURLOPT_TIMEOUT => 4
		];
		
		// Other methods
		if ( $method != 'POST' && $method != 'GET' ) {
			
			$headers[] = 'X-HTTP-Method-Override: ' . $method;
			
		}
		
		if ( $method == 'GET' && count( $params ) > 0 ) // set URL for GET method
			$options[ CURLOPT_URL ] = $url . ( strpos( $url, '?' ) === FALSE ? '?' : '' ). http_build_query( $params );
		else if ( $method != 'GET' ) // Not GET
			$options = $options + [
				CURLOPT_POST => 1,
				CURLOPT_FRESH_CONNECT => 1,
				CURLOPT_FORBID_REUSE => 1,
				CURLOPT_POSTFIELDS => json_encode( $params )
			];
		
		// Set HTTP Headers 
		$options[ CURLOPT_HTTPHEADER ] = $headers;
		
		// Rocks
		try {

			$ch = curl_init();
			if ( !$ch ) throw new \Exception( curl_error($ch), curl_errno($ch) );

			foreach ( $options as $option => $value ) curl_setopt( $ch, $option, $value ); // Better than setopt array
			$result = curl_exec($ch);
			
			if ( !$result ) throw new \Exception( curl_error($ch), curl_errno($ch) );

			curl_close( $ch );
			$result = json_decode( $result );

		} catch ( Exception $e ) {

			trigger_error( sprintf(
				'Curl failed with error #%d: %s',
				$e->getCode(), $e->getMessage()),
				E_USER_ERROR );

		}

		return $result;
		
	}
	
}
