<?
namespace Zineer\MarkerSprite;

class MarkerSprite {
		
	private $symbols;
	private $colors;
	private $numSymbolsPerRow;
	private $markerWidth;
	private $markerHeight;

	function __construct($color, $range, $includeBlank){
		//parse range text
		$inputSymbols = explode(',', $range);
		if(count($inputSymbols) > 1){
			$this->symbols = array_map('trim', $inputSymbols);
		}else{
			$inputSymbols = explode('-', $range);
			if(count($inputSymbols) == 2){
				$inputSymbols = array_map('trim', $inputSymbols);
				$this->symbols = range($inputSymbols[0], $inputSymbols[1]);
			}else{
				$this->symbols = array($range);
			}
		}
		
		if(!is_array($this->symbols) || count($this->symbols) < 0){
			$this->symbols = array('A');
		}
		
		if($includeBlank){
			$this->symbols[] = '';
		}
		
		$this->colors = [];
		if(is_array($color)){
			foreach($color AS $c){
				$cClean = ltrim($c, '#');
				$rgb = sscanf($cClean, "%02x%02x%02x");
				if($rgb != -1){
					while(array_key_exists($cClean, $this->colors)){
						$rgb[2]++;
						$cClean = sprintf('%02x', $rgb[0]) . sprintf('%02x', $rgb[1]) . sprintf('%02x', $rgb[2]);
					}
					$this->colors[$cClean] = $rgb;
				}
			}
		}else{
			$cClean = ltrim($c, '#');
			$rgb = sscanf($cClean, "%02x%02x%02x");
			if($rgb != -1){
				$this->colors[$cClean] = $rgb;
			}
		}
		if(count($this->colors) == 0){
			$this->colors['FFFFFF'] = array(255, 255, 255);
		}
				
		//make it a square sprite sheet
		$this->numSymbolsPerRow = ceil(sqrt(count($this->symbols)*count($this->colors)));
		
		$marker = imagecreatefrompng(__DIR__.'/images/marker.png');
		$this->markerWidth = imagesx($marker);
		$this->markerHeight = imagesy($marker);
		imagedestroy($marker);
	}
	
	public function generateImage($filePath = false){				
		$spriteSheet = imagecreatetruecolor($this->numSymbolsPerRow * $this->markerWidth, $this->numSymbolsPerRow * $this->markerHeight);
		imagefill($spriteSheet, 0, 0, IMG_COLOR_TRANSPARENT);
		imagealphablending($spriteSheet, false);
		imagesavealpha($spriteSheet, true);
		
		$whiteColor = imagecolorallocate($spriteSheet, 255, 255, 255);
		$blackColor = imagecolorallocate($spriteSheet, 0, 0, 0);
		
		$row = 0;
		$col = 0;
		$count = 0;
		foreach($this->colors AS $color){
			$marker = imagecreatefrompng(__DIR__.'/images/marker.png');
			$colorMarker = imagecreatefrompng(__DIR__.'/images/marker.png');
			
			imagefill($colorMarker, 0, 0, IMG_COLOR_TRANSPARENT);
			imageAlphaBlending($colorMarker, false);
			imageSaveAlpha($colorMarker, true);
			
			for ($y = 0; $y < $this->markerHeight; $y++) {
				for ($x = 0; $x < $this->markerWidth; $x++) {
					$rgb = imagecolorat($marker, $x, $y);
					$c = imagecolorsforindex($marker, $rgb);
					$amt = $c['red'] / 255; //amount of r,g,b should be same in all pixels, so we'll use red
							
					$newColor = imagecolorallocatealpha(
						$colorMarker,
						(int)($color[0] * $amt),
						(int)($color[1] * $amt),
						(int)($color[2] * $amt),
						$c['alpha']
					);
					
					imagesetpixel( $colorMarker, $x, $y, $newColor);
				}
			}
			
			// Counting the perceptive luminance - human eye favors green color... 
			if ((1 - ( 0.299 * $color[0] + 0.587 * $color[1] + 0.114 * $color[2])/255) < 0.5){
				$fontColor = $blackColor;
			}else{
				$fontColor = $whiteColor;
			}
			
			foreach($this->symbols AS $string){
				if($string == '#'){
					if($count > 0){
						$string = ($count);
					}else{
						$string = '';
					}
				}
				//generate tmp image with marker and symbol
				$tmp = imagecreatetruecolor($this->markerWidth, $this->markerHeight);
				imagefill($tmp, 0, 0, IMG_COLOR_TRANSPARENT);
				imagecopy($tmp, $colorMarker, 0, 0, 0, 0, $this->markerWidth, $this->markerHeight);
				switch(strlen($string)){
					case 1:
						$xPos = $this->markerWidth / 2 - 3;
						$yPos = 4;
						$font = 3;
						break;
					case 2:
						$xPos = $this->markerWidth / 2 - 7;
						$yPos = 4;
						$font = 3;
						break;
					case 3:
					default:
						$xPos = $this->markerWidth / 2 - (strlen($string) * 3);
						$yPos = 4;
						$font = 2;
						break;
				}
				imagestring($tmp, $font, $xPos, $yPos, $string, $fontColor);
				
				//copy marker with symbol onto sprite sheet
				imagecopy($spriteSheet, $tmp, $col * $this->markerWidth, $row * $this->markerHeight, 0, 0, $this->markerWidth, $this->markerHeight);
				
				imagedestroy($tmp);
				
				$col++;
				$count++;
				if($col >= $this->numSymbolsPerRow){
					$col = 0;
					$row++;
				}
			}
			imagedestroy($marker);
			imagedestroy($colorMarker);
		}
		
		if($filePath){
			return imagepng($spriteSheet, $filePath);
		}else{		
			header('Content-Disposition: Attachment;filename=markers.png');
			header("Content-type: image/png");
			imagepng($spriteSheet);
		}
	}
	
	public function generateJS($filename = 'markers.png'){
		$baseIcon = array(
			'url' => '\''.$filename.'\'',
			'size' => 'new google.maps.Size('.$this->markerWidth.', '.$this->markerHeight.')',
			'anchor' => 'new google.maps.Point('.($this->markerWidth / 2).', '.($this->markerHeight - 2).')',
			'scaledSize' => 'new google.maps.Size('.($this->numSymbolsPerRow * $this->markerWidth).', '.($this->numSymbolsPerRow * $this->markerHeight).')'
		);
		
		$includeColorName = count($this->colors) > 1;
		$icons = array();
		$row = 0;
		$col = 0;
		foreach($this->colors AS $hex => $color){
			foreach($this->symbols AS $string){
				$baseIcon['origin'] = 'new google.maps.Point('.($col * $this->markerWidth).', '.($row * $this->markerHeight).')';
				
				$icons['icon'.($includeColorName?'_'.$hex:'').($string?'_'.$string:'')] = $baseIcon;
				
				$col++;
				if($col >= $this->numSymbolsPerRow){
					$col = 0;
					$row++;
				}
			}
		}
		
		$e = "\n";
		$t = "\t";
		$str  = 'var icons = {'.$e;
		foreach($icons AS $name => $params){
			$str .= $t.$name.': {'.$e;
			foreach($params AS $p => $v){
				$str .= $t.$t.$p.': '.$v.','.$e;
			}
			$str .= $t.'},'.$e;
		}
		$str .= '}';
		return $str;
	}
	
	//this is just a utility function used limit the number of markers generated by demo code
	public function truncateSymbols($max){
		array_splice($this->symbols, $max);
		$this->numSymbolsPerRow = ceil(sqrt(count($this->symbols)*count($this->colors)));
	}
	
	public function __get($name){
		return $this->$name;
	}
}


