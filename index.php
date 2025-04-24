<?php 
session_start(); 

// Handle logout
if (isset($_GET['logout'])) {
    unset($_SESSION['google_token']);
    unset($_SESSION['google_user']);
    unset($_SESSION['selected_calendar']);
    unset($_SESSION['daterange']);
    unset($_SESSION['template']);
    header('Location: index.php');
    exit();
}

$is_authenticated = isset($_SESSION['google_token']);
$calendars = [];

if ($is_authenticated) {
    // Load Composer's autoloader
    require_once __DIR__ . '/vendor/autoload.php';

    // Load configuration
    $config = require_once __DIR__ . '/google/config.php';

    // Load calendar API functions
    require_once __DIR__ . '/google/calendar_api.php';

    // Create and configure Google client
    $client = new Google\Client();
    $client->setClientId($config['client_id']);
    $client->setClientSecret($config['client_secret']);
    $client->setRedirectUri($config['redirect_uri']); // Ensure this points correctly, likely index.php or a handler

    // Set the access token
    $client->setAccessToken($_SESSION['google_token']);

    // Refresh token if expired
    if ($client->isAccessTokenExpired()) {
        if ($client->getRefreshToken()) {
            try {
                $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
                $_SESSION['google_token'] = $client->getAccessToken();
            } catch (Exception $e) {
                // Handle refresh token failure, e.g., redirect to auth
                unset($_SESSION['google_token']);
                unset($_SESSION['google_user']);
                header('Location: google/auth.php?error=refresh_failed');
                exit();
            }
        } else {
            // No refresh token, redirect to auth page
            unset($_SESSION['google_token']);
            unset($_SESSION['google_user']);
            header('Location: google/auth.php');
            exit();
        }
    }

    // Get list of calendars if authenticated
    try {
        $calendars = getCalendarList($client);
    } catch (Exception $e) {
        // Handle error fetching calendars, maybe logout or show error
        // For now, we can log the error and potentially show a message
        error_log("Error fetching calendar list: " . $e->getMessage());
        // Optionally unset session and redirect
        unset($_SESSION['google_token']);
        unset($_SESSION['google_user']);
        // Redirect or display an error message
        header('Location: index.php?error=calendar_fetch_failed');
        exit();
    }


    // Process form submission if authenticated and form submitted
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['calendar_id']) && isset($_POST['daterange']) && isset($_POST['template'])) {
        $_SESSION['selected_calendar'] = $_POST['calendar_id'];
        $_SESSION['daterange'] = $_POST['daterange'];
        $_SESSION['template'] = $_POST['template'];

        // Always redirect to the standard export page
        header('Location: google_export.php');
        exit();
    }
}
?>
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
    <link rel="icon" href="images/logo.png">
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
<body x-data="{ event_popup: false, login_popup: false }">
    <!-- calendar export tutorial -->
    <div x-cloak x-show="event_popup" x-transition class="w-full h-full fixed top-0 left-0 flex justify-center items-center py-5 z-10">
        <div @click.outside="event_popup = false" class="w-full h-full pt-10 max-w-4xl mx-3 md:m-0 p-5 bg-white rounded-xl myshadow relative flex flex-col">
            <img @click="event_popup = false" src="images/icons/cross.svg" class="w-8 absolute top-3 z-20 right-3 cursor-pointer transition hover:scale-110">
            <h3 class="font-bold text-2xl pl-8 pb-4">Jak nastavit akce v kalendáři?</h3>
            <div class="flex-1 overflow-y-auto pr-5">
                <ul aria-label="Activity feed" role="feed" class="relative flex flex-col gap-8 py-8 pl-8 after:absolute after:top-8 after:left-8 after:bottom-8 after:border after:-translate-x-1/2 after:border-slate-200">
                    <li class="relative pl-8 flex items-start">
                        <span class="absolute left-0 z-10 flex items-center justify-center w-10 h-10 -translate-x-1/2 rounded-full text-slate-700 ring-2 ring-white bg-slate-200 ">
                            <img src="images/icons/calendar.svg" class="size-6">
                        </span>
                        <div class="flex flex-col flex-1 gap-0">
                            <p class="text-base text-slate-500">Vytvoř si jednotlivé bloky v kalendáři dle dnů.</p>
                            <img src="images/events.jpg" class="w-96 rounded-md border border-gray-200 mt-3">
                        </div>
                    </li>
                    <li class="relative pl-8 flex items-start">
                        <span class="absolute left-0 z-10 flex items-center justify-center w-10 h-10 -translate-x-1/2 rounded-full text-slate-700 ring-2 ring-white bg-slate-200 ">
                            <img src="images/icons/event.svg" class="size-6">
                        </span>
                        <div class="flex flex-col flex-1 gap-0">
                            <p class="text-base text-slate-500">
                                Přidej do popisu bloku příznaky, které chceš, aby se zobrazily v harmonogramu. <b>Příznaky vždy odděluj na nové řádky.</b><br>
                                <code><b>povinný</b></code> = zobrazí ikonu (povinný) v právé části bloku<br>
                                <code><b>program</b></code> = pole bloku se zbarví šedě<br>
                                <code><b>jakýkoliv další text</b></code> = zobrazí se jako popis bloku pod jeho názvem<br>
                            </p>
                            <img src="images/event_detail.jpg" class="w-full max-w-2xl rounded-md border border-gray-200 mt-3">
                        </div>
                    </li>
                    <li class="relative pl-8 flex items-start">
                        <span class="absolute left-0 z-10 flex items-center justify-center w-10 h-10 -translate-x-1/2 rounded-full text-slate-700 ring-2 ring-white bg-slate-200 ">
                            <img src="images/icons/template.svg" class="size-6 stroke-red-700">
                        </span>
                        <div class="flex flex-col flex-1 gap-0">
                            <p class="text-base text-slate-500">
                                Vyber si šablonu z dostupných možností, nebo mi napiš na <a href="mailto:jan.korbay@skaut.cz" class="text-blue-600 hover:text-blue-800">jan.korbay@skaut.cz</a> a mohu vytvořit šablonu přímo pro váš kurz/akci na míru.
                            </p>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </div>
    
    <!-- Google login warning popup -->
    <div x-cloak x-show="login_popup" x-transition class="w-full h-full fixed top-0 left-0 flex justify-center items-center py-5 z-20">
        <div @click.outside="login_popup = false" class="w-full max-w-md mx-3 md:m-0 p-5 bg-white rounded-xl shadow-lg relative">
            <img @click="login_popup = false" src="images/icons/cross.svg" class="w-6 absolute top-3 right-3 cursor-pointer transition hover:scale-110">
            <div class="mt-3 text-center">
                <h2 class="text-xl font-bold text-gray-800 mb-3">Důležité upozornění</h2>
                <p class="text-gray-600 mb-2">Pro správné fungování aplikace je nezbytné povolit <strong>všechna oprávnění</strong> na následujících obrazovkách.</p>
                <p class="text-gray-600 mb-4">Prosím, zaškrtněte všechna políčka, když k tomu budete vyzváni.</p>
                <div class="flex justify-center">
                    <a href="google/auth.php" class="inline-flex items-center justify-center gap-2 bg-blue-500 text-white font-medium py-2 px-4 rounded-lg hover:bg-blue-600 transition-all">
                        Pokračovat s přihlášením
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="flex justify-center bg-gray-200 items-center w-full p-0 w-0 h-[90vh] z-0">
        <div x-cloak class="w-full max-w-lg mx-3 bg-white rounded-xl shadow-lg p-3">
            <div class="flex justify-center items-center w-full gap-3 m-5 mb-8">
                <img src="images/logo.png" class="h-8 sm:h-9" alt="Logo" />
                <h1 class="font-['funnel-sans'] text-xl whitespace-nowrap">Generátor <span class="text-[#2a93d6]">harmonogramu</span></h1>
            </div>

            <?php if ($is_authenticated && isset($_SESSION['google_user'])): ?>
                <!-- Authenticated State: Calendar Selection Form -->
                <div class="mb-4 flex items-center justify-between space-x-3 p-2 md:p-3 bg-gray-100 rounded-lg">
                    <div class="flex items-center gap-3">
                        <img src="<?php echo htmlspecialchars($_SESSION['google_user']['picture']); ?>" alt="Profile" class="w-10 h-10 rounded-full">
                        <div>
                            <div class="font-medium"><?php echo htmlspecialchars($_SESSION['google_user']['name']); ?></div>
                            <div class="text-sm text-gray-500"><?php echo htmlspecialchars($_SESSION['google_user']['email']); ?></div>
                        </div>
                    </div>
                    <a href="index.php?logout=1" class="ml-auto text-sm text-gray-700 hover:text-gray-900 border border-gray-300 rounded-lg px-3 py-1.5 bg-gray-50 transition duration-150 ease-in-out shadow-sm hover:shadow-md">Odhlásit</a>
                </div>

                <?php if (isset($_GET['error'])): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                        <strong class="font-bold">Chyba:</strong>
                        <span class="block sm:inline">
                            <?php
                                switch ($_GET['error']) {
                                    case 'refresh_failed':
                                        echo "Nepodařilo se obnovit přihlášení. Prosím, přihlaste se znovu.";
                                        break;
                                    case 'calendar_fetch_failed':
                                        echo "Nepodařilo se načíst seznam kalendářů. Zkuste to prosím znovu.";
                                        break;
                                    default:
                                        echo "Nastala neznámá chyba.";
                                }
                            ?>
                        </span>
                    </div>
                <?php endif; ?>


                <form action="index.php" method="post" class="flex flex-col mb-1 gap-3 items-start font-['themix']">
                    <div class="flex flex-col gap-1 w-full">
                        <label for="calendar_id" class="font-medium">Vyberte kalendář</label>
                        <select name="calendar_id" id="calendar_id" class="bg-gray-50 px-2 py-1.5 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full" required>
                            <?php foreach ($calendars as $calendar): ?>
                                <option value="<?php echo htmlspecialchars($calendar['id']); ?>" <?php echo $calendar['primary'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($calendar['summary']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="flex flex-col gap-1 w-full">
                        <div class="flex gap-1 flex-row items-center">
                            <label for="template" class="font-medium">Šablona</label>
                            <img @click="event_popup = !event_popup" src="images/icons/question.svg" class="w-6 cursor-pointer transition hover:scale-110" alt="Help">
                        </div>
                        <select name="template" id="template" class="bg-gray-50 px-2 py-1.5 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full" required>
                            <option value="default" selected>Výchozí</option>
                            <option value="ursus">VK Ursus</option>
                            <option value="pecka">RK Pecka</option>
                        </select>
                    </div>

                    <div class="flex flex-col gap-1 w-full">
                         <label for="daterange" class="font-medium">Výběr dnů</label>
                        <input type="text" name="daterange" id="daterange" class="bg-gray-50 px-2 py-1.5 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full" required/>
                    </div>

                    <div class="flex w-full mt-3">
                        <button type="submit" class="shadow-lg border rounded-lg p-2 hover:ring-2 hover:ring-blue-300 transition duration-150 ease-in-out bg-blue-500 text-white flex-grow">Vygenerovat</button>
                    </div>
                    <?php if (isset($_SESSION['export_error']) && $_SESSION['export_error'] === 'no_events'): ?>
                        <div class="text-red-600 text-sm w-full text-center bg-red-50 py-1 px-2 rounded-lg border border-red-200">
                            V zadaném období nebyly nalezeny žádné události. Zkuste změnit období nebo vybrat jiný kalendář.
                        </div>
                        <?php unset($_SESSION['export_error']); // Clear the error message after displaying ?>
                    <?php endif; ?>
                </form>

            <?php else: ?>
                 <!-- Not Authenticated State: Login Button -->
                 <div x-data="{ showIntro: true }" class="flex flex-col">
                     <div x-show="showIntro" 
                          x-transition:enter="transition ease-out duration-300"
                          x-transition:enter-start="opacity-0 transform scale-95"
                          x-transition:enter-end="opacity-100 transform scale-100"
                          x-transition:leave="transition ease-in duration-200"
                          x-transition:leave-start="opacity-100 transform scale-100"
                          x-transition:leave-end="opacity-0 transform scale-95"
                          class="flex flex-col bg-gray-50 p-4 rounded-lg border border-gray-200 text-center">
                          <h1 class="text-lg font-semibold mb-2">Jak to funguje?</h1>
                          <p class="text-sm text-gray-600 mb-3">Do Google kalendáře si přidejte jednotlivé programové bloky dle dnů na vašem táboře, kurzu nebo jakékoliv jiné akci. Pak se stačí zde jen přihlásit skrze svůj Google účet a vygenerovat harmonogram, který si můžete stáhnout nebo vytisknout.</p>
                          <button @click="showIntro = false" class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600 font-medium self-center transition duration-150 ease-in-out">Chci to zkusit!</button>
                      </div>
  
                      <div x-show="!showIntro" 
                           x-transition:enter="transition ease-out duration-300 delay-200"
                           x-transition:enter-start="opacity-0 transform scale-95"
                           x-transition:enter-end="opacity-100 transform scale-100"
                           x-transition:leave="transition ease-in duration-200"
                           x-transition:leave-start="opacity-100 transform scale-100"
                           x-transition:leave-end="opacity-0 transform scale-95"
                           class="flex flex-col gap-4">
                           <div class="bg-blue-50 p-4 rounded-lg border border-blue-200 text-center">
                               <h2 class="text-lg font-semibold mb-2">Jak to vypadá?</h2>
                               <p class="text-sm text-gray-600 mb-3">Vygenerujte si ukázkový harmonogram bez přihlášení.</p>
                               <a href="sample.php" class="inline-flex items-center justify-center gap-2 bg-blue-500 hover:bg-blue-600 text-white font-medium py-2 px-4 rounded-lg transition-all shadow-sm">
                                   Ukázkový harmonogram
                               </a>
                           </div>
                       
                           <div class="bg-gray-50 p-4 rounded-lg border border-gray-200 text-center">
                              <h2 class="text-lg font-semibold mb-2">Google Kalendář API</h2>
                              <p class="text-sm text-gray-600 mb-3">Pro pokračování se přihlaste pomocí Google účtu.</p>
                               <?php if (isset($_GET['error'])): ?>
                              <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                                  <strong class="font-bold">Chyba:</strong>
                                  <span class="block sm:inline">
                                       <?php
                                          switch ($_GET['error']) {
                                              case 'refresh_failed':
                                                  echo "Nepodařilo se obnovit přihlášení. Prosím, přihlaste se znovu.";
                                                  break;
                                              case 'calendar_fetch_failed':
                                                  echo "Nepodařilo se načíst seznam kalendářů. Zkuste to prosím znovu.";
                                                  break;
                                              default:
                                                  echo "Nastala neznámá chyba při přihlašování.";
                                          }
                                      ?>
                                  </span>
                              </div>
                              <?php endif; ?>
                              <button @click="login_popup = true" class="inline-flex items-center justify-center gap-2 bg-white border border-gray-300 text-gray-800 font-medium py-2 px-4 rounded-lg hover:bg-gray-50 transition-all shadow-sm">
                                  <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 186.69 190.5">
                                      <g transform="translate(1184.583 765.171)">
                                          <path clip-path="none" mask="none" d="M-1089.333-687.239v36.888h51.262c-2.251 11.863-9.006 21.908-19.137 28.662l30.913 23.986c18.011-16.625 28.402-41.044 28.402-70.052 0-6.754-.606-13.249-1.732-19.483z" fill="#4285f4"/>
                                          <path clip-path="none" mask="none" d="M-1142.714-651.791l-6.972 5.337-24.679 19.223h0c15.673 31.086 47.796 52.561 85.03 52.561 25.717 0 47.278-8.486 63.038-23.033l-30.913-23.986c-8.486 5.715-19.31 9.179-32.125 9.179-24.765 0-45.806-16.712-53.34-39.226z" fill="#34a853"/>
                                          <path clip-path="none" mask="none" d="M-1174.365-712.61c-6.494 12.815-10.217 27.276-10.217 42.689s3.723 29.874 10.217 42.689c0 .086 31.693-24.592 31.693-24.592-1.905-5.715-3.031-11.776-3.031-18.098s1.126-12.383 3.031-18.098z" fill="#fbbc05"/>
                                          <path clip-path="none" mask="none" d="M-1089.333-727.244c14.028 0 26.497 4.849 36.455 14.201l27.276-27.276c-16.539-15.413-38.013-24.852-63.731-24.852-37.234 0-69.359 21.388-85.032 52.561l31.692 24.592c7.533-22.514 28.575-39.226 53.34-39.226z" fill="#ea4335"/>
                                      </g>
                                  </svg>
                                  Přihlásit se pomocí Google
                              </button>
                          </div>
                       </div>
                   </div>
            <?php endif; ?>
        </div>
    </div>
    <footer class="bg-white">
        <div class="max-w-screen-xl p-4 py-6 mx-auto md:pb-8 md:p-8 md:pt-0">
            <hr class="my-6 border-gray-200 sm:mx-auto md:my-8">
            <div class="text-center flex flex-col gap-2">
                <div class="flex justify-center items-center gap-3">
                    <img src="images/logo.png" class="h-6 sm:h-9" alt="Logo" />
                    <h1 class="font-['funnel-sans'] text-xl whitespace-nowrap">Generátor <span class="text-[#2a93d6]">harmonogramu</span></h1>
                </div>
                <span class="block text-sm text-center text-gray-500">
                    Jan Korbay - <a href="mailto:jan.korbay@skaut.cz" class="text-blue-800 hover:underline">jan.korbay@skaut.cz</a>
                </span>
                <ul class="flex justify-center gap-2">
                    <li>
                        <a href="https://www.facebook.com/jankorbay/"
                            class="text-gray-500 hover:text-gray-900">
                            <svg fill="currentColor" class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path d="M12 0c-6.627 0-12 5.373-12 12s5.373 12 12 12 12-5.373 12-12-5.373-12-12-12zm3 8h-1.35c-.538 0-.65.221-.65.778v1.222h2l-.209 2h-1.791v7h-3v-7h-2v-2h2v-2.308c0-1.769.931-2.692 3.029-2.692h1.971v3z"/></svg>
                        </a>
                    </li>
                    <li>
                        <a href="https://www.linkedin.com/in/jan-korbay/"
                            class="text-gray-500 hover:text-gray-900">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" class="w-5 h-5" viewBox="0 0 24 24"><path d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.79-1.75-1.764s.784-1.764 1.75-1.764 1.75.79 1.75 1.764-.783 1.764-1.75 1.764zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z"/></svg>
                        </a>
                    </li>
                    <li>
                        <a href="https://github.com/Fradd747"
                            class="text-gray-500 hover:text-gray-900">
                            <svg class="w-5 h-5" viewBox="0 0 96 96" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><path d="M48.854 0C21.839 0 0 22 0 49.217c0 21.756 13.993 40.172 33.405 46.69 2.427.49 3.316-1.059 3.316-2.362 0-1.141-.08-5.052-.08-9.127-13.59 2.934-16.42-5.867-16.42-5.867-2.184-5.704-5.42-7.17-5.42-7.17-4.448-3.015.324-3.015.324-3.015 4.934.326 7.523 5.052 7.523 5.052 4.367 7.496 11.404 5.378 14.235 4.074.404-3.178 1.699-5.378 3.074-6.6-10.839-1.141-22.243-5.378-22.243-24.283 0-5.378 1.94-9.778 5.014-13.2-.485-1.222-2.184-6.275.486-13.038 0 0 4.125-1.304 13.426 5.052a46.97 46.97 0 0 1 12.214-1.63c4.125 0 8.33.571 12.213 1.63 9.302-6.356 13.427-5.052 13.427-5.052 2.67 6.763.97 11.816.485 13.038 3.155 3.422 5.015 7.822 5.015 13.2 0 18.905-11.404 23.06-22.324 24.283 1.78 1.548 3.316 4.481 3.316 9.126 0 6.6-.08 11.897-.08 13.526 0 1.304.89 2.853 3.316 2.364 19.412-6.52 33.405-24.935 33.405-46.691C97.707 22 75.788 0 48.854 0z"/></svg>
                        </a>
                    </li>
                </ul>
                <div class="mt-2 text-sm text-gray-500">
                    <a href="privacy-policy.php" class="text-blue-800 hover:underline mx-2">Zásady ochrany osobních údajů</a> | 
                    <a href="terms-of-service.php" class="text-blue-800 hover:underline mx-2">Obchodní podmínky</a>
                </div>
            </div>
        </div>
    </footer>
</body>
<script>
    $(function() {
        // Initialize daterangepicker only if the input exists (i.e., user is logged in)
        if ($('input[name="daterange"]').length) {
            $('input[name="daterange"]').daterangepicker({
                opens: 'left',
                "locale": {
                    "format": "DD.MM.YYYY",
                    "separator": " - ",
                    "applyLabel": "Použít",
                    "cancelLabel": "Zrušit",
                    "fromLabel": "Od",
                    "toLabel": "Do",
                    "customRangeLabel": "Vlastní",
                    "weekLabel": "T",
                    "daysOfWeek": [
                        "Ne", "Po", "Út", "St", "Čt", "Pá", "So"
                    ],
                    "monthNames": [
                        "Leden", "Únor", "Březen", "Duben", "Květen", "Červen", "Červenec", "Srpen", "Září", "Říjen", "Listopad", "Prosinec"
                    ],
                    "firstDay": 1
                }
            });
        }
    });
</script>
</html>

