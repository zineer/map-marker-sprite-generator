# map-marker-sprite-generator
A tool for generating Google Map Marker Icons in a sprite image sheet with a custom color marker and a range of alphanumeric symbols

# Demo
Visit [http://mapsprites.zineer.com](http://mapsprites.zineer.com) for a demo.

# Installation
Installing the code is simple as long as you have [Composer](http://getcomposer.com) installed! Just run
```
composer require zineer/map-maker-sprite-generator
```

#Usage
Include the library into your code using
```
require 'vendor/autoload.php';
use Zineer\MarkerSprite\MarkerSprite;
```
then to generate a spritesheet and its associated javascript:
```
$ms = new MarkerSprite(array('FF0000', '00FF00', '0000FF'), 'A-Z', true);
$pngFile = 'markers.png';
$ms->generateImage('images/'.$pngFile);
$js = $ms->generateJS($pngFile);
```