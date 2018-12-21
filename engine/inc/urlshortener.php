<?php
/*
=============================================
 Name      : MWS URL Shortener v1.3
 Author    : Mehmet Hanoğlu ( MaRZoCHi )
 Site      : http://dle.net.tr/
 License   : MIT License
 Date      : 02.08.2018
=============================================
*/

if ( !defined( 'DATALIFEENGINE' ) OR !defined( 'LOGGED_IN' ) ) {
	die( "Hacking attempt!" );
}

if ( $member_id['user_group'] != 1 ) {
	msg( "error", $lang['index_denied'], $lang['index_denied'] );
}

require_once ENGINE_DIR . "/data/urlshortener.conf.php";

if ( ! is_writable(ENGINE_DIR . '/data/urlshortener.conf.php' ) ) {
	$lang['stat_system'] = str_replace( "{file}", "engine/data/urlshortener.conf.php", $lang['stat_system'] );
	$fail = "<div class=\"alert alert-error\">{$lang['stat_system']}</div>";
} else $fail = "";

if ( $_REQUEST['action'] == "save" ) {
	if ( $member_id['user_group'] != 1 ) { msg( "error", $lang['opt_denied'], $lang['opt_denied'] ); }
	if ( $_REQUEST['user_hash'] == "" or $_REQUEST['user_hash'] != $dle_login_hash ) { die( "Hacking attempt! User not found" ); }

	$save_con = $_POST['save_con'];
	$save_con['cut_internal'] = intval( $save_con['cut_internal'] );
	$save_con['use_cache'] = intval( $save_con['use_cache'] );
	$save_con['target_blank'] = intval( $save_con['target_blank'] );
	$save_con['rel_nofollow'] = intval( $save_con['rel_nofollow'] );

	$find = array(); $replace = array();
	$find[] = "'\r'"; $replace[] = "";
	$find[] = "'\n'"; $replace[] = "";

	$save_con = $save_con + $alsett;

	$handler = fopen( ENGINE_DIR . '/data/urlshortener.conf.php', "w" );

	fwrite( $handler, "<?PHP \n\n//MWS URL Shortener Settings\n\n\$alsett = array (\n" );
	foreach ( $save_con as $name => $value ) {
		$value = ( is_array( $value ) ) ? implode(",", $value ) : $value;
		$value = trim(strip_tags(stripslashes( $value )));
		$value = htmlspecialchars( $value, ENT_QUOTES, $config['charset']);
		$value = preg_replace( $find, $replace, $value );
		$name = trim(strip_tags(stripslashes( $name )));
		$name = htmlspecialchars( $name, ENT_QUOTES, $config['charset'] );
		$name = preg_replace( $find, $replace, $name );
		$value = str_replace( "$", "&#036;", $value );
		$value = str_replace( "{", "&#123;", $value );
		$value = str_replace( "}", "&#125;", $value );
		//$value = str_replace( ".", "", $value );
		if ( $name != "api_site" ) $value = str_replace( '/', "", $value );
		$value = str_replace( chr(92), "", $value );
		$value = str_replace( chr(0), "", $value );
		$value = str_replace( '(', "", $value );
		$value = str_replace( ')', "", $value );
		$value = str_ireplace( "base64_decode", "base64_dec&#111;de", $value );
		$name = str_replace( "$", "&#036;", $name );
		$name = str_replace( "{", "&#123;", $name );
		$name = str_replace( "}", "&#125;", $name );
		$name = str_replace( ".", "", $name );
		$name = str_replace( '/', "", $name );
		$name = str_replace( chr(92), "", $name );
		$name = str_replace( chr(0), "", $name );
		$name = str_replace( '(', "", $name );
		$name = str_replace( ')', "", $name );
		$name = str_ireplace( "base64_decode", "base64_dec&#111;de", $name );
		fwrite( $handler, "'{$name}' => '{$value}',\n" );
	}
	fwrite( $handler, ");\n\n?>" );
	fclose( $handler );
	@unlink( ENGINE_DIR . "/cache/news_urlshortener.tmp" );

	msg( "info", $lang['opt_sysok'], $lang['opt_sysok_1'], "{$PHP_SELF}?mod=urlshortener" );

}

function en_serialize( $value ) { return str_replace( '"', "'", serialize( $value ) ); }
function de_serialize( $value ) { return unserialize( str_replace("'", '"', $value ) ); }

function showRow( $title = "", $description = "", $field = "", $id = "" ) {
	$_id = ( ! empty( $id ) ) ? " id=\"{$id}\"" : "";
	echo "<tr{$_id}><td class=\"col-xs-6 col-sm-6 col-md-7\"><h6 class=\"media-heading text-semibold\">{$title}</h6><span class=\"text-muted text-size-small hidden-xs\">{$description}</span></td><td class=\"col-xs-6 col-sm-6 col-md-5\">{$field}</td></tr>";
}

