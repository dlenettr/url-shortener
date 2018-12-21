<?php
/*
=============================================
 Name      : MWS URL Shortener v1.3
 Author    : Mehmet Hanoğlu ( MaRZoCHi )
 Site      : https://dle.net.tr/
 License   : MIT License
 Date      : 02.08.2018
=============================================
*/

if ( ! defined( 'DATALIFEENGINE' ) ) {
	header( "HTTP/1.1 403 Forbidden" );
	header( 'Location: ../../' );
	die( "Hacking attempt!" );
}

require_once ENGINE_DIR . "/data/urlshortener.conf.php";

$_out = array( 'error' => "", 'success' => "" );

if ( isset( $_POST['action'] ) && $_POST['action'] == "info" && isset( $_POST['url'] ) ) {

	if ( filter_var( $_POST['url'], FILTER_VALIDATE_URL ) ) {

		require_once ENGINE_DIR . "/classes/urlshortener/" . $alsett['plugin'] . ".php";

		$al = new URLShortener();
		$al->key = $alsett['api_key'];
		$al->uid = $alsett['api_uid'];
		$al->host = $alsett['api_site'];

		// [plugin]
		if ( in_array( $alsett['plugin'], array( "wurlie.class" ) ) ) {
			$info = $al->info( $_POST['url'] );
			if ( $info->code == "200" ) {
				$_out['info'] = $info->data;
			} else {
				$_out['error'] = "Bilgiler okunamadı";
			}
		} else {
			$_out['error'] = "Bu pluginde bilgi alımı sağlanamıyor";
		}

	} else {
		$_out['error'] = "Hatalı URL girildi";
	}

}

else if ( isset( $_POST['action'] ) && $_POST['action'] == "delete" && isset( $_POST['url'] ) ) {

	if ( filter_var( $_POST['url'], FILTER_VALIDATE_URL ) ) {

		$db->query( "DELETE FROM " . PREFIX . "_urlshortener WHERE short_url = '{$_POST['url']}';" );
		@unlink( ENGINE_DIR . "/cache/news_urlshortener.tmp" );
		$_out['success'] = "Kısa link bilgileri silindi. İlk sayfa görüntülemesinde link yeniden kısaltılacak.";

	} else {
		$_out['error'] = "Hatalı URL girildi";
	}

}


else if ( isset( $_POST['action'] ) && $_POST['action'] == "disable" && isset( $_POST['url'] ) ) {


}

echo json_encode( $_out );

?>