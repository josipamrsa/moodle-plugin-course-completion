<?php
defined('MOODLE_INTERNAL') || die();
$capabilities = array(
    'report/coursecompletion:view' => array(
        'riskbitmask' => RISK_PERSONAL,
        'captype' => 'read',
        'contextlevel' => CONTEXT_COURSE,
        //'contextlevel' => CONTEXT_COURSE,
        'archetypes' => array(
            'teacher' => CAP_ALLOW,
            //'student' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        ),
        
        'clonepermissionsfrom' => 'moodle/site:viewreports'
    )
);
