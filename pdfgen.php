<?php
  // author: KITA Toshihiro http://tkita.net/
  // @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

// http://se-suganuma.blogspot.com/2009/02/tcpdf-45xxxfpdi-121.html
// ./tcpdf/examples/example_050.php 
// set_include_path(get_include_path() . PATH_SEPARATOR . dirname(__FILE__) . './fpdi');  
  //require('./tcpdf/config/lang/jpn.php');  
  //require('./tcpdf/tcpdf.php');  
  //require('../../lib/tcpdf/config/lang/jpn.php');  
require('../../lib/tcpdf/tcpdf.php');  
require('./fpdi/fpdi.php');   
  
$dirroot="../..";
require_once($dirroot."/config.php");

$template   = $_POST['template_path'];
$qrnouserid = $_POST['qrnouserid'];

//$objpdf = new FPDI('P', PDF_UNIT, 'A4'); 
$objpdf = new FPDI('P', PDF_UNIT, $_POST['paper_size']); 
$objpdf->setPrintHeader(false);
$objpdf->setPrintFooter(false);

global $DB;


$objpdf->SetFont("kozgopromedium", "", 10.5);
//$objpdf->SetFont("hysmyeongjostdmedium", "", 10.5);
//$objpdf->Image('logo.jpg', 10, 10, 20, 20, '', '', '', true, 150);

// set style for barcode
$barstyle = array(
	'border' => 2,
	'vpadding' => 'auto',
	'hpadding' => 'auto',
	'fgcolor' => array(0,0,0),
	'bgcolor' => false, //array(255,255,255)
	'module_width' => 1, // width of a single module in points
	'module_height' => 1 // height of a single module in points
);


if($qrnouserid=="1"){ // 学生番号なし
  $numpage= 150;
}else{
  $numpage= sizeof($_POST['userids']);
}


for($i=0; $i<$numpage; $i++){
  $objpdf->AddPage();

  $userid= $_POST['userids'][$i];
  $asno= $_POST['itemid'];

  //  $assignment = $DB->get_record("assignment", array("id"=>$asno));
  $assignment = $DB->get_record("assign", array("id"=>$asno));
  $acourse = $DB->get_record("course", array("id"=>$assignment->course));

  // $template= '';
  if (file_exists($template)){
    $objpdf->setSourceFile($template);
    $objpdf->useTemplate($objpdf->importPage(1));  
  }

  if($qrnouserid=="1"){ // 学生番号なし
    $objpdf->Text(75,7, $acourse->fullname);
    $objpdf->Text(75,15, $assignment->name);
    //    $objpdf->Text(72,25, "学生番号: ______________");
    //    $objpdf->Text(122,25, "氏名: _______________________");

    $objpdf->SetFont("kozgopromedium", "", 22);
    $objpdf->Text(39,12, "⇒ _ _ _");

    $objpdf->SetFont("kozgopromedium", "", 8);
    $objpdf->Text(47,22, "(ログイン後に");
    $objpdf->Text(46,25, "出る文字を記入)");

    $objpdf->SetFont("kozgopromedium", "", 10.5);
    $objpdf->SetXY(12,5);
    // QRCODE,M : QR-CODE Medium error correction
    $qrstr2= $CFG->wwwroot."/blocks/pdf2submission/sn.php?asno=$asno&sn=$i";
    //    $objpdf->Text(120,25, $qrstr2);
    $objpdf->write2DBarcode($qrstr2, 'QRCODE,M', '', '',26,26, $barstyle, 'N');

    $objpdf->SetFont("kozgopromedium", "", 8);
    $objpdf->Text(10,32, $i);
  }else{
    $objpdf->Text(60,7, $acourse->fullname);
    $objpdf->Text(60,15, $assignment->name);
    $objpdf->Text(60,23, $_POST['usernames'][$i]."  ".$_POST['fullnames'][$i]);

    $objpdf->SetXY(20,5);
    // QRCODE,M : QR-CODE Medium error correction
    $qrstr= "$asno,$userid,$acourse->shortname,".$_POST['usernames'][$i];
    //$qrstr= "$userid,$asno,$acourse->shortname,".$_POST['usernames'][$i];
    $objpdf->write2DBarcode($qrstr, 'QRCODE,M', '', '',28,28, $barstyle, 'N');
  }

  //  $pdf->Image("/tmp/pdfmake$i.png", ceil($minx+($size*0.3)), ceil($miny+($size*0.05)), ceil($size*$size*0.09));

  // get comment for the submitted assignment
  //$cm = get_coursemodule_from_instance("assignment", $assignment->id, $acourse->id);
  //$assignmentinstance = new assignment_base($cm->id, $assignment, $cm, $acourse);
  //$submission = $assignmentinstance->get_submission($userid);
  
  //  $submission = $DB->get_record('assignment_submissions',array('assignment'=>$asno, 'userid'=>$userid));
  $submission = $DB->get_record('assign_submission',array('assignment'=>$asno, 'userid'=>$userid));
  @$comment= $submission->submissioncomment;

  if ($comment != ""){
    $comment = "\n\n == comment == \n" . $comment;
    $comment = preg_replace('/\<br *\/*\>/',"\n",$comment);
    $comment = strip_tags($comment);
    //    $comment = mb_convert_encoding($comment,'EUC-JP','UTF-8');
    //    $pdf->SetFont(KOZMIN, '', $size);
    //    $pdf->SetFont(GOTHIC, '', $size);
    //    $pdf->Write(18*($size/20)*($size/20),$comment);
    //    $pdf->Write(18*($size/20)*($size/20),$comment);
    $objpdf->Text(60,80, $comment);
  }

}
$objpdf->Output("QRcodesheet-".$acourse->shortname.$asno.".pdf", 'D');
?> 
