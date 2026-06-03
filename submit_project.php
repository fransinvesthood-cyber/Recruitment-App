<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="styles.css">
    <title>Submit Project</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6f8;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 450px;
            margin: 80px auto;
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0px 6px 18px rgba(0,0,0,0.1);
            text-align: center;
        }

        h1 {
            font-size: 1.5rem;
            margin-bottom: 20px;
            color: #333;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #444;
        }

        input[type="file"] {
            padding: 10px;
            width: 100%;
            border: 1px solid #ccc;
            border-radius: 8px;
            margin-bottom: 15px;
            background: #fafafa;
            cursor: pointer;
        }

        .btn {
            background: #007bff;
            color: white;
            padding: 10px 18px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1rem;
            transition: background 0.3s ease;
        }

        .btn:hover {
            background: #0056b3;
        }

        /* Popup styling */
        .popup {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0px 6px 18px rgba(0,0,0,0.2);
            z-index: 1000;
            text-align: center;
            width: 300px;
        }

        .popup.show {
            display: block;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translate(-50%, -55%); }
            to { opacity: 1; transform: translate(-50%, -50%); }
        }

        .popup.success {
            border-left: 6px solid #28a745;
        }

        .popup.error {
            border-left: 6px solid #dc3545;
        }

        .popup p {
            font-size: 1rem;
            margin-bottom: 15px;
        }

        .popup .btn {
            background: #007bff;
            padding: 8px 15px;
            border-radius: 6px;
        }

        .popup.success .btn {
            background: #28a745;
        }

        .popup.error .btn {
            background: #dc3545;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📁 Submit Your Project</h1>
        <form id="projectForm" enctype="multipart/form-data">
            <label for="project_zip">Choose ZIP file:</label>
            <input type="file" name="project_zip" id="project_zip" accept=".zip" required>
            <button type="submit" class="btn">Upload Project</button>
        </form>
    </div>

    <div id="popup" class="popup">
        <p id="popupMessage"></p>
        <button class="btn" onclick="closePopup()">OK</button>
    </div>

    <script>
        const form = document.getElementById('projectForm');
        const popup = document.getElementById('popup');
        const popupMessage = document.getElementById('popupMessage');

        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(form);

            fetch('Upload_project.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                popupMessage.textContent = data.message;
                popup.classList.remove('success', 'error');
                popup.classList.add(data.status === 'success' ? 'success' : 'error');
                popup.classList.add('show');
                if (data.status === 'success') form.reset();
            })
            .catch(() => {
                popupMessage.textContent = 'Something went wrong.';
                popup.classList.add('error', 'show');
            });
        });

        function closePopup() {
            popup.classList.remove('show');
        }
    </script>
</body>
</html>
