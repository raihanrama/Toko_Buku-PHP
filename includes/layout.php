<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aqilafotokopi - <?php echo ucfirst(str_replace('.php', '', $current_page)); ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include 'navbar.php'; ?>

    <main>
        <?php echo $content; ?>
    </main>

    <?php include 'footer.php'; ?>

    <script src="assets/js/main.js"></script>
</body>
</html> 