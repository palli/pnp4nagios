<?php defined('SYSPATH') OR die('No direct access allowed.');
/**
 * Retrieves and manipulates current status of hosts (and services?)
 */
class Graphite_Model extends System_Model
{

    private $RRD_CMD   = FALSE;
    /*
    *
    *
    */
    public function __construct(){
        $this->config = new Config_Model();
        $this->config->read_config();
    }


    public function doImage($RRD_CMD, $out='STDOUT') {
        $conf = $this->config->conf;
        # construct $command to rrdtool
	$command = '';
        $width = 0;
        $height = 0;
        if ($out == 'PDF'){
            if($conf['pdf_graph_opt']){
                $command .= $conf['pdf_graph_opt'];
            }
            if (isset($conf['pdf_width']) && is_numeric($conf['pdf_width'])){
                $width = abs($conf['pdf_width']);
            }
            if (isset($conf['pdf_height']) && is_numeric($conf['pdf_height'])){
                $height = abs($conf['pdf_height']);
            }
        }else{
            if($conf['graph_opt']){
                $command .= $conf['graph_opt'];
            }
            if(is_numeric($conf['graph_width'])){
                $width = abs($conf['graph_width']);
            }
            if(is_numeric($conf['graph_height'])){
                $height = abs($conf['graph_height']);
            }
        }

	$url = sprintf("%s?width=%s&height=%s&%s", $conf['graphite-web'], $width, $height, $RRD_CMD);
	$ch = curl_init();
	curl_setopt( $ch, CURLOPT_URL, $url );
	curl_setopt( $ch, CURLOPT_RETURNTRANSFER, True );
	curl_setopt( $ch, CURLOPT_USERAGENT, "pnp4nagios" );
	$data = curl_exec($ch);
	$info = curl_getinfo($ch);
        if($info['http_code'] == 200){
            return $data;
        }else{
            $data =  "ERROR: Grahite retuns HTTP State ". $info['http_code']."\n";
            $data .= "URL: ". $info['url'];
            return $data;
        }
    }


    public function streamImage($data = FALSE){
        if ( $data === FALSE ){
            header("Content-type: image/png");
            echo base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAAAXNSR0IArs4c6QAAAAZiS0dEAP8A
                /wD/oL2nkwAAAAlwSFlzAAALEwAACxMBAJqcGAAAAAd0SU1FB9kCCAoDKSKZ0rEAAAAZdEVYdENv
                bW1lbnQAQ3JlYXRlZCB3aXRoIEdJTVBXgQ4XAAAADUlEQVQI12NgYGBgAAAABQABXvMqOgAAAABJ
                RU5ErkJggg==');
            return;       
	}

	if (preg_match('/^ERROR/', $data)) {
            if(preg_match('/NOT_AUTHORIZED/', $data)){
                // TODO: i18n
                $data .= "\n\nYou are not authorized to view this Image";
                // Set font size
                $font_size = 3;
            }else{
                // Set font size
		$data = $this->format_graphite_debug($data);
                $font_size = 1.5;
            }
            $ts=explode("\n",$data);
            $width=0;
            foreach ($ts as $k=>$string) {
                $width=max($width,strlen($string));
            }

            $width  = imagefontwidth($font_size)*$width;
            if($width <= $this->config->conf['graph_width']){
                $width = $this->config->conf['graph_width'];
            }
            $height = imagefontheight($font_size)*count($ts);
            if($height <= $this->config->conf['graph_height']){
                $height = $this->config->conf['graph_height'];
            }
            $el=imagefontheight($font_size);
            $em=imagefontwidth($font_size);
            // Create the image pallette
            $img = imagecreatetruecolor($width,$height);
            // Dark red background
            $bg = imagecolorallocate($img, 0xAA, 0x00, 0x00);
            imagefilledrectangle($img, 0, 0,$width ,$height , $bg);
            // White font color
            $color = imagecolorallocate($img, 255, 255, 255);

            foreach ($ts as $k=>$string) {
                // Length of the string
                $len = strlen($string);
                // Y-coordinate of character, X changes, Y is static
                $ypos_offset = 5;
                $xpos_offset = 5;
                // Loop through the string
                for($i=0;$i<$len;$i++){
                      // Position of the character horizontally
                      $xpos = $i * $em + $ypos_offset;
                      $ypos = $k * $el + $xpos_offset;
                      // Draw character
                      imagechar($img, $font_size, $xpos, $ypos, $string, $color);
                      // Remove character from string
                      $string = substr($string, 1);
                }
            }
            header("Content-type: image/png");
            imagepng($img);
            imagedestroy($img);
        }else{
            header("Content-type: image/png");       
            echo $data;
	}
    }

    public function saveImage($data = FALSE){
        $img = array();
        $img['file'] = tempnam($this->config->conf['temp'],"PNP");
        if(!$fh = fopen($img['file'],'w') ){
            throw new Kohana_Exception('save-rrd-image', $img['file']);
        }
        fwrite($fh, $data);
        fclose($fh);
        if (function_exists('imagecreatefrompng')) {
                $image = imagecreatefrompng($img['file']);
                imagepng($image, $img['file']);
                list ($img['width'], $img['height'], $img['type'], $img['attr']) = getimagesize($img['file']);
        }
        return $img;
    }

    private function format_graphite_debug($data) {
        $data = preg_replace('/(&)/',"\n", $data);
        return $data;
    }
}
