<?php
require 'config.php'; // Include your database connection

// Query to fetch data from both the books and ebooks tables
$query = "SELECT b.book_id, b.title, b.cover_image, b.available_stock, e.id AS ebook_id, e.title AS ebook_title
          FROM books b
          LEFT JOIN ebooks e ON b.book_id = e.book_id"; // Adjust the table and field names accordingly

$result = $conn->query($query);

if ($result->num_rows > 0) {
    while ($book = $result->fetch_assoc()) {
        // Fetch data for each book
        $book_id = $book['book_id'];
        $title = $book['title'];
        $cover_image = $book['cover_image'];
        $available_stock = $book['available_stock'];
        $ebook_id = $book['ebook_id'];
        $ebook_title = $book['ebook_title'] ? $book['ebook_title'] : null; // If ebook_title is null, don't use it

        ?>
        <div class="col-lg-3 col-sm-6">
            <div class="item">
                <img src="assets/images/<?php echo $cover_image; ?>" alt="<?php echo $title; ?>">
                <h4><?php echo $title; ?><br><span>Stocks: <span class="stock-count" data-id="<?php echo $book_id; ?>"><?php echo $available_stock; ?></span></span></h4>
                <a href="borrow.php?book_id=<?php echo $book_id; ?>" class="btn btn-primary">Borrow</a>
                
                <?php if ($ebook_id): // Check if an ebook exists for this book ?>
                    <a href="read.php?ebook_id=<?php echo $ebook_id; ?>" class="btn btn-info">Read eBook</a>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
} else {
    echo "No books available.";
}
?>
