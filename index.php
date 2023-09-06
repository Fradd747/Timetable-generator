<?php session_start(); ?>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Timetable generator</title>
    <script src="https://cdn.tailwindcss.com/3.3.0"></script>    
    <script type="text/javascript" src="https://cdn.jsdelivr.net/jquery/latest/jquery.min.js"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
    <style>
        @font-face {
            font-family: 'themix';
            src: url('fonts/themix-bold.ttf') format('truetype');
            font-style: bold;
            font-weight: normal;
        }
        @font-face {
            font-family: 'themix';
            src: url('fonts/themix-normal.ttf') format('truetype');
            font-style: normal;
            font-weight: normal;
        }
        @font-face {
            font-family: 'skautbold';
            src: url('fonts/skautbold.ttf') format('truetype');
            font-style: normal;
            font-weight: normal;
        }
    </style>
</head>
<body>
    <div class="flex justify-center bg-gray-200 items-center w-screen h-screen">
        <div class="w-full max-w-xl bg-white rounded-xl shadow-lg p-3">
            <h1 class="font-['skautbold'] text-lg mb-2">Generátor harmonogramu</h1>
            <form action="export.php" method="post" class="flex flex-col gap-3 items-start font-['themix']" enctype="multipart/form-data">
                <div class="flex flex-col gap-1">
                    <label for="file">ICS export z Google kalendáře</label>
                    <input type="file" name="file" id="file" accept=".ics" required>
                </div>
                <div class="flex flex-col gap-1">
                    <label for="template">Šablona</label>
                    <select name="template" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block min-w-[12rem] w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                        <option value="default" selected>Výchozí</option>
                        <option value="ursus">Ursus</option>
                    </select>
                </div>
                <div class="flex flex-col gap-1">
                    <label for="file">Rozsah exportu</label>
                    <input type="text" name="daterange" class="p-1 border rounded-lg" required/>
                    <?php 
                        if (isset($_SESSION['error'])) {
                            echo '<div class="bg-red-200 border border-red-400 text-red-700 text-sm p-1 rounded-md relative" role="alert">'.$_SESSION['error'].'</div>';
                            unset($_SESSION['error']);
                        }
                    ?>
                </div>
                <button type="submit" class="shadow-lg border rounded-lg p-2 hover:scale-105 transition">Vygenerovat</button>
            </form>
        </div>
    </div>
</body>
<script>
    $(function() {
        $('input[name="daterange"]').daterangepicker({
            opens: 'left'
        });
    });
</script>
</html>

