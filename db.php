<?php

// Adatbázis kapcsolat létrehozása (ha nem létezik, akkor létrehozza)
$dsn = 'mysql:host=localhost;charset=utf8';
$username = 'root';
$password = '';
$options = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION];

try {
    $pdo = new PDO($dsn, $username, $password, $options);
    $pdo->exec("CREATE DATABASE IF NOT EXISTS classbook CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE classbook");
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Adatbázis táblák létrehozása
$pdo->exec("CREATE TABLE IF NOT EXISTS classes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    year INT NOT NULL,
    name VARCHAR(10) NOT NULL
);");

$pdo->exec("CREATE TABLE IF NOT EXISTS students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    class_id INT NOT NULL,
    FOREIGN KEY (class_id) REFERENCES classes(id)
);");

$pdo->exec("CREATE TABLE IF NOT EXISTS subjects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL
);");

$pdo->exec("CREATE TABLE IF NOT EXISTS marks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    subject_id INT NOT NULL,
    grade INT NOT NULL,
    FOREIGN KEY (student_id) REFERENCES students(id),
    FOREIGN KEY (subject_id) REFERENCES subjects(id)
);");

// Konstansok
const MARKS_COUNT = 5;
const MIN_CLASS_COUNT = 10;
const MAX_CLASS_COUNT = 15;
const SUBJECTS = ['math', 'history', 'biology', 'chemistry', 'physics', 'informatics', 'alchemy', 'astrology'];
const CLASSES = ['11A', '11B', '11C', '11D', '11E', '11F', '12A', '12B', '12C', '12D', '12E', '12F'];
const NAMES = [
    'lastnames' => ['Major', 'Riz', 'Kard', 'Pum', 'Víz', 'Kandisz', 'Patta', 'Para', 'Pop', 'Remek'],
    'firstnames' => [
        'men' => ['Ottó', 'Pál', 'Elek', 'Simon', 'Ödön', 'Kálmán', 'Áron', 'Elemér', 'Szilárd', 'Csaba'],
        'women' => ['Anna', 'Virág', 'Nóra', 'Zita', 'Ella', 'Viola', 'Emma', 'Mónika', 'Dóra', 'Blanka']
    ]
];

// Tantárgyak feltöltése
foreach (SUBJECTS as $subject) {
    $stmt = $pdo->prepare("INSERT INTO subjects (name) VALUES (?) ON DUPLICATE KEY UPDATE name=name");
    $stmt->execute([$subject]);
}

// Osztályok feltöltése
foreach (CLASSES as $class) {
    $year = (strpos($class, '11') !== false) ? 2024 : 2025;
    $stmt = $pdo->prepare("INSERT INTO classes (year, name) VALUES (?, ?) ON DUPLICATE KEY UPDATE name=name");
    $stmt->execute([$year, $class]);
    $classId = $pdo->lastInsertId();

    // Véletlenszerű diákok létrehozása
    $studentsCount = rand(MIN_CLASS_COUNT, MAX_CLASS_COUNT);
    for ($i = 0; $i < $studentsCount; $i++) {
        $lastname = NAMES['lastnames'][array_rand(NAMES['lastnames'])];
        $gender = (rand(0, 1) == 0) ? 'men' : 'women';
        $firstname = NAMES['firstnames'][$gender][array_rand(NAMES['firstnames'][$gender])];
        $fullName = "$lastname $firstname";

        $stmt = $pdo->prepare("INSERT INTO students (name, class_id) VALUES (?, ?)");
        $stmt->execute([$fullName, $classId]);
        $studentId = $pdo->lastInsertId();

        // Jegyek létrehozása
        $subjectStmt = $pdo->query("SELECT id FROM subjects");
        $subjects = $subjectStmt->fetchAll(PDO::FETCH_COLUMN);
        foreach ($subjects as $subjectId) {
            $marksCount = rand(3, 5);
            for ($j = 0; $j < $marksCount; $j++) {
                $grade = rand(1, 5);
                $stmt = $pdo->prepare("INSERT INTO marks (student_id, subject_id, grade) VALUES (?, ?, ?)");
                $stmt->execute([$studentId, $subjectId, $grade]);
            }
        }
    }
}

echo "Database and tables created, data populated successfully!";
?>
