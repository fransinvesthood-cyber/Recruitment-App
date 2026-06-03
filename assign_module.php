<!DOCTYPE html>
<html>
<head>
    <title>Assign Training Module</title>
    <style>
        body { font-family: Arial; margin: 30px; }
        .container { max-width: 600px; margin: auto; }
        .form-group { margin-bottom: 20px; }
        label { display: block; font-weight: bold; }
        select, button { width: 100%; padding: 10px; }
        .success { color: green; }
        .error { color: red; }
    </style>
</head>
<body>
<div class="container">
    <h2>Assign Training Module to Consultant</h2>

    <?php if (isset($success)) echo "<p class='success'>$success</p>"; ?>
    <?php if (isset($error)) echo "<p class='error'>$error</p>"; ?>

    <form method="POST">
        <div class="form-group">
            <label>Select Consultant:</label>
            <select name="user_id" required>
                <option value="">-- Select Consultant --</option>
                <?php while ($row = $consultants->fetch_assoc()) : ?>
                    <option value="<?= $row['user_id'] ?>"><?= htmlspecialchars($row['username']) ?></option>
                <?php endwhile; ?>
            </select>
        </div>

        <div class="form-group">
            <label>Select Training Module:</label>
            <select name="module_id" required>
                <option value="">-- Select Module --</option>
                <?php while ($row = $modules->fetch_assoc()) : ?>
                    <option value="<?= $row['module_id'] ?>"><?= htmlspecialchars($row['title']) ?></option>
                <?php endwhile; ?>
            </select>
        </div>

        <button type="submit">Assign Training</button>
    </form>
</div>
</body>
</html>