<?
function parseMediaInfo($string) {
	$string = trim($string);
	$output = array();
	$outputblock = 0; // counter

	//if no blank lines, skip mi processing
	if (stripos($string, "\n\n") === FALSE && stripos($string, "\r\n\r\n") === FALSE) {
		$output[] = sanitizeHTML($string);
		goto returnOutput;
	}

	// mediainfo data array
	$mi = array();
	$mi['audioformat'] = array();
	$mi['audiobitrate'] = array();
	$mi['audiochannels'] = array();
	$mi['audiolang'] = array();
	$mi['audioprofile'] = array();
	$mi['audiotitle'] = array();
	$mi['filename'] = "Mediainfo log";
	
	//flags
	$inmi = false; // currently processing MI block
	$insection = false; // currently in a MI section
	$miHadBlankLine = false; // MI block must have 1+ blanklines
	$audionum = 0; // count audio tracks
	$section=""; // current section name
	$anymi = false; // debug
	
	//regexes
	$mistart="/^(?:general$|unique id|complete name)/i";
	$misection="/^(?:(?:video|audio|text|menu)(?:\s\#\d+?)*)$/i";
	
	// split on newlines
	$lines = preg_split("/\r\n|\n|\r/", $string);
	
	// main loop
	for ($i=0, $imax=count($lines); $i < $imax; $i++) {
		beginning:
		$line = trim($lines[$i]);
		$prevline = trim($lines[$i-1]);
		
		if (strlen($line) == 0) { // blank line?
			$insection = false;
			$output[$outputblock] .= "\n";
			continue;
		}
		
		if (!$inmi) { // check if it's the start of a MI block
			if (preg_match($mistart, $line)) {
				$inmi = true;
				$anymi = true;
				$insection = true;
				$section = "general";
				$outputblock++;
			}
		}
		
		if ($inmi && $insection && !strlen($line) == 0) {
			// extract mi data
			$array = explode(": ", $line);
			$property = strtolower(trim($array[0]));
			$value = trim($array[1]);
			if ($section === "general") {
				switch ($property) {
					case "complete name":
						$mi['filename'] = miStripPath($value);
						$line = "Complete name : " . $mi['filename'];
						break;
					case "format":
						$mi['generalformat'] = $value;
						break;
					case "duration":
						$mi['duration'] = $value;
						break;
					case "file size":
						$mi['filesize'] = $value;
						break;
				}
			} else if (stripos($section, "video") > -1) {
				switch ($property) {
					case "format":
						$mi['videoformat'] = $value;
						break;
					case "format version":
						$mi['videoformatversion'] = $value;
						break;
					case "codec id":
						$mi['codec'] = strtolower($value);
						break;
					case "width":
						$mi['width'] = miParseSize($value);
						break;
					case "height":
						$mi['height'] = miParseSize($value);
						break;
					case "writing library":
						$mi['writinglibrary'] = $value;
						break;
					case "frame rate mode":
						$mi['frameratemode'] = $value;
						break;
					case "frame rate":
						// if variable this becomes Original frame rate
						$mi['framerate'] = $value;
						break;
					case "display aspect ratio":
						$mi['aspectratio'] = $value;
						break;
					case "bit rate":
						$mi['bitrate'] = $value;
						break;
					case "bit rate mode":
						$mi['bitratemode'] = $value;
						break;
					case "nominal bit rate":
						$mi['nominalbitrate'] = $value;
						break;
					case "bits/(pixel*frame)":
						$mi['bpp'] = $value;
						break;
				}
			} else if (stripos($section, "audio") > -1) {
				switch ($property) {
					case "format":
						$mi['audioformat'][$audionum] = $value;
						break;
					case "bit rate":
						$mi['audiobitrate'][$audionum] = $value;
						break;
					case "channel(s)":
						$mi['audiochannels'][$audionum] = $value;
						break;
					case "title":
						$mi['audiotitle'][$audionum] = $value;
						break;
					case "language":
						$mi['audiolang'][$audionum] = $value;
						break;
					case "format profile":
						$mi['audioprofile'][$audionum] = $value;
						break;
				}
			}
			// not making use of subtitles info yet
			/* else if (stripos($section, "text") > -1) {
				switch ($property) {
					case "language":
					$misubs[] = $value;
				}
			}
			*/
		}
		
		if ($inmi && !$insection) { // is it a section start?
			if (preg_match($misection, $line)) {
				$insection = true;
				$section = $line;
				if (stripos($section, "audio") > -1) {
					$audionum++;
				}
				if (strlen($prevline) == 0) {
					$miHadBlankLine = true;
				}
				goto outputLine;
			}
		}
		
		if ($inmi && !$insection && strlen($prevline) == 0) {
			// end of MI block
			
			if ($miHadBlankLine) {
				$output[$outputblock] = miAddHTML($output[$outputblock], $mi, $audionum);
			}
			
			// reset in case of another mi block
			$mi = array();
			$mi['audioformat'] = array();
			$mi['audiobitrate'] = array();
			$mi['audiochannels'] = array();
			$mi['audiolang'] = array();
			$mi['audioprofile'] =  array();
			$mi['audiotitle'] = array();
			$mi['filename'] = "Mediainfo log";
			// reset flags
			$inmi = false;
			$insection = false;
			$miHadBlankLine = false;
			$audionum = 0;
			$section="";

			$outputblock++;
			goto beginning; // restart loop to process current line
		}
		
		// all tests false? then:
		outputLine:
		$output[$outputblock] .= sanitizeHTML($line) . "\n";
	}
	
	if ($inmi && $miHadBlankLine) { // need to close mi block?
		$output[$outputblock] = miAddHTML($output[$outputblock], $mi, $audionum);
	}

	/*
	if ($anymi) { // debug
		$output[] = "\n--------------------------\n\n" . sanitizeHTML($string);
	}
	*/
	
	returnOutput:
	return str_replace("\n", "<br />\n", trim(implode("", $output)));
}

