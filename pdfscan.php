<?php
// author: KITA Toshihiro http://tkita.net/
// @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

// Make sure that QR codes are successfully decoded. If not so, some person's sheets are concatenated to other person's sheets !!

// ***** also edit $inputfolder in sn2.php ******
$inputfolder= "/home/ec2-user/pdfs/";  // Where the PDF files are FIXME: configureable or upload by form

//define('ONEPAGEFOREACH', true); // no multiple pages for each, that means if no QR in a page, that is marked as QRXX (QR recognition failure).

define('QRSN', 'QRSN'); // prefix for dummy userid
global $gs_command, $convert_command, $zbarimg_command, $tmpd, $mailmsg;
global $DB, $CFG;
// zbarimg command can be installed by 'yum install zbar'

$zbarimg_command = "zbarimg ";  // FIXME:
$convert_command = "convert";  // FIXME:
$gs_command = "gs";  // FIXME:

$tmpd = $CFG->dataroot . "/temp/";  // The directory for temporary files

$warning_mailto= $CFG->supportemail;  

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');  // It must be included from a Moodle page
}

//require_once(dirname(__FILE__) . '/../../config.php');
//$dirroot= $CFG->dirroot;
//$datdir=  $CFG->dataroot;
//require_once($dirroot."/config.php");
//require_once($dirroot."/mod/assignment/lib.php");
//require_once($dirroot.'/mod/assignment/type/uploadsingle/assignment.class.php');
//require_once($CFG->dirroot."/mod/assign/lib.php");
//require_once($dirroot.'/mod/assign/type/uploadsingle/assignment.class.php');

require_once $CFG->dirroot.'/lib/tcpdf/tcpdf.php';  
require_once $CFG->dirroot.'/blocks/pdf2submission/fpdi/fpdi.php';

// From http://www.setasign.com/products/fpdi/demos/concatenate-fake/
class ConcatPdf extends FPDI
{
    public $files = array();
    
    public function setFiles($files) {
        $this->files = $files;
    }
    
    public function concat() {
        foreach($this->files AS $file) {
            $pageCount = $this->setSourceFile($file);
            for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                $tplIdx = $this->ImportPage($pageNo);
                $s = $this->getTemplatesize($tplIdx);
                $this->AddPage($s['w'] > $s['h'] ? 'L' : 'P', array($s['w'], $s['h']));
                $this->useTemplate($tplIdx);
            }
        }
    }
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

// process all the PDF files in $inputfolder
$dir = dir($inputfolder);
while (($ent = $dir->read()) !== FALSE) {
    if(preg_match('/.pdf$/', $ent)){
        $origfilename = $ent;
        $origfilenamebase = str_replace(".pdf", "", $origfilename);
        $currentfolder = "$inputfolder/$origfilenamebase/";
        if (!file_exists($currentfolder)){
            $ret0 = split_into_files_of_each_user($currentfolder, $inputfolder.$origfilename);
            if ($mailmsg != "") mail($warning_mailto, 'pdf2submission(in progress..)', $mailmsg);
            if ($ret0 > 1) {
                exec("touch $currentfolder/splitdone");
                $tmpcf1= rtrim($currentfolder,"/");
                rename($tmpcf1, $tmpcf1."-".uniqid());
                unlink($inputfolder.$origfilename); // remove if successfully split
            }
        }
    }
}

// process all the folders in $inputfolder
$dir = dir($inputfolder);
while (($ent = $dir->read()) !== FALSE) {
    if(is_dir("$inputfolder/$ent")){
        $mailmsg .= $ent." ";
        $origdir= $ent;
        $currentfolder = "$inputfolder/$origdir/";
        if (!file_exists("$currentfolder/splitdone")) continue;
        // $subret= 1;  $submiterr= FALSE;
        // rescan currentfolder and submit
        $dir3 = dir($currentfolder);
        
        while (($ent3 = $dir3->read()) !== FALSE) {
            // if(eregi(".pdf$",$ent3)){
            if (preg_match('/.pdf$/',$ent3)) {
                $filename= $ent3;
                //	$subret= submit_file($filename, $currentfolder, $datdir);
                //	mtrace("submit ".$filename."\n"); // debug
                $subret= submit_file($filename, $currentfolder);
                //	if ($subret<0){ $submiterr= TRUE; }
            }
        }
        
        // if (!$submiterr){
        //     $tmpcf1= rtrim($currentfolder,"/");
        //     rename($tmpcf1, $tmpcf1."-".uniqid());
        //     unlink($inputfolder.$origfilename); // remove if successfully submitted
        // }
    }
}

