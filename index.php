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
	$ans = sqlite_query($db , "SELECT count from dlcount where id = '$file';");
	if (0 == sqlite_num_rows($ans)) {
		sqlite_query($db , "INSERT INTO dlcount (id, count, init) values ('$file', 0, 0);");
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
if ($db = sqlite_open("files.db")) {
	try {
		sqlite_exec($db , "CREATE TABLE dlcount (id TEXT, count INT, init INT, PRIMARY KEY (id))");
	} catch(Exception $e) {
		;
	}
}

if (isset($_GET["path"])) {
	$path .= $_GET["path"];
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
		$ans = sqlite_query($db , "UPDATE dlcount set count = (count+1) where id = '$file';");
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

//if ($handle = opendir("./$pool/$path")) {
//	while (false !== ($entry = readdir($handle))) {
$files = scandir("./$pool/$path");
if (files) {
	foreach($files as $entry) {
		if ($entry != ".") {
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
				$ans = sqlite_query($db , "SELECT init, count from dlcount where id = '$filepath';");
				$values = sqlite_fetch_array($ans);
				$dl_count = $values["init"] + $values["count"];
			} else {
				$isdir = 1;
				$dl_count = 0;
			}
			add_entry_to_list($name, $filelink, $isdir, $strsize, $strdate, $dl_count);
		}
	}

	closedir($handle);
}
?>

</table>

<?php
if (file_exists($footer))
	require($footer);
?>

</div>
</body>
</html>
