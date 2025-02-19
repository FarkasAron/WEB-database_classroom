<?php
// Adatbázis kapcsolat beállítása
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "naplo";

// Kapcsolódás az adatbázishoz
$conn = new mysqli($servername, $username, $password, $dbname);

// Ellenőrizni kell a kapcsolatot
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Lekérdezzük az osztályokat, diákokat, tantárgyakat és jegyeket
$sql = "
    SELECT 
        c.code AS class_code,
        s.name AS student_name,
        sub.name AS subject_name,
        m.mark AS mark,
        m.date AS mark_date
    FROM 
        classes c
    JOIN 
        students s ON c.id = s.class_id
    JOIN 
        marks m ON s.id = m.student_id
    JOIN 
        subjects sub ON m.subject_id = sub.id
    ORDER BY 
        c.code, s.name, sub.name, m.date
";

$result = $conn->query($sql);

// Ellenőrizzük, hogy van-e eredmény
if ($result->num_rows > 0) {
    echo "<table border='1'>";
    echo "<thead><tr><th>Osztály</th><th>Diák neve</th><th>Tantárgy</th><th>Jegy</th><th>Dátum</th></tr></thead>";
    echo "<tbody>";

    // Kiírjuk az eredményeket
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['class_code'] . "</td>";
        echo "<td>" . $row['student_name'] . "</td>";
        echo "<td>" . $row['subject_name'] . "</td>";
        echo "<td>" . $row['mark'] . "</td>";
        echo "<td>" . $row['mark_date'] . "</td>";
        echo "</tr>";
    }

    echo "</tbody></table>";
} else {
    echo "Nincs adat az adatbázisban!";
}

// Kapcsolat bezárása
$conn->close();
?>
