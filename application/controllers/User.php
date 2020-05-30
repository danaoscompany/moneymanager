<?php

class User extends CI_Controller {
	
	public function get_audiobooks() {
		$audioBooks = $this->db->get('audiobooks')->result_array();
		for ($i=0; $i<sizeof($audioBooks); $i++) {
			$audioBooks[$i]['vendor'] = $this->db->get_where('audiobook_vendors', array('id' => intval($audioBooks[$i]['vendor_id'])))->row_array()['name'];
		}
		header('Content-Type: application/json');
		echo json_encode($audioBooks);
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
		$email = $this->input->post('email');
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
				$this->db->insert('users', array(
					'email' => $email,
					'g_uid' => $googleUid,
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
}
