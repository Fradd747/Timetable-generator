<?php session_start(); ?>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-E06PEENMY1"></script>
    <script>
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('js', new Date());

    gtag('config', 'G-E06PEENMY1');
    </script>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="images/logo_without_text.png">
    <title>Generátor harmonogramu</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="styles.css">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/jquery/latest/jquery.min.js"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
    <style>
        ::-webkit-scrollbar {
            width: 5px;
            height: 5px;
        }

        ::-webkit-scrollbar-track {
        -webkit-box-shadow: inset 0 0 6px rgba(0, 0, 0, 0.3);
        -webkit-border-radius: 10px;
        border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb {
        -webkit-border-radius: 10px;
        border-radius: 10px;
        background: rgba(255, 255, 255, 0.3);
        -webkit-box-shadow: inset 0 0 6px rgba(0, 0, 0, 0.5);
        }

        ::-webkit-scrollbar-thumb:window-inactive {
        background: rgba(255, 255, 255, 0.3);
        }
    </style>
</head>
<body x-data="{ export_popup: false, event_popup: false }">
    <!-- calendar export tutorial -->
    <div x-cloak x-show="export_popup" x-transition class="w-full h-full fixed top-0 left-0 flex justify-center items-center py-5 z-10">
        <div @click.outside="export_popup = false" class="w-full h-full pt-10 max-w-2xl mx-3 md:m-0 p-3 bg-white rounded-xl myshadow relative">
            <img @click="export_popup = false" src="images/icons/cross.svg" class="w-6 absolute top-2 z-20 right-2 cursor-pointer transition hover:scale-110">
            <ul aria-label="Activity feed" role="feed" class="h-full overflow-y-auto relative flex flex-col gap-6 py-6 pl-6 before:absolute before:top-0 before:left-6 before:h-full h-fit before:border before:-translate-x-1/2 before:border-slate-200 before:border-dashed after:absolute after:top-6 after:left-6 after:bottom-6 after:border after:-translate-x-1/2 after:border-slate-200 ">
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
    <div x-cloak x-show="event_popup" x-transition class="w-full h-full fixed top-0 left-0 flex justify-center items-center py-5 z-10">
        <div @click.outside="event_popup = false" class="w-full h-full pt-10 max-w-2xl mx-3 md:m-0 p-3 bg-white rounded-xl myshadow relative">
            <img @click="event_popup = false" src="images/icons/cross.svg" class="w-6 absolute top-2 z-20 right-2 cursor-pointer transition hover:scale-110">
            <ul aria-label="Activity feed" role="feed" class="h-full overflow-y-auto relative flex flex-col gap-6 py-6 pl-6 before:absolute before:top-0 before:left-6 before:h-full h-fit before:border before:-translate-x-1/2 before:border-slate-200 before:border-dashed after:absolute after:top-6 after:left-6 after:bottom-6 after:border after:-translate-x-1/2 after:border-slate-200 ">
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
    <div class="flex justify-center bg-gray-200 items-center w-full p-0 w-0 h-[90vh] z-0">
        <div class="w-fit max-w-xl mx-3 bg-white rounded-xl shadow-lg p-3">
            <!-- <h1 class="font-['skautbold'] text-lg mb-2">Generátor harmonogramu</h1> -->
            <div class="flex justify-center w-full">
                <img src="images/logo.svg" class="h-6 sm:h-9 m-5 mb-8" alt="Logo" />
            </div>
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
    <footer class="bg-white dark:bg-gray-800">
        <div class="max-w-screen-xl p-4 py-6 mx-auto md:pb-8 md:p-8 md:pt-0">
            <hr class="my-6 border-gray-200 sm:mx-auto dark:border-gray-700 md:my-8">
            <div class="text-center flex flex-col gap-2">
                <img src="images/logo.svg" class="h-6 sm:h-9" alt="Logo" />
                <span class="block text-sm text-center text-gray-500 dark:text-gray-400">
                    Jan Korbay - <a href="mailto:jan.korbay@skaut.cz" class="text-blue-800 hover:underline dark:text-blue-500">jan.korbay@skaut.cz</a>
                </span>
                <ul class="flex justify-center gap-2">
                    <li>
                        <a href="https://www.facebook.com/jankorbay/"
                            class="text-gray-500 hover:text-gray-900 dark:hover:text-white dark:text-gray-400">
                            <svg fill="currentColor" class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path d="M12 0c-6.627 0-12 5.373-12 12s5.373 12 12 12 12-5.373 12-12-5.373-12-12-12zm3 8h-1.35c-.538 0-.65.221-.65.778v1.222h2l-.209 2h-1.791v7h-3v-7h-2v-2h2v-2.308c0-1.769.931-2.692 3.029-2.692h1.971v3z"/></svg>
                        </a>
                    </li>
                    <li>
                        <a href="https://www.linkedin.com/in/jan-korbay/"
                            class="text-gray-500 hover:text-gray-900 dark:hover:text-white dark:text-gray-400">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" class="w-5 h-5" viewBox="0 0 24 24"><path d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.79-1.75-1.764s.784-1.764 1.75-1.764 1.75.79 1.75 1.764-.783 1.764-1.75 1.764zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z"/></svg>
                        </a>
                    </li>
                    <li>
                        <a href="https://github.com/Fradd747"
                            class="text-gray-500 hover:text-gray-900 dark:hover:text-white dark:text-gray-400">
                            <svg class="w-5 h-5" viewBox="0 0 96 96" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><path d="M48.854 0C21.839 0 0 22 0 49.217c0 21.756 13.993 40.172 33.405 46.69 2.427.49 3.316-1.059 3.316-2.362 0-1.141-.08-5.052-.08-9.127-13.59 2.934-16.42-5.867-16.42-5.867-2.184-5.704-5.42-7.17-5.42-7.17-4.448-3.015.324-3.015.324-3.015 4.934.326 7.523 5.052 7.523 5.052 4.367 7.496 11.404 5.378 14.235 4.074.404-3.178 1.699-5.378 3.074-6.6-10.839-1.141-22.243-5.378-22.243-24.283 0-5.378 1.94-9.778 5.014-13.2-.485-1.222-2.184-6.275.486-13.038 0 0 4.125-1.304 13.426 5.052a46.97 46.97 0 0 1 12.214-1.63c4.125 0 8.33.571 12.213 1.63 9.302-6.356 13.427-5.052 13.427-5.052 2.67 6.763.97 11.816.485 13.038 3.155 3.422 5.015 7.822 5.015 13.2 0 18.905-11.404 23.06-22.324 24.283 1.78 1.548 3.316 4.481 3.316 9.126 0 6.6-.08 11.897-.08 13.526 0 1.304.89 2.853 3.316 2.364 19.412-6.52 33.405-24.935 33.405-46.691C97.707 22 75.788 0 48.854 0z"/></svg>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </footer>
</body>
<script>
    $(function() {
        $('input[name="daterange"]').daterangepicker({
            opens: 'left'
        });
    });
</script>
</html>

