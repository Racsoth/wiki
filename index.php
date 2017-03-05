<?php
/*
 * Category Readability Tester. By Racso.
 */

error_reporting(0);

function extractDataFromApiResponse($apiStringResponse) {
    $apiResult = unserialize($apiStringResponse);
    $array = $apiResult["query"]["pages"];
    $results = array();
    foreach ($array as $pageData) {
        $results[$pageData["title"]] = $pageData["extract"];
    }
    return $results;
}

function calculateScore($extract, $words) {
    //Note: Search can be changed to a binary search. However, given that $words has a constant length, complexity would not change from O(n), where n=words in the extract. It would still be faster, though.
    $scoringWords = 0;
    $extract = strtolower($extract);
    preg_match_all("/\b\w+\b/", $extract, $extractWords);
    $extractWords = $extractWords[0];
    if (sizeof($extractWords) === 0) {
        return -1;
    }
    foreach ($extractWords as $word) {
        if (in_array($word, $words)) {
            $scoringWords += 1;
        }
    }
    return $scoringWords / sizeof($extractWords);
}

function calculateScores($extracts, $words) {
    foreach ($extracts as $title => $value) {
        $extracts[$title] = calculateScore($value, $words);
    }
    return $extracts;
}

$simpleWords = file("simple.txt", FILE_IGNORE_NEW_LINES);
$simpleWords = array_map("strtolower", $simpleWords);
$category = filter_var($_GET["category"], FILTER_SANITIZE_STRING);
if ($category !== null) {
    $apiRawResult = file_get_contents("https://en.wikipedia.org/w/api.php?action=query&format=php&prop=extracts&list=&generator=categorymembers&redirects=1&exsentences=10&exlimit=20&exintro=1&explaintext=1&gcmlimit=20&gcmnamespace=0&gcmtitle=Category:$category");
    $extracts = extractDataFromApiResponse($apiRawResult);
    $scores = calculateScores($extracts, $simpleWords);
} else {
    $scores = null;
}
?>

<html>
    <head>
        <link rel="stylesheet" href="http://tools.wmflabs.org/style.css">
        <link rel="stylesheet" href="style.css">
        <meta charset="UTF-8">
        <title>Wikipedia Readability Tester</title>
    </head>
    <body>
        <div  class="container" id="main_content">
            <h1>Wikipedia Readability Tester</h1>
            <div class="section" id="results">
                <?php
                if ($scores !== null && sizeof($scores) !== 0) {
                    arsort($scores);
                    echo "<h2>Results for category: $category</h2>";
                    echo "<ol>";
                    foreach ($scores as $title => $score) {
                        echo "<li>(" . number_format($score * 100, 1) . "%) <a href=\"https://en.wikipedia.org/wiki/$title\" target=\"_blank\">$title</a></li>";
                    }
                    echo "</ol>";
                }
                ?>
            </div>
            <div class="section" id="about">
                <h2>About this tool</h2>
                <p>This tool ranks the articles from a category in the English Wikipedia according to their readability. Readability is measured as the relative amount of <a href="https://simple.wikipedia.org/wiki/Wikipedia:Basic_English_combined_wordlist" target="_blank">simple English words</a> in the introduction of the article. For example, if an article's introduction is composed only by simple English words, its readability is 100%.</p>
                <p>The tool works on a maximum of 20 articles (in the main namespace) per category.</p>
                <p><span class="em">To use the tool</span>, simply invoke it with a "category" parameter in the query string. For an example, <a href="?category=Hydrostatics">click here</a>.</p>
                <p><span class="em">Author:</span> <a href="https://meta.wikimedia.org/wiki/user:Racso" target="_blank">Racso</a>.</h3>
            </div>
        </div>
    </body>
</html>