function miAddHTML($string, $mi, $audionum) {

	$mi['codec'] = miComputeCodec($mi);
	
	$miaudio = array();
	for ($i=1; $i < $audionum+1; $i++) {
		if (strtolower($mi['audioformat'][$i]) === "mpeg audio") {
			switch (strtolower($mi['audioprofile'][$i])) {
				case "layer 3":
					$mi['audioformat'][$i] = "MP3";
					break;
				case "layer 2":	
					$mi['audioformat'][$i] = "MP2";
					break;
				case "layer 1":
					$mi['audioformat'][$i] = "MP1";
					break;
			}
		}
		
		$chans = $mi['audiochannels'][$i];
		$chansreplace = array(
			' '	 => '',
			'channels'	 => 'ch',
			'channel'	 => 'ch',
			'1ch'	 => '1.0ch',
			'7ch'	 => '6.1ch',
			'6ch'	 => '5.1ch',
			'2ch'	 => '2.0ch',
		);
		$chans = str_ireplace(array_keys($chansreplace), $chansreplace, $chans);
		
		$result =
			$mi['audiolang'][$i]
			. " " . $chans
			. " " . $mi['audioformat'][$i];
		if ($mi['audiobitrate'][$i]) {
			$result .= " @ " . $mi['audiobitrate'][$i];
		}
		if ($mi['audiotitle'][$i]) {
			$result .= " (" . $mi['audiotitle'][$i] . ")";
		}
		$miaudio[] = $result;
	}
	
	if (strtolower($mi['frameratemode']) != "constant" && $mi['frameratemode']) {
		$mi['framerate'] = $mi['frameratemode'];
	}
	
	// sanitize input! -----------------------------------
	sanitizeHTML($mi);
	sanitizeHTML($miaudio);
	// ---------------------------------------------------
	
	if (!$mi['bitrate']) {
		if (strtolower($mi['bitratemode']) === "variable") {
			$mi['bitrate'] = "Variable";
		} else {
			$mi['bitrate'] = "<i>" . $mi['nominalbitrate'] . "</i>";
		}
	}

	$midiv_start = "<div><a href='#' onclick='javascript:toggleDisplay(this.nextSibling); return false;'>" . $mi['filename'] . "</a><div class='mediainfo' style='display:none;'>";
	$midiv_end = "</div>";

	$table = '<table class="mediainfo"><tbody><tr><td>'
	. '<table class="nobr"><caption>General</caption><tbody>'
	. '<tr><td>Container:&nbsp;&nbsp;</td><td>'. $mi['generalformat']
	. '</td></tr><tr><td>Runtime:&nbsp;</td><td>' . $mi['duration']
	. '</td></tr><tr><td>Size:&nbsp;</td><td>' . $mi['filesize']
	. '</td></tr></tbody></table></td>'
	. '<td><table class="nobr"><caption>Video</caption><tbody>'
	. '<tr><td>Codec:&nbsp;</td><td>' . $mi['codec']
	. '</td></tr><tr><td>Resolution:&nbsp;</td><td>' . $mi['width'] . 'x' . $mi['height']
	. '</td></tr><tr><td>Aspect&nbsp;ratio:&nbsp;&nbsp;</td><td>' . $mi['aspectratio']
	. '</td></tr><tr><td>Frame&nbsp;rate:&nbsp;</td><td>' . $mi['framerate']
	. '</td></tr><tr><td>Bit&nbsp;rate:&nbsp;</td><td>' . $mi['bitrate']
	. '</td></tr><tr><td>BPP:&nbsp;</td><td>' . $mi['bpp']
	. '</td></tr></tbody></table></td><td>'
	. '<table><caption>Audio</caption><tbody>';
	
	for ($i=0; $i < count($miaudio); $i++) {
		$table .= '<tr><td>#' . intval($i+1) .': &nbsp;</td><td>'
		. $miaudio[$i] . '</td></tr>';
	}
	$table .= '</tbody></table></td></tr></tbody></table>';
	
	return $midiv_start . $string . $midiv_end . $table;
}

function miParseSize($string) {
	$string = str_replace("pixels", "", $string);
	$string = str_replace(" ", "", $string);
	return $string;
}

function miComputeCodec($mi) {
	switch (strtolower($mi['videoformat'])) {
		case "mpeg video":
			switch (strtolower($mi['videoformatversion'])) {
				case "version 2":
					return "MPEG-2";
				case "version 1":
					return "MPEG-1";
			}
			return $mi['videoformat'];
	}
	
	switch (strtolower($mi['codec'])) {
		case "div3":
			return "DivX 3";
		case "divx":
		case "dx50":
			return "DivX";
		case "xvid":
			return "XviD";
		case "x264":
			return "x264";
	}
	
	$chk = strtolower($mi['codec']);
	$wl = strtolower($mi['writinglibrary']);
	if (($chk === "v_mpeg4/iso/avc" || $chk === "avc1") && strpos($wl, "x264 core") === FALSE) {
		return "H264";
	} else if (($chk === "v_mpeg4/iso/avc" || $chk === "avc1") && strpos($wl, "x264 core") > -1)  {
		return "x264";
	} else if (strtolower($mi['videoformat']) === "avc" && strpos($wl, "x264 core") === FALSE) {
		return "H264";
	}
}

function miStripPath($string) { // remove filepath
	$string = str_replace("\\", "/", $string);
	$path_parts = pathinfo($string);
	return $path_parts['basename'];
}
?>