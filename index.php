<?php 
/*
Plugin Name: Portfolio Maker
Plugin URI:  https://github.com/
Description: Let's make a portfolio and docx to posts
Version:     1.0
Author:      Tom Woodward
Author URI:  http://altlab.vcu.edu
License:     GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Domain Path: /languages
Text Domain: opened-duplicator

*/
//namespace Docx_reader;
//use ZipArchive;

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

// built as a result https://twitter.com/JohnStewartPhD/status/1116874603157184512

add_action('wp_enqueue_scripts', 'portfolio_duplicator_scripts');

function portfolio_duplicator_scripts() {                           
    $deps = array('jquery');
    $version= '1.0'; 
    $in_footer = true;    
    wp_enqueue_script('portfolio-dup-main-js', plugin_dir_url( __FILE__) . 'js/portfolio-dup-main.js', $deps, $version, $in_footer); 
    wp_enqueue_style( 'portfolio-dup-main-css', plugin_dir_url( __FILE__) . 'css/portfolioi-dup-main.css');
}

add_action( 'gform_after_submission_1', 'make_portfolio_cloner', 10, 2 );//specific to the gravity form id

function make_portfolio_cloner($entry, $form){
    $_POST =  [
          'action'         => 'process',
          'clone_mode'     => 'core',
          'source_id'      => 30, //specific to the site your cloneing
          'target_name'    => rgar( $entry, '1' ), //specific to the form entry fields - need to parallel site url restrictions URL/DOMAIN
          'target_title'   => rgar( $entry, '2' ), //specific to the form entry fields TITLE
          'disable_addons' => true,
          'clone_nonce'    => wp_create_nonce('ns_cloner')
      ];
    
    // Setup clone process and run it.
    $ns_site_cloner = new ns_cloner();
    $ns_site_cloner->process();


    //NOW DEAL WITH DOCX FILES
    $about = docToPage('http://192.168.33.10/wordpress/sbees/wp-content/uploads/sites/35/2019/04/Support-for-Making-Better-Computer-Choices.docx');
    $teaching = 'here is the teaching page content';
    $research = 'here is the research page content';

    $site_id = $ns_site_cloner->target_id;
    $site_info = get_blog_details( $site_id );
    if ( $site_info ) {
     // Clone successful!
        switch_to_blog($site_id);        
        makePortfolioPage('About', $about);
        makePortfolioPage('Teaching', $teaching);
        makePortfolioPage('Research', $research);
    }
}



function makePortfolioPage($title,$content){
    $page_id = get_page_by_title($title);
    // Update post 37
  $new_page = array(
      'ID'           => $page_id->ID,
      'post_content' => $content,
  );

// Update the post into the database
  wp_update_post( $new_page );

}


class Docx_reader {

    private $fileData = false;
    private $errors = array();
    private $styles = array();

    public function __construct() {
        
    }

    private function load($file) {
        if (file_exists($file)) {
            $zip = new ZipArchive();
            $openedZip = $zip->open($file);
            if ($openedZip === true) {
                //attempt to load styles:
                if (($styleIndex = $zip->locateName('word/styles.xml')) !== false) {
                    $stylesXml = $zip->getFromIndex($styleIndex);
                    $xml = simplexml_load_string($stylesXml);
                    $namespaces = $xml->getNamespaces(true);

                    $children = $xml->children($namespaces['w']);

                    foreach ($children->style as $s) {
                        $attr = $s->attributes('w', true);
                        if (isset($attr['styleId'])) {
                            $tags = array();
                            $attrs = array();
                            foreach (get_object_vars($s->rPr) as $tag => $style) {
                                $att = $style->attributes('w', true);
                                switch ($tag) {
                                    case "b":
                                        $tags[] = 'strong';
                                        break;
                                    case "i":
                                        $tags[] = 'em';
                                        break;
                                    case "color":
                                        //echo (String) $att['val'];
                                        $attrs[] = 'color:#' . $att['val'];
                                        break;
                                    case "sz":
                                        $attrs[] = 'font-size:' . $att['val'] . 'px';
                                        break;
                                }
                            }
                            $styles[(String)$attr['styleId']] = array('tags' => $tags, 'attrs' => $attrs);
                        }
                    }
                    $this->styles = $styles;
                }

                if (($index = $zip->locateName('word/document.xml')) !== false) {
                    // If found, read it to the string
                    $data = $zip->getFromIndex($index);
                    // Close archive file
                    $zip->close();
                    return $data;
                }
                $zip->close();
            } else {
                switch($openedZip) {
                    case ZipArchive::ER_EXISTS:
                        $this->errors[] = 'File exists.';
                        break;
                    case ZipArchive::ER_INCONS:
                        $this->errors[] = 'Inconsistent zip file.';
                        break;
                    case ZipArchive::ER_MEMORY:
                        $this->errors[] = 'Malloc failure.';
                        break;
                    case ZipArchive::ER_NOENT:
                        $this->errors[] = 'No such file.';
                        break;
                    case ZipArchive::ER_NOZIP:
                        $this->errors[] = 'File is not a zip archive.';
                        break;
                    case ZipArchive::ER_OPEN:
                        $this->errors[] = 'Could not open file.';
                        break;
                    case ZipArchive::ER_READ:
                        $this->errors[] = 'Read error.';
                        break;
                    case ZipArchive::ER_SEEK:
                        $this->errors[] = 'Seek error.';
                        break;
                }
            }
        } else {
            $this->errors[] = 'File does not exist.';
        }
    }

