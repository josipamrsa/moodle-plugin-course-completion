<?php

class Student {
    // ----------------------------- POLJA
    
    // Informacije
    public $id;
    public $firstname;
    public $lastname;
    public $email;
    
    // Neriješeni zadaci
    public $lessons = array();
    public $quizzes = array();
    public $assignments = array();
    
    // ----------------------------- METODE
    
    // Informacije
    function set_id($sid) { $this->id = $sid; }
    function get_id() { return $this->id; }
    
    function set_firstname($fname) { $this->firstname = $fname; }
    function get_firstname() { return $this->firstname; }
    
    function set_lastname($lname) { $this->lastname = $lname; }
    function get_lastname() { return $this->lastname; }
    
    function set_email($mail) { $this->email = $mail; }
    function get_email() { return $this->email; }
    
    // Neriješeni zadaci
    function set_lessons($ls) { $this->lessons = $ls; }
    function get_lessons() { return $this->lessons; }
    
    function set_quizzes($qz) { $this->quizzes = $qz; }
    function get_quizzes() { return $this->quizzes; }
    
    function set_assign($agn) { $this->assign = $agn; }
    function get_assign() { return $this->assign; }
}