// if ($mailmsg != "") mail($warning_mailto, 'pdf2submission', $mailmsg);

file_put_contents("/tmp/pdf3.txt", "\n".date("r").$mailmsg, FILE_APPEND); // debug

function split_into_files_of_each_user($currentfolder, $origfile){
    //  global $pdftk_command, $tmpd, $mailmsg;
    global $tmpd, $mailmsg;
    $burstfolder = $currentfolder . "burst/";
    //  exec("mkdir $currentfolder");
    mkdir($currentfolder);  // TODO: add exception handling
    //  exec("mkdir $burstfolder");
    mkdir($burstfolder);  // TODO: add exception handling
    //  exec("cd $burstfolder; $pdftk_command $origfile burst");
    // as default, burst to pg_0001.pdf, pg_0002.pdf, ...
    
    // instead of pdftk ------------------
    // From http://tt-house.com/2009/04/php-split-pdf.html
    $temp_pdf = new FPDI();
    $_page = $temp_pdf->setSourceFile($origfile);
    unset($temp_pdf);
    
    for ($i=1; $i<=$_page; $i++) {
        $_write_pdf = new FPDI();
        $_write_pdf->setSourceFile($origfile);
        $_tmp_info = $_write_pdf->importPage($i);
        $_write_pdf->addPage();
        $_write_pdf->useTemplate($_tmp_info);
        // $_write_pdf->Output(“hoge_”.$i.”pd"”,”F”);
        $_write_pdf->Output( $burstfolder.sprintf("pg_%04d.pdf",$i), 'F');
        unset ($_write_pdf);
    }
    // instead of pdftk ^^^^^^^^^^^^^^^^^^^^
    
    for($co=1; $co<10000; $co++){
        $filename= sprintf("pg_%04d.pdf",$co);
        $filenamefull= $burstfolder.$filename; 
        if (!file_exists($filenamefull)) break;
        list($asno, $userid, $coursename, $username, $jpgfile) = decode_qr($filenamefull);
        file_put_contents("/tmp/pdf2.txt",date("r")." ".$co." ".$asno."\n", FILE_APPEND ); // debug
        if (is_null($userid) || is_null($asno)){ // No QR or read err
            if (defined('ONEPAGEFOREACH')) { // read err
                // Rename the files with dummy data
                $newfnamebase = "000ASNO__USERID__coursen__".sprintf("pg_%04d",$co).uniqid()."__";
                $newfname = $newfnamebase.".pdf.QRXX";
                $newfnamejpg= $newfnamebase.".jpg";
                if (copy($filenamefull, $currentfolder.$newfname)) {
                    unlink($filenamefull);
                    copy($jpgfile, $currentfolder.$newfnamejpg);
                }
                $mailmsg .= ",".$newfname."\n";
            } else { // maybe No QR
                // append to previous QRcode sheet
                if (!is_null($lastQRfile)){
                    $tmpcat1 = $tmpd."/pdfscan-cat".uniqid().".pdf";
                    //	exec("$pdftk_command $lastQRfile $filenamefull cat output $tmpcat1");
                    $pdf = new ConcatPdf();
                    $pdf->setFiles(array($lastQRfile, $filenamefull));
                    $pdf->concat();
                    $pdf->Output($tmpcat1, 'I');
                    
                    copy($tmpcat1, $lastQRfile);  unlink($tmpcat1);
                    // remove concatenated file (actually, moved to 'cat' folder) 
                    exec("mkdir $burstfolder"."/cat/");  // FIXME: rewrite in php code
                    copy($filenamefull, $burstfolder."/cat/".$filename);  // FIXME: rewrite in php code
                    unlink($filenamefull);
                }
            }
        } else { // sheet with QRcode
            // Rename the files after the recognized userID, etc.
            if (preg_match('/^'.QRSN.'/', $userid)) {
                $newfnamebase= $asno."__".$userid."__".$coursename."__".$username."__";
                $newfname=    $newfnamebase.".pdf.".QRSN;
                $newfnamejpg= $newfnamebase.".jpg";
                copy($jpgfile, $currentfolder.$newfnamejpg);  // FIXME: rewrite in php code
            } else {
                $newfname= $asno."__".$userid."__".$coursename."__".$username."__.pdf";
            }
            
            $lastQRfile= $currentfolder.$newfname;
            if (copy($filenamefull, $lastQRfile)){
                unlink($filenamefull);
            }
            $mailmsg .= ",".$newfname."\n";
        }
    } // for($co=1; $co<10000; $co++)
    return $co;
}

