<?php
/*
=============================================
 Name      : MWS LinkTL v1.2
 Author    : Mehmet Hanoğlu ( MaRZoCHi )
 Site      : http://dle.net.tr/
 License   : MIT License
 Date      : 22.11.2016
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

	private function _removeBom( $text ) {
	    $bom = pack('H*','EFBBBF');
	    $text = preg_replace("/^$bom/", '', $text);
	    return $text;
	}

	private function _get( $url ) {
		if ( function_exists( 'file_get_contents' ) ) {
			$result = file_get_contents( $this->host . "/api.php?key=" . $this->key . "&uid=" . $this->uid . "&adtype=" . $this->set['adtype'] . "&url=" . $url );
		} else if ( function_exists( 'curl_init' ) ) {
			$ch = curl_init();
			curl_setopt( $ch, CURLOPT_URL, $url );
			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
			curl_setopt( $ch, CURLOPT_REFERER, "" );
			curl_setopt( $ch, CURLOPT_ENCODING, "utf-8" );
			$result = curl_exec( $ch );

			$result = str_replace( "<b>Notice</b>:  Undefined index: to in <b>/home/linktl/public_html/global.php</b> on line <b>2</b><br />", "", $result );
			$result = str_replace( "<br />", "", $result );
			$result = $this->_removeBom( trim( $result ) );

			curl_close( $ch );
		} else {
			$result = "";
		}
		return $result;
	}
}


/*
?key=781022c2c4d950e93237f3f0d14c049a
&uid=56753
&adtype=int
&url=http://www.siteurl.com
*/