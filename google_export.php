<?php

session_start();

// Check if user is authenticated and has selected a calendar
if (!isset($_SESSION['google_token']) || !isset($_SESSION['selected_calendar']) || !isset($_SESSION['daterange']) || !isset($_SESSION['template'])) {
    header('Location: index.php');
    exit();
}

// Load necessary files
require_once __DIR__ . '/vendor/autoload.php';
$config = require_once __DIR__ . '/google/config.php';
require_once __DIR__ . '/google/calendar_api.php';

// Create and configure Google client
$client = new Google\Client();
$client->setClientId($config['client_id']);
$client->setClientSecret($config['client_secret']);
$client->setRedirectUri($config['redirect_uri']);

// Set the access token
$client->setAccessToken($_SESSION['google_token']);

// Refresh token if expired
if ($client->isAccessTokenExpired()) {
    if ($client->getRefreshToken()) {
        $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
        $_SESSION['google_token'] = $client->getAccessToken();
    } else {
        // No refresh token, redirect to auth page
        header('Location: google/auth.php');
        exit();
    }
}

// Process date range
$dateRange = explode(' - ', $_SESSION['daterange']);
$startDate = new DateTime($dateRange[0]);
$endDate = new DateTime($dateRange[1]);
$endDate->add(new DateInterval('P1D')); // Add one day to include end date

// Get events from Google Calendar
$events = getCalendarEvents($client, $_SESSION['selected_calendar'], $startDate, $endDate);

// Add this check: If no events found, redirect back with an error message
if (empty($events)) {
    $_SESSION['export_error'] = 'no_events';
    header('Location: index.php');
    exit();
}

// Group events by single days
$days = [];
foreach($events as $event) {
    $day = $event['DTSTART']->format('Y-m-d');
    $days[$day][] = $event;
}

// Sort events by time
foreach($days as $key => $day) {
    usort($day, function($a, $b) {
        return $a['DTSTART'] <=> $b['DTSTART'];
    });
    $days[$key] = $day;
}

ksort($days);

// Group events happening at the same time
$groupedEvents = [];
foreach ($days as &$day) {
    $grouped = [];
    foreach ($day as $event) {
        foreach ($grouped as $key => $group) {
            foreach ($group as $groupEvent) {
                if($event['DTSTART'] < $groupEvent['DTEND'] && $event['DTEND'] > $groupEvent['DTSTART']) {
                    $grouped[$key][] = $event;
                    continue 3;
                }
            }
        }
        $grouped[] = [$event];
    }
    $day = $grouped;
}

// Restructure events happening at the same time
foreach ($days as &$day) {
    foreach ($day as &$group) {
        if (count($group) > 2) {
            // Find the longest event key
            $longest = 0;
            for ($i=0; $i < count($group); $i++) { 
                if ($group[$i]['DTEND']->getTimestamp() - $group[$i]['DTSTART']->getTimestamp() > $group[$longest]['DTEND']->getTimestamp() - $group[$longest]['DTSTART']->getTimestamp()) {
                    $longest = $i;
                }
            }

            $group[] = $group;
            unset($group[array_key_last($group)][$longest]);
            // Remove all keys except the longest
            $group = [$group[$longest], $group[array_key_last($group)]];
        }
    }
}

// Do not remove. It's for reference binding
unset($day);

$week_days = ['neděle', 'pondělí', 'úterý', 'středa', 'čtvrtek', 'pátek', 'sobota'];
$food = ['snídaně', 'svačina', 'oběd', 'večeře'];

// Load template
include 'templates/' . $_SESSION['template'] . '.php';
?> 