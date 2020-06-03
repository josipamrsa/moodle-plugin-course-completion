<?php

require_once('../../config.php');
require($CFG->dirroot . '/report/coursecompletion/index_form.php');
require('class/student.php'); // klasa studenta za spremanje podataka

// Sustav - kontekst
$systemcontext = context_system::instance();
$url           = new moodle_url('/report/coursecompletion/index.php');

// Osnovna dopuštenja
require_capability('report/coursecompletion:view', $systemcontext);

// Dohvat jezičnih postavki
$strcoursecompletion = get_string('coursecompletion', 'report_coursecompletion');
$strname       = get_string('name', 'report_coursecompletion');
$strtitle      = get_string('title', 'report_coursecompletion');

// Postavljanje objekta stranice
$PAGE->set_url($url);
$PAGE->set_context($systemcontext);
$PAGE->set_title($strtitle);
$PAGE->set_pagelayout('report');
$PAGE->set_heading($strtitle);

// ----------------------------- DOHVAT INICIJALNIH PODATAKA

// Potrebne globalne i pomoćne varijable
global $CFG, $PAGE, $USER, $DB; 
$currentuser = $USER->id;

// Dohvati kolegije profesora za prikaz u dropdownu

$sql = "SELECT c.id, c.shortname
       FROM mdl_course c 
       LEFT OUTER JOIN mdl_context cx ON c.id = cx.instanceid 
       LEFT OUTER JOIN mdl_role_assignments ra ON cx.id = ra.contextid AND ra.roleid = '3'
       LEFT OUTER JOIN mdl_user u ON ra.userid = u.id WHERE u.id = :currentuser;";

$courses = $DB->get_records_sql_menu($sql, array(
    'currentuser' => $currentuser));

$mform = new coursecompletion_form('', array(
    'courses' => $courses
));

$noRecord = true; // Za ispis kad nema zapisa

// ----------------------------- FORMA

echo $OUTPUT->header();
$mform->display();

// POST - vrijednosti sa forme
$courseid = $_POST[course];
$sd = $_POST[assesstimestart];
$ed = $_POST[assesstimeend];

// Potrebna konverzija datuma
$startdate = strtotime(join("-", $sd));
$enddate = strtotime(join("-", $ed));

// ----------------------------- LISTA STUDENATA NA KOLEGIJU

$sql =          "SELECT u.id
				FROM mdl_user u
				INNER JOIN mdl_user_enrolments ue ON ue.userid = u.id
				INNER JOIN mdl_enrol e ON e.id = ue.enrolid
				INNER JOIN mdl_course c ON e.courseid = c.id
				WHERE c.id = :courseid";

$students = $DB->get_records_sql_menu($sql, array(
    'courseid' => $courseid
));

$studentid = array();
foreach ($students as $k=>$v) { array_push($studentid, $k); }

// ----------------------------- LISTA ZADAĆA NA KOLEGIJU

// Dohvaća zadaće sa zadanim vremenskim periodom (ignorira one koje nemaju postavljen datum)

$sql = "SELECT a.id
       FROM mdl_assign a
       INNER JOIN mdl_course c on c.id = a.course
       WHERE c.id = :courseid AND a.allowsubmissionsfromdate > :tstart AND a.duedate < :tend"; 

$assignments = $DB->get_records_sql_menu($sql, array(
    'courseid' => $courseid,
    'tstart' => $startdate,
    'tend' => $enddate
));

$assignid = array();
foreach ($assignments as $k=>$v) { array_push($assignid, $k); }

// ----------------------------- LISTA TESTOVA NA KOLEGIJU

// Dohvaća testove sa zadanim vremenskim periodom (ignorira one koje nemaju postavljen datum)

$sql = "SELECT q.id
        FROM mdl_quiz q
        INNER JOIN mdl_course c on c.id = q.course
        WHERE q.timeopen>0 and q.timeclose>0 and c.id = :courseid AND q.timeopen > :tstart AND q.timeclose < :tend"; 

$quizzes = $DB->get_records_sql_menu($sql, array(
    'courseid' => $courseid,
    'tstart' => $startdate,
    'tend' => $enddate
));

$quizid = array();
foreach ($quizzes as $k=>$v) { array_push($quizid, $k); }

// ----------------------------- LISTA LEKCIJA NA KOLEGIJU

// Dohvaća lekcije sa zadanim vremenskim periodom (ignorira one koje nemaju postavljen datum)

