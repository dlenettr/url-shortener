<?php
/*
=============================================
 Name      : MWS URL Shortener v1.3.1
 Author    : Mehmet Hanoğlu ( MaRZoCHi )
 Site      : http://dle.net.tr/
 License   : MIT License
 Date      : 02.08.2018
=============================================
*/

if ( ! defined( 'DATALIFEENGINE' ) ) {
	die( "Hacking attempt!" );
}

require_once ENGINE_DIR . "/data/urlshortener.conf.php";

if ( $alsett['plugin'] != "" ) {

	if ( ! empty( $alsett['api_key'] ) ) {

		$cache_name = ENGINE_DIR . "/cache/urlshortener.tmp";
		$_local = clean_url( $config['http_home_url'] );

		require_once ENGINE_DIR . "/classes/urlshortener/" . $alsett['plugin'] . ".php";

		$al = new URLShortener();
		$al->key = $alsett['api_key'];
		$al->uid = $alsett['api_uid'];
		$al->host = $alsett['api_site'];

		if ( ! file_exists( $cache_name ) ) {
			$f = @fopen( $cache_name, "w" );
			@chmod( '0777', $cache_name );
			$all_sql = $db->query( "SELECT hash, short_url FROM " . PREFIX . "_urlshortener WHERE status = '1'" );
			while( $link = $db->get_row( $all_sql ) ) {
				@fwrite( $f, $link['hash'] . "::" . $link['short_url'] . "::" . $link['org_url'] . "\n" );
			}
			@fclose( $f );
		}

		function save_link( $url ) {
			global $al, $db, $cache_name, $alsett;
			$url_hash = md5( $url );
			$short_url = "";

			// Kısaltılacak link daha önce kısaltılmış ise engelle
			$url_host = clean_url( $url );
			if (
				( $alsett['plugin'] == "linktl.site" && $url_host == "link.tl" ) ||
				( $alsett['plugin'] == "tmearn.site" && $url_host == "tmearn.com" ) ||
				( $alsett['plugin'] == "cutwin.site" && $url_host == "cutwin.com" ) ||
				( $alsett['plugin'] == "ally.site" && $url_host == "ally.sh" ) ||
				( $alsett['plugin'] == "bcvc.site" && $url_host == "bc.vc" )
			) {
				return $url;
			}

			$result = $al->create( $url );

			// [plugin]
			if ( in_array( $alsett['plugin'], array( "wurlie.class" ) ) ) {
				//echo "object";
				if ( $result->code == "200" ) {
					$short_url = $result->data->short_url;
				}
			// [plugin]
			} else if ( in_array( $alsett['plugin'], array( "linktl.site" ) ) ) {
				//echo "string";
				if ( ! empty( $result ) ) {
					$short_url = trim( $result );
				}
			// [plugin]
			} else if ( in_array( $alsett['plugin'], array( "bcvc.site" ) ) ) {
				//echo "string";
				if ( ! empty( $result ) ) {
					$short_url = trim( $result );
				}
			}
			// [plugin]
			else if ( in_array( $alsett['plugin'], array( "tmearn.site" ) ) ) {
				//echo "object";
				if ( $result['status'] == "success" ) {
					$short_url = $result['shortenedUrl'];
				}
			}
			// [plugin]
			else if ( in_array( $alsett['plugin'], array( "cutwin.site" ) ) ) {
				//echo "object";
				if ( $result['status'] == "success" ) {
					$short_url = $result['shortenedUrl'];
				}
			}
			// [plugin]
			else if ( in_array( $alsett['plugin'], array( "ally.site" ) ) ) {
				//echo "object";
				if ( $result['error'] == "0" ) {
					$short_url = $result['short'];
				}
			}
			if ( ! empty( $short_url ) ) {
				if ( $alsett['use_cache'] ) {
					$f = @fopen( $cache_name, "a" );
					@chmod( '0777', $cache_name );
					@fwrite( $f, $url_hash . "::" . $short_url . "::" . $url . "\n" );
					@fclose( $f );
				}
				$db->query( "INSERT INTO " . PREFIX . "_urlshortener ( short_url, org_url, hash, type ) VALUES ( '{$short_url}', '{$url}', '{$url_hash}', '1' );" );
				return $short_url;
			} else {
				return $url;
			}

		}

		function attr_control( $arr ) {
			global $alsett;

			$attr = array();
			if ( $alsett['target_blank'] && strpos( $arr[2], "target" ) === false ) {
				$attr['target'] = "_blank";
			}
			if ( $alsett['target_blank'] && ( strpos( $arr[2], "nofollow" ) === false ) ) {
				$attr['rel'] = "nofollow";
			}
			$new_attr = "";
			foreach( $attr as $key => $val ) {
				$new_attr .= " " . $key . "=\"" . $val . "\"";
			}
			$new_attr .= $arr[2];
			$arr[0] = str_replace( $arr[2], str_replace( "  ", " ", $new_attr ), $arr[0] );
			return $arr;
		}

		function short_url( $url ) {
			global $db, $alsett, $cache_name;

			if ( ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
				return $url;
			}
			$url_hash = md5( $url );
			if ( $alsett['use_cache'] ) {
				if ( file_exists( $cache_name ) ) {
					//echo $url . PHP_EOL;
					$f = @fopen( $cache_name, "r" );
					$data = fread( $f, filesize( $cache_name ) );
					@fclose( $f );
					$links = array();
					$_tmp = explode( "\n", str_replace( "\r", "", $data ) );
					foreach( $_tmp as $line ) { $_tmp2 = explode( "::", $line ); $links[ $_tmp2[0] ] = $_tmp2[1]; }
					if ( array_key_exists( $url_hash, $links ) ) {
						$url = $links[ $url_hash ];
					} else {
						$_saved = $db->query( "SELECT short_url FROM " . PREFIX . "_urlshortener WHERE hash = '{$url_hash}' LIMIT 0,1;" );
						if ( $_saved->num_rows > 0 ) {
							$_tmp = $db->get_row( $_saved );
							$url = $_tmp['short_url'];
						} else {
							$url = save_link( $url );
						}
					}
				} else {
					$url = save_link( $url );
				}
			} else {
				$_saved = $db->super_query( "SELECT short_url FROM " . PREFIX . "_urlshortener WHERE hash = '{$url_hash}';" );
				if ( array_key_exists( "short_url", $_saved ) && ! empty( $_saved['short_url'] ) ) {
					$url = $_saved['short_url'];
				} else {
					$url = save_link( $url );
				}
			}

			return $url;
		}

		function link_control( $url ) {
			global $alsett;

			$cut = true;
			$host = clean_url( $url );
			$_all = explode( ",", $alsett['allowed_domains'] );
			$_blc = explode( ",", $alsett['blocked_domains'] );
			$_all = array_map( "trim", $_all );
			$_blc = array_map( "trim", $_blc );
			$_wildc = substr( $host, strpos( $host, "." ) );
			if ( ! empty( $alsett['allowed_domains'] ) ) {
				if ( strpos( $alsett['allowed_domains'], "*" . $_wildc ) !== false ) { $cut = false; }
				if ( in_array( $host, $_all ) ) { $cut = false; }
			} else if ( ! empty( $alsett['blocked_domains'] ) ) {
				$cut = false;
				if ( strpos( $alsett['blocked_domains'], "*" . $_wildc ) !== false ) { $cut = true; }
				if ( in_array( $host, $_blc ) ) { $cut = true; }
			}
			return $cut;
		}

		function find_leechs( $m ) {
			global $alsett;
			$_tmp = explode( "url=", $m[1] );
			$url = base64_decode( urldecode( $_tmp[1] ) );
			$cutted = false;
			if ( $alsett['leech_rest'] == "1" ) {
				$cutted = true;
				$url = short_url( $url );
			} else if ( $alsett['leech_rest'] == "2" ) {
				if ( link_control( $url ) ) {
					$cutted = true;
					$url = short_url( $url );
				}
			} else {
				$url = $url;
			}
			if ( $cutted ) {
				$m = attr_control( $m );
				$result = str_replace( $m[1], $url, $m[0] );
			} else {
				$result = $m[0];
			}

			//echo "[L:" . $url ."]" . PHP_EOL;
			return $result;
		}

		function find_urls( $m ) {
			global $alsett, $config, $_local;
			//print_r( $m );
			$_href = $m[1];
			$cutted = false;

			if ( ! filter_var( $_href, FILTER_VALIDATE_URL ) ) {
				return $m[0];
			}

			$_first_char = substr( $_href, 0, 1 );
			$_host = clean_url( $_href );

			if ( empty( $_local ) && ! empty( $alsett['default_local'] ) ) $_local = clean_url( $alsett['default_local'] );

			if ( $alsett['cut_internal'] ) {
				if ( $_first_char == "/" ) {
					$_href = ( $config['only_ssl'] ? "https" : "http" ) . "://" . $_local . $_href;
					$_href = short_url( $_href );
					$cutted = true;
				} else if ( $_local == $_host ) {
					$_href = short_url( $_href );
					$cutted = true;
				}
			}

			if ( $_host != $_local && $_first_char != "#" && strpos( $_href, $alsett['api_site'] ) === false ) {
				if ( link_control( $_href ) ) {
					$_href = short_url( $_href );
					$cutted = true;
				}
			}

			if ( $cutted ) {
				$m = attr_control( $m );
				$result = str_replace( $m[1], $_href, $m[0] );
			} else {
				$result = $m[0];
			}

			//echo "[A:" . $_href ."]" . PHP_EOL;
			return $result;
		}


		function urlshortener_tag( $m ) {
			$_link = trim( $m[1] );
			if ( filter_var( $_link, FILTER_VALIDATE_URL ) ) {
				$_nlink = short_url( $_link );
				//echo $_link . " :: " . $_nlink . PHP_EOL;
				return $_nlink;
			} else {
				return $_link;
			}
		}

		if ( strpos( $alsett['fields'], "leech" ) !== false ) {
			$tpl->result['main'] = preg_replace_callback( "#<!--dle_leech_begin--><a href=\"(.+?)\"(.+?)>(.+?)</a><!--dle_leech_end-->#i", "find_leechs", $tpl->result['main'] );
		}

		if ( strpos( $alsett['fields'], "others" ) !== false ) {
			$tpl->result['main'] = preg_replace_callback( "#<a href=\"(.+?)\"(.*?)>(.+?)</a>#i", "find_urls", $tpl->result['main'] );
		}

		if ( strpos( $tpl->result['main'], "[urlshortener=" ) !== false ) {
			$tpl->result['main'] = preg_replace_callback( "#\\[urlshortener=(.+?)\\]#i", "urlshortener_tag", $tpl->result['main'] );
		}

	}

}