function showSep( ) {
	echo "<tr><td class=\"col-xs-12\" colspan=\"2\">&nbsp;</td></tr>";
}

function makeDropDown($options, $name, $selected) {
	$output = "<select class=\"uniform\" style=\"min-width:100px;\" name=\"{$name}\">\r\n";
	foreach ( $options as $value => $description ) {
		$output .= "<option value=\"{$value}\"";
		if( $selected == $value ) {
			$output .= " selected ";
		}
		$output .= ">{$description}</option>\n";
	}
	$output .= "</select>";
	return $output;
}

function makeCheckBox($name, $selected) {
	$selected = $selected ? "checked" : "";
	return "<div class=\"text-center\"><input class=\"switch\" type=\"checkbox\" name=\"{$name}\" value=\"1\" {$selected}></div>";
}

function makeMultiSelect($options, $name, $selected) {
	$selected = explode( ",", $selected );
	$size = (count($options) >= 6) ? 6 : count($options);
	$output = "<select class=\"categoryselect\" style=\"min-width:100px;\" size=\"".$size."\" name=\"{$name}[]\" multiple=\"multiple\">\r\n";
	foreach ( $options as $value => $description ) {
		$output .= "<option value=\"{$value}\"";
		for ($x = 0; $x <= count($selected); $x++) {
			if ($value == $selected[$x]) $output .= " selected ";
		}
		$output .= ">{$description}</option>\n";
	}
	$output .= "</select>";
	return $output;
}

function readPlugins() {
	$plugins = array( "" => "-- Seçiniz --" );
	$directory = ENGINE_DIR . "/classes/urlshortener/";
	$files = array_diff( scandir( $directory ), array( "..", ".", ".htaccess" ) );
	foreach ( $files as $file ) {
		$file = str_replace( ".php", "", $file );
		$plugins[ $file ] = $file;
	}
	return $plugins;
}

echoheader( "<i class=\"fa fa-share-alt\"></i> MWS URL Shortener v1.3", "Sitenizdeki dış bağlantıları kolayca kısaltın" );

$_ACTION = ( isset( $_REQUEST['action'] ) ) ? $_REQUEST['action'] : false;

