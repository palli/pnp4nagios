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

	$url = sprintf("http://graphite/render?width=%s&height=%s&%s", $width, $height, $RRD_CMD);
	$fh = fopen($url, "r");
	$data = stream_get_contents($fh);
	fclose($fh);
        if($data){
            return $data;
        }else{
            return FALSE;
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
}