    public function setFile($path) {
        $this->fileData = $this->load($path);
    }

    public function to_plain_text() {
        if ($this->fileData) {
            return strip_tags($this->fileData);
        } else {
            return false;
        }
    }

    public function to_html() {
        if ($this->fileData) {
            $xml = simplexml_load_string($this->fileData);
            $namespaces = $xml->getNamespaces(true);

            $children = $xml->children($namespaces['w']);
            $html = '';
            //$html = '<!doctype html><html><head><meta http-equiv="Content-Type" content="text/html;charset=utf-8" /><title></title><style>span.block { display: block; }</style></head><body>';

            foreach ($children->body->p as $p) {
                $style = '';
                
                $startTags = array();
                $startAttrs = array();
                
                if($p->pPr->pStyle) {                    
                    $objectAttrs = $p->pPr->pStyle->attributes('w',true);
                    $objectStyle = (String) $objectAttrs['val'];
                    if(isset($this->styles[$objectStyle])) {
                        $startTags = $this->styles[$objectStyle]['tags'];
                        $startAttrs = $this->styles[$objectStyle]['attrs'];
                    }
                }
                
                if ($p->pPr->spacing) {
                    $att = $p->pPr->spacing->attributes('w', true);
                    if (isset($att['before'])) {
                        $style.='padding-top:' . ($att['before'] / 10) . 'px;';
                    }
                    if (isset($att['after'])) {
                        $style.='padding-bottom:' . ($att['after'] / 10) . 'px;';
                    }
                }

                $html.='<span class="block" style="' . $style . '">';
                $li = false;
                if ($p->pPr->numPr) {
                    $li = true;
                    $html.='<li>';
                }
                
                foreach ($p->r as $part) {
                    //echo $part->t;
                    $tags = $startTags;
                    $attrs = $startAttrs;                                        

                    foreach (get_object_vars($part->pPr) as $k => $v) {
                        if ($k = 'numPr') {
                            $tags[] = 'li';
                        }
                    }

                    foreach (get_object_vars($part->rPr) as $tag => $style) {
                        //print_r($style->attributes());
                        $att = $style->attributes('w', true);
                        switch ($tag) {
                            case "b":
                                $tags[] = 'strong';
                                break;
                            case "i":
                                $tags[] = 'em';
                                break;
                            case "color":
                                //echo (String) $att['val'];
                                $attrs[] = 'color:#' . $att['val'];
                                break;
                            case "sz":
                                $attrs[] = 'font-size:' . $att['val'] . 'px';
                                break;
                        }
                    }
                    $openTags = '';
                    $closeTags = '';
                    foreach ($tags as $tag) {
                        $openTags.='<' . $tag . '>';
                        $closeTags.='</' . $tag . '>';
                    }
                    $html.='<span style="' . implode(';', $attrs) . '">' . $openTags . $part->t . $closeTags . '</span>';
                }
                if ($li) {
                    $html.='</li>';
                }
                $html.="</span>";
            }

            //Trying to weed out non-utf8 stuff from the file:
            $regex = <<<'END'
/
  (
    (?: [\x00-\x7F]                 # single-byte sequences   0xxxxxxx
    |   [\xC0-\xDF][\x80-\xBF]      # double-byte sequences   110xxxxx 10xxxxxx
    |   [\xE0-\xEF][\x80-\xBF]{2}   # triple-byte sequences   1110xxxx 10xxxxxx * 2
    |   [\xF0-\xF7][\x80-\xBF]{3}   # quadruple-byte sequence 11110xxx 10xxxxxx * 3 
    ){1,100}                        # ...one or more times
  )
| .                                 # anything else
/x
END;
            preg_replace($regex, '$1', $html);

            //return $html . '</body></html>';
            return $html;
            exit();
        }
    }

    public function get_errors() {
        return $this->errors;
    }

    private function getStyles() {
        
    }

}

function docToPage($url){
    $doc = new Docx_reader();
    $doc->setFile($url);

    if(!$doc->get_errors()) {
        $html = $doc->to_html();
        $plain_text = $doc->to_plain_text();

        echo $html;
    } else {
        echo implode(', ',$doc->get_errors());
    }
   return $html;
}