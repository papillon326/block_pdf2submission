<?php


defined('MOODLE_INTERNAL') || die();


/**
 * Form for editing 
 *
 * author: KITA Toshihiro http://tkita.net/
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_pdf2submission_edit_form extends block_edit_form {
  protected function specific_definition($mform) {
    global $DB;

    // Section header title according to language file.
    $mform->addElement('header', 'configheader', get_string('blocksettings', 'block'));

    $mform->addElement('selectyesno', 'config_invisible', 'invisible to students: ');

    $paperoptions = array(
      PDF2_SUBMISSION_SIZE_FORMAT_A4 => 'A4',
      PDF2_SUBMISSION_SIZE_FORMAT_B4 => 'B4');
    $mform->addElement('select', 'config_sizeformat', 'paper size: ', $paperoptions);
    $mform->setDefault('config_sizeformat', PDF2_SUBMISSION_SIZE_FORMAT_A4);

    $mform->addElement('text', 'config_templatepath', 'template PDF path: ', array('size' => 40));

    $mform->addElement('selectyesno', 'config_qrnouserid', 'QR code with serial number instead of userid: ');

    $mform->addElement('html', '<div style="margin:2em;"><a href="../blocks/pdf2submission/sn2.php" target="_BLANK">Jump to page for mapping serial number to userid</a></div>');

    //    $mform->setDefault('config_templatepath', './template.pdf');
  }
	
	
}
