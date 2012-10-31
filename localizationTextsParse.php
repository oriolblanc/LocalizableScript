#!/usr/bin/php

<?php 

$url = $argv[1];
$destPath = "";

$width = shell_exec('tput cols');
if(!$width) {$width = 80;}
$outputDivider = str_repeat('-',$width);

echo $outputDivider."\n";

if($argv[2])
{
	$destPath = $argv[2];
	echo("Destination Path:\n".$destPath."\n");
	echo $outputDivider."\n";
}

if (!$argv[1])
{
    die("\n".'ERROR: Syntax: localizationTextsParse <url> [<destination_path>] [<formats>]'."\n\n");
}

/*

argv 3 specifies which files get exported in a terrible, terrible way

100 exports only iOS
010 exports only Android
001 exports only JSON

110 Exports iOS+Android

And you get the idea.

*/

if (!$argv[3])
{
    $argv[3] = '111';
}




$localizationFileLines = file_get_contents($url);
//var_dump($localizationFileLines);

$localizationFileLines = explode("\n", $localizationFileLines);

$iOSFiles = array();
$androidFiles = array();

echo "Lines: ".count($localizationFileLines)."\n";

if (count($localizationFileLines) > 0)
{
	$i = 0;

	foreach ($localizationFileLines as $line)
	{
		if (trim($line) == "") // Get rid of empty lines
		{
			continue;
		}
	
		$fields = explode("\t", $line);

		$key = $fields[0];
		$values = array_slice($fields, 1);
				
		if ($i == 0)
		{
			$languages = $values;
		}
		else
		{
			$lineIsAComment = count($values) == 0;
		
			if (!$lineIsAComment) // It's not a comment
			{
				
				echo $key,',';
				
				$languageIndex = 0;
								
				foreach ($values as $value)
				{
					$iOSParsedLine = iOSLineParse($key, $value);
					$androidParsedLine = androidLineParse($key, $value);
	
					$languageName = $languages[$languageIndex];
	
					$iOSFiles[$languageName][] = $iOSParsedLine;
					$androidFiles[$languageName][] = $androidParsedLine;
					$jsonFiles[convertLanguageToISO639($languageName)][$key] = $value;
					
					$languageIndex++;
				}
			}	
			else
			{
				$iOSParsedComment = iOSCommentParse($key);
				$androidParsedComment = androidCommentParse($key);
				
				foreach ($languages as $lang)
				{
					$iOSFiles[$lang][] = $iOSParsedComment;
					$androidFiles[$lang][] = $androidParsedComment;
				}
			}	
		}

		$i++;
	}
	
	echo "\n".$outputDivider."\n";
	
	if($argv[3][0] == '1') {
		writeIOSFiles($iOSFiles, $destPath);
	}
	if($argv[3][1] == '1') {
		writeAndroidFiles($androidFiles);
	}
	if($argv[3][2] == '1') {
		writeJSONFiles($jsonFiles, $destPath);
	}
	
}
else
{
	die("Error opening file $localizationFileName");
}

function iOSLineParse($key, $localizedString)
{	
	return "\"$key\" = \"$localizedString\";";
}

function androidLineParse($key, $localizedString)
{
	$localizedString = str_replace("%@", "%s", $localizedString);
	$localizedString = str_replace("'", "\'", $localizedString);
	// Add more rules here.

	return "\t<string name=\"".$key."\">".$localizedString."</string>";
}

function iOSCommentParse($comment)
{
	return "\n// ".$comment;
}

function androidCommentParse($comment)
{
	return "\n\t<!--".$comment."-->";
}

function writeIOSFiles($files, $destPath)
{
    $iOSPath = $destPath;

    $CatPath = "ca.lproj";
    $EnglishPath = "en.lproj";
    $SpanishPath = "es.lproj";
    $GermanPath = "de.lproj";
    $FrenchPath = "fr.lproj";
    $ItalianPath = "it.lproj";
    $PortuguesePath = "pt.lproj";
    $DutchPath = "nl.lproj";
    $SwedishPath = "sv.lproj";

	foreach ($files as $languageName => $lines)
	{
		$directory = "";
		
		if(strcmp($languageName,"Catalan") == 0)
		{
			$directory = $CatPath;
		}
		else if($languageName == "English")
		{
			$directory = $EnglishPath;
		}
		else if($languageName == "Spanish")
		{
			$directory = $SpanishPath;
		}
		else if($languageName == "German")
		{
			$directory = $GermanPath;
		}
		else if($languageName == "French")
		{
			$directory = $FrenchPath;
		}
		else if($languageName == "Italian")
		{
			$directory = $ItalianPath;
		}
		else if($languageName == "Portuguese")
		{
			$directory = $PortuguesePath;
		}	
		else if($languageName == "Dutch")
		{
			$directory = $DutchPath;
		}
		else if($languageName == "Swedish")
		{
			$directory = $SwedishPath;
		}			
		else
		{
			$directory = $languageName;
		}
		
		$filename = $iOSPath."/".$directory."/localizable.strings";
		echo("iOS  - Trying to Write:\n".$filename."\n");	
        createPathIfDoesntExists($filename);
		$fh = fopen($filename, "w");
		
		if ($fh !== FALSE)
		{
			foreach ($lines as $line)
			{
				fwrite($fh, $line."\n");
			}
			
			fclose($fh);
			echo "iOS  - ".chr(27)."[1;32m".'Done'.chr(27)."[0m"."\n\n";
		}
		else
		{
			echo "iOS  - Error opening file $filename to write\n\n";
		}
	}
}

function writeAndroidFiles($files)
{
    $androidPath = "android";

	foreach ($files as $languageName => $lines)
	{
        $filename = $androidPath."/localizations-".$languageName.".xml";
        echo("ANDR - Trying to Write:\n".$filename."\n");	
        createPathIfDoesntExists($filename);
		$fh = fopen($filename, "w");
		
		if ($fh !== FALSE)
		{
			fwrite($fh, "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n");
			fwrite($fh, "<resources>\n\n");
			
			foreach ($lines as $line)
			{
				fwrite($fh, $line."\n\n");
			}
			
			fwrite($fh, "\n</resources>");
			
			fclose($fh);
			
			echo "ANDR - ".chr(27)."[1;32m".'Done'.chr(27)."[0m"."\n\n";
		}
		else
		{
			echo "ANDR - Error opening file $filename to write\n\n";
		}
	}
}

function writeJSONFiles($files,$destPath)
{
	
	$filename = $destPath.'/stringsFromApp.json';
	echo("JSON - Trying to Write:\n".$filename."\n");	
	createPathIfDoesntExists($filename);
		
	$fh = fopen($filename, "w");
	if ($fh !== FALSE) {
		fwrite($fh, json_encode($files));
		echo "JSON - ".chr(27)."[1;32m".'Done'.chr(27)."[0m"."\n\n";
	}
	else
	{
		echo "JSON - Error opening file $filename to write\n\n";
	}
	
}

function createPathIfDoesntExists($filename)
{
    $dirname = dirname($filename);
     if (!is_dir($dirname))
    {
        mkdir($dirname, 0755, true);
    }
}

function convertLanguageToISO639($language) {

	$languages['Catalan'] = "ca";
	$languages['English'] = "en";
	$languages['Spanish'] = "es";
	$languages['German'] = "de";
	$languages['French'] = "fr";
	$languages['Italian'] = "it";
	$languages['Portuguese'] = "pt";
	$languages['Dutch'] = "nl";
	$languages['Swedish'] = "sv";

	return $languages[$language];
}