// TODO: rewrite this function to return class
function decode_qr($pdffile){
    global $gs_command, $convert_command, $zbarimg_command, $tmpd, $mailmsg;  // FIXME: not use global vars
    preg_match('|/(\w+)[.]pdf$|', $pdffile, $match);
    $fnamebase= $match[1];
    
    $tmpjpg1= $tmpd."/pdfscan-".$fnamebase."-1-".uniqid().".jpg";
    $tmpjpg2= $tmpd."/pdfscan-".$fnamebase."-2-".uniqid().".jpg";
    //  $execcmd1="$gs_command -dSAFER -dBATCH -dNOPAUSE -sDEVICE=jpeg -r200 -sOutputFile=$tmpjpg1 -dFirstPage=1 -dLastPage=1 $pdffile";
    $execcmd1 = "$gs_command -dSAFER -dBATCH -dNOPAUSE -sDEVICE=jpeg -r400 " .
                "-sOutputFile=$tmpjpg1 -dFirstPage=1 -dLastPage=1 $pdffile";
    exec($execcmd1);
    // you may need some tuning for preprocessing QR code image
    //  $execcmd2="$convert_command -blur 2 $tmpjpg1 $tmpjpg2";
    //  $execcmd2="$convert_command -blur 1 $tmpjpg1 $tmpjpg2";
    $execcmd2 = "$convert_command -white-threshold 98% -geometry 2400x3600 -crop 2000x1000+60+60 " .
                "$tmpjpg1 $tmpjpg2";
    exec($execcmd2);
    $execdmtx = "$zbarimg_command -q --raw $tmpjpg2";
    $outs = $userid = $asno = $coursename = $username = NULL;
    exec($execdmtx, $outs, $ret);
    $outstr = implode("",$outs);
    if( preg_match('|http:|',$outstr) ){
        preg_match('|/sn[.]php[?]asno=(\d+)&sn=(\d+)|', $outstr, $match);
        $asno = $match[1];
        $userid = QRSN.$match[2];
        $coursename = "coursen";
        $username = QRSN.$fnamebase;
    } else {
        $qrparams = preg_split('/[,\n]/', $outstr);
        if (count($qrparams) >= 4) {
            list($asno, $userid, $coursename, $username) = preg_split('/[,\n]/', $outstr);
        }
    }
    //  list($userid,$asno,$coursename,$username) = preg_split( '/[,\n]/',implode("",$outstr) );
    
    $mailmsg .= "\npdfscan.php, " . $execcmd1 .", " .$execcmd2 .", " . $execdmtx . ", (" . $outstr . "),
    // userid:" . $userid . ",asno:" . $asno . ",". $coursename . "," . $username . ",";
    //  unlink($tmpjpg1);  unlink($tmpjpg2);
    return array($asno, $userid, $coursename, $username, $tmpjpg2);
}

