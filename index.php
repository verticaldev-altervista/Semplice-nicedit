<?php
/****************************************************
*
* 	SEMPLICE  09/12/2010
*	vroby.mail@gmail.com
*
*****************************************************/
include "lang.php";

// API ----------------------------------------------------------------------------------------------------------------------------------------
function do_xhtml ( $string ) {$string = stripslashes ( $string );$string = str_replace ( "'" , "'" , $string );$string = str_replace ( '"', '"', $string );$string = str_replace ( '?', '-', $string );$string = str_replace ( '?', '\'', $string );$string = str_replace ( '?', '', $string );$string = str_replace ( '?', '', $string );$string = str_replace ( '`', '', $string );return $string;}
function fsave( $filename, $dati){ $fp = fopen($filename, 'w'); fwrite($fp, $dati); fclose($fp); }
function fcheck($path){ @touch($path."/tmp"); $res= (file_exists($path."/tmp")); @unlink($path."/tmp"); return $res;}
function filter($dati){ $dati= str_replace("\"","",$dati); $dati= str_replace("\\","",$dati); return $dati;}
function isadmin(){$mypassword=@$_COOKIE['admin']; $admin=@file("datas/admin.php");if (trim($admin[1])==trim($mypassword))return true; else return false; }
//--------------------------------------------------------------------------------------------------------------------------------------------


$URL="http://".$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'];

$msg="";
$op=@$_GET['op'];

// carica configurazione o avvia procedra installazione --------------------------------------------------------
if (file_exists("datas/admin.php"))
	$admin=file("datas/admin.php");
else
	if ($op!='saveadmin'){
		$op='admin';
		$msg=_PRIMA_INSTALAZIONE;
		$admin[1]=md5("");
	}

// verifica parametri passati dal form di configurazione ------------------------------------------------
if ($op=="saveadmin"){
	if($_POST['password']!=$_POST['confpassword']){$msg=_UNCONF_PASSWORD; $op='admin';}
	if($_POST['email']==""){$msg=_NOMAIL; $op='admin';}
	if(trim($_POST['email'])!=trim($_POST['confemail'])){$msg=_UNCONF_EMAIL; $op='admin';}
}
//------------------------------------------------------------------------------------------------------------------------------------

// salvataggio configurazione -----------------------------------------------------------------------------------------
if ($op=="saveadmin" && isadmin()){
	$fp = fopen("datas/admin.php", 'w');
	fwrite($fp, "<?php /*\n");
	if ($_POST['password']!="") fwrite($fp, md5($_POST['password'])."\n");else fwrite($fp,$admin[1]);
	fwrite($fp, $_POST['email']."\n");
	fwrite($fp, $_POST['sitename']."\n");
	fwrite($fp, $_POST['headers']."\n");
	fwrite($fp, "*/ ?>\n");
	fclose($fp);
	if (@$_POST['sendpassword']!=0){
		$subject=_CAMBIO_PASSWORD.$_POST['sitename'];
		$message="password : ".$_POST['password'];
		mail($_POST['email'],$subject,$message);
	}
	$msg=_ADMIN_UPDATE;
	$op="";
}
//----------------------------------------------------------------------------------------------------------------------------

$page=@$_GET['page'];
// filtri per evitare pagine strane -------------------------------------------------------------------------
$page=str_replace("/","",$page);
$page=str_replace(".","",$page);
if ($page=="")$page="main";
//----------------------------------------------------------------------------------------------------------------------------

//login & logout ------------------------------------------------------------------------------------------------------
if( $op=='logout') {
	setcookie("admin","",NULL,"");
	echo "<script language=javascript>window.location='index.php'</script>";
	exit();
}

if(@$_POST['password'] !="")
	$mypassword=md5($_POST['password']);
else
	$mypassword=@$_COOKIE['admin'];

if ($op=='login' ){
	if( trim($admin[1])!=trim($mypassword)){
		$op="";
	}
	else {
		setcookie("admin",$mypassword,0,"");
		echo "<script language=javascript>window.location='index.php?page=$page'</script>";
		exit();
	}
}
//----------------------------------------------------------------------------------------------------------------------------

