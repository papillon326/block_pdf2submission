<?php
  // author: KITA Toshihiro http://tkita.net/
  // @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

define('PDF2_SUBMISSION_SIZE_FORMAT_A4', 1);
define('PDF2_SUBMISSION_SIZE_FORMAT_B4', 2);

defined('MOODLE_INTERNAL') || die();

class block_pdf2submission extends block_base
{
    function init() {
        $this->title = get_string('pluginname', 'block_pdf2submission');
    }
    
    public function has_config() {
        return true;
    }
    
    public function instance_allow_config() {
        return true;
    }
    
    function get_content() {
        global $USER, $DB, $PAGE, $CFG, $COURSE;
        
        if ($this->content !== null) {
            return $this->content;
        }
        
        if (empty($this->config)) {
            $this->config = new stdClass();
        }
        
        // init config
        if (!isset($this->config->sizeformat)) {
            $this->config->sizeformat = PDF2_SUBMISSION_SIZE_FORMAT_A4;
        }

        $context = context_course::instance($course->id);
        if (!has_capability('mod/assignment:grade', $context) && $this->config->invisible) {
            return '';
        }
        

        $assignments = $DB->get_records('assign', array('course' => $COURSE->id));
        
        $this->content = new stdClass();
        $this->content->text .= '<form action="../blocks/pdf2submission/pdfgen.php" method="post">';

        if($assignments != null){
            $this->content->text .= 'Cover sheets for:<br/>'
                                 .  '<select name="assignid">';
            foreach($assignments as $assignment){
                $this->content->text .='<option value="'. $assignment->id .'">'.$assignment->name;
            }
            $this->content->text .= "</select>";
        }
        
        $users = $DB->get_records('role_assignments', array('contextid' => $context->id));
        
        // check student
        if(count($users) > 0){
            // paper size
            switch ($this->config->sizeformat) {
                default:  // default A4
                case PDF2_SUBMISSION_SIZE_FORMAT_A4:
                    $this->content->text .= '<input type="hidden" name="paper_size" value="A4">';
                    break;
                    
                case PDF2_SUBMISSION_SIZE_FORMAT_B4:
                    $this->content->text.= '<input type="hidden" name="paper_size" value="B4">';
                    break;
            }
            
            // template PDF
            if (!empty($this->config->templatepath)) {
                $this->content->text .= '<input type="hidden" name="template_path" value="'.$this->config->templatepath.'">';
            }
            
            if ($this->config->qrnouserid == 1){
                $this->content->text .= '<input type="hidden" name="qrnouserid" value="1">';
            }
            
            $this->content->text .= '<br/><input type="submit" value="Create"></form>';
        } else {
            $this->content->text .= 'any student is not assigned in this course!';
        }
        $this->content->footer = ' ';
        
        return $this->content;
    }
}
