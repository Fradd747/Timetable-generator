<?php session_start(); ?>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Timetable generator</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="styles.css">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/jquery/latest/jquery.min.js"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
</head>
<body x-data="{ export_popup: false, event_popup: false }">
    <!-- calendar export tutorial -->
    <div x-cloak x-show="export_popup" x-transition class="w-full h-full fixed top-0 left-0 flex justify-center items-center z-10">
        <div @click.outside="export_popup = false" class="w-full max-w-2xl mx-3 md:m-0 p-3 bg-white rounded-xl myshadow relative">
            <img @click="export_popup = false" src="images/icons/cross.svg" class="w-6 absolute top-2 z-20 right-2 cursor-pointer transition hover:scale-110">
            <ul aria-label="Activity feed" role="feed" class="relative flex flex-col gap-6 py-6 pl-6 before:absolute before:top-0 before:left-6 before:h-full h-fit before:border before:-translate-x-1/2 before:border-slate-200 before:border-dashed after:absolute after:top-6 after:left-6 after:bottom-6 after:border after:-translate-x-1/2 after:border-slate-200 ">
                <li class="relative pl-3">
                    <h3 class="font-bold">Jak exportovat ICS soubor z Google kalendáře?</h3>
                </li>
                <li class="relative pl-6 flex items-center">
                    <span class="absolute left-0 z-10 flex items-center justify-center w-8 h-8 -translate-x-1/2 rounded-full text-slate-700 ring-2 ring-white bg-slate-200 ">
                        1
                    </span>
                    <div class="flex flex-col flex-1 gap-0">
                        <p class="text-xs text-slate-500">Otevřete <a class="text-blue-500" href="https://calendar.google.com/">Google Kalendář</a> ve svém prohlížeči.</p>
                    </div>
                </li>
                <li class="relative pl-6 flex items-start">
                    <span class="absolute left-0 z-10 flex items-center justify-center w-8 h-8 -translate-x-1/2 rounded-full text-slate-700 ring-2 ring-white bg-slate-200 ">
                        2
                    </span>
                    <div class="flex flex-col flex-1 gap-0">
                        <p class="text-xs text-slate-500">Na levé straně okna Kalendáře klikněte na tři vodorovné tečky vedle názvu kalendáře který chcete exportovat. Klepněte na "<b>Nastavení a sdílení</b>" (Settings and sharing).</p>
                        <img src="images/settings.jpg" class="w-80 rounded-md border border-gray-200 mt-1">
                    </div>
                </li>
                <li class="relative pl-6 flex items-start">
                    <span class="absolute left-0 z-10 flex items-center justify-center w-8 h-8 -translate-x-1/2 rounded-full text-slate-700 ring-2 ring-white bg-slate-200 ">
                        3
                    </span>
                    <div class="flex flex-col flex-1 gap-0">
                        <p class="text-xs text-slate-500">Klikněte na tlačítko "<b>Exportovat kalendář</b>" (Export calendar) a soubor se začne stahovat do vašeho počítače.</p>
                        <img src="images/download.jpg" class="w-80 rounded-md border border-gray-200 mt-1">
                    </div>
                </li>
                <li class="relative pl-6 flex items-center">
                    <span class="absolute left-0 z-10 flex items-center justify-center w-8 h-8 -translate-x-1/2 rounded-full text-slate-700 ring-2 ring-white bg-slate-200 ">
                        4
                    </span>
                    <div class="flex flex-col flex-1 gap-0">
                        <p class="text-xs text-slate-500">Stažený archiv rozbalte pomocí libovolného programu jako je například <a class="text-blue-500" href="https://www.7-zip.org/">7Zip</a> nebo <a class="text-blue-500" href="https://www.rar.cz/download.php">WinRar</a>, čímž získáte požadovaný soubor .ics .</p>
                    </div>
                </li>
            </ul>
        </div>
    </div>
    <!-- calendar export tutorial -->
    <div x-cloak x-show="event_popup" x-transition class="w-full h-full fixed top-0 left-0 flex justify-center items-center z-10">
        <div @click.outside="event_popup = false" class="w-full max-w-2xl mx-3 md:m-0 p-3 bg-white rounded-xl myshadow relative">
            <img @click="event_popup = false" src="images/icons/cross.svg" class="w-6 absolute top-2 z-20 right-2 cursor-pointer transition hover:scale-110">
            <ul aria-label="Activity feed" role="feed" class="relative flex flex-col gap-6 py-6 pl-6 before:absolute before:top-0 before:left-6 before:h-full h-fit before:border before:-translate-x-1/2 before:border-slate-200 before:border-dashed after:absolute after:top-6 after:left-6 after:bottom-6 after:border after:-translate-x-1/2 after:border-slate-200 ">
                <li class="relative pl-3">
                    <h3 class="font-bold">Jak nastavit akce v kalendáři?</h3>
                </li>
                <li class="relative pl-6 flex items-start">
                    <span class="absolute left-0 z-10 flex items-center justify-center w-8 h-8 -translate-x-1/2 rounded-full text-slate-700 ring-2 ring-white bg-slate-200 ">
                        <img src="images/icons/calendar.svg">                        
                    </span>
                    <div class="flex flex-col flex-1 gap-0">
                        <p class="text-xs text-slate-500">Vytvořte si jednotlivé bloky v kalendáři dle dnů.</p>
                        <img src="images/events.jpg" class="w-56 rounded-md border border-gray-200 mt-1">
                    </div>
                </li>
                <li class="relative pl-6 flex items-start">
                    <span class="absolute left-0 z-10 flex items-center justify-center w-8 h-8 -translate-x-1/2 rounded-full text-slate-700 ring-2 ring-white bg-slate-200 ">
                        <img src="images/icons/event.svg">
                    </span>
                    <div class="flex flex-col flex-1 gap-0">
                        <p class="text-xs text-slate-500">
                            Přidejte do popisu bloku příznaky, které chcete aby se zobrazily. <b>Příznaky vždy oddělujte na nové řádky.</b><br>
                            <code><b>povinný</b></code> = zobrazí ikonu (povinný) v právé části bloku<br>
                            <code><b>program</b></code> = pole bloku se zbarví šedě<br>
                            <code><b>jakýkoliv další text</b></code> = zobrazí se jako popis bloku<br>
                        </p>
                        <img src="images/event_detail.jpg" class="w-96 rounded-md border border-gray-200 mt-1">
                    </div>
                </li>
            </ul>
        </div>
    </div>
    <div class="flex justify-center bg-gray-200 items-center w-screen h-screen z-0">
        <div class="w-full max-w-xl mx-3 bg-white rounded-xl shadow-lg p-3">
            <h1 class="font-['skautbold'] text-lg mb-2">Generátor harmonogramu</h1>
            <form action="export.php" method="post" class="flex flex-col gap-3 items-start font-['themix']" enctype="multipart/form-data">
                <div class="flex flex-col gap-1">
                    <div class="flex gap-1 flex-row items-base">
                        <label for="file">ICS export z Google kalendáře</label>
                        <img @click="export_popup = !export_popup" src="images/icons/question.svg" class="w-6 cursor-pointer transition hover:scale-110">
                    </div>
                    <input type="file" name="file" id="file" accept=".ics" required>
                </div>
                <div class="flex flex-col gap-1">
                    <div class="flex gap-1 flex-row items-base">
                        <label for="template">Šablona</label>
                        <img @click="event_popup = !event_popup" src="images/icons/question.svg" class="w-6 cursor-pointer transition hover:scale-110">
                    </div>
                    <select name="template" class="bg-gray-50 px-2 py-1.5 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block min-w-[12rem] w-full dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                        <option value="default" selected>Výchozí</option>
                        <option value="ursus">Ursus</option>
                    </select>
                </div>
                <div class="flex flex-col gap-1">
                    <label for="file">Výběr dnů</label>
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

