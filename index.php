<?php

/* config */
$pool = "pool";
$header = "header.html";
$footer = "footer.html";

?>

<?php

/* util functions */
function create_if_not_exist($db, $file)
{
	$ans = $db->query("SELECT count from dlcount where id = '$file';");
	$values	= $ans->fetch();
	if (count($values) == 0) {
		$db->exec("INSERT INTO dlcount (id, count, init) values ('$file', 0, 0);");
	}
}

function send_file_to_dl($file)
{
	header('Content-Description: File Transfer');
	header('Content-Type: application/octet-stream');
	header('Content-Disposition: attachment; filename='.basename($file));
	header('Content-Transfer-Encoding: binary');
	header('Expires: 0');
	header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
	header('Pragma: public');
	header('Content-Length: ' . filesize($file));

	ob_clean();
	flush();
	readfile($file);
}

function add_entry_to_list($name, $link, $isdir, $size, $date, $dl)
{
	print "  <tr>\n";
	print "   <td> ";
	if ($isdir) {
		if ($name == "..")
			print " <a href='$link' class='icon up'>$name</a>";
		else 
			print " <a href='$link' class='icon dir'>$name</a>";
	} else {
		print " <a href='$link' class='icon file'>$name</a>";
	}
	print " </td>\n";
	print "   <td class='detailsColumn'> $size </td>\n";
	print "   <td class='detailsColumn'> $date </td>\n";
	print "   <td class='detailsColumn'> $dl </td>\n";
	print "  </tr>\n";
}

?>

<?

/* pre page load processing */
$db = new PDO('sqlite:files.db');
$db->exec('CREATE TABLE IF NOT EXISTS dlcount (id TEXT, count INT, init INT, PRIMARY KEY (id))');

if (isset($_GET["path"])) {
	$path = $_GET["path"];
} else {
	print "<META http-equiv='refresh' content='0;URL=/$pool'>";
	exit;
}

if (is_dir("./$pool$path")) {
	if (substr($path, -1) != "/")
		$path .= "/";
} else {
	$file = "./$pool$path";
	if (file_exists($file)) {
		create_if_not_exist($db, $file);
		$ans = $db->exec("UPDATE dlcount set count = (count+1) where id = '$file';");
		send_file_to_dl($file);
		exit;
	}
}
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<link rel="stylesheet" type="text/css" href="/index.css"/>
<title id="title"> <?php print "download $pool$path"; ?> </title>
</head>

<body>

<div id="page">

<?php
if (file_exists("./$pool$path$header"))
		require("./$pool$path$header");
else 
	if (file_exists($header))
		require($header);
?>

<h1 id="header" > <?php print "Index of /$pool$path"; ?> </h1>

<table id="table">
  <tr class="header">
    <td >Name</td>
    <td class="detailsColumn" >Size</td>
    <td class="detailsColumn" >Date Modified</td>
    <td class="detailsColumn" >Download Count</td>
  </tr>

<?php

$files = scandir("./$pool/$path");
if ($files) {
	foreach($files as $entry) {
		if ($entry != "." && $entry != "$footer" && $entry != "$header") {
			$order = 0;
			$unit = array(" B", " kB", " MB", "GB", " TB");

			$name = $entry;
			$filepath = "./$pool$path$entry";
			$filelink = "/$pool$path$entry";
			$size = filesize($filepath);
			$date = filemtime($filepath);

			while ($size > 1024) {
				$order++;
				$size /= 1024;
			}

			$strsize = round($size, 2) . $unit[$order];
			$strdate = date("Y-m-d H:i:s", $date);

			if (!is_dir($filepath)) {
				$isdir = 0;
				create_if_not_exist($db, $filepath);
				$ans = $db->query("SELECT init, count FROM dlcount WHERE id = '$filepath';");
				$values	= $ans->fetch();
				$dl_count = $values["init"] + $values["count"];
			} else {
				$isdir = 1;
				$dl_count = 0;
			}
			add_entry_to_list($name, $filelink, $isdir, $strsize, $strdate, $dl_count);
		}
	}
}
?>

</table>

<?php
if (file_exists("./$pool$path$footer"))
	require("./$pool$path$footer");
else
	if (file_exists($footer))
		require($footer);
?>

</div>
</body>
</html>
