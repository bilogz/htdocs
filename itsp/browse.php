<?php
// Fetch all books
$books_query = "SELECT * FROM books ORDER BY book_id DESC";
$books_result = $conn->query($books_query); 