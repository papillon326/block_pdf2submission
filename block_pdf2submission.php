<?php
  // author: KITA Toshihiro http://tkita.net/
  // @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

define('PDF2_SUBMISSION_SIZE_FORMAT_A4', 1);
define('PDF2_SUBMISSION_SIZE_FORMAT_B4', 2);

defined('MOODLE_INTERNAL') || die();

class block_pdf2submission extends block_base {
   
  function init() {
    $this->title = 'pdf2submission';
  }

  //  function instance_allow_config() { return true; }

  function get_content() {
    global $USER, $DB, $CFG;
	
    if ($this->content !== NULL) {
      return $this->content;
    }

    $course = $this->page->course; 
    $context = get_context_instance(CONTEXT_COURSE, $course->id);
    if ( !has_capability('mod/assignment:grade', $context) and $this->config->invisible ) {
      return;
    }

    $names = $DB->get_records('role_assignments',array('contextid' => $context->id));
    if (!has_capability('mod/assignment:grade', $context)){
      $names = array((object)array('userid' => $USER->id));
    }
    // $this->content->text .= var_dump($context);
    //    $items = $DB->get_records('assignment', array('course'=>$context->instanceid));
    $items = $DB->get_records('assign', array('course'=>$context->instanceid));
    $this->content->text .= '<form action="../blocks/pdf2submission/pdfgen.php" method="post">';
    if($items!=null){
      $this->content->text .= 'Cover sheets for:<br/>';
      $this->content->text .='<select name="itemid">';
      foreach($items as $item){
	$this->content->text .='<option value="'. $item->id .'">'.$item->name;
      }
      $this->content->text .= "</select>";
    }

    // get user
    if($names!=null){
      foreach($names as $name){
	$user = $DB->get_record('user',array('id'=>$name->userid));
	if ($course->id==30569){ // gensai literacy 2014
	  $key1 = mb_substr(fullname($user),5); // cut year info as '2014-'
	}else{
	  $key1 = fullname($user); 
	}
	$tmparr1[$key1] = 
	  '<input type="hidden" name="userids[]" value="' . $name->userid . '">' .
	  '<input type="hidden" name="usernames[]" value="' . $user->username . '">' .
	  '<input type="hidden" name="fullnames[]" value="'. fullname($user) . '">';
      }
      ksort($tmparr1);
      foreach($tmparr1 as $hiddendat){
	$this->content->text.= $hiddendat;
      }

      // paper size
      if ( $this->config->sizeformat == PDF2_SUBMISSION_SIZE_FORMAT_A4){
        $this->content->text.= '<input type="hidden" name="paper_size" value="A4">';
      }else if( $this->config->sizeformat == PDF2_SUBMISSION_SIZE_FORMAT_B4){
        $this->content->text.= '<input type="hidden" name="paper_size" value="B4">';
      }else{
        $this->content->text.= '<input type="hidden" name="paper_size" value="A4">';
      }

      // template PDF
      if (!empty($this->config->templatepath)){
      	$this->content->text.= '<input type="hidden" name="template_path" value="'.$this->config->templatepath.'">';
      }

      if ($this->config->qrnouserid){
	$this->content->text.= '<input type="hidden" name="qrnouserid" value="1">';
      }
	  
      $this->content->text.='<br/><input type="submit" value="Create"></form>';
    }
    $this->content->footer = ' ';

    return $this->content;
  }

  /*
  function cron(){
    global $CFG, $DB;
    include($CFG->dirroot.'/blocks/pdf2submission/pdfscan.php');
    mtrace("...pdf2submission done\n");
  }
  */
}