if ( ! $_ACTION ) {

$_xact = ( strpos( $alsett['fields'], "leech" ) !== false ) ? "fadeIn" : "hide";

echo <<< HTML
<style>
#leech { display: none; }
</style>
<script type="text/javascript">
$(document).ready( function() {
	$('.categoryselect').chosen({allow_single_deselect:true, no_results_text: '{$lang['addnews_cat_fault']}'});
	$("#leech").{$_xact}();
});
$(document).ready( function() {
	$("select[multiple]").chosen({allow_single_deselect:true, no_results_text: 'Hiçbir şey bulunamadı'});
	$('select[name="save_con[fields][]"]').change( function() {
		var selected = $(this).val();
		console.log( selected );
		if ( selected.indexOf("leech") != -1 ) {
			$("#leech").fadeIn();
			console.log( "ilave alan seçildi" );
		} else {
			$("#leech").fadeOut();
			console.log( "ilave alan kaldırıldı" );
		}
	});
});

</script>
HTML;

echo <<< HTML
{$fail}
<form action="{$PHP_SELF}?mod=urlshortener&action=save" name="conf" id="conf" method="post" class="systemsettings">
<div class="panel panel-flat">
	<div class="panel-heading">
		<b>Ayarlar</b>
        <div class="heading-elements">
            <ul class="icons-list">
                <li>
					<a href="{$PHP_SELF}?mod=urlshortener&action=list"><i class="fa fa-reorder"></i> Listeye Bak</a>
				</li>
			</ul>
		</div>
	</div>
	<div class="table-responsive">
		<table class="table table-striped">
HTML;

	showRow( "Kullanılacak Plugin", "Link kısaltması için kullanılacak plugin'i seçiniz",
		makeDropDown( readPlugins(), "save_con[plugin]", "{$alsett['plugin']}" )
	);

	showRow( "Site adresi", "Link kısaltması için kullanmak istediğiniz sitenin adresi. <b>/ ile bitmemelidir.</b>", "<input type=\"text\" class=\"form-control\" name=\"save_con[api_site]\" placeholder=\"http://ornek.com\" value=\"{$alsett['api_site']}\" size=\"60\">");

	showRow( "API Key", "Siteden alacağınız API key bilgisi", "<input type=\"text\" class=\"form-control\" style=\"text-align: center; width: 90%\" name=\"save_con[api_key]\" value=\"{$alsett['api_key']}\" maxlength=\"40\" size=\"40\">&nbsp;<i class=\"btn btn-info btn-sm\" data-rel=\"popover\" data-trigger=\"hover\" data-placement=\"left\" data-content=\"API Key 32 ya da 40 karakterlidir.\" >?</i>");

	showRow( "User ID", "Siteden alacağınız Kullanıcı ID bilgisi", "<input type=\"text\" class=\"form-control\" style=\"text-align: center; width: 90%\" name=\"save_con[api_uid]\" value=\"{$alsett['api_uid']}\" maxlength=\"40\" size=\"20\">&nbsp;<i class=\"btn btn-info btn-sm\" data-rel=\"popover\" data-trigger=\"hover\" data-placement=\"left\" data-content=\"Kullanıcı ID sayı ve rakam içerebilir.\" >?</i>");

	showRow( "Önbellekleme kullan", "Önbellekleme kullanarak link kontrolünü çok kısa sürede yaptırabilirsiniz. Böylece MySQL yükünü azaltmış olursunuz. Sayfa açılış hızlarında önemli ölçüde hızlanma olur.", makeCheckBox( "save_con[use_cache]", "{$alsett['use_cache']}" ) );

	showRow( "Bu alanlar için kullan", "İçerik eklerken kullandığınız taglar veya ilave alanı seçerek linklerin kısaltılmışları ile değiştirilmesini sağlayabilirsiniz.",
		makeMultiSelect( array(
				"leech" => "Leech tagı ile girilmiş",
				"others" => "Diğer tüm linkler",
			), "save_con[fields]", "{$alsett['fields']}"
		)
	);

	showRow( "Leech için domain kısıtlaması", "Aşağıda belirlenecek olan domain kısıtlamasını leech tagı için kapatıp, tüm leech tagı ile eklenmiş linklerin kısaltılamasını sağlayabilirsiniz. Leech tagı ile girilen linkler sistem tarafından kodlandığı için diğer linklerden ayrı işlem yapılabiliyor.",
		makeDropDown(
			array(
				"1" => "Kullan",
				"0" => "Kullanma",
				"2" => "Kontrol Et",
			), "save_con[leech_rest]", "{$alsett['leech_rest']}"
		), "leech"
	);

	showRow( "İç bağlantıları kısalt", "Sitedeki tüm iç bağlantıları kısaltmak için bu ayarı aktifleştiriniz. <b>( Önerilmez )</b>", makeCheckBox( "save_con[cut_internal]", "{$alsett['cut_internal']}" ), "internal" );

	showRow( "Yeni sekmede aç", "Kısaltılan linkleri yeni sekmede açtırmak için bu ayarı aktifleştiriniz.", makeCheckBox( "save_con[target_blank]", "{$alsett['target_blank']}" ) );

	showRow( "Nofollow ekle", "Kısaltılan linklere rel=\"nofollow\" niteliği eklemek için bu ayarı aktifleştiriniz. Bu konu hakkında arama motorlarını kullanarak bilgi sahibi olabilirsiniz.", makeCheckBox( "save_con[rel_nofollow]", "{$alsett['rel_nofollow']}" ) );

	showRow( "Site adresiniz", "Sitenizin domain adresi sistemden okunamaması durumunda bu bilgi okunacaktır. Domainizi girmeniz şiddetle önerilir. Aksi halde sitenizdeki tüm iç ve dış linkler kısaltılmaya çalışılacaktır.", "<input type=\"text\" class=\"form-control\" name=\"save_con[default_local]\" placeholder=\"siteniz.com\" value=\"{$alsett['default_local']}\" size=\"60\">");

	showRow( "Kısaltılmayacak domainler", "Sadece şifrelenmesini istemediğiniz domainleri örnekteki gibi girin: <br />a.site.com,siteb.com,*.siteler.com<br /><font color=\"red\">Eğer kısıtlama kullanmak istemiyorsanız alanı boş bırakın</font> -- <b>( Öncelik bu alandadır )</b><br />Mantıklı bir şekilde çalışması için iki alandan bir tanesini kullanın.", "
		<textarea style=\"width:100%;height:100px;\" name=\"save_con[allowed_domains]\">{$alsett['allowed_domains']}</textarea><br />
		<span class=\"note\">Her domaini virgül ile ayırarak ekleyin. Tüm alt domainler için *.domain.com yapısını kullanabilirsiniz.</span>
		"
	);

	showRow( "Kısaltılacak domainler", "Sadece şifrelenmesini istediğiniz domainleri örnekteki gibi girin: <br />a.site.com,siteb.com,*.siteler.com<br /><font color=\"red\">Eğer kısıtlama kullanmak istemiyorsanız alanı boş bırakın</font><br />Mantıklı bir şekilde çalışması için iki alandan bir tanesini kullanın.", "
		<textarea style=\"width:100%;height:100px;\" name=\"save_con[blocked_domains]\">{$alsett['blocked_domains']}</textarea><br />
		<span class=\"note\">Her domaini virgül ile ayırarak ekleyin. Tüm alt domainler için *.domain.com yapısını kullanabilirsiniz.</span>"
	);

echo <<< HTML
		</table>
	</div>
	<div class="panel-footer">
		<div class="pull-right">
			<input type="hidden" name="user_hash" value="{$dle_login_hash}" />
			<button class="btn bg-teal btn-raised" id="save"><i class="fa fa-floppy-o"></i>{$lang['user_save']}</button>
		</div>
	</div>
</div>
</form>
HTML;

}


