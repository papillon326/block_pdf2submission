<?php
  // author: KITA Toshihiro http://tkita.net/
  // @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

$inputfolder= "/home/ec2-user/pdfs/";  // Where the PDF files are

require_once("../../config.php");

global $DB, $CFG;

$tmpd= $CFG->dataroot . "/temp/";  // The directory for temporary files
$warning_mailto= $CFG->supportemail;  


require_login();

$useridarr=array();
function qrsn2userid($assignno, $qrsn){
  global $tmpd, $useridarr;

  if ( !isset($useridarr) || count($useridarr)==0 ){
    $dir = scandir($tmpd);
    foreach($dir as $ent){
      if(preg_match('/^pdf2submission/',$ent)){
	$dfile= $tmpd."/".$ent;
	//    $origfilenamebase= str_replace(".pdf","",$origfilename);
	if (($handle = fopen($dfile, "r")) !== false) {
	  while (($line = fgetcsv($handle, 1000, ",")) !== false) {
	    // "$asno,$sn,$USER->id,$USER->username\n"
	    $asno = $line[0];
	    $sn = $line[1];
	    $userid = $line[2];
	    $useridarr[$asno][$sn] = $userid;
	    //	  echo "$asno $sn =".$useridarr[$asno][$sn]."\n";
	  } 
	}
	fclose($handle); 
      }
    }
  }
  return $useridarr[$assignno][$qrsn];
}


$mailmsg = "";
if (!is_readable($inputfolder)){
  $mailmsg .= "cannot read $inputfolder .\n";
  mail($warning_mailto,'pdf2submission (err)',$mailmsg);
}
if (!is_writable($inputfolder)){
  $mailmsg .= "not writable: $inputfolder .\n";
  mail($warning_mailto,'pdf2submission (error)',$mailmsg);
}

//echo "<html><body bgcolor=\"lightgreen\">\n";
echo "<html><body><form action=\"./sn2.php\" method=post>";

printf("<div style=\"background-color:%s;padding:1em;\">","ivory");
echo "<input type=\"submit\">";

// trimming the sheet iamges for cutting off unnecesarry part
//$clip="clip:rect(0px,800px,240px,0px); position:absolute;";
//$dheight="height:240px;";

/*
if ( isset($_POST["cliptop"]) ){
  $cliptop = $_POST["cliptop"];
  $clipright = $_POST["clipright"];
  $clipbuttom = $_POST["clipbuttom"];
  $clipleft = $_POST["clipleft"];
}else{
  $cliptop = 0;
  $clipright = 800;
  $clipbuttom = 240;
  $clipleft = 0;
}
echo("<span style=\"margin-left:60px;\"> clip:rect(");
printf("<input type=text name=\"cliptop\" value=\"%s\" size=2>",$cliptop);
printf("<input type=text name=\"clipright\" value=\"%s\" size=2>",$clipright);
printf("<input type=text name=\"clipbuttom\" value=\"%s\" size=2>",$clipbuttom);
printf("<input type=text name=\"clipleft\" value=\"%s\" size=2>",$clipleft);
printf(")</span>");

$clip="clip:rect(${cliptop}px,${clipright}px,${clipbuttom}px,${clipleft}px); position:absolute;";
// rect（上,右,下,左）
$dheight="height:".$clipbuttom."px;";
*/

$dirs0 = scandir($inputfolder);

echo "<span style=\"margin-left:3em;\"></span><select name=\"cfolder\">";
foreach($dirs0 as $dir0){
  if ($dir0 != "." && $dir0 !=".."){
    if ($dir0 == $_POST['cfolder']){
      echo "<option value=\"$dir0\" selected>$dir0</option>";
    }else{
      echo "<option value=\"$dir0\">$dir0</option>";
    }
  }
}
echo "</select>";

printf("</div>");

// $currentfolder= $inputfolder."/kumadai01-555471a49dff6/";
$currentfolder= $inputfolder."/".$_POST['cfolder']."/";

if ( !file_exists("$currentfolder/splitdone") ){  exit; }
$dir3 = dir($currentfolder);