//supporto cookie  e autenticazione -operazioni --------------------------------------------------------
if ($op=='save' && !isadmin())$op="";
if ($op=='admin' && !isadmin() && file_exists("datas/admin.php"))$op="";
//----------------------------------------------------------------------------------------------------------------------------

//salvataggio pagine editate ------------------------------------------------------------------------------------
if ($op=="save"){
	$title=filter($_POST['title']);
	$textpage=filter($_POST['textpage']);
	$sidebar=filter($_POST['sidebar']);
	$footer=filter($_POST['footer']);

	fsave("datas/title",$title);
	fsave("datas/$page",$textpage);
	fsave("datas/sidebar",$sidebar);
	fsave("datas/footer",$footer);
	$msg= _MODIFICHE_SALVATE;

	//Genera sitemap -----------------------------------------------------------------------
	$sitemap= '<urlset xmlns="http://www.google.com/schemas/sitemap/0.84" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.google.com/schemas/sitemap/0.84 http://www.google.com/schemas/sitemap/0.84/sitemap.xsd">'."\n";
	$fd=opendir("datas/");
	while (false !== ($nf= readdir($fd))){
		if (substr_count($nf,".")==0){
			$sitemap.= "<url>\n";
			$sitemap.="<loc>$URL?page=$nf</loc>\n";
			$sitemap.="<lastmod>".date("j/m/y h:i", filemtime("datas/$nf"))."</lastmod>\n";
			$sitemap.="<changefreq>monthly</changefreq>\n";
			$sitemap.="<priority>0.8</priority>\n";
			$sitemap.="</url>\n";
		}
	}
	$sitemap.="</urlset>\n";
	if (is_writable("Sitemap.xml")) fsave("Sitemap.xml",'<?xml version="1.0" encoding="UTF-8"?>'."\n".do_xhtml($sitemap));
	//-----------------------------------------------------------------------------------------------------------
}
//----------------------------------------------------------------------------------------------------------------------------

//eliminazione pagina --------------------------------------------------------------------------------
if ($op=='deletepage' && isadmin()) {
	$file=$_GET['file'];
	unlink("datas/$file");
	$msg=" <font color='f00'>$file "._ELIMINATO."</font> " ;
	$op='admin';
}
//----------------------------------------------------------------------------------------------------------------------------

//upload file -----------------------------------------------------------------------------------------------------------
if($op=="uploadfile" && isadmin()){
	if (is_uploaded_file($_FILES['myfile']['tmp_name'])) {
		if (!move_uploaded_file($_FILES['myfile']['tmp_name'], 'datas/'.$_FILES['myfile']['name'])) {
			$msg = _UPLOAD_ERROR;
			break;
		}
		else{
			$msg=" <font color='00f'>$file "._CARICATO."</font> " ;
		}
	}
	$op="admin";
}
//----------------------------------------------------------------------------------------------------------------------------

//<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\" \"http://www.w3.org/TR/html4/loose.dtd \">
//<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01//EN \">
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
	<head>
		<title><?=trim($admin[3])."-".$page; ?></title>
		<meta name="robots" content="" >
		<meta name="generator" content="" >
		<meta name="keywords" content="<?=trim($admin[4]) ?>" >
		<meta name="description" content="" >
		<meta name="MSSmartTagsPreventParsing" content="true" >
		<meta http-equiv="distribution" content="global" >
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" >
		<meta http-equiv="Resource-Type" content="document" >
		<link rel="stylesheet" type="text/css" href="theme.css" media="all" />

		<?php if ($op=='edit') { ?>
		<script src="./nicEdit.js" type="text/javascript"></script>
		<script type="text/javascript">
			bkLib.onDomLoaded(function() {
				var myNicEditor = new nicEditor({fullPanel : true, buttonList : ['html','xhtml']});
				myNicEditor.setPanel('myNicPanel');
				myNicEditor.addInstance('div-title');
				myNicEditor.addInstance('div-sidebar');
				myNicEditor.addInstance('div-textpage');
				myNicEditor.addInstance('div-footer');
			});

			function  flush(){
				document.base.title.value=nicEditors.findEditor('div-title').getContent();
				document.base.sidebar.value=nicEditors.findEditor('div-sidebar').getContent();
				document.base.textpage.value=nicEditors.findEditor('div-textpage').getContent();
				document.base.footer.value=nicEditors.findEditor('div-footer').getContent();
			}
		</script>
		<?php } ?>
	</head>


