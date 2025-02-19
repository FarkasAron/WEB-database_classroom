<?php
// Adatbázis kapcsolat beállítása
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "school";

// Diákok generálásához szükséges konstansok
require_once "request.php";

// Kattintásra adatbázis és táblák létrehozása
if (isset($_POST['create_database'])) {
    // Kapcsolódás az adatbázishoz
    $conn = new mysqli($servername, $username, $password);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Ellenőrizzük, hogy létezik-e már a 'school' adatbázis
    $sql = "SHOW DATABASES LIKE '$dbname'";
    $result = $conn->query($sql);

    if ($result->num_rows == 0) {
        // Ha nem létezik, akkor létrehozzuk
        $sql = "CREATE DATABASE $dbname CHARACTER SET utf8 COLLATE utf8_hungarian_ci";
        if ($conn->query($sql) === TRUE) {
            echo "Database '$dbname' created successfully.<br>";
        } else {
            echo "Error creating database: " . $conn->error;
        }
    }

    // Váltsunk az új adatbázisra
    $conn->select_db($dbname);

    // Adattáblák létrehozása
    create_tables($conn);
    // Véletlenszerű adatok beszúrása
    insert_random_data($conn);

    $conn->close();
}

// Adattáblák létrehozásához szükséges függvény
function create_tables($conn) {
    // students tábla
    $sql = "CREATE TABLE IF NOT EXISTS students (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        class_id INT NOT NULL
    )";
    $conn->query($sql);

    // subjects tábla
    $sql = "CREATE TABLE IF NOT EXISTS subjects (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL
    )";
    $conn->query($sql);

    // classes tábla (javítva a year oszloppal)
    $sql = "CREATE TABLE IF NOT EXISTS classes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        code VARCHAR(50) NOT NULL,
        year INT NOT NULL  -- Itt van a 'year' oszlop
    )";
    $conn->query($sql);

    // marks tábla
    $sql = "CREATE TABLE IF NOT EXISTS marks (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id INT NOT NULL,
        subject_id INT NOT NULL,
        mark INT NOT NULL,
        date DATE NOT NULL,
        FOREIGN KEY (student_id) REFERENCES students(id),
        FOREIGN KEY (subject_id) REFERENCES subjects(id)
    )";
    $conn->query($sql);
}


// Véletlenszerű adatok beszúrása
function insert_random_data($conn) {
    // Tantárgyak beszúrása
    $subject_ids = [];  // Tantárgy ID-k tárolása
    foreach (SUBJECTS as $subject) {
        $sql = "INSERT INTO subjects (name) VALUES ('$subject')";
        if ($conn->query($sql) === TRUE) {
            // Az utolsó beszúrt tantárgy ID-ját eltároljuk
            $subject_ids[$subject] = $conn->insert_id;
        } else {
            echo "Error inserting subject $subject: " . $conn->error . "<br>";
        }
    }

    // Diákok generálása minden osztályhoz (10-15 diák osztályonként)
    foreach (CLASSES as $class_code) {
        // Először lekérdezzük a class_id-t az osztálykód alapján
        $sql = "SELECT id FROM classes WHERE code = '$class_code'";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            $class_id = $result->fetch_assoc()['id'];  // Az osztály ID-ja

            $num_students = rand(MIN_CLASS_COUNT, MAX_CLASS_COUNT);  // Véletlenszerű szám a 10-15 közötti diákok számára

            for ($i = 0; $i < $num_students; $i++) {
                // Véletlenszerű diák név generálása
                $gender = rand(0, 1) ? 'men' : 'women';  // Véletlenszerűen férfi vagy nő
                $firstname = NAMES['firstnames'][$gender][array_rand(NAMES['firstnames'][$gender])];
                $lastname = NAMES['lastnames'][array_rand(NAMES['lastnames'])];
                $student_name = "$firstname $lastname";

                // Diák beszúrása
                $sql = "INSERT INTO students (name, class_id) VALUES ('$student_name', $class_id)";
                if ($conn->query($sql) === TRUE) {
                    $student_id = $conn->insert_id; 

                    //jegyek generálása 3-5
                    foreach ($subject_ids as $subject_id => $subject_name) {
                        $num_marks = rand(3, 5);  // 3-5 jegy minden tantárgyhoz

                        for ($j = 0; $j < $num_marks; $j++) {
                            $mark = rand(1, 5);  // Véletlenszerű jegy 1-5 között
                            $date = date('Y-m-d', strtotime("2025-01-01 +$j days"));  // Véletlenszerű dátum a 2025-ös év elejétől
                            $sql = "INSERT INTO marks (student_id, subject_id, mark, date) VALUES ($student_id, $subject_id, $mark, '$date')";
                            $conn->query($sql);
                        }
                    }
                } else {
                    echo "Hiba a tanulo beillesztésekor $student_name: " . $conn->error . "<br>";
                }
            }
        } else {
            echo "Class $class_code not found in the classes table.<br>";
        }
    }
}


?>

<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Adatbázis Létrehozása</title>
</head>
<body>
    <h1>Adatbázis Létrehozása</h1>
    <form method="POST">
        <button type="submit" name="create_database">Adatbázis létrehozása</button>
    </form>
</body>
</html>
