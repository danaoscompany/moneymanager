<?php

class User extends CI_Controller {

	public function get_audiobook_by_id() {
		$id = intval($this->input->post('id'));
		$audioBook = $this->db->get_where('audiobooks', array('id' => $id))->row_array();
		$audioBook['vendor'] = $this->db->get_where('audiobook_vendors', array('id' => intval($audioBook['vendor_id'])))->row_array()['name'];
		echo json_encode($audioBook);
	}

	public function book_event() {
		$userID = intval($this->input->post('user_id'));
		$eventID = intval($this->input->post('event_id'));
		$name = $this->input->post('name');
		$phone = $this->input->post('phone');
		$gender = $this->input->post('gender');
		$birthday = $this->input->post('birthday');
		$address = $this->input->post('address');
		$province = $this->input->post('province');
		$bookings = $this->db->get_where('event_bookings', array('user_id' => $userID, 'event_id' => $eventID))->result_array();
		if (sizeof($bookings) > 0) {
			$booking = $bookings[0];
			$this->db->where('id', intval($booking['id']));
			$this->db->update('event_bookings', array(
				'name' => $name,
				'phone' => $phone,
				'gender' => $gender,
				'birthday' => $birthday,
				'address' => $address,
				'province' => $province
			));
			echo intval($booking['id']);
		} else {
			$this->db->insert('event_bookings', array(
				'user_id' => $userID,
				'event_id' => $eventID,
				'name' => $name,
				'phone' => $phone,
				'gender' => $gender,
				'birthday' => $birthday,
				'address' => $address,
				'province' => $province
			));
			echo $this->db->insert_id();
		}
	}
	
	public function update_payment_status() {
		$callbackResponse = file_get_contents("php://input");
		$obj = json_decode($callbackResponse, true);
		$externalID = $obj['external_id'];
		$status = $obj['status'];
		$payment = $this->db->get_where('payments', array('external_id' => $externalID))->row_array();
		$amount = intval($payment['amount']);
		$userID = intval($payment['user_id']);
		$user = $this->db->get_where('users', array('id' => $userID))->row_array();
		$this->db->where('external_id', $externalID);
		$this->db->update('payments', array(
			'status' => $status,
			'callback_response' => $callbackResponse
		));
		$paymentType = $payment['type'];
		if ($paymentType == 'event') {
			$eventBookingID = intval($payment['type_id']);
			$this->db->where('id', $eventBookingID);
			$this->db->update('event_bookings', array(
				'payment_status' => $status
			));
		} else if ($paymentType == 'delivery') {
			$deliveryID = intval($payment['type_id']);
			$this->db->where('id', $deliveryID);
			$this->db->update('deliveries', array(
				'payment_status' => $status
			));
		} else if ($paymentType == 'audiobook') {
			$audiobookPaymentID = intval($payment['type_id']);
			$this->db->where('id', $audiobookPaymentID);
			$completed = 0;
			if ($status == 'PAID') {
				$completed = 1;
			}
			$this->db->update('audiobook_payments', array(
				'completed' => $completed
			));
		}
		$url = "https://fcm.googleapis.com/fcm/send";
   	 	$token = $user['fcm_token'];
    	$serverKey = 'AAAAS9nORf0:APA91bGmqUkFQ9Ct6qL6IMNwqv4Noni7dSO6TiGghKi8XO4uwaRnweKM1P7ckBPhoPrK9OjKIPPJUUg2B6dYSZ7WckgINSqj-M2HmuwH2AIysk6Ek3dvM_ZF2p_FXs7HiGVhPevAcvvi';
    	$title = "";
    	if ($status == 'PAID') {
    		$title = "Pembayaran telah berhasil";
    	} else if ($status == 'FAILED') {
    		$title = "Pembayaran gagal";
    	} else {
    		$title = "Status pembayaran: " . $status;
    	}
    	if ($paymentType == 'event') {
    		$body = "Pembayaran sebesar Rp" . $amount . " telah kami terima. Anda sudah kami daftarkan di event yang Anda pilih.";
    	} else if ($paymentType == 'audiobook') {
    		$body = "Pembayaran sebesar Rp" . $amount . " telah kami terima. Anda dapat menikmati audiobook yang sudah Anda beli.";
    	} else if ($paymentType == 'delivery') {
    		$body = "Pembayaran sebesar Rp" . $amount . " telah kami terima. Paket akan segera diantarkan ke alamat penerima.";
    	}
    	$notification = array('title' =>$title , 'body' => $body, 'sound' => 'default', 'badge' => '1');
    	$arrayToSend = array('to' => $token, 'notification' => $notification, 'priority'=>'high', 'data' => array(
    		'type' => '1',
    		'external_id' => $externalID,
    		'status' => $status
    	));
    	$json = json_encode($arrayToSend);
    	$headers = array();
    	$headers[] = 'Content-Type: application/json';
    	$headers[] = 'Authorization: key='. $serverKey;
    	$ch = curl_init();
    	curl_setopt($ch, CURLOPT_URL, $url);
    	curl_setopt($ch, CURLOPT_CUSTOMREQUEST,"POST");
    	curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
    	curl_setopt($ch, CURLOPT_HTTPHEADER,$headers);
    	//Send the request
    	$response = curl_exec($ch);
    	//Close request
    	if ($response === FALSE) {
    		die('FCM Send Error: ' . curl_error($ch));
    	}
    	curl_close($ch);
		echo "OK";
	}
	