$sql = "SELECT l.id
        FROM mdl_lesson l
        INNER JOIN mdl_course c on c.id = l.course
        WHERE c.id = :courseid AND l.available > :tstart AND l.deadline < :tend"; 

$lessons = $DB->get_records_sql_menu($sql, array(
    'courseid' => $courseid,
    'tstart' => $startdate,
    'tend' => $enddate
));

$lessonid = array();
foreach ($lessons as $k=>$v) { array_push($lessonid, $k); }


// ----------------------------- POKUŠAJI ZADAĆA, LEKCIJA, TESTOVA

// POMOĆNE FUNKCIJE

// Uparuje id aktivnosti sa id-ovima studenata koji su je riješili/pokušali rješiti
function activityPair($temp, $orig) {
    $qAtt = array();
    foreach ($orig as $o) { $qAtt[$o]=array(); } 
    foreach ($temp as $qt) { 
        $t = explode('-', $qt);   
        if (array_key_exists($t[1], $qAtt)) {
            array_push($qAtt[$t[1]], $t[0]);
    }
    
  }
    
    return $qAtt;
}

// Skladišti podatke o studentu
function breakRecord($strInfo, $obj) {
    $sAtt = explode('#', $strInfo);
    $obj->id = $sAtt[0];
    $obj->firstname = $sAtt[1];
    $obj->lastname = $sAtt[2];
    $obj->email = $sAtt[3];
    return $obj;
}


// VARIJABLE I STRUKTURE

// Pomoćne za testove
$quizAttemptsTemp = array();
$quizAttempts = array();

// Pomoćne za zadaće
$assignSubmissionsTemp = array();
$assignSubmissions = array();

// Pomoćne za lekcije
$lessonSolvedTemp = array();
$lessonSolved = array();


// Provjera rješenosti testova

foreach ($quizid as $qid) { 
    $sql =      "SELECT CONCAT(u.id, '-', q.id) AS pair
                FROM mdl_quiz_attempts qa
                INNER JOIN mdl_quiz q on q.id = qa.quiz
                INNER JOIN mdl_user u on u.id = qa.userid
                WHERE q.id = :qid";

    $qaids = $DB->get_records_sql_menu($sql, array('qid' => $qid));
    foreach ($qaids as $k=>$v) { array_push($quizAttemptsTemp, $k); }
    $quizAttempts = activityPair($quizAttemptsTemp, $quizid);
}

// Provjera rješenosti lekcija

foreach ($lessonid as $lid) {
    $sql =      "SELECT CONCAT(u.id, '-', l.id) AS pair
                FROM mdl_lesson_attempts la
                INNER JOIN mdl_lesson l on l.id = la.lessonid
                INNER JOIN mdl_user u on u.id = la.userid
                WHERE l.id = :lid";

    $laids = $DB->get_records_sql_menu($sql, array('lid' => $lid));
    foreach ($laids as $k=>$v) { array_push($lessonSolvedTemp, $k); }
    $lessonSolved = activityPair($lessonSolvedTemp, $lessonid);
}

// Provjera predaje zadaća

foreach ($assignid as $aid) {
    $sql =      "SELECT CONCAT(u.id, '-', a.id) AS pair
                FROM mdl_assignment_submissions ans
                INNER JOIN mdl_assignment a on a.id = ans.assignment
                INNER JOIN mdl_user u on u.id = ans.userid
                WHERE a.id = :aid";

    $aaids = $DB->get_records_sql_menu($sql, array('aid' => $aid));
    foreach ($aaids as $k=>$v) { array_push($assignSubmissionsTemp, $k); }
    $assignSubmissions = activityPair($assignSubmissionsTemp, $assignid);
}


// ----------------------------- ISPIS STUDENATA S DUGOVIMA

// Ako je odabran kolegij

if (isset($_POST[course]) AND $_POST[course]==0) { echo "<b>INFO: <p style='color:red'>Odaberite kolegij!</p></b>"; }

elseif ($startdate > $enddate) { 
    echo "<b>INFO: <p style='color:red'>Datum početka ne može biti veći od datuma završetka!</p></b>"; 
    $noRecord = false;
}

elseif (isset($_POST[course])) {
    echo "<br><em>Period od " . join(".", $sd) . ". do " . join(".", $ed) . ".</em>";
    echo "<br><h1><b>Popis studenata sa dugovima </b></h1><hr></hr><br>";
}
    
