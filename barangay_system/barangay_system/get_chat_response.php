<?php
/**
 * get_chat_response.php
 * Secure proxy script for Gemini API Integration
 * Part of Barangay 76-A Web-Based System Capstone
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// 1. Get raw JSON input from the JavaScript frontend
$inputRaw = file_get_contents('php://input');
$inputData = json_decode($inputRaw, true);

$userMessage = $inputData['message'] ?? '';
$clientHistory = $inputData['history'] ?? [];

if (empty(trim($userMessage))) {
    echo json_encode(['reply' => "I didn't catch that. Please type a question about the capstone project!"]);
    exit;
}

// 2. Configuration Variables
$apiKey = "GEMINI API KEY HERE";
$modelName = "gemini-2.5-flash";
$apiUrl = "https://generativelanguage.googleapis.com/v1beta/models/" . $modelName . ":generateContent?key=" . $apiKey;

$systemPrompt = "You are a helpful FAQ chatbot for a capstone project titled:
\"WEB-BASED SYSTEM FOR BARANGAY 76-A\" (Integrated Web-Based System for Barangay Legislative Information and Civic Issue Monitoring)

Created by: Jerick Julian Chavez and Walnor G. Datu
Adviser: Gladys B. Joven, MIT
School: St. John Paul II College of Davao, College of Information and Communication Technology
Degree: Bachelor of Science in Information Technology
Expected completion: May 2027

PROJECT OVERVIEW:
The system is a web-based platform for Barangay 76-A, Davao City that combines:
1. A Legislative Information Module - a searchable digital repository for barangay ordinances and resolutions
2. A Civic Issue Monitoring Module - allows residents to submit, track, and resolve community concerns with photo evidence

PROBLEM ADDRESSED:
- Barangay 76-A relied on manual records (physical ledgers, spreadsheets) causing delays
- No real-time dashboard for tracking pending legislation
- Civic issue reports handled informally without follow-up
- No centralized system bridging legislative action and civic feedback

OBJECTIVES:
1. Design a web-based platform with categorized modules for legislative record management, resident profiling, and civic issue reporting
2. Develop a searchable digital repository for barangay ordinances and resolutions
3. Implement a civic issue monitoring module for submission, tracking, and resolution

SCOPE AND LIMITATIONS:
- Covers only Barangay 76-A, Davao City
- Built with PHP and MySQL
- Excludes financial and judicial records
- Requires internet connectivity and digital literacy

TECHNOLOGY STACK:
- Backend: PHP 8.4
- Frontend: HTML5, CSS, JavaScript (ES2024)
- Database: XAMPP MySQL 8.2.12
- IDE: Visual Studio Code 1.109
- Hardware: Windows 11, Intel Core i5, 8GB RAM, 256GB SSD

USER ROLES:
1. Residents/Constituents - register, view ordinances, submit civic issues, track reports
2. Barangay Officials/Staff - manage legislative database, review and assign civic reports
3. System Administrator - full access, user management, analytics

KEY FEATURES:
- Landing Page, Registration & Role-based Login
- Resident Dashboard, Ordinances viewer, Issue Submission with photo/location
- My Reports history, Real-time Notifications
- Officials Dashboard, Legislative Database archive, Civic Issue management panel

RELATED SYSTEMS:
- FixMyStreet: civic issue reporting only, no legislative management
- LEGIS (Philippine Senate): legislative records only, national scale
- QCitizen Portal (Quezon City): city-wide, too complex for barangay level
The proposed system uniquely integrates BOTH modules at the barangay level.

KEY LITERATURE:
- Alampay and Marte (2020): only ~30% of Philippine LGUs have digitalized
- Soriano (2022): integrated platforms reduced response times by 40%
- Bautista et al. (2023): transparency linked to records accessibility
- Reyes and Tabuga (2023): role-based access control for data integrity

Answer ONLY questions about this capstone project. Keep answers clear, concise, and friendly.";


// 3. Format Conversation History for Gemini Ecosystem
$contentsArray = [];

if (is_array($clientHistory) && count($clientHistory) > 0) {
    foreach ($clientHistory as $turn) {
        if (empty($turn['content'])) continue;
        
        // Enforce the 'user' or 'model' roles explicitly
        $role = ($turn['role'] === 'user') ? 'user' : 'model';
        $contentsArray[] = [
            'role' => $role,
            'parts' => [['text' => $turn['content']]]
        ];
    }
}

// Add the latest prompt turn to the end of the payload array
$contentsArray[] = [
    'role' => 'user',
    'parts' => [['text' => $userMessage]]
];

// 4. Construct JSON Payload Matching Google Generative AI Docs
$payload = [
    "systemInstruction" => [
        "parts" => [["text" => $systemPrompt]]
    ],
    "contents" => $contentsArray
];

// 5. Native PHP cURL request execution
$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json'
]);

$apiResponse = curl_exec($ch);
curl_close($ch);

// 6. Handle API Output Response Parsing
if ($apiResponse) {
    $decodedData = json_decode($apiResponse, true);
    error_log("GEMINI RAW: " . $apiResponse);
    // Safely extract text content from deep inside Gemini's response structure
    $botReply = $decodedData['candidates'][0]['content']['parts'][0]['text'] ?? null;
    
    if ($botReply !== null) {
        echo json_encode(['reply' => $botReply]);
    } else {
        // Log error and return raw response string details for diagnostic checks
        error_log("Gemini API Parse Failure: " . $apiResponse);
       echo json_encode(['reply' => $apiResponse]);
    }
} else {
    echo json_encode(['reply' => "⚠️ Connection error to backend server engine."]);
}