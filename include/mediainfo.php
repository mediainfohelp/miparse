<?
class miparse {

	// options
	public	$checkEncodingSettings = FALSE;
	public	$characterSet = 'ISO-8859-1';  // hopefully your site is UTF-8, if so change this default

	// outputs
	public 	$filename = 'Mediainfo log';
	public	$sanitizedLog = '';
	public	$audio = array();
	public	$logs = array(); // will contain an object for each mediainfo log processed

	// internal use
	private $hadBlankLine = FALSE; // only parse as a mediainfo log if it included a blank line
	private $audionum = 0;
	private $currentSection = ''; // tracks log section while parsing
	
	/**
	 * Public interface for parsing text containing any amount of mediainfo logs
	 * @param string $string	input text
	 * 
	 * @property-write string $output	final HTML output
	 * @property-write array $logs	one object per log
	*/
	public function parse($string) {
		
		$string = trim($string);
		$output = array();
		$outputblock = 0; // counter
		$logcount = 0;
		
		//flags
		$inmi = false; // currently in a mediainfo log
		$insection = false; // currently in a mediainfo log section
		$anymi = false; // debug
		
		//regexes
		$mistart="/^(?:general$|unique ?id(\/string)?\s*:|complete ?name\s*:|format\s*:\s*(matroska|avi|bdav)$)/i";
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
			
			if (!$inmi) { 
				if (preg_match($mistart, $line)) { // start of a mediainfo log?
					
					$Log = new miparse;  // create an instance of the class
					if ($this->checkEncodingSettings === TRUE) {
						$Log->checkEncodingSettings = TRUE;
					}
					
					$inmi = true;
					$anymi = true;
					$insection = true;
					$Log->currentSection = "general";
					$outputblock++;
				}
			}
			
			if ($inmi && $insection && !strlen($line) == 0) {
				$line = $Log->parseProperties($line); // parse "property : value" pairs
			}
			
			if ($inmi && !$insection) { 
				if (preg_match($misection, $line)) { // is it a section start?
					$insection = true;
					$Log->currentSection = $line;
					if (stripos($Log->currentSection, "audio") > -1) {
						$Log->audionum++;
					}
					if (strlen($prevline) == 0) {
						$Log->hadBlankLine = true;
					}
					goto outputLine;
				}
			}
			
			if ($inmi && !$insection && strlen($prevline) == 0) {
				// end of a mediainfo log
				
				if ($Log->hadBlankLine) {
					$Log->sanitizedLog = $output[$outputblock];
					$output[$outputblock] = $Log->addHTML();
					$this->logs[$logcount] = $Log; // store current $Log object in array
					$logcount++;
				}
				$outputblock++;
				
				// reset flags
				$inmi = false;
				$insection = false;
				$Log->currentSection='';
				
				goto beginning; // restart loop to process current line
			}
			
			// all tests false? then:
			outputLine:
			$output[$outputblock] .= self::sanitizeHTML($line) . "\n";
		}
		
		if ($inmi && $Log->hadBlankLine) { // need to close mi block?
			$Log->sanitizedLog = $output[$outputblock];
			$output[$outputblock] = $Log->addHTML();
			$this->logs[$logcount] = $Log; // store current $Log object in array
		}
		
