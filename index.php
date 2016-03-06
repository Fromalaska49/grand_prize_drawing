<?php

/*******
csvToXML Converter for Texas Weddings Ltd.
Convert a list of CSV files to a single XML file containing the Firstname and Lastname fields as person objects
Author: Larry Davis (lazd@lazd.net)
Date: 2010-01-14
*******/

/**
Check a global array to see if the input is unique
*/
function uniqueEmail($email) {
	global $uniqueEmailList;
	if (!isset($uniqueEmailList))
		$uniqueEmailList = array();
	if (isset($uniqueEmailList[$email]))
		return false;
	else
		$uniqueEmailList[$email]=true;
	return true;
}

$siteHeader = <<<EOD
	<html>
	<head>
		<title>CSV to XML Conversion</title>
		<script type="text/javascript">
			function addUploader(destId) {
				var tempDiv = document.createElement('div');
				tempDiv.className = "lazdMultiField";

				tempDiv.innerHTML += '<input type="file" name="files[]" />';

				var qC = document.getElementById(destId).childNodes;
				if (qC.length==0)
					document.getElementById(destId).innerHTML = tempDiv.innerHTML;
				else
					domInsertAfter(tempDiv,qC[qC.length-1]);
			}

			function domInsertAfter(newChild, refChild) {
				refChild.parentNode.insertBefore(newChild,refChild.nextSibling);
			}
		</script>	
		<style type="text/css">

			body {
				font-family: Verdana;
			}
			input {
				margin: 5px 0;
			}
			.button {
				margin: 4px;
				padding: 2px 3px 3px 3px;
				border: 2px solid #aaa;
				-moz-border-radius: 5px;
				text-decoration: none;
				color: #000;
				-webkit-border-radius: 5px;
				background-color: #f1f1f1;
			}
			.green {
				background-color: #d1fcd1;
			}
			#uploaders {
				margin: 10px 0; 
			}

		</style>
	</head>
	<body>
	<h2>CSV to XML Conversion</h2>
EOD;

$siteFooter = <<<EOD
</body>
</html>
EOD;


if (!isset($_POST['convert'])) {

	// inital upload form
	print $siteHeader;
	?>
	<form name="convert" enctype="multipart/form-data" method="post" action="index.php">
		<input type="hidden" name="convert" value="1" />
		<a href="javascript:addUploader('uploaders');">[+] Add Uploader</a><br />
		<div id="uploaders"><input type="file" name="files[]" /></div>
		<a class="button green" href="javascript:document.convert.submit();">Convert</a>
	</form>
	<?php
	print $siteFooter;
}
else {
	
	// build array of uploaded file paths
	$files = array();
	foreach ($_FILES['files']['tmp_name'] as $file) {
		if ($file != '') {
			$files[] = $file;
		}
	}
	
	if (count($files)!=0) {
		//delete old file
		unlink("names.xml");
		
		//begin XML output
		$xmlOutput = '<?phpxml version="1.0" encoding="utf-8"?>'."\n".'<people>'."\n";
		
		$duplicates = 0;
		$nameCount = 0;
		$firstNameField = 0;
		$lastNameField = 1;
		$emailField = 2;
		foreach ($files as $fileName) {
			if ($fh = fopen($fileName, "r")) {
				$i = 0;
			    while ($data = fgetcsv($fh, 65535, ",")) {
			    	if ($i == 0) {
						$numFields = count($data);
				        for ($j = 0; $j < $numFields; $j++) {
				            if (preg_match('/^first.*name$/i',$data[$j]))
								$firstNameField = $j;
							else if (preg_match('/^last.*name$/i',$data[$j]))
								$lastNameField = $j;
							else if (preg_match('/^e-{0,1}mail/i',$data[$j]))
								$emailField = $j;
				        }
					}
					else {
						$name = '';
						$email = '';
						
						// correct case in names
						if (!empty($data[$firstNameField])) {
							if (strtolower($data[$firstNameField]) == $data[$firstNameField] || strtoupper($data[$firstNameField]) == $data[$firstNameField])
								$data[$firstNameField] = ucwords(strtolower($data[$firstNameField]));
							$name .= $data[$firstNameField];
						}
						if (!empty($data[$lastNameField])) {
							if (strtolower($data[$lastNameField]) == $data[$lastNameField] || strtoupper($data[$lastNameField]) == $data[$lastNameField])
								$data[$lastNameField] = ucwords(strtolower($data[$lastNameField]));
							$name .= ' '.$data[$lastNameField];
						}

						if (!empty($data[$emailField])) {
							$email = $data[$emailField];
						}
						if (!empty($name)) {
							// check for unique e-mail address, escape name, add to output
							if (empty($email) || uniqueEmail($email)) {
								if(mb_detect_encoding($name)=='ASCII'&&mb_detect_encoding($email)=='ASCII'){
									$xmlOutput .= '<person name="'.htmlentities($name).'" email="'.htmlentities($email).'"></person>'."\n";
									$nameCount++;
								}
							}
							else {
								$duplicates++;
								//print 'Found duplicate: '.$name.'<br />';
							}
						}
					}
					$i++;
			    }
			    fclose($fh);
			}
		}

		//end XML output
		$xmlOutput .= '</people>';
		
		// write output
		$myFile = "names.xml";
		$fh = fopen($myFile, 'w') or die("Can't open names.xml for writing!");
		fwrite($fh, $xmlOutput);
		fclose($fh);
		
		//header('Location: names.xml'); // redirect to output file
		
		header('Location: drawing.html'); // redirect to output file
		
		// provide results page
		print $siteHeader;
		?>
		<ul>
		<li><?php print count($files); ?> file<?php print (count($files)==1)?'':'s'; ?> processed.</li>
		<li><?php print $nameCount; ?> name<?php print ($nameCount==1)?'':'s'; ?> processed.</li>
		<li><?php print $duplicates; ?> duplicate<?php print ($duplicates==1)?'':'s'; ?> ignored.</li>
		</ul>
		<a class="button green" href="names.xml">Download names.xml</a>
		<a class="button" href="index.php">Convert Again</a>
		<br /><br />
		<object width="550" height="400">
			<param name="movie" value="output/names.swf">
			<embed src="output/names.swf" width="550" height="400"></embed>
		</object>
		<?php
		print $siteFooter;
	}
	else
		print 'Error, no files were uploaded!';
}

?>