else if ( $_ACTION == "list" ) {

echo <<< HTML
<style>
.links td[data-url] { color: #009; cursor: pointer; }
.links td[data-url]:hover { background: #eee; }
.links td a { color: #111;  }
.links td a:hover { color: #009; text-decoration: underline; }
</style>
<script type="text/javascript">
$(document).ready( function() {
	$("td[data-url]").click( function() {
		url = $(this).attr( 'data-url' );
		console.log( url );
		if ( url != "" ) {
			$.post( "engine/ajax/controller.php?mod=urlshortener", { action: 'info', url: url }, function( data ) {
				console.log( data );
				if ( data.error != "" ) {
					DLEalert( data.error, "Hata" );
				} else {
					html = '<table class="table table-normal">';
					console.log( data.info );
					for ( x in data.info ) {
						html += "<tr>";
						html += "<td>" + x + "</td>";
						html += "<td>" + data.info[x] + "</td>";
						html += "</tr>";
					}
					html += "</table>";
					DLEalert( html, "Kısaltılmış link bilgileri" );
				}
			}, 'json');
		} else {
			DLEalert( "Hatalı link !", "Hata" );
		}
	});
});

function action_link( action, url ) {

	$.post( "engine/ajax/controller.php?mod=urlshortener", { action: action, url: url }, function( data ) {
		console.log( data );
		if ( data.error != "" ) {
			DLEalert( data.error, "Hata" );
		} else {
			DLEalert( data.success, "Bilgilendirme" );
		}
	}, 'json');

}

</script>
<div class="panel panel-flat">
	<div class="panel-heading">
		<b>Veritabanı Link Listesi</b>
        <div class="heading-elements">
            <ul class="icons-list">
                <li>
					<a href="{$PHP_SELF}?mod=urlshortener"><i class="fa fa-wrench"></i> Ayarlar</a>
				</li>
			</ul>
		</div>
	</div>
	<div class="table-responsive">
		<table class="table table-striped links">
			<thead>
				<tr>
					<td>Kısa URL</td>
					<td>Orj. URL</td>
					<td>URL Hash</td>
					<td>Oluşturma Tipi</td>
					<td>Durumu</td>
					<td>Şifreli</td>
					<td>İşlemler</td>
				</tr>
			</thead>
			<tbody>
HTML;
		$sel_links = $db->query( "SELECT * FROM " . PREFIX . "_urlshortener" );
		while( $row = $db->get_row( $sel_links ) ) {
			$_type = ( $row['type'] == "1" ) ? "Otomatik" : "Manuel";
			$_pass = ( $row['has_pass'] == "1" ) ? "Şifreli" : " ---- ";
			$_status = ( $row['status'] == "1" ) ? "Aktif" : "Deaktif";
			if ( strlen( $row['org_url'] ) > 53 ) {
				$_url = substr( $row['org_url'], 0, 50 ) . " ...";
			} else $_url = $row['org_url'];
			echo <<< HTML
			<tr>
				<td data-url="{$row['short_url']}">{$row['short_url']}</td>
				<td><a href="{$row['org_url']}" target="_blank">{$_url}</a></td>
				<td align="center">{$row['hash']}</td>
				<td align="center">{$_type}</td>
				<td align="center">{$_status}</td>
				<td align="center">{$_pass}</td>
				<td align="center">
					<button class="btn btn-sm btn-danger" title="Sil" onclick="action_link( 'delete', '{$row['short_url']}' ); return false;"><i class="fa fa-trash" style="color: white"></i></button>
					<!--button class="btn btn-sm btn-black" title="Deaktif Et" onclick="action_link( 'disable', '{$row['short_url']}' ); return false;"><i class="fa fa-ban-circle" style="color: white"></i></button-->
				</td>
			</tr>
HTML;
		}

echo <<< HTML
			</tbody>
		</table>
	</div>
</div>

HTML;

}


else if ( $_ACTION == "create" ) {


}


echofooter();


?>