<nav style="background: #13375b; padding: 0.7rem 0;">
  <div style="display: flex; align-items: center; max-width: 1400px; margin: 0 auto;">
    <!-- Logo -->
    <div style="display: flex; align-items: center; min-width: 220px;">
      <img src="img\libmsLOGO.png" alt="LibMS Logo" style="height: 48px; margin-right: 10px;">
      <span style="font-size: 2rem; font-weight: 700; color: #2ecbfa; letter-spacing: 1px;">
    </div>
    <!-- Search Bar -->
    <div style="flex: 1; display: flex; justify-content: center; align-items: center;">
      <?php if (isset($_SESSION['student_id']) && isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'student'): ?>
        <span style="color: #fff; font-size: 1.1rem;">Welcome, <?php echo htmlspecialchars($_SESSION['student_name'] ?? 'Student'); ?>!</span>
      <?php elseif (isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>
        <span style="color: #fff; font-size: 1.1rem;">Welcome, Admin!</span>
      <?php else: ?>
        <span style="color: #fff; font-size: 1.1rem;">Welcome to the Library Management System!</span>
      <?php endif; ?>
    </div>
    <!-- Navigation Links -->
    <div style="display: flex; align-items: center; gap: 1.5rem; margin-left: 2rem;">
      <a href="index.php" style="color: #4eaaff; font-weight: 500; text-decoration: none; font-size: 1.1rem;">Home</a>
      <!-- <a href="books.php" style="color: #b0b8c1; font-weight: 500; text-decoration: none; font-size: 1.1rem;">Books</a> -->
      <a href="profile.php" style="color: #b0b8c1; font-weight: 500; text-decoration: none; font-size: 1.1rem; display: flex; align-items: center;">
        Profile
        <img src="assets/images/avatar.png" alt="Profile" style="height: 32px; width: 32px; border-radius: 50%; margin-left: 7px; object-fit: cover;">
      </a>
      <a href="my_schedules.php" style="background: #23272b; color: #fff; border-radius: 2rem; padding: 0.5rem 1.5rem; font-weight: 500; text-decoration: none; font-size: 1.1rem; margin-left: 0.5rem;">My Schedules</a>
    </div>
  </div>
</nav> 