	public function get_audiobooks() {
		$audioBooks = $this->db->get('audiobooks')->result_array();
		for ($i=0; $i<sizeof($audioBooks); $i++) {
			$audioBooks[$i]['vendor'] = $this->db->get_where('audiobook_vendors', array('id' => intval($audioBooks[$i]['vendor_id'])))->row_array()['name'];
		}
		header('Content-Type: application/json');
		echo json_encode($audioBooks);
	}
	
	public function use_promo_code() {
		$userID = intval($this->input->post('user_id'));
		$promoCode = $this->input->post('promo_code');
		$promoCodeObjs = $this->db->get_where('promo_codes', array('code' => $promoCode))->result_array();
		if (sizeof($promoCodeObjs) > 0) {
			$promoCodeObj = $promoCodeObjs[0];
			if (sizeof($this->db->get_where('promo_code_users', array('promo_code_id' => intval($promoCodeObj['id']), 'user_id' => $userID))->result_array()) > 0) {
				echo json_encode(array('response_code' => -1));
			} else {
				$this->db->insert('promo_code_users', array('user_id' => $userID, 'promo_code_id' => intval($promoCodeObj['id'])));
				echo json_encode(array('response_code' => 1));
			}
		} else {
			echo json_encode(array('response_code' => -2));
		}
	}
	
	public function get_playlists() {
		$playlists = $this->db->get('playlists')->result_array();
		for ($i=0; $i<sizeof($playlists); $i++) {
			$playlist = $playlists[$i];
			$videos = $this->db->get_where('videos', array('playlist_id' => intval($playlist['id'])))->result_array();
			if (sizeof($videos) > 0) {
				$video = $videos[0];
				$videoResponse = file_get_contents('https://www.googleapis.com/youtube/v3/videos?id=' . $video['video_id'] . '&key=AIzaSyCvSdK10QrdbzUMxeHbrSUAcabunUwTawc&part=snippet,contentDetails,statistics,status');
				$videoInfo = json_decode($videoResponse, true);
				if (sizeof($videoInfo['items']) > 0) {
					$playlists[$i]['thumbnail_url'] = $videoInfo['items'][0]['snippet']['thumbnails']['standard']['url'];
				}
			}
			$playlists[$i]['video_count'] = sizeof($videos);
		}
		header('Content-Type: application/json');
		echo json_encode($playlists);
	}
	
	public function login_with_google() {
		$googleUid = $this->input->post('google_uid');
		$name = $this->input->post('name');
		$email = $this->input->post('email');
		$profilePictureURL = $this->input->post('profile_picture_url');
		$registrationDate = $this->input->post('registration_date');
		$trialEnd = $this->input->post('trial_end');
		$users = $this->db->get_where('users', array(
			'g_uid' => $googleUid
		))->result_array();
		if (sizeof($users) > 0) {
			$user = $users[0];
			echo json_encode(array(
				'id' => intval($user['id']),
				'email' => $user['email'],
				'password' => $user['password'],
				'sign_in_method' => 'google',
				'response_code' => 1,
				'verified' => intval($user['verified'])
			));
		} else {
			$users = $this->db->get_where('users', array('email' => $email))->result_array();
			if (sizeof($users) > 0) {
				$user = $users[0];
				if ($user['g_uid'] != $googleUid) {
					echo json_encode(array(
						'response_code' => -1
					));
				} else {
					echo json_encode(array(
						'id' => intval($user['id']),
						'email' => $user['email'],
						'password' => $user['password'],
						'sign_in_method' => 'google',
						'response_code' => 1,
						'verified' => intval($user['verified'])
					));
				}
			} else {
				$profilePicture = file_get_contents($profilePictureURL);
				$profilePictureName = $this->generateUUID();
				file_put_contents('userdata/images/' . $profilePictureName, $profilePicture);
				$this->db->insert('users', array(
					'name' => $name,
					'email' => $email,
					'g_uid' => $googleUid,
					'profile_picture' => $profilePictureName,
					'registration_date' => $registrationDate,
					'trial_end' => $trialEnd,
					'trial' => 1,
					'premium' => 0,
					'sign_in_method' => 'google'
				));
				$id = intval($this->db->insert_id());
				$user = $this->db->get_where('users', array('id' => $id))->row_array();
				echo json_encode(array(
					'id' => intval($user['id']),
					'email' => $user['email'],
					'password' => $user['password'],
					'sign_in_method' => 'google',
					'response_code' => 1,
					'verified' => intval($user['verified'])
				));
			}
		}
	}
	
	private function generateUUID() {
    	return sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        // 32 bits for "time_low"
        mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),

        // 16 bits for "time_mid"
        mt_rand( 0, 0xffff ),

        // 16 bits for "time_hi_and_version",
        // four most significant bits holds version number 4
        mt_rand( 0, 0x0fff ) | 0x4000,

        // 16 bits, 8 bits for "clk_seq_hi_res",
        // 8 bits for "clk_seq_low",
        // two most significant bits holds zero and one for variant DCE1.1
        mt_rand( 0, 0x3fff ) | 0x8000,

        // 48 bits for "node"
        mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
    	);
    }
}
