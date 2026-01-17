<?php
require_once('../config.php');
Class Master extends DBConnection {
	private $settings;
	public function __construct(){
		global $_settings;
		$this->settings = $_settings;
		parent::__construct();
	}
	public function __destruct(){
		parent::__destruct();
	}
	function capture_err(){
		if(!$this->conn->error)
			return false;
		else{
			$resp['status'] = 'failed';
			$resp['error'] = $this->conn->error;
			return json_encode($resp);
		}
	}
	function save_source(){
		extract($_POST);
		$data = "";
		foreach($_POST as $k =>$v){
			if(!in_array($k,array('id'))){
				if(!is_numeric($v))
					$v = $this->conn->real_escape_string($v);
				if(!empty($data)) $data .=",";
				$data .= " `{$k}`='{$this->conn->real_escape_string($v)}' ";
			}
		}
		if(empty($id)){
			$sql = "INSERT INTO `source_list` set {$data} ";
		}else{
			$sql = "UPDATE `source_list` set {$data} where id = '{$id}' ";
		}
		$check = $this->conn->query("SELECT * FROM `source_list` where `name` = '{$name}' ".(is_numeric($id) && $id > 0 ? " and id != '{$id}'" : "")." ")->num_rows;
		if($check > 0){
			$resp['status'] = 'failed';
			$resp['msg'] = ' Source Name already exists.';
			
		}else{
			$save = $this->conn->query($sql);
			if($save){
				$rid = !empty($id) ? $id : $this->conn->insert_id;
				$resp['id'] = $rid;
				$resp['status'] = 'success';
				if(empty($id))
					$resp['msg'] = " Source has successfully added.";
				else
					$resp['msg'] = " Source details has been updated successfully.";
			}else{
				$resp['status'] = 'failed';
				$resp['msg'] = "An error occured.";
				$resp['err'] = $this->conn->error."[{$sql}]";
			}
		}
		if($resp['status'] =='success')
			$this->settings->set_flashdata('success',$resp['msg']);
		return json_encode($resp);
	}
	function delete_source(){
		extract($_POST);
		$del = $this->conn->query("UPDATE `source_list` set delete_flag = 1 where id = '{$id}'");
		if($del){
			$resp['status'] = 'success';
			$this->settings->set_flashdata('success'," Source has been deleted successfully.");
		}else{
			$resp['status'] = 'failed';
			$resp['error'] = $this->conn->error;
		}
		return json_encode($resp);
	}
public function save_lead() {
    extract($_POST);

    $interested_in = $this->conn->real_escape_string($interested_in ?? '');
    $source_id = $this->conn->real_escape_string($source_id ?? '');
    $remarks = $this->conn->real_escape_string($remarks ?? '');
    $assigned_to = $this->conn->real_escape_string($assigned_to ?? null);
    $status = $this->conn->real_escape_string($status ?? 5);
    $lead_type = $this->conn->real_escape_string($lead_type ?? '');
    $project_name = $this->conn->real_escape_string($project_name ?? '');
    $other_info = $this->conn->real_escape_string($other_info ?? '');
    $is_done = 0;

    $userdata = $_SESSION['userdata'] ?? [];
    $user_id = $userdata['id'] ?? null;
    $admin_id = $userdata['type'] == 1 ? $userdata['id'] : ($userdata['admin_id'] ?? $userdata['id']); // fallback
    $user_type = $userdata['type'] ?? null;

    // Auto assign lead to self if user is staff (not admin or super admin)
    if ($user_type != 1 && $user_type != 4) {
        $assigned_to = $user_id;
    }

    $firstname = $this->conn->real_escape_string($firstname ?? '');
    $email = $this->conn->real_escape_string($email ?? '');
    $contact = $this->conn->real_escape_string($contact ?? '');
    $address = $this->conn->real_escape_string($address ?? '');

    if (!empty($id)) {
        // Update lead
        $update_sql = "UPDATE lead_list SET
            source_id = '$source_id',
            interested_in = '$interested_in',
            remarks = '$remarks',
            assigned_to = " . ($assigned_to ?: 'NULL') . ",
            status = '$status',
            lead_type = '$lead_type',
            project_name = '$project_name',
            other_info = '$other_info',
            date_updated = NOW()
            WHERE id = '$id'";

        $update = $this->conn->query($update_sql);

        if (!$update) {
            return json_encode(['status' => '500', 'msg' => 'Lead update failed: ' . $this->conn->error]);
        }

        // Update client info
        $check_client = $this->conn->query("SELECT id FROM client_list WHERE lead_id = '{$id}'");
        if ($check_client->num_rows > 0) {
            $this->conn->query("UPDATE client_list SET
                firstname = '$firstname',
                email = '$email',
                contact = '$contact',
                address = '$address',
                date_updated = NOW()
                WHERE lead_id = '{$id}'");
        } else {
            $this->conn->query("INSERT INTO client_list (lead_id, firstname, email, contact, address)
                VALUES ('$id', '$firstname', '$email', '$contact', '$address')");
        }

        return json_encode(['status' => '200', 'msg' => 'Lead updated successfully']);
    } else {
        // Create new lead
        $prefix = date('Ym') . '-';
        $getLastCode = $this->conn->query("SELECT code FROM lead_list WHERE code LIKE '{$prefix}%' ORDER BY id DESC LIMIT 1");

        if ($getLastCode->num_rows > 0) {
            $lastCode = explode('-', $getLastCode->fetch_array()['code'])[1];
            $code = $prefix . str_pad(((int)$lastCode + 1), 5, '0', STR_PAD_LEFT);
        } else {
            $code = $prefix . "00001";
        }

        $sql = "INSERT INTO lead_list (code, source_id, interested_in, remarks, assigned_to, user_id, admin_id, status, lead_type, project_name, other_info, is_done, date_created, date_updated)
            VALUES (
                '$code',
                '$source_id',
                '$interested_in',
                '$remarks',
                " . ($assigned_to ?: 'NULL') . ",
                " . ($user_id ?: 'NULL') . ",
                " . ($admin_id ?: 'NULL') . ",
                '$status',
                '$lead_type',
                '$project_name',
                '$other_info',
                '$is_done',
                NOW(),
                NOW()
            )";

        if ($this->conn->query($sql)) {
            $lead_id = $this->conn->insert_id;

            $client_sql = "INSERT INTO client_list (lead_id, firstname, email, contact, address)
                VALUES ('$lead_id', '$firstname', '$email', '$contact', '$address')";
            $this->conn->query($client_sql);

            return json_encode(['status' => '200', 'msg' => 'Lead successfully added']);
        } else {
            return json_encode(['status' => '500', 'msg' => 'Database Error: ' . $this->conn->error]);
        }
    }
}


	function delete_lead(){
		extract($_POST);
		$del = $this->conn->query("DELETE FROM `lead_list` where id = '{$id}'");
		if($del){
			$resp['status'] = 'success';
			$this->settings->set_flashdata('success'," Lead has been deleted successfully.");
		}else{
			$resp['status'] = 'failed';
			$resp['error'] = $this->conn->error;
		}
		return json_encode($resp);
	}
	function save_call_reminder() {
    extract($_POST);
    $call_date = $this->conn->real_escape_string($call_date);
    $notes = $this->conn->real_escape_string($notes);
    $user_id = $this->settings->userdata('id'); // logged-in agent

    // Check if lead is assigned to this user
    $lead_check = $this->conn->query("SELECT * FROM lead_list WHERE id = '{$lead_id}' AND assigned_to = '{$user_id}'");
    if ($lead_check->num_rows == 0) {
        return json_encode(['status' => 'failed', 'msg' => 'You are not assigned to this lead']);
    }

    $sql = "INSERT INTO `call_reminders` (`lead_id`, `user_id`, `call_date`, `notes`)
            VALUES ('$lead_id', '$user_id', '$call_date', '$notes')";

    $save = $this->conn->query($sql);
    if ($save) {
        return json_encode(['status' => 'success', 'msg' => 'Call reminder saved']);
    } else {
        return json_encode(['status' => 'failed', 'msg' => $this->conn->error]);
    }
}


	function save_log(){
		if(empty($_POST['id'])){
			$_POST['user_id'] = $this->settings->userdata('id');
		}
		extract($_POST);
		$get_lead = $this->conn->query("SELECT * FROM `lead_list` where id = '{$lead_id}'");
		$lead_res = $get_lead->fetch_array();
		if(isset($lead_res['status'])){
			$status = $lead_res['status'] < 2 ? 2 : $lead_res['status'];
		}
		$data = "";
		foreach($_POST as $k =>$v){
			if(!in_array($k,array('id'))){
				if(!is_numeric($v))
					$v = $this->conn->real_escape_string($v);
				if(!empty($data)) $data .=",";
				$data .= " `{$k}`='{$this->conn->real_escape_string($v)}' ";
			}
		}
		if(empty($id)){
			$sql = "INSERT INTO `log_list` set {$data} ";
		}else{
			$sql = "UPDATE `log_list` set {$data} where id = '{$id}' ";
		}
		$save = $this->conn->query($sql);
		if($save){
			$resp['status'] = 'success';
			if(empty($id))
				$resp['msg'] = " Log has successfully added.";
			else
				$resp['msg'] = " Log details has been updated successfully.";
			$this->conn->query("UPDATE `lead_list` set `status` = '{$status}' where id = '{$lead_id}' ");
		}else{
			$resp['status'] = 'failed';
			$resp['msg'] = "An error occured.";
			$resp['err'] = $this->conn->error."[{$sql}]";
		}
		if($resp['status'] =='success')
			$this->settings->set_flashdata('success',$resp['msg']);
		return json_encode($resp);
	}
	function delete_log(){
		extract($_POST);
		$del = $this->conn->query("DELETE FROM `log_list` where id = '{$id}'");
		if($del){
			$resp['status'] = 'success';
			$this->settings->set_flashdata('success'," Log has been deleted successfully.");
		}else{
			$resp['status'] = 'failed';
			$resp['error'] = $this->conn->error;
		}
		return json_encode($resp);
	}
	function save_note(){
		if(empty($_POST['id'])){
			$_POST['user_id'] = $this->settings->userdata('id');
		}
		extract($_POST);
		$get_lead = $this->conn->query("SELECT * FROM `lead_list` where id = '{$lead_id}'");
		$lead_res = $get_lead->fetch_array();
		if(isset($lead_res['status'])){
			$status = $lead_res['status'] < 2 ? 2 : $lead_res['status'];
		}
		$data = "";
		foreach($_POST as $k =>$v){
			if(!in_array($k,array('id'))){
				if(!is_numeric($v))
					$v = $this->conn->real_escape_string($v);
				if(!empty($data)) $data .=",";
				$data .= " `{$k}`='{$this->conn->real_escape_string($v)}' ";
			}
		}
		if(empty($id)){
			$sql = "INSERT INTO `note_list` set {$data} ";
		}else{
			$sql = "UPDATE `note_list` set {$data} where id = '{$id}' ";
		}
		$save = $this->conn->query($sql);
		if($save){
			$resp['status'] = 'success';
			if(empty($id))
				$resp['msg'] = " Note has successfully added.";
			else
				$resp['msg'] = " Note details has been updated successfully.";
			$this->conn->query("UPDATE `lead_list` set `status` = '{$status}' where id = '{$lead_id}' ");
		}else{
			$resp['status'] = 'failed';
			$resp['msg'] = "An error occured.";
			$resp['err'] = $this->conn->error."[{$sql}]";
		}
		if($resp['status'] =='success')
			$this->settings->set_flashdata('success',$resp['msg']);
		return json_encode($resp);
	}
	function delete_note(){
		extract($_POST);
		$del = $this->conn->query("DELETE FROM `note_list` where id = '{$id}'");
		if($del){
			$resp['status'] = 'success';
			$this->settings->set_flashdata('success'," Note has been deleted successfully.");
		}else{
			$resp['status'] = 'failed';
			$resp['error'] = $this->conn->error;
		}
		return json_encode($resp);
	}
	public function update_lead_status() {
    extract($_POST);
    
    $id = (int)$id;
    $status = (int)$status;

    $update = $this->conn->query("UPDATE lead_list SET status = '$status', date_updated = NOW() WHERE id = '$id'");
    
    if ($update) {
        return json_encode(['status' => 'success']);
    } else {
        return json_encode([
            'status' => 'failed',
            'error' => $this->conn->error
        ]);
    }
}
public function update_lead_done() {
    error_log("update_lead_done function called");
    extract($_POST);

    $id = isset($id) ? (int)$id : 0;
    $is_done = isset($is_done) ? (int)$is_done : 0;

    if ($id > 0) {
        $update = $this->conn->query("UPDATE `lead_list` SET `is_done` = '{$is_done}', `date_updated` = NOW() WHERE `id` = '{$id}'");
        if ($update) {
            error_log("Lead updated successfully: ID={$id}");
            return json_encode(['status' => 200, 'msg' => 'Lead marked as done successfully.']);
        } else {
            error_log("SQL Error: " . $this->conn->error);
            return json_encode(['status' => 500, 'msg' => 'Failed to update lead status.']);
        }
    } else {
        error_log("Invalid lead ID provided: {$id}");
        return json_encode(['status' => 400, 'msg' => 'Invalid lead ID.']);
    }
}


}


$Master = new Master();
$action = !isset($_GET['f']) ? 'none' : strtolower($_GET['f']);
$sysset = new SystemSettings();
switch ($action) {
	case 'save_source':
		echo $Master->save_source();
	break;
	case 'delete_source':
		echo $Master->delete_source();
	break;
	case 'save_lead':
		echo $Master->save_lead();
	break;
	case 'delete_lead':
		echo $Master->delete_lead();
	break;
	case 'save_log':
		echo $Master->save_log();
	break;
	case 'delete_log':
		echo $Master->delete_log();
	break;
	case 'save_note':
		echo $Master->save_note();
	break;
	case 'delete_note':
		echo $Master->delete_note();
	break;
	case 'update_lead_status':
		echo $Master->update_lead_status();
	break;
	case 'save_call_reminder':
    echo $Master->save_call_reminder();
    break;
    case 'update_lead_done':
	echo $Master->update_lead_done();
	break;
	


	default:
		// echo $sysset->index();
		break;
}
