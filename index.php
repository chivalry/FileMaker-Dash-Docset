<?php
require_once ( "vendor/autoload.php");
ob_end_flush();
use Symfony\Component\DomCrawler\Crawler;
use Keboola\Csv\CsvFile;

/* The directory containing the FileMaker Help Files */
$relativePath = '/html/';
$path = __DIR__ . $relativePath;

$htmlFiles = new DirectoryIterator ( $path );

//$dom = new Dom;
$htmlContents = new Crawler();
/* Loop through an create the csv and add anchors. */
$i = 0;

echo "Processing files...\n";

foreach ( $htmlFiles as $file ) {

    $currentType = null;

    if($file->isDot()) continue;
    $currentFile =  $file->getFilename();

    $fileContents = file_get_contents($path . $currentFile);
    $htmlContents = new Crawler ( $fileContents );
    
    //$htmlContents->addHTMLContent ( file_get_contents($path . $currentFile));

    $title = $htmlContents->filterXPath('//head/title')->text();
    
    /* Only generate data for the specific types. */
    if ( preg_match( '/^scripts_ref/', $currentFile ))
    {
        $currentType = "Command";
    } elseif ( preg_match( '/^func_ref/', $currentFile )) {
        $currentType = "Functions";
    } elseif (preg_match( '/^script_trigg/', $currentFile )) {
        $currentType =  "Events";
    }

    if ( ! is_null ( $currentType ))
    {
        $csvOutput[$i]['name'] = $title;
        $csvOutput[$i]['$type'] = $currentType;
        $csvOutput[$i]['filename'] = $relativePath . $currentFile;

        /* 
         * Write TOC Anchor
         *     <a name="//apple_ref/cpp/Entry Type/Entry Name" class="dashAnchor"></a>
         */
        /* Strip existing anchors */
        $fileContents = preg_replace('/<a name="\/\/apple_ref\/cpp\/.*<\/a>\n/', null, $fileContents );
        // $tocAnchor = '<a name="//apple_ref/cpp/' . $currentType . '/' . $title . '" class="dashAnchor"></a>';
        // $fileContentsBreakOnClosingBody = preg_split('/(<body.*>)/', $fileContents, 2, PREG_SPLIT_DELIM_CAPTURE );

        // $fileContents =
        //     $fileContentsBreakOnClosingBody[0] . 
        //     $fileContentsBreakOnClosingBody[1] . 
        //     "\n    $tocAnchor" .
        //     $fileContentsBreakOnClosingBody[2];
        $updatedFileContents[$currentFile] = $fileContents;
    }

    $i++;
}

echo "$i files processed...\n";
$i = 1;
foreach ( $updatedFileContents as $file => $contents )
{
    file_put_contents($path . $file, $contents);
    $i++;
}

// echo "TOC Anchor added to $i files...\n";

/* Save CSV */
if ( ! isset ( $csvOutput ))
{
    echo "ERROR:No data exported to CSV...\n";
} else {
    echo "Saving CSV File...\n";
    $csvFile = new CsvFile ( __DIR__ . '/help_index.csv');

    foreach ( $csvOutput as $row ) {
        $csvFile->writeRow($row);
    }
}