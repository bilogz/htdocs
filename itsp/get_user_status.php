<?php
require 'config.php';
$student_id = intval($_GET['student_id'] ?? 0);
$query = "
    SELECT bb.record_id, b.title, bb.borrow_date, bb.due_date, bb.return_date, bb.status, bb.admin_confirmed_return
    FROM borrowed_books bb
    JOIN books b ON bb.book_id = b.book_id
    WHERE bb.student_id = ?
    ORDER BY bb.borrow_date DESC
";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();
echo "<table class='table'><thead><tr><th>Title</th><th>Borrow Date</th><th>Due Date</th><th>Return Date</th><th>Status</th><th>Action</th></tr></thead><tbody>";
while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($row['title']) . "</td>";
    echo "<td>" . htmlspecialchars($row['borrow_date']) . "</td>";
    echo "<td>" . htmlspecialchars($row['due_date']) . "</td>";
    echo "<td>" . ($row['return_date'] ? htmlspecialchars($row['return_date']) : '<span class=\'badge badge-warning\'>Not Returned</span>') . "</td>";
    echo "<td>" . htmlspecialchars($row['status']) . "</td>";
    echo "<td>";
    if ($row['admin_confirmed_return'] == 0 && $row['return_date']) {
        echo "<button class='btn btn-success btn-sm' onclick='confirmReturn(" . $row['record_id'] . ")'>Confirm Return</button>";
    } elseif ($row['admin_confirmed_return'] == 1) {
        echo "<button class='btn btn-secondary btn-sm' disabled>Returned</button>";
    } else {
        echo "<button class='btn btn-light btn-sm' disabled>No Action</button>";
    }
    echo "</td>";
    echo "</tr>";
}
echo "</tbody></table>";
?>
<script>
function confirmReturn(recordId) {
    if (confirm('Are you sure you want to confirm the return of this book?')) {
        $.ajax({
            url: 'confirm_return.php',
            type: 'POST',
            data: { record_id: recordId },
            success: function(response) {
                alert(response);
                location.reload();
            }
        });
    }
}
</script> 