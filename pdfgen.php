<?php
// author: KITA Toshihiro http://tkita.net/
// @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

// http://se-suganuma.blogspot.com/2009/02/tcpdf-45xxxfpdi-121.html
// ./tcpdf/examples/example_050.php 
// set_include_path(get_include_path() . PATH_SEPARATOR . dirname(__FILE__) . './fpdi');  
//require('./tcpdf/config/lang/jpn.php');  
//require('./tcpdf/tcpdf.php');  
//require('../../lib/tcpdf/config/lang/jpn.php');  

require_once __DIR__.'/../../config.php';
require_once __DIR__.'/../../lib/tcpdf/tcpdf.php';
require_once __DIR__.'/fpdi/fpdi.php';

$assignid    = required_param('assignid', PARAM_INT);
$template    = optional_param('template_path', '', PARAM_CLEAN);
$qrnouserid  = optional_param('qrnouserid', 0, PARAM_INT);
$paper_size  = required_param('paper_size', PARAM_CLEAN);
$orientation = optional_param('orientaion', 'P', PARAM_CLEAN);

$font = 'kozgopromedium';

$objpdf = new FPDI($orientation, PDF_UNIT, $paper_size); 
$objpdf->setPrintHeader(false);
$objpdf->setPrintFooter(false);

global $DB, $COURSE;

// validation
$assign = $DB->get_record('assign', array('id' => $assignid), '*', MUST_EXIST);
$course = $DB->get_record('course', array('id' => $assign->course));
// TODO: check course exist
// TODO: check current user is course editing teacher

$objpdf->SetFont($font, "", 10.5);
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



$context = context_course::instance($course->id);

$users = $DB->get_records_sql('SELECT * FROM {role_assignments} AS ra
                               LEFT JOIN user AS u ON ra.userid = u.id
                               WHERE contextid = ?
                               ORDER BY u.id ASC',
                               array($context->id));

$counter = 1;
foreach ($users as $user) {
    $objpdf->AddPage();
    
    $fullname = fullname($user);
    
    // $template= '';
    if (file_exists($template)) {
        $objpdf->setSourceFile($template);
        $objpdf->useTemplate($objpdf->importPage(1));  
    }
    
    if ($qrnouserid == "1") { // 学生番号なし
        $objpdf->Text(75,  7, $course->fullname);
        $objpdf->Text(75, 15, $assign->name);
        // $objpdf->Text(72,25, "学生番号: ______________");
        // $objpdf->Text(122,25, "氏名: _______________________");
        
        $objpdf->SetFont($font, "", 22);
        $objpdf->Text(39, 12, "⇒ _ _ _");
        
        $objpdf->SetFont($font, "", 8);
        $objpdf->Text(47, 22, "(ログイン後に");
        $objpdf->Text(46, 25, "出る文字を記入)");
        
        $objpdf->SetFont($font, "", 10.5);
        $objpdf->SetXY(12, 5);
        // QRCODE,M : QR-CODE Medium error correction
        $qrstr2= $CFG->wwwroot."/blocks/pdf2submission/sn.php?asno={$assignid}&sn={$counter}";
        $objpdf->write2DBarcode($qrstr2, 'QRCODE,M', '', '', 26, 26, $barstyle, 'N');
        
        $objpdf->SetFont($font, "", 8);
        $objpdf->Text(10, 32, $counter);
    } else {
        $objpdf->Text(60,  7, $course->fullname);
        $objpdf->Text(60, 15, $assign->name);
        $objpdf->Text(60, 23, $user->username."  ".$fullname);
        
        $objpdf->SetXY(20, 5);
        // QRCODE,M : QR-CODE Medium error correction
        $qrstr= "$assignid,$user->id,$course->shortname,".$user->username;
        $objpdf->write2DBarcode($qrstr, 'QRCODE,M', '', '',28, 28, $barstyle, 'N');
    }
    
    //  $pdf->Image("/tmp/pdfmake$i.png", ceil($minx+($size*0.3)), ceil($miny+($size*0.05)), ceil($size*$size*0.09));
    
    // get comment for the submitted assignment
    //$cm = get_coursemodule_from_instance("assignment", $assignment->id, $course->id);
    //$assignmentinstance = new assignment_base($cm->id, $assignment, $cm, $course);
    //$submission = $assignmentinstance->get_submission($userid);
    
    //  $submission = $DB->get_record('assignment_submissions',array('assignment'=>$asno, 'userid'=>$userid));
    $submission = $DB->get_record('assign_submission', array('assignment'=>$assignid, 'userid'=>$user->id));

    @$comment = $submission->submissioncomment;
    if ($comment != "") {
        $comment = "\n\n == comment == \n" . $comment;
        $comment = preg_replace('/\<br *\/*\>/', "\n", $comment);
        $comment = strip_tags($comment);
        // $comment = mb_convert_encoding($comment,'EUC-JP','UTF-8');
        // $pdf->SetFont(KOZMIN, '', $size);
        // $pdf->SetFont(GOTHIC, '', $size);
        // $pdf->Write(18*($size/20)*($size/20),$comment);
        // $pdf->Write(18*($size/20)*($size/20),$comment);
        $objpdf->Text(60, 80, $comment);
    }
    $counter++;
}
$objpdf->Output("QRcodesheet-{$course->shortname}{$assignid}.pdf", 'D');
