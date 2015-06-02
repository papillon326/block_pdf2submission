<?php
require('../../config.php');
//require_once("$CFG->dirroot/mod/folder/locallib.php");

$asno = optional_param('asno', 0, PARAM_INT);  // Course module ID
$sn  = optional_param('sn', 0, PARAM_INT);   // Folder instance id

require_login();

$tmpstr= "ABCDEFGHJKLMNPQRSTUVWXYZ";
$ch1= substr($tmpstr, rand(0,mb_strlen($tmpstr)-1), 1);
$ch2= substr($tmpstr, rand(0,mb_strlen($tmpstr)-1), 1);
$ch3= substr($tmpstr, rand(0,mb_strlen($tmpstr)-1), 1);
$conf1= "$ch1$ch2$ch3";

$sdata= "$asno,$sn,$USER->id,$USER->username,$conf1\n";

//$tmpfname = tempnam("/tmp", date("c")."_");
$tmpfname = tempnam($CFG->dataroot."/temp/", "pdf2submission".date("c")."_");

$handle = fopen($tmpfname, "w");
fwrite($handle, $sdata);
fclose($handle);

echo "<font size=30>";
echo "<pre> $ch1 $ch2 $ch3</pre>";
echo "</font>";
echo "<a href=\"$CFG->wwwroot\">jump to Moodle top</a>";

/*
echo "<pre>";
$sndir= $CFG->dataroot."/temp/";
//$dir = dir($sndir);
$dir = scandir($sndir);
//while(($ent = $dir->read()) !== FALSE){
foreach($dir as $ent){
  //  if(eregi(".pdf$",$ent)){
  if(preg_match('/^pdf2submission/',$ent)){
    $dfile= $sndir.$ent;
    //    $origfilenamebase= str_replace(".pdf","",$origfilename);
    if (($handle = fopen($dfile, "r")) !== false) {
      while (($line = fgetcsv($handle, 1000, ",")) !== false) {
	// "$asno,$sn,$USER->id,$USER->username\n"
	$asno = $line[0];
	$sn = $line[1];
	$userid = $line[2];
	$useridarr[$asno][$sn] = $userid;
	echo "$asno $sn =".$useridarr[$asno][$sn]."\n";
      } 
    }
    fclose($handle); 
  }
}
echo "</pre>";
*/