$co=0;
while(($ent3 = $dir3->read()) !== FALSE){
  if(preg_match('/^(\w+).pdf.QRSN$/',$ent3,$match)){
    $co++;
    //    $filename= $ent3;
    $filenamebase= $match[1];
    list($asno,$userid,$coursename,$username) = preg_split( '/__/',$filenamebase );
    $mailmsg.=  $filenamebase.": ".$asno." ".$userid." ".$coursename." ".$username."\n";

    $key1= $filenamebase;
    unset($users);  unset($user);

    if ( preg_match('/QRSN(\d+)/',$userid,$match0) ){
      $qnum= $match0[1];
      $userid0= qrsn2userid($asno, $qnum);
      if ( $userid0 ){
	$users = $DB->get_records('user',array('id'=>$userid0));
	$user = reset($users);
      }
    }
    if ( isset($_POST[$key1]) && $_POST[$key1]!="" ){
      $users = $DB->get_records_sql("SELECT * FROM {user} WHERE idnumber LIKE :idnumber", ['idnumber' => '%'.$_POST[$key1].'%']);
      $user = reset($users);
    }
    //    if ( (isset($_POST[$key1]) && $_POST[$key1]=="") || 
	 //	 (isset($_POST[$key1]) && count($users)!=1) ){
    if ( !isset($users) || (isset($users) && count($users)!=1) ){
      echo count($users);
      printf("<div style=\"background-color:%s;padding:1em;\">",($co%2==0)?"red":"salmon");
    }else{
      printf("<div style=\"background-color:%s;padding:1em;\">",($co%2==0)?"#aa88ee":"#ddbbff");
    }
    preg_match('/QRSN(\d+)/',$userid,$match2); $qnum= $match2[1];
    printf("asno = %d",$asno);
    printf(", qrsn = %s",$qnum);
    if (count($users)==1){  printf(", userid = %d",$user->id);  }
    printf("<small><code>  ($filenamebase)</code></small><br />");
    printf("<div style=\"$dheight\"><img style=\"$clip\" width=600px src=\"data:image/gif;base64,%s\"></div>",base64_encode(file_get_contents($currentfolder.$filenamebase.".jpg")) );
//    printf("<div style=\"$dheight\"><img style=\"$clip\" width=600px src=\"./tmpdebug01/%s\"></div>",$filenamebase.".jpg"); // debug
    printf("user.idnumber = <input type=text name=\"%s\" value=\"%s\" size=4>",$key1,$_POST[$key1]);
    //    printf(" %s %s %s",$user->idnumber,$user->lastname,$user->firstname);
    if ($users){
      if (count($users)==1){
      printf(" %s <span style=\"margin-right:2em;\"></span> %s",$user->idnumber,fullname($user));
      }else{
        echo "<div style=\"margin:1em;\"><code>****** non-unique result!  (".count($users)." users) : </code></div>\n";
	echo "<ul>";
	foreach($users as $user0){
	  printf("<li>%s <span style=\"margin-right:2em;\"></span> %s</li>",$user0->idnumber,fullname($user0));
	}
	echo "</ul>";
      }
    }
    //    if ( isset($_POST[$key1]) && isset($users) && count($users)==1 ){
    if ( isset($users) && count($users)==1 ){
      if($_POST['dorename']=='yes'){
	$newfname= $asno."__".$user->id."__".$coursename."__".$user->idnumber."__.pdf"; /////
	$filenamefull= $currentfolder."/".$ent3;
	if (copy($filenamefull, $currentfolder."/".$newfname)){
	  echo "<div>successfully fixed as ".$newfname."</div>\n";
	  unlink($filenamefull);
	  unlink($currentfolder.$filenamebase.".jpg");
	  //  copy($jpgfile, $currentfolder.$newfnamejpg);
        }
    //    $subret= submit_file($filename, $currentfolder);
      }
    }
    printf("</div>\n");
  }
  if(preg_match('/^(\w+).pdf.QRXX$/',$ent3,$match)){
    $filenamebase= $match[1];
    //    printf("<div style=\"$dheight\"><img style=\"$clip\" width=600px src=\"data:image/gif;base64,%s\"></div>",base64_encode(file_get_contents($currentfolder.$filenamebase.".jpg")) );
    echo $filenamebase;
    printf("<div><img width=600px src=\"data:image/gif;base64,%s\"></div>",base64_encode(file_get_contents($currentfolder.$filenamebase.".jpg")) );
  }
}
printf("<div style=\"background-color:%s;padding:1em;\">","ivory");
echo "<div style=\"background-color:%s;padding:1em;\">";
echo "<input type=\"checkbox\" name=dorename value=yes> Fix userid</div>";
echo "<input type=\"submit\"></div>";

//if ($mailmsg != ""){  mail($warning_mailto,'pdf2submission sn2',$mailmsg); }

?>
