<?php
header("Content-Type: application/json");

$filePath = 'results.xml';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = htmlspecialchars($_POST['name']);
    $score = intval($_POST['score']);
    $set = intval($_POST['set']);
    $timestamp = date("Y-m-d H:i:s");

    // Load or create the XML file
    if (!file_exists($filePath)) {
        $xml = new SimpleXMLElement('<leaderboards></leaderboards>');
    } else {
        $xml = simplexml_load_file($filePath);
    }

    // Look for an existing user entry based on name
    $userEntry = null;
    foreach ($xml->user as $entry) {
        if ((string)$entry->name === $name) {
            $userEntry = $entry;
            break;
        }
    }

    // If the user entry doesn't exist, create a new one
    if (!$userEntry) {
        $userEntry = $xml->addChild('user');
        $userEntry->addChild('name', $name);
    }

    // Add the new result under the existing user entry
    $result = $userEntry->addChild('result');
    $result->addChild('score', $score);
    $result->addChild('set', $set);
    $result->addChild('timestamp', $timestamp);

    // Save the XML file
    $xml->asXML($filePath);
    echo json_encode(['success' => true]);
} else {
    // Fetch leaderboard data
    $setFilter = isset($_GET['set']) ? $_GET['set'] : 'all';

    if (file_exists($filePath)) {
        $xml = simplexml_load_file($filePath);
    } else {
        $xml = new SimpleXMLElement('<leaderboards></leaderboards>');
    }

    // Prepare leaderboard data based on filter
    $data = [];
    foreach ($xml->user as $entry) {
        $highestScore = 0;
        $mostRecentTimestamp = '';
        $userSet = 0; // To identify the set if filtering by set is required

        // Loop through each result for the user to find the highest score and filter by set if needed
        foreach ($entry->result as $result) {
            $score = (int)$result->score;
            $set = (int)$result->set;

            if ($setFilter === 'all' || $set == $setFilter) {
                $highestScore = max($highestScore, $score);
                if ((string)$result->timestamp > $mostRecentTimestamp) {
                    $mostRecentTimestamp = (string)$result->timestamp;
                    $userSet = $set;
                }
            }
        }

        if ($highestScore > 0) {
            $data[] = [
                'name' => (string)$entry->name,
                'score' => $highestScore,
                'set' => $userSet,
                'timestamp' => $mostRecentTimestamp
            ];
        }
    }

    // Sort data by score in descending order
    usort($data, fn($a, $b) => $b['score'] - $a['score']);
    echo json_encode($data);
}
?>