<body>

<?php if($op=='admin') { ?>
<!-- adminFrame  --------------------------------------------------->
<div id='div-adminframe'>
<fieldset>
<legend><b><?=_ADMIN ?>:</b><?=$msg ?></legend>

<fieldset>
<legend><?=_CONFIGURA; ?></legend>
<form name="admin" action="index.php?op=saveadmin" method="post"  >
<?=_NOME_SITO; ?><input type='entry' name='sitename'  size='80' value='<?=@$admin[3];?>'><br/>
headers  <input type='entry' name='headers' size='120'  value='<?=@$admin[4];?>'><br/>
<?=_EMAIL; ?> <input type='entry' name='email' value='<?=@$admin[2];?>'><?=_CONFERMA_EMAIL; ?> <input type='entry' name='confemail' value='<?=@$admin[2];?>'><br/>
<?=_PASSWORD; ?>  <input type='password' name='password'> <?=_CONFERMA_PASSWORD; ?><input type='password' name='confpassword'> <input type='checkbox' name='sendpassword' ><?=_INVIA_PASSWORD; ?><br/>
<p align='right'><input type='submit'  value='<?=_SALVA; ?>'  /></p>
</form>
</fieldset>
<br/>

<!-- Pannello pubblicazione Sitemap -->
<fieldset>
	<legend><b> <?= _PUBBLICA_SITEMAP; ?></b></legend>
	<b><?=_VEDI ?>:</b>
	<a href="Sitemap.xml" target='Sitemap'>Sitemap.xml</a>
	<?php
		if (file_exists("Sitemap.xml")){
			if (is_writable("Sitemap.xml"))
				echo"<td>"._SCRIVIBILE ." </td></tr>\n";
			else
				echo "<td>". _NON_SCRIVIBILE."</td></tr>\n";
		}
		else {
				echo "<td>". _NON_ESISTE."</td></tr>\n";
		}
	?><br/>
	<b><?=_PUBBLICA ?>:</b>
	<a href="http://www.google.com/webmasters/sitemaps/ping?sitemap=<?=substr($URL, 0, -9);?>Sitemap.xml" target="google">Google</a> |
	<a href="http://www.bing.com/webmaster/ping.aspx?siteMap=<?=substr($URL, 0, -9);?>Sitemap.xml" target="bing">Bing</a> |
	<a href="http://search.yahooapis.com/SiteExplorerService/V1/updateNotification?appid=YahooDemo&url=<?=substr($URL, 0, -9);?>Sitemap.xml" target="yahoo">Yahoo</a>
</fieldset>
<br/>

<!-- Pannello gestione pagine e files -->
<fieldset>
<legend><b> <?= _PERMESSI; ?></b></legend>
<br/>
<?php

if ( fcheck ("datas") )
	echo ""._CARTELLA_DATAS_SCRIVIBILE ;
else
	echo ""._CARTELLA_DATAS_NON_SCRIVIBILE;

echo "<br/><br/>\n";
echo "<table>";
echo "<tr><td><b>"._PAGE."</b></td><td><b>"._DATE."</b><td><b>"._SIZE."</b></td><td><b>"._VEDI."</b></td><td><b>"._ELIMINA."</b></td><td><b>"._STATO."</b></td></tr>";
$fd=opendir("datas/");
while (false !== ($nf= readdir($fd))){
	if ($nf!='.'  && $nf!='..'){
		echo "<tr><td>$nf</td>";
		echo"<td><i>".date("j/m/y h:i", filemtime("datas/$nf"))."<i></td>\n";
		echo"<td><i>". filesize("datas/$nf")."<i></td>\n";
		echo"<td>[<a href='datas/$nf' target='new'>"._VEDI."</a>]</td>";
		echo"<td>[<a href='?op=deletepage&file=$nf'  onclick=\"if (confirm('cancellare'))return true; else return false;\">"._ELIMINA."</a>]</td>";
		if (is_writable("datas/$nf") )
			echo"<td>"._SCRIVIBILE ." </td></tr>\n";
		else
			echo "<td>". _NON_SCRIVIBILE."</td></tr>\n";

	}
 }
closedir($fd);
echo "</table>";
?>
<hr/>
<div align='right'>
<form action="index.php?op=uploadfile" method="post" enctype="multipart/form-data">
<input name="myfile" type="file"  />
<input type="submit" value="carica" />
</form>
</div>
</fieldset><br/>
</fieldset><br/>
<div align='right'>
<a href='index.php?page=<?=$page ?>'><input type='button' value='<?=_ESCI; ?>'  onclick="location='index.php?page=<?=$page?>';" /></a>
</div>
</div>
<!-------------------------------------------------------------------------->
<?php } ?>


