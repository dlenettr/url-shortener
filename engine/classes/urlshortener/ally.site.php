<?php
/*
=============================================
 Name      : MWS AL.LY v1.0
 Author    : Mehmet Hanoğlu ( MaRZoCHi )
 Site      : http://dle.net.tr/
 License   : MIT License
 Date      : 03.02.2018
=============================================
*/

class URLShortener {

	public $host = "https://al.ly/";
	public $key = "";
	public $uid = "";
	public $set = array( 'adtype' => "int" ); // (int|none)
	public $type = "string";

	public function URLShortener( ) { }

	public function create( $url ) {
		$encoded_url = urlencode( $url );
		$result = $this->_get( $encoded_url );
		$result = json_decode( $result, true );
		return $result;
	}

	public function create_advanced( $url, $custom_url = "", $password = "", $expiry_date = "", $base_domain = "" ) {
		return "false";
	}

	public function disable( $short_url ) {
		return "false";
	}

	public function activate( $short_url ) {
		return "false";
	}

	public function info( $short_url ) {
		return "false";
	}

	public function list_all( ) {
		return "false";
	}

	private function _get( $url ) {
		if ( function_exists( 'file_get_contents' ) ) {
			$result = file_get_contents( $this->host . "/api?api=" . $this->key . "&url=" . $url );
		} else if ( function_exists( 'curl_init' ) ) {
			$ch = curl_init();
			curl_setopt( $ch, CURLOPT_URL, $url );
			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
			curl_setopt( $ch, CURLOPT_REFERER, "" );
			curl_setopt( $ch, CURLOPT_ENCODING, "utf-8" );
			$result = curl_exec( $ch );
			curl_close( $ch );
		} else {
			$result = "";
		}
		return $result;
	}
}

/*
?key=XXXXXXXXXXXXXX
&url=http://www.siteurl.com
*/