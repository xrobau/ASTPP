<?php

// ##############################################################################
// ASTPP - Open Source VoIP Billing Solution
//
// Copyright (C) 2016 iNextrix Technologies Pvt. Ltd.
// Samir Doshi <samir.doshi@inextrix.com>
// ASTPP Version 3.0 and above
// License https://www.gnu.org/licenses/agpl-3.0.html
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU Affero General Public License as
// published by the Free Software Foundation, either version 3 of the
// License, or (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU Affero General Public License for more details.
//
// You should have received a copy of the GNU Affero General Public License
// along with this program. If not, see <http://www.gnu.org/licenses/>.
// #############################################################################
class Freeswitch_model extends CI_Model {
	function Freeswitch_model() {
		parent::__construct ();
	}
	
	/**
	 * ****
	 * ASTPP 3.0
	 * Voicemail data get
	 * *****
	 */
	function get_sipdevices_list($flag, $accountid = "", $entitytype = '', $start = "", $limit = "") {
		if ($accountid != "") {
			$where = array (
					"accountid" => $accountid 
			);
		}
		$this->db_model->build_search ( 'fssipdevices_list_search' );
		$instant_search = $this->session->userdata ( 'left_panel_search_' . $entitytype . '_sipdevices' );
		if (! empty ( $instant_search )) {
			$like_str = "(username like '%$instant_search%' OR creation_date like '%$instant_search%' OR last_modified_date like '%$instant_search%')";
			$this->db->where ( $like_str );
		}
		$query = array ();
		if ($flag) {
			$deviceinfo = $this->db_model->select ( "*", "sip_devices", $where, "id", "ASC", $limit, $start );
			if ($deviceinfo->num_rows () > 0) {
				$add_array = $deviceinfo->result_array ();
				foreach ( $add_array as $key => $value ) {
					$vars = json_decode ( $value ['dir_vars'] );
					$vars_new = json_decode ( $value ['dir_params'], true );
					$passowrds = json_decode ( $value ['dir_params'] );
					$query [] = array (
							'id' => $value ['id'],
							'username' => $value ['username'],
							'accountid' => $value ['accountid'],
							'status' => $value ['status'],
							'sip_profile_id' => $value ['sip_profile_id'],
							'effective_caller_id_name' => $vars->effective_caller_id_name,
							'voicemail_enabled' => $vars_new ['vm-enabled'],
							'voicemail_password' => $vars_new ['vm-password'],
							'voicemail_mail_to' => $vars_new ['vm-mailto'],
							'voicemail_attach_file' => $vars_new ['vm-attach-file'],
							'vm_keep_local_after_email' => $vars_new ['vm-keep-local-after-email'],
							'effective_caller_id_number' => $vars->effective_caller_id_number,
							'password' => $passowrds->password,
						/*
						ASTPP  3.0 
						ADD creation,modified date
						*/
						'creation_date' => $value ['creation_date'],
							'last_modified_date' => $value ['last_modified_date'] 
					)/**
					 * ***********************************************
					 */
					;
				}
			}
		} else {
			$query = $this->db_model->countQuery ( "*", "sip_devices", $where );
		}
		return $query;
	}
	/**
	 * *******************
	 */
	/**
	 * ****
	 * ASTPP 3.0
	 * Voicemail add in database
	 * *****
	 */
	function add_freeswitch($add_array) {
		$where = array (
				'id' => $add_array ['accountcode'] 
		);
		$query = $this->db_model->getSelect ( "*", "accounts", $where );
		$query = $query->result_array ();
		$account_data = $query [0];
		
		$log_type = $this->session->userdata ( "logintype" );
		
		$reseller_id = $log_type == 1 ? $account_data ['reseller_id'] : 0;
		
		$sip_profile_id = isset ( $add_array ['sip_profile_id'] ) ? $add_array ['sip_profile_id'] : $this->common->get_field_name ( 'id', 'sip_profiles', array (
				'name' => 'default' 
		) );
		
		$parms_array = array (
				'password' => $add_array ['fs_password'],
				'vm-enabled' => $add_array ['voicemail_enabled'],
				'vm-password' => $add_array ['fs_password'],
				'vm-mailto' => $add_array ['voicemail_mail_to'],
				'vm-attach-file' => $add_array ['voicemail_attach_file'],
				'vm-keep-local-after-email' => $add_array ['vm_keep_local_after_email'],
				'vm-email-all-messages' => $add_array ['vm_send_all_message'] 
		);
		
		$add_array ['status'] = isset ( $add_array ['status'] ) ? $add_array ['status'] : "0";
		$parms_array_vars = array (
				'effective_caller_id_name' => $add_array ['effective_caller_id_name'],
				'effective_caller_id_number' => $add_array ['effective_caller_id_number'],
				'user_context' => 'default' 
		);
		/*
		 * ASTPP 3.0
		 * creation date add
		 */
		$new_array = array (
				'creation_date' => gmdate ( 'Y-m-d H:i:s' ),
				'username' => $add_array ['fs_username'],
				'reseller_id' => $reseller_id,
				'accountid' => $add_array ['accountcode'],
				'status' => $add_array ['status'],
				'dir_params' => json_encode ( $parms_array ),
				'dir_vars' => json_encode ( $parms_array_vars ),
				'sip_profile_id' => $sip_profile_id 
		);
		/**
		 * **************************************8
		 */
		$this->db->insert ( 'sip_devices', $new_array );
		/**
		 * ****
		 * ASTPP 3.0
		 * Email broadcast when Sip Device create
		 * *****
		 */
		$mail = (isset ( $add_array ['voicemail_mail_to'] ) && $add_array ['voicemail_mail_to'] != "") ? $add_array ['voicemail_mail_to'] : $account_data ['email'];
		
		$add_array ['id'] = $add_array ['accountcode'];
		$add_array ['reseller_id'] = $account_data ['reseller_id'];
		$add_array ['email'] = $mail;
		$add_array ['first_name'] = $account_data ['first_name'];
		$add_array ['last_name'] = $account_data ['last_name'];
		$add_array ['number'] = $add_array ['fs_username'];
		// $add_array['password']=$this->common->decode($add_array['fs_password']);
		$add_array ['password'] = $add_array ['fs_password'];
		// echo "<pre>"; print_r($add_array); exit;
		$this->common->mail_to_users ( 'add_sip_device', $add_array );
		/**
		 * *************************
		 */
		return true;
	}
	/**
	 * ********************
	 */
	/**
	 * ****
	 * ASTPP 3.0
	 * Voicemail edit
	 * *****
	 */
	function edit_freeswitch($add_array, $id) {
		$parms_array = array (
				'password' => $add_array ['fs_password'],
				'vm-enabled' => $add_array ['voicemail_enabled'],
				'vm-password' => $add_array ['voicemail_password'],
				'vm-mailto' => $add_array ['voicemail_mail_to'],
				'vm-attach-file' => $add_array ['voicemail_attach_file'],
				'vm-keep-local-after-email' => $add_array ['vm_keep_local_after_email'],
				'vm-email-all-messages' => $add_array ['vm_send_all_message'] 
		);
		// echo "<pre>";print_r($parms_array);exit;
		
		$parms_array_vars = array (
				'effective_caller_id_name' => $add_array ['effective_caller_id_name'],
				'effective_caller_id_number' => $add_array ['effective_caller_id_number'] 
		);
		$log_type = $this->session->userdata ( "logintype" );
		if ($log_type == 0 || $log_type == 3 || $log_type == 1) {
			$add_array ['sip_profile_id'] = $this->common->get_field_name ( 'id', 'sip_profiles', array (
					'name' => 'default' 
			) );
		}
		$add_array ['status'] = isset ( $add_array ['status'] ) ? $add_array ['status'] : "0";
		/*
		 * ASTPP 3.0 edit modified date
		 */
		$new_array = array (
				'last_modified_date' => gmdate ( 'Y-m-d H:i:s' ),
				'username' => $add_array ['fs_username'],
				'accountid' => $add_array ['accountcode'],
				'status' => $add_array ['status'],
				'dir_params' => json_encode ( $parms_array ),
				'dir_vars' => json_encode ( $parms_array_vars ),
				'sip_profile_id' => $add_array ['sip_profile_id'] 
		);
		/**
		 * ****************************************
		 */
		$this->db->where ( 'id', $id );
		$this->db->update ( 'sip_devices', $new_array );
		// echo $this->db->last_query(); exit;
		return true;
	}
	/**
	 * ********************
	 */
	/**
	 * ****
	 * ASTPP 3.0
	 * Voicemail edit data get
	 * *****
	 */
	function get_edited_data($edit_id) {
		$deviceinfo = array ();
		$where = array (
				'id' => $edit_id 
		);
		$deviceinfo = $this->db_model->getSelect ( "*", "sip_devices", $where );
		$add_array = $deviceinfo->result_array ();
		foreach ( $add_array as $key => $value ) {
			$vars = json_decode ( $value ['dir_vars'] );
			$vars_new = json_decode ( $value ['dir_params'], true );
			$passowrds = json_decode ( $value ['dir_params'] );
			$query = array (
					'id' => $value ['id'],
					'fs_username' => $value ['username'],
					'accountcode' => $value ['accountid'],
					'status' => $value ['status'],
					'sip_profile_id' => $value ['sip_profile_id'],
					'effective_caller_id_name' => $vars->effective_caller_id_name,
					'voicemail_enabled' => $vars_new ['vm-enabled'],
					'voicemail_password' => $vars_new ['vm-password'],
					'voicemail_mail_to' => $vars_new ['vm-mailto'],
					'voicemail_attach_file' => $vars_new ['vm-attach-file'],
					'vm_keep_local_after_email' => $vars_new ['vm-keep-local-after-email'],
					'vm_send_all_message' => $vars_new ['vm-email-all-messages'],
					'effective_caller_id_number' => $vars->effective_caller_id_number,
					'fs_password' => $passowrds->password 
			);
		}
		return $query;
	}
	/**
	 * **********************
	 */
	function delete_freeswitch_devices($id) {
		$this->db->where ( 'id', $id );
		$this->db->delete ( 'sip_devices' );
		return true;
	}
	/**
	 * ****
	 * ASTPP 3.0
	 * Get json data
	 * *****
	 */
	function fs_retrieve_sip_user($flag, $start = 0, $limit = 0) {
		$deviceinfo = array ();
		$this->db_model->build_search ( 'fssipdevices_list_search' );
		$accountinfo = $this->session->userdata ( "accountinfo" );
		$reseller_id = $accountinfo ['type'] == 1 ? $accountinfo ['id'] : 0;
		$query = array ();
		$logintype = $this->session->userdata ( "logintype" );
		
		$where ['reseller_id'] = $reseller_id;
		
		if ($flag) {
			$deviceinfo = $this->db_model->select ( "*", "sip_devices", $where, "id", "ASC", $limit, $start );
			if ($deviceinfo->num_rows () > 0) {
				$add_array = $deviceinfo->result_array ();
				foreach ( $add_array as $key => $value ) {
					$vars = json_decode ( $value ['dir_vars'] );
					$vars_new = json_decode ( $value ['dir_params'], true );
					$passowrds = json_decode ( $value ['dir_params'] );
					/*
					 * ASTPP 3.0
					 * add two filed show on gried
					 */
					$query [] = array (
							'id' => $value ['id'],
							'username' => $value ['username'],
							'accountid' => $value ['accountid'],
							'status' => $value ['status'],
							'creation_date' => $value ['creation_date'],
							'last_modified_date' => $value ['last_modified_date'],
							'sip_profile_id' => $value ['sip_profile_id'],
							'effective_caller_id_name' => $vars->effective_caller_id_name,
							'voicemail_enabled' => $vars_new ['vm-enabled'],
							'voicemail_password' => $vars_new ['vm-password'],
							'voicemail_mail_to' => $vars_new ['vm-mailto'],
							'voicemail_attach_file' => $vars_new ['vm-attach-file'],
							'vm_keep_local_after_email' => $vars_new ['vm-keep-local-after-email'],
							'effective_caller_id_number' => $vars->effective_caller_id_number,
							'password' => $passowrds->password 
					);
				}
			}
		} else {
			$query = $this->db_model->countQuery ( "*", 'sip_devices', $where );
		}
		return $query;
	}
	/**
	 * *******************
	 */
	function get_gateway_list($flag, $start = 0, $limit = 0) {
		$this->db_model->build_search ( 'fsgateway_list_search' );
		if ($flag) {
			$query = $this->db_model->select ( "*", "gateways", "", "id", "desc", $limit, $start );
		} else {
			$query = $this->db_model->countQuery ( "*", "gateways", "" );
		}
		return $query;
	}
	function get_sipprofile_list($flag, $start = 0, $limit = 0) {
		$this->db_model->build_search ( 'fssipprofile_list_search' );
		if ($flag) {
			$query = $this->db_model->select ( "*", "sip_profiles", "", "id", "desc", $limit, $start );
		} else {
			$query = $this->db_model->countQuery ( "*", "sip_profiles", "" );
		}
		return $query;
	}
	function profile_authentication($profile_data) {
		if ($profile_data ['id'] != '') {
			$where = array (
					'id <>' => $profile_data ["id"],
					'sip_ip' => $profile_data ["sip_ip"],
					"sip_port" => $profile_data ["sip_port"] 
			);
		} else {
			$where = array (
					'sip_ip' => $profile_data ["sip_ip"],
					"sip_port" => $profile_data ["sip_port"] 
			);
		}
		$query = $this->db_model->getSelect ( "*", "sip_profiles", $where );
		return $query;
	}
	function get_fsserver_list($flag, $start = 0, $limit = 0) {
		$this->db_model->build_search ( 'fsserver_list_search' );
		if ($flag) {
			$query = $this->db_model->select ( "*", "freeswich_servers", "", "id", "desc", $limit, $start );
		} else {
			$query = $this->db_model->countQuery ( "*", "freeswich_servers", "" );
		}
		return $query;
	}
	function add_fssever($data) {
		/*
		 * ASTPP 3.0 ADD creation date
		 */
		$data ['creation_date'] = gmdate ( 'Y-m-d H:i:s' );
		/**
		 * ********************************
		 */
		unset ( $data ['action'] );
		unset ( $data ['id'] );
		$this->db->insert ( 'freeswich_servers', $data );
		return true;
	}
	function edit_fsserver($data, $id) {
		unset ( $data ['action'] );
		/*
		 * ASTPP 3.0 edit modified date add
		 */
		$data ['last_modified_date'] = gmdate ( 'Y-m-d H:i:s' );
		/**
		 * ********************************************
		 */
		$this->db->where ( 'id', $id );
		$this->db->update ( 'freeswich_servers', $data );
		return true;
	}
	function fsserver_delete($id) {
		$this->db->where ( 'id', $id );
		$this->db->delete ( 'freeswich_servers' );
		return true;
	}
	function reload_freeswitch($command, $server_host = "") {
		$response = '';
		$query = $this->db_model->getSelect ( "*", "freeswich_servers", "" );
		$fs_data = $query->result_array ();
		foreach ( $fs_data as $fs_key => $fs_value ) {
			$fp = $this->freeswitch_lib->event_socket_create ( $fs_value ["freeswitch_host"], $fs_value ["freeswitch_port"], $fs_value ["freeswitch_password"] );
			if ($fp) {
				$response .= $this->freeswitch_lib->event_socket_request ( $fp, $command );
				fclose ( $fp );
			}
		}
		return $response;
	}
	function reload_live_freeswitch($command) {
		$response = '';
		$query = $this->db_model->getSelect ( "*", "freeswich_servers", "" );
		$fs_data = $query->result_array ();
		
		foreach ( $fs_data as $fs_key => $fs_value ) {
			$fp = $this->freeswitch_lib->event_socket_create ( $fs_value ["freeswitch_host"], $fs_value ["freeswitch_port"], $fs_value ["freeswitch_password"] );
			if ($fp) {
				$response .= $this->freeswitch_lib->event_socket_request ( $fp, $command );
				fclose ( $fp );
			}
		}
		$response = str_replace ( "0 total.", "", $response );
		return $response;
	}
}