		returnOutput:
		$this->output = str_replace("\n", "<br />\n", trim(implode("", $output)));
	}

	
	/**
	 * parse "property : value" pairs, load data into object
	 * @param string $line
	 * @return string
	*/ 
	protected function parseProperties($line) {
		$array = explode(":", $line, 2);
		$property = strtolower(trim($array[0]));
		$property = preg_replace("#/string$#", "", $property);
		$value = trim($array[1]);
		
		if (strtoupper($array[0]) == $array[0]) {
			// ignore ALL CAPS tags, as set by mkvmerge 7
			$property = "";
		}
		
		if ($this->currentSection === "general") {
			switch ($property) {
				case "complete name":
				case "completename":
					$this->filename = self::stripPath($value);
					$line = "Complete name : " . $this->filename;
					break;
				case "format":
					$this->generalformat = $value;
					break;
				case "duration":
					$this->duration = $value;
					break;
				case "file size":
				case "filesize":
					$this->filesize = $value;
					break;
			}
		} else if (stripos($this->currentSection, "video") > -1) {
			switch ($property) {
				case "format":
					$this->videoformat = $value;
					break;
				case "format version":
				case "format_version":
					$this->videoformatversion = $value;
					break;
				case "codec id":
				case "codecid":
					$this->codec = strtolower($value);
					break;
				case "width":
					$this->width = self::parseSize($value);
					break;
				case "height":
					$this->height = self::parseSize($value);
					break;
				case "writing library":
				case "encoded_library":
					$this->writinglibrary = $value;
					break;
				case "frame rate mode":
				case "framerate_mode":
					$this->frameratemode = $value;
					break;
				case "frame rate":
				case "framerate":
					// if variable this becomes Original frame rate
					$this->framerate = $value;
					break;
				case "display aspect ratio":
				case "displayaspectratio":
					$this->aspectratio = str_replace("/", ":", $value); // mediainfo sometimes uses / instead of :
					break;
				case "bit rate":
				case "bitrate":
					$this->bitrate = $value;
					break;
				case "bit rate mode":
				case "bitrate_mode":
					$this->bitratemode = $value;
					break;
				case "nominal bit rate":
				case "bitrate_nominal":
					$this->nominalbitrate = $value;
					break;
				case "bits/(pixel*frame)":
				case "bits-(pixel*frame)":
					$this->bpp = $value;
					break;
				case "bit depth":
				case "bitdepth":
					$this->bitdepth = $value;
					break;
				case "encoding settings":
					$this->encodingsettings = $value;
					break;
			}
		} else if (stripos($this->currentSection, "audio") > -1) {
			switch ($property) {
				case "format":
					$this->audio[$this->audionum]['format'] = $value;
					break;
				case "bit rate":
				case "bitrate":
					$this->audio[$this->audionum]['bitrate'] = $value;
					break;
				case "channel(s)":
					$this->audio[$this->audionum]['channels'] = $value;
					break;
				case "title":
					$this->audio[$this->audionum]['title'] = $value;
					break;
				case "language":
					$this->audio[$this->audionum]['lang'] = $value;
					break;
				case "format profile":
				case "format_profile":
					$this->audio[$this->audionum]['profile'] = $value;
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
		
		return $line;
	}
	
	
	/**
	 * compute mediainfo specs and add HTML
	 * @return string HTML
	*/
	protected function addHTML() {
		
		$this->codeccomputed = $this::computeCodec();
		
		$miaudio = array();
		for ($i=1; $i < count($this->audio)+1; $i++) {
			if (strtolower($this->audio[$i]['format']) === "mpeg audio") {
				switch (strtolower($this->audio[$i]['profile'])) {
					case "layer 3":
						$this->audio[$i]['format'] = "MP3";
						break;
					case "layer 2":	
						$this->audio[$i]['format'] = "MP2";
						break;
					case "layer 1":
						$this->audio[$i]['format'] = "MP1";
						break;
				}
			}
			
			$chansreplace = array(
				' '	 => '',
				'channels'	 => 'ch',
				'channel'	 => 'ch',
				'1ch'	 => '1.0ch',
				'7ch'	 => '6.1ch',
				'6ch'	 => '5.1ch',
				'2ch'	 => '2.0ch'
			);
			$chans = str_ireplace(array_keys($chansreplace), $chansreplace, $this->audio[$i]['channels']);
			
			$result =
				$this->audio[$i]['lang']
				. " " . $chans
				. " " . $this->audio[$i]['format'];
			if ($this->audio[$i]['bitrate']) {
				$result .= " @ " . $this->audio[$i]['bitrate'];
			}
			if ($this->audio[$i]['title']
				&& (stripos($this->filename, $this->audio[$i]['title']) === FALSE) ) { // ignore audio track title if it contains filename
				$result .= " (" . $this->audio[$i]['title'] . ")";
			}
			$miaudio[] = $result;
		}

		if (strtolower($this->frameratemode) != "constant" && $this->frameratemode) {
			$this->framerate = $this->frameratemode;
		}
		
		if (!$this->bitrate) {
			if (strtolower($this->bitratemode) === "variable") {
				$this->bitrate = "Variable";
			} else {
				$this->bitrate = $this->nominalbitrate;
				$italicBitrate = TRUE;
			}
		}
		
		// begin building HTML //
		$midiv_start = "<div><a href='#' onclick='javascript:toggleDisplay(this.nextSibling); return false;'>"
		. self::sanitizeHTML($this->filename)
		. "</a><div class='mediainfo' style='display:none;'>";
		$midiv_end = "</div>";

		$table = '<table class="mediainfo"><tbody><tr><td>'
		. '<table class="nobr"><caption>General</caption><tbody>'
		. '<tr><td>Container:&nbsp;&nbsp;</td><td>'. self::sanitizeHTML($this->generalformat)
		. '</td></tr><tr><td>Runtime:&nbsp;</td><td>' . self::sanitizeHTML($this->duration)
		. '</td></tr><tr><td>Size:&nbsp;</td><td>' . self::sanitizeHTML($this->filesize)
		. '</td></tr></tbody></table></td>'
		. '<td><table class="nobr"><caption>Video</caption><tbody>'
		. '<tr><td>Codec:&nbsp;</td><td>' . self::sanitizeHTML($this->codeccomputed);
		
		if (stripos($this->bitdepth, "10 bit") !== FALSE) {
			$table .= " (10-bit)";
		}
		
		$table .= '</td></tr><tr><td>Resolution:&nbsp;</td><td>' . self::sanitizeHTML($this->width) . 'x' . self::sanitizeHTML($this->height) . "&nbsp;" . self::displayDimensions()
		. '</td></tr><tr><td>Aspect&nbsp;ratio:&nbsp;&nbsp;</td><td>' . self::sanitizeHTML($this->aspectratio)
		. '</td></tr><tr><td>Frame&nbsp;rate:&nbsp;</td><td>' . self::sanitizeHTML($this->framerate)
		. '</td></tr><tr><td>Bit&nbsp;rate:&nbsp;</td><td>'; 
		
		if ($italicBitrate === TRUE) {
			$table .= "<em>" . self::sanitizeHTML($this->bitrate) . "</em>";
		} else {
			$table .= self::sanitizeHTML($this->bitrate);
		}
		
		$table .= '</td></tr><tr><td>BPP:&nbsp;</td><td>' . self::sanitizeHTML($this->bpp)
		. '</td></tr></tbody></table></td><td>'
		. '<table><caption>Audio</caption><tbody>';

		for ($i=0; $i < count($miaudio); $i++) {
			$table .= '<tr><td>#' . intval($i+1) .': &nbsp;</td><td>'
			. self::sanitizeHTML($miaudio[$i]) . '</td></tr>';
		}

		$table .= '</tbody></table></td></tr>';
		
		if ($this->checkEncodingSettings && $this->encodingsettings) {
			$poorSpecs = $this->checkEncodingSettings();
			if ($poorSpecs) {
				$table .= '<tr><td colspan="3">
				Encoding specs checks: '
				. self::sanitizeHTML($poorSpecs)
				. '</td></tr>';
			}
		}
		
		$table .= '</tbody></table>';
		
		return $midiv_start . $this->sanitizedLog . $midiv_end . $table;
	}

	/**
	 * check video encoding settings
	 * @return string or null
	*/
	protected function checkEncodingSettings() {
		$poorSpecs = array();
		$settings = explode("/", $this->encodingsettings);
		
		foreach($settings as $str) {
			$arr = explode("=", $str);
			$property = strtolower( trim($arr[0]) );
			$value = trim( $arr[1] );
			
			switch ($property) {
				case "rc_lookahead":
					if ($value < 60) {
						$poorSpecs[] = "rc_lookahead=".$value." (<60)";
					}
					break;
				
				case "subme":
					if ($value < 9) {
						$poorSpecs[] = "subme=".$value." (<9)";
					}
					break;
			}
		}
		
		return implode(". ", $poorSpecs);	
	}
	
	/**
	 * calculates approximate display dimensions of anamorphic video
	 * @return string HTML or null
	*/
	private function displayDimensions() {
		$w = intval($this->width);
		$h = intval($this->height);
		if ($h < 1 || $w < 1 || !$this->aspectratio) {
			return; // bad input
		}
		
		$ar = explode(":", $this->aspectratio);
		if (count($ar) > 1) {
			$ar = $ar[0] / $ar[1]; // e.g. 4:3 becomes 1.333...
		} else {
			$ar = $ar[0];
		}
		
		$calcw = intval($h * $ar);
		$calch = intval($w / $ar);
		$output = $calcw . "x" . $h;
		$outputAlt = $w . "x" . $calch;
		
		$chk = 27;
		$chkw = $calcw > ($w-$chk) && $calcw < ($w+$chk);
		$chkh = $calch > ($h-$chk) && $calch < ($h+$chk);
		if ($chkw && $chkh) {
			// calculated dimensions are +/-$chk pixels of source dimensions, return null
			return; 
		}
		
		if ( ($w * $calch) > ($calcw * $h) ) { // pick greater overall size
			$tmp = $output;
			$output = $outputAlt;
			$outputAlt = $tmp;
		}
		
		return "~&gt;&nbsp;<span title='Alternatively "
			. $outputAlt . "'>" . $output . "</span>";
	}

	/**
	 * Removes unneeded data from $string when calculating width and height in pixels
	 * @param string $string
	 * @return string
	*/
	private function parseSize($string) {
		return str_replace(array('pixels', ' '), null, $string);
	}

	/**
	 * Calculates the codec of the input mediainfo file
	 * @return string
	*/
	private function computeCodec() {
		switch (strtolower($this->videoformat)) {
			case "mpeg video":
				switch (strtolower($this->videoformatversion)) {
					case "version 2":
						return "MPEG-2";
					case "version 1":
						return "MPEG-1";
				}
				return $this->videoformat;
		}
		
		switch (strtolower($this->codec)) {
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
		
		$chk = strtolower($this->codec);
		$wl = strtolower($this->writinglibrary);
		if (($chk === "v_mpeg4/iso/avc" || $chk === "avc1") && strpos($wl, "x264 core") === FALSE) {
			return "H264";
		} else if (($chk === "v_mpeg4/iso/avc" || $chk === "avc1") && strpos($wl, "x264 core") > -1)  {
			return "x264";
		} else if (strtolower($this->videoformat) === "avc" && strpos($wl, "x264 core") === FALSE) {
			return "H264";
		}
	}

	/**
	 * Removes file path from $string
	 * @param string $string
	 * @return string
	*/
	private function stripPath($string) {
		$string = str_replace("\\", "/", $string);
		$path_parts = pathinfo($string);
		return $path_parts['basename'];
	}


	/**
	 * Function to sanitize user input
	 * @param mixed $value str or array
	 * @return mixed sanitized output
	*/
	private function sanitizeHTML (&$value) {
	
		if (is_array($value)){
			foreach ($value as $k => $v){
				$value[$k] = self::sanitizeHTML($v);
			}
		}
		
		return htmlentities((string) $value, ENT_QUOTES, $this->characterSet);
	}

} // end class

