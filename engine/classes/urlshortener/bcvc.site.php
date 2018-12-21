<?php
/*
=============================================
 Name      : MWS Bc.VC v1.0
 Author    : Mehmet Hanoğlu ( MaRZoCHi )
 Site      : http://dle.net.tr/
 License   : MIT License
 Date      : 28.01.2016
=============================================
*/

class URLShortener {

	public $host = "";
	public $key = "";
	public $uid = "";
	public $set = array( 'adtype' => "int" ); // (int|none)
	public $type = "string";

	public function URLShortener( ) { }

	public function create( $url ) {
		$encoded_url = urlencode( $url );
		$result = $this->_get( $encoded_url );
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
			$result = file_get_contents( $this->host . "/api.php?key=" . $this->key . "&uid=" . $this->uid . "&url=" . $url );
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
&uid=YYYYYYYYYYY
&url=http://www.siteurl.com
*/