foreach ($studentid as $sid) { 
    
    // Dohvati svakog studenta sa kolegija
    
    $sql =      "SELECT u.id, CONCAT(u.id, '#', u.firstname, '#', u.lastname, '#', u.email) AS basicinfo
                FROM mdl_user u               
                WHERE u.id = :uid";
    $sInfo = $DB->get_records_sql_menu($sql, array('uid' => $sid));
    
    // Zabilježi osnovne podatke - ime, prezime, email
    
    foreach($sInfo as $s) {    
        $studentObj = new Student;
        $studentInfo = breakRecord($s, $studentObj);
        $currId = $studentInfo->id;
        
        // Provjeri nalazi li se studentov id u pokušajima testova
        
        foreach($quizAttempts as $k=>$v) {
            if (!in_array($currId, $v) || empty($v)) {  
                $sql =      "SELECT q.id, CONCAT (q.name, ' (', FROM_UNIXTIME(q.timeopen, '%d.%m.%Y.'), ' - ', FROM_UNIXTIME(q.timeclose, '%d.%m.%Y.'), ')') as INFO
                            FROM mdl_quiz q               
                            WHERE q.id = :qid";
                $qInfo = $DB->get_records_sql_menu($sql, array('qid' => $k));
                foreach ($qInfo as $key=>$val) { array_push($studentInfo->quizzes, $val); }
            }                 
        }
        
        // Provjeri nalazi li se studentov id u pokušajima lekcija
        
        foreach($lessonSolved as $k=>$v) {
            if (!in_array($currId, $v) || empty($v)) {  
                $sql =      "SELECT l.id, CONCAT (l.name, ' (', FROM_UNIXTIME(l.available, '%d.%m.%Y.'), ' - ', FROM_UNIXTIME(l.deadline, '%d.%m.%Y.'), ')') as INFO
                            FROM mdl_lesson l               
                            WHERE l.id = :lid";
                $lInfo = $DB->get_records_sql_menu($sql, array('lid' => $k));
                foreach ($lInfo as $key=>$val) { array_push($studentInfo->lessons, $val); }
                
            }                 
        }
        
        // Provjeri nalazi li se studentov id u predajama zadaća
        
        foreach($assignSubmissions as $k=>$v) {
            if (!in_array($currId, $v) || empty($v)) {  
                $sql =      "SELECT a.id, CONCAT (a.name, ' (', a.allowsubmissionsfromdate, ' - ', a.duedate, ')') as INFO
                            FROM mdl_assign a               
                            WHERE a.id = :aid";
                $aInfo = $DB->get_records_sql_menu($sql, array('aid' => $k));
                foreach ($aInfo as $key=>$val) { array_push($studentInfo->assignments, $val); }
            }                 
        }
        
        // Ako studentove kolekcije imaju bilo koji zapis unutra, ispiši podatke o studentu i neriješene stavke
        
        if ((count($studentInfo->quizzes) != 0) or (count($studentInfo->lessons) != 0) or (count($studentInfo->assignments) != 0)) {
            $noRecord = false;
            echo "<b>" . $studentInfo->firstname . " " . $studentInfo->lastname . "</b> <a href='mailto:'" . $studentInfo->email . "'>" . $studentInfo->email . "</a><br><br>";
            if (count($studentInfo->quizzes) != 0) {
                echo "<em>Neriješeni testovi: </em><br>";
                foreach ($studentInfo->quizzes as $q) {echo $q . "<br>";}
                echo "<br>";
            }
            
            else {
                echo "<em>Neriješeni testovi: </em><br>";
                echo "Nema zapisa.<br><br>";
            }
                                 
            if (count($studentInfo->lessons) != 0) {
                echo "<em>Neriješene lekcije: </em><br>";
                foreach ($studentInfo->lessons as $l) {echo $l . "<br>";}
                echo "<br>";
            }
            
            else {
                echo "<em>Neriješene lekcije: </em><br>";
                echo "Nema zapisa.<br>";
            }
            
            if (count($studentInfo->assignments) != 0) {
                echo "<em>Zadaće koje nisu predane: </em><br>";
                foreach ($studentInfo->assignments as $a) {echo $a . "<br>";}
                echo "<br>";
            }
            
            else {
                echo "<em>Zadaće koje nisu predane: </em><br>";
                echo "Nema zapisa.<br>";
            }
            
            echo "<br><hr></hr><br>";
        }      
    }           
}

// Ako nema rezultata iz queryja, ispis poruke

if (isset($_POST[course]) AND $_POST[course]!=0 AND $noRecord) { echo "<em>Nema zapisa</em>"; }

echo $OUTPUT->footer(); 


















