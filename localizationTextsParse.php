#!/usr/bin/php

<?php 

$url = $argv[1];

if (!$argv[1])
{
    die("\n".'ERROR: Syntax: localizationTextsParse <url>'."\n\n");
}

$localizationFileLines = file_get_contents($url);
var_dump($localizationFileLines);

$localizationFileLines = explode("\n", $localizationFileLines);

$iOSFiles = array();
$androidFiles = array();

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
				$languageIndex = 0;
				
				foreach ($values as $value)
				{
					$iOSParsedLine = iOSLineParse($key, $value);
					$androidParsedLine = androidLineParse($key, $value);
	
					$languageName = $languages[$languageIndex];
	
					$iOSFiles[$languageName][] = $iOSParsedLine;
					$androidFiles[$languageName][] = $androidParsedLine;
					
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
	
	writeIOSFiles($iOSFiles);
	
	writeAndroidFiles($androidFiles);
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

function writeIOSFiles($files)
{
    $iOSPath = "iPhone";

    $CatPath = "ca.lproj";
    $EnglishPath = "en.lproj";
    $SpanishPath = "es.lproj";

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
		else
		{
			$directory = $languageName;
		}
		
		$filename = $iOSPath."/".$directory."/localizable.strings";
        createPathIfDoesntExists($filename);
		$fh = fopen($filename, "w");
		
		if ($fh !== FALSE)
		{
			foreach ($lines as $line)
			{
				fwrite($fh, $line."\n");
			}
			
			fclose($fh);
		}
		else
		{
			echo "Error opening file $filename to write";
		}
	}
}

function writeAndroidFiles($files)
{
    $androidPath = "android";

	foreach ($files as $languageName => $lines)
	{
        $filename = $androidPath."/localizations-".$languageName.".xml";
        createPathIfDoesntExists($filename);
		$fh = fopen($filename, "w");
		
		if ($fh !== FALSE)
		{
			fwrite($fh, "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n");
			fwrite($fh, "<resources>\n\n");
			
			foreach ($lines as $line)
			{
				fwrite($fh, $line."\n");
			}
			
			fwrite($fh, "\n</resources>");
			
			fclose($fh);
		}
		else
		{
			echo "Error opening file $filename to write";
		}
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
