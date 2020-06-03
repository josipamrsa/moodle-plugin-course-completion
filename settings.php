<?php

defined('MOODLE_INTERNAL') || die;

$ADMIN->add('reports', new admin_externalpage('reportcoursecompletion', get_string('pluginname', 
        'report_coursecompletion'), "$CFG->wwwroot/report/coursecompletion/index.php",'report/coursecompletion:view'));

// no report settings
$settings = null;