//function submit_file($filename,$currentfolder,$datdir){
function submit_file($filename, $currentfolder){
    global $mailmsg;
    global $DB,$CFG,$USER;
    
    require_once $CFG->dirroot.'/mod/assign/lib.php';
    //  require_once($CFG->dirroot."/mod/assign/locallib.php");
    
    // filename to asno, uid, ...
    list($asno, $userid, $coursename, $username) = preg_split('/__/', $filename);
    $mailmsg .= $filename.": ".$asno." ".$userid." ".$coursename." ".$username."\n";
    $assignment = $DB->get_record("assign", array("id"=>$asno));
    $courseid = $assignment->course;
    
    //  if ( is_null($courseid) || $courseid=="" ) return -4;
    // copy the file
    /*
    $outputfolder= $datdir."/".$courseid."/moddata/assignment/".$asno."/".$userid."/";
    exec("mkdir -p $outputfolder");
    if ( file_exists($outputfolder.$filename) ){
        $mailmsg.= "*ERROR: $outputfolder$filename exists. do not copy the same submission twice.\n\n";
        return -2;
    }else{
        if ( copy($currentfolder.$filename, $outputfolder.$filename) ){
            //      chown($outputfolder.$filename,"apache");
            unlink($currentfolder.$filename);
        }else{
            $mailmsg.= "*ERROR: copy to assignment folder failed.\n\n";
            return -3;  // do not submit
        }
    }
    */
    
    $course = $DB->get_record("course", array("id" => $assignment->course));
    $cm = get_coursemodule_from_instance("assign", $assignment->id, 0, FALSE, MUST_EXIST);
    $context = context_module::instance($cm->id);
    //  $assign = new assign($context,$cm,$course);
    
    $params = array('assignment'=>$assignment->id, 'userid'=>$userid, 'groupid'=>0);
    $submissions = $DB->get_records('assign_submission', $params, 'attemptnumber DESC', '*', 0, 1);
    if ($submissions) {
        $submission = reset($submissions);
        $submission->timemodified = time();
        $result= $DB->update_record('assign_submission', $submission);
    } else {
        // from assign/locallib.php get_user_submission()
        $submission = new stdClass();
        // $submission->assignment   = $assign->get_instance()->id;
        $submission->assignment    = $assignment->id;
        $submission->userid        = $userid;
        $submission->timecreated   = time();
        $submission->timemodified  = $submission->timecreated;
        $submission->status        = 'submitted';
        $submission->groupid       = 0;
        $submission->attemptnumber = 0;
        $submission->latest = 1;
        $sid = $DB->insert_record('assign_submission', $submission);
        $submission->id = $sid;
    }
    // $submission->status = ASSIGN_SUBMISSION_STATUS_SUBMITTED;
    
    /*
    // From assign/locallib.php  save_submission()
    $instance = $assign->get_instance();
    if ($instance->teamsubmission) {
        $submission = $assign->get_group_submission($userid, 0, true);
    } else {
        $submission = $assign->get_user_submission($userid, true);
    }
    if ($instance->submissiondrafts) {
        $submission->status = ASSIGN_SUBMISSION_STATUS_DRAFT;
    } else {
        $submission->status = ASSIGN_SUBMISSION_STATUS_SUBMITTED;
    }
    */
    
    // copy the file to submit
    // code from http://docs.moodle.org/dev/File_API
    //  $context = get_context_instance(CONTEXT_COURSE, $preferences->backup_course);
    $fs = get_file_storage();
    $fileinfo = array(
        'contextid'    => $context->id, 
        'component'    => 'assignsubmission_file',
        'filearea'     => 'submission_files',
        'itemid'       => $submission->id,
        'filepath'     => '/',
        'filename'     => $filename,
        'timecreated'  => time(),
        'timemodified' => time(),
        'userid'       => $userid
    );
    // 'filearea'=>ASSIGNSUBMISSION_FILE_FILEAREA,
    // Get file
    $cfile = $fs->get_file($fileinfo['contextid'], $fileinfo['component'], $fileinfo['filearea'],
                           $fileinfo['itemid'], $fileinfo['filepath'], $fileinfo['filename']);
    // Delete it if it exists
    if ($cfile) $cfile->delete();
    if ($fs->create_file_from_pathname($fileinfo, $currentfolder.$filename)) {
        unlink($currentfolder.$filename);
    } else {
        exec("touch $currentfolder/file_create_err");
        return -5;
    }

    // from assign/submission/file/locallib.php save()
    $filesubmission = new stdClass();
    //  $filesubmission->numfiles = $this->count_files($submission->id, ASSIGNSUBMISSION_FILE_FILEAREA);
    $filesubmission->numfiles = 1;
    $filesubmission->submission = $submission->id;
    //  $filesubmission->assignment = $assign->get_instance()->id;
    $filesubmission->assignment = $assignment->id;
    
    if (!$DB->get_record('assignsubmission_file',
      array('assignment' => $filesubmission->assignment, 'submission' => $filesubmission->submission))) {
        $DB->insert_record('assignsubmission_file', $filesubmission);
        $mailmsg.=  "*** submitted : $filename\n\n";
    }
    return 1;
}
