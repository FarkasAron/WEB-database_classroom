<?php
// Adatbázis kapcsolat
function getDbConnection() {
    $host = 'localhost';
    $db = 'classbook';
    $user = 'root';
    $pass = '';

    try {
        return new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    } catch (PDOException $e) {
        echo "Connection failed: " . $e->getMessage();
    }
}

// Adatbázis lekérdezések
function getClasses() {
    $pdo = getDbConnection();
    $stmt = $pdo->query('SELECT * FROM classes');
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getStudentsByClass($classId) {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare('SELECT * FROM students WHERE class_id = :class_id ORDER BY name');
    $stmt->execute(['class_id' => $classId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getClassAverage($classId) {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare('SELECT AVG(marks.grade) AS average FROM marks 
                            JOIN students ON marks.student_id = students.id 
                            WHERE students.class_id = :class_id');
    $stmt->execute(['class_id' => $classId]);
    return $stmt->fetch(PDO::FETCH_ASSOC)['average'];
}

function getStudentAverage($studentId) {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare('SELECT AVG(grade) AS average FROM marks WHERE student_id = :student_id');
    $stmt->execute(['student_id' => $studentId]);
    return $stmt->fetch(PDO::FETCH_ASSOC)['average'];
}

function getStudentSubjectAverage($studentId) {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare('SELECT subjects.name, AVG(marks.grade) AS average 
                            FROM marks 
                            JOIN subjects ON marks.subject_id = subjects.id 
                            WHERE marks.student_id = :student_id 
                            GROUP BY subjects.id');
    $stmt->execute(['student_id' => $studentId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getClassSubjectAverage($classId) {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare('SELECT subjects.name, AVG(marks.grade) AS average 
                            FROM marks 
                            JOIN students ON marks.student_id = students.id 
                            JOIN subjects ON marks.subject_id = subjects.id 
                            WHERE students.class_id = :class_id 
                            GROUP BY subjects.id');
    $stmt->execute(['class_id' => $classId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getTop10Students() {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare('SELECT students.name, AVG(marks.grade) AS average 
                            FROM marks 
                            JOIN students ON marks.student_id = students.id 
                            GROUP BY students.id 
                            ORDER BY average DESC 
                            LIMIT 10');
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getHallOfFame() {
    // 1. A legjobb osztály meghatározása
    $classes = getClasses();
    $bestClass = null;
    $bestClassAverage = 0;

    foreach ($classes as $class) {
        $classAverage = getClassAverage($class['id']);
        if ($classAverage > $bestClassAverage) {
            $bestClass = $class;
            $bestClassAverage = $classAverage;
        }
    }

    // 2. A legjobb 10 diák meghatározása
    $topStudents = getTop10Students();

    // 3. A legjobb osztály és legjobb diákok visszaadása
    return [
        'best_class' => $bestClass,
        'top_students' => $topStudents
    ];
}

function getAllStudentsWithMarks() {
    $pdo = getDbConnection();
    
    // Az összes tantárgy lekérése
    $subjects = getSubjects();  // Funkcióval lekérhetjük a tantárgyakat

    // Készítünk egy dinamikus SQL lekérdezést, ahol minden tantárgy egy oszlopot képvisel
    $subjectColumns = [];
    foreach ($subjects as $subject) {
        // Módosítjuk a lekérdezést, hogy az összes jegy jelenjen meg, vesszővel elválasztva
        $subjectColumns[] = "GROUP_CONCAT(CASE WHEN subjects.name = '{$subject['name']}' THEN marks.grade END ORDER BY marks.grade SEPARATOR ', ') AS '{$subject['name']}'";
    }
    $subjectColumnsSql = implode(', ', $subjectColumns);

    $stmt = $pdo->prepare("
        SELECT students.name, classes.name AS class_name, {$subjectColumnsSql}
        FROM marks
        JOIN students ON marks.student_id = students.id
        JOIN subjects ON marks.subject_id = subjects.id
        JOIN classes ON students.class_id = classes.id
        GROUP BY students.id, classes.name
        ORDER BY classes.name, students.name
    ");
    
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}




// Tantárgyak lekérése
function getSubjects() {
    $pdo = getDbConnection();
    $stmt = $pdo->query('SELECT * FROM subjects');
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Diákok jegyeinek lekérése osztályok alapján
function getAllStudentMarks($classId) {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare('SELECT students.id AS student_id, students.name AS student_name, students.class_id, subjects.name AS subject_name, marks.grade
                            FROM marks
                            JOIN students ON marks.student_id = students.id
                            JOIN subjects ON marks.subject_id = subjects.id
                            WHERE students.class_id = :class_id
                            ORDER BY students.name, subjects.name');
    $stmt->execute(['class_id' => $classId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


?>

<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>School Classbook</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f9;
        }
        header {
            background-color: #4CAF50;
            color: white;
            padding: 15px;
            text-align: center;
        }
        nav {
            background-color: #333;
            overflow: hidden;
        }
        nav a {
            float: left;
            display: block;
            color: white;
            padding: 14px 20px;
            text-align: center;
            text-decoration: none;
        }
        nav a:hover {
            background-color: #ddd;
            color: black;
        }
        .container {
            margin: 20px;
        }
        .content {
            background-color: white;
            padding: 20px;
            box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 10px;
            text-align: left;
        }
        th {
            background-color: #4CAF50;
            color: white;
        }
        .button {
            padding: 10px 15px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .button:hover {
            background-color: #45a049;
        }
        h2 {
            color: #333;
            text-align: center;
        }
    </style>
</head>
<body>

<header>
    <h1>School Classbook</h1>
</header>

<nav>
    <a href="?">Home</a>
    <a href="?action=classes">Classes</a>
    <a href="?action=top_students">Top 10 Students</a>
    <a href="?action=hall_of_fame">Hall of Fame</a>
    <a href="?action=all_students">All Students and Grades</a>
</nav>

<div class="container">
    <?php
    // Navigáció alapján eldöntjük, mi jelenjen meg
    if (isset($_GET['action']) && $_GET['action'] == 'classes') {
        // Osztályok megjelenítése
        $classes = getClasses();
        echo "<div class='content'>";
        echo "<h2>Classes</h2><table><tr><th>Class Name</th><th>Average Grade</th></tr>";
        foreach ($classes as $class) {
            $classAverage = getClassAverage($class['id']);
            echo "<tr><td><a href='?action=class_students&class_id={$class['id']}'>{$class['name']}</a></td><td>{$classAverage}</td></tr>";
        }
        echo "</table>";
        echo "</div>";
    } elseif (isset($_GET['action']) && $_GET['action'] == 'top_students') {
        // Top 10 diákok megjelenítése
        $top10Students = getTop10Students();
        echo "<div class='content'>";
        echo "<h2>Top 10 Students</h2><table><tr><th>Student Name</th><th>Average Grade</th></tr>";
        foreach ($top10Students as $student) {
            echo "<tr><td>{$student['name']}</td><td>{$student['average']}</td></tr>";
        }
        echo "</table>";
        echo "</div>";
    } elseif (isset($_GET['action']) && $_GET['action'] == 'hall_of_fame') {
        // Hall of Fame megjelenítése
        $hallOfFame = getHallOfFame();
        echo "<div class='content'>";
        echo "<h2>Hall of Fame</h2>";
        echo "<h3>Top Class: {$hallOfFame['best_class']['name']}</h3>";
        echo "<h4>Top 10 Students:</h4><table><tr><th>Student Name</th><th>Average Grade</th></tr>";
        foreach ($hallOfFame['top_students'] as $student) {
            echo "<tr><td>{$student['name']}</td><td>{$student['average']}</td></tr>";
        }
        echo "</table>";
        echo "</div>";
    } elseif (isset($_GET['action']) && $_GET['action'] == 'all_students') {
        // Minden diák és jegy megjelenítése egy sorban
        $allStudentsWithMarks = getAllStudentsWithMarks();
        echo "<div class='content'>";
        echo "<h2>All Students and Grades</h2>";
        echo "<table><tr><th>Student Name</th><th>Class</th>";
        
        // Dinamikusan megjelenítjük az oszlopokat az összes tantárgyhoz
        $subjects = getSubjects();
        foreach ($subjects as $subject) {
            echo "<th>{$subject['name']}</th>";
        }
        echo "</tr>";
        
        // Diákok adatainak megjelenítése
        foreach ($allStudentsWithMarks as $row) {
            echo "<tr><td>{$row['name']}</td><td>{$row['class_name']}</td>";
            foreach ($subjects as $subject) {
                // Megjelenítjük a diák adott tantárgyhoz tartozó jegyét
                echo "<td>{$row[$subject['name']]}</td>";
            }
            echo "</tr>";
        }
        
        echo "</table>";
        echo "</div>";
    }
     elseif (isset($_GET['action']) && $_GET['action'] == 'class_students' && isset($_GET['class_id'])) {
        // Osztályhoz tartozó diákok és jegyek táblázata
        $classId = $_GET['class_id'];
        $students = getStudentsByClass($classId);
        $classAverage = getClassAverage($classId);
        $subjectAverages = getClassSubjectAverage($classId);

        echo "<div class='content'>";
        echo "<h2>Students {$classId}</h2><table><tr><th>Student Name</th><th>Average</th></tr>";
        foreach ($students as $student) {
            $studentAverage = getStudentAverage($student['id']);
            echo "<tr><td>{$student['name']}</td><td>{$studentAverage}</td></tr>";
        }
        echo "</table>";
        echo "<h3>Class Average: {$classAverage}</h3>";
        echo "<h2>Class Subject Averages</h2><table><tr><th>Subject</th><th>Average</th></tr>";
        foreach ($subjectAverages as $subject) {
            echo "<tr><td>{$subject['name']}</td><td>{$subject['average']}</td></tr>";
        }
        echo "</table>";
        echo "</div>";
    } else {
        // Kezdőlap
        echo "<div class='content'>";
        echo "<h2>Welcome to the School Classbook</h2>";
        echo "<p>Choose an option from the menu above to get started.</p>";
        echo "</div>";
    }
    ?>
</div>

</body>
</html>
