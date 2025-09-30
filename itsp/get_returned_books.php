<?php
require 'config.php';
$student_id = intval($_GET['student_id'] ?? 0);
$returned_query = "
    SELECT b.title, bb.return_date
    FROM borrowed_books bb
    JOIN books b ON bb.book_id = b.book_id
    WHERE bb.student_id = ? AND bb.return_date IS NOT NULL
    ORDER BY bb.return_date DESC
";
$stmt = $conn->prepare($returned_query);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();
echo "<table class='table'><thead><tr><th>Title</th><th>Returned On</th></tr></thead><tbody>";
while ($row = $result->fetch_assoc()) {
    echo "<tr><td>" . htmlspecialchars($row['title']) . "</td><td>" . htmlspecialchars($row['return_date']) . "</td></tr>";
}
echo "</tbody></table>"; 