<!-- Toolbar --------------------------------------------------------->
<div id='div-toolbar' <?php if($op=='edit') echo " style=\" display: block; position: fixed; top:0; left:0; width: 100%;  \" "; ?>>
<?php if($op=='edit') { ?>
<form name="base" action="index.php?op=save&page=<?=$page?>" method="post"  >
<table width='100%'><tr ><td  >
<div id="myNicPanel" style=" width: 100%;"></div>
</td><td width='100'>
<input type='submit' value='<?=_SALVA; ?>'  onclick="flush();"/>
<a href="index.php?page=<?=$page?>" ><input type='button' value='<?=_ESCI; ?>'    onclick="location='index.php?page=<?=$page?>';" /></a>
</td></tr></table>
<input type='hidden' name="title" id="title" >
<input type='hidden' name="sidebar" id="sidebar" >
<input type='hidden' name="textpage" id="textpage" >
<input type='hidden' name="footer" id="footer" >
</form>

<?php }else{ ?>
<?php if (isadmin()) { ?>
<form action="index.php?op=edit&page=<?=$page?>" method='post' >
<p align='right'>
<b><?=$admin[3] ?></b>
<?=$msg." " ?>
<a href="index.php?op=admin&page=<?=$page?>"><?=_ADMIN; ?></a>
<a href='index.php?op=logout'><?=_LOGOUT ?></a>
<input type='submit' value='edit' /></p></form>
<?php }else{ ?>
<?php if ($op=='auth'){ ?>
<form action="index.php?op=login&page=<?=$page?>" method='post' >
<p align='right'>
<b><?=$admin[3] ?></b>
<?=$msg." " ?>
<input type='password' name ='password'   />
<input type='submit' value='<?=_LOGIN; ?>' />
</p>
</form>
<?php } ?>
<?php } ?>
<?php } ?>
</div>
<!-------------------------------------------------------------------------->

<!-- Title --------------------------------------------------------------->
<div id='div-title'   <?php if($op=='edit') echo " style=\" margin-top:40px; \" "; ?> >
 <?php echo filter((@join(@file("datas/title")))); ?>
</div>
<!-------------------------------------------------------------------------->

<!-- sidebar ----------------------------------------------------------->
<div id='div-sidebar'>
<?php echo filter((@join(@file("datas/sidebar")))); ?>
<?php if($op!='edit'){ ?>
<?php } ?>
</div>
<!-------------------------------------------------------------------------->

<!-- textpage --------------------------------------------------------------->
<div id='div-textpage'>
<?php echo filter((@join(@file("datas/$page")))); ?>
</div>
<!-------------------------------------------------------------------------->

<!-- Footer ------------------------------------------------------------->
<div id='div-footer'>
<?php echo filter((@join(@file("datas/footer")))); ?>
<!-------------------------------------------------------------------------->

</body>
</html>
