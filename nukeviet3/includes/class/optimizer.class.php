<?php

/**
 * @Project NUKEVIET 3.0
 * @Author VINADES.,JSC (contact@vinades.vn)
 * @Copyright (C) 2010 VINADES.,JSC. All rights reserved
 * @Createdate 27/11/2010, 22:45
 */

/**
 * optimezer
 * 
 * @package   
 * @author NUKEVIET 3.0
 * @copyright VINADES.,JSC
 * @version 2010
 * @access public
 */
class optimezer
{
    private $_content;
    private $_conditon = array();
    private $_conditonCss = array();
    private $_conditonJs = array();
    private $_condCount = 0;
    private $_meta = array();
    private $_title = "<title></title>";
    private $_style = array();
    private $_links = array();
    private $_cssLinks = array();
    private $_cssIgnoreLinks = array();
    private $_jsLinks = array();
    private $_jsInline = array();
    private $_jsMatches = array();
    private $_jsCount = 0;

    private $siteRoot;
    private $base_siteurl;
    private $cssDir = "files/css";
    private $jsDir = "files/js";
    private $eol = "\r\n";
    private $cssImgNewPath;

    /**
     * optimezer::__construct()
     * 
     * @param mixed $content
     * @return
     */
    public function __construct( $content )
    {
        $this->_content = $content;
        $this->siteRoot = preg_replace( "/[\/]+$/", '', str_replace( '\\', '/', realpath( dirname( __file__ ) . '/../../' ) ) );
        $base_siteurl = pathinfo( $_SERVER['PHP_SELF'], PATHINFO_DIRNAME );
        if ( $base_siteurl == '\\' or $base_siteurl == '/' ) $base_siteurl = '';
        if ( ! empty( $base_siteurl ) ) $base_siteurl = str_replace( '\\', '/', $base_siteurl );
        if ( ! empty( $base_siteurl ) ) $base_siteurl = preg_replace( "/[\/]+$/", '', $base_siteurl );
        if ( ! empty( $base_siteurl ) ) $base_siteurl = preg_replace( "/^[\/]*(.*)$/", '/\\1', $base_siteurl );
        $this->base_siteurl = $base_siteurl . '/';
    }

    /**
     * optimezer::process()
     * 
     * @return
     */
    public function process()
    {
        $conditionRegex = "/<\!--\[if([^\]]+)\].*?\[endif\]-->/is";
        preg_match_all( $conditionRegex, $this->_content, $conditonMatches );
        if ( $conditonMatches )
        {
            $this->_conditon = $conditonMatches[0];
            $this->_content = preg_replace_callback( $conditionRegex, array( $this, 'conditionCallback' ), $this->_content );

        }
        
        $this->_content = preg_replace("/<script[^>]+src\s*=\s*[\"|']([^\"']+\jquery.min.js)[\"|'][^>]*>/is","",$this->_content);
        $jsRegex = "/<script[^>]*>[^\<]*<\/script>/is";
        preg_match_all( $jsRegex, $this->_content, $jsMatches );
        if($jsMatches)
        {
            $this->_jsMatches = $jsMatches[0];
            $this->_content = preg_replace_callback( $jsRegex, array( $this, 'jsCallback' ), $this->_content );
        }

        //$regex = "!<meta[^>]+>|<title>[^<]+<\/title>|<link[^>]+>|<style[^>]*>[^\<]*</style>|<script[^>]*>[^\<]*</script>!is";
        $regex = "!<meta[^>]+>|<title>[^<]+<\/title>|<link[^>]+>|<style[^>]*>[^\<]*</style>!is";
        preg_match_all( $regex, $this->_content, $matches );
        if ( $matches )
        {
            foreach ( $matches[0] as $tag )
            {
                if ( preg_match( "/^<meta/", $tag ) )
                {
                    $this->_meta[] = $tag;
                } elseif ( preg_match( "/^<title>[^<]+<\/title>/is", $tag ) )
                {
                    $this->_title = $tag;
                } elseif ( preg_match( "/^<style[^>]*>([^<]*)<\/style>/is", $tag, $matches2 ) )
                {
                    $this->_style[] = $matches2[1];
                } elseif ( preg_match( "/^<link/", $tag ) )
                {
                    preg_match_all( "/([a-zA-Z]+)\s*=\s*[\"|']([^\"']+)/is", $tag, $matches2 );
                    $combine = array_combine( $matches2[1], $matches2[2] );
                    if ( isset( $combine['rel'] ) and preg_match( "/stylesheet/is", $combine['rel'] ) )
                    {
                        if ( ! isset( $combine['title'] ) and isset( $combine['href'] ) and preg_match( "/^(?!http(s?)|ftp\:\/\/)(.*?)\.css$/", $combine['href'], $matches3 ) )
                        {
                            $media = isset( $combine['media'] ) ? $combine['media'] : "";
                            $this->_cssLinks[$matches3[0]] = $media;
                        }
                        else
                        {
                            $this->_cssIgnoreLinks[] = $tag;
                        }
                    }
                    else
                    {
                        $this->_links[] = $tag;
                    }
                }
                /*elseif ( preg_match( "/^<script[^>]+src\s*=\s*[\"|']((?!http(s?)|ftp\:\/\/)[^\"']+\.js)[\"|'][^>]*>/is", $tag, $matches2 ) )
                {
                    $this->_jsLinks[] = $matches2[1];
                } elseif ( preg_match( "/<script[^>]*>([^\<]*)<\/script>/is", $tag, $matches2 ) )
                {
                    $this->_jsInline[] = $matches2[1];
                }*/
            }

            $this->_content = preg_replace( $regex, "", $this->_content );
        }

        if ( ! empty( $this->_conditon ) )
        {
            foreach ( $this->_conditon as $key => $value )
            {
                $this->_content = preg_replace( "/\{\|condition\_" . $key . "\|\}/", $value, $this->_content );
            }
        }
        
        if(!empty($this->_jsMatches))
        {
            foreach($this->_jsMatches as $key => $value)
            {
                $value = $this->minifyJsInline($value);
                $this->_content = preg_replace( "/\{\|js\_" . $key . "\|\}/", $value, $this->_content );
            }
        }

        $this->_content = $this->minifyHTML( $this->_content );

        $head = "<head>" . $this->eol . $this->_title . $this->eol;
        if ( ! empty( $this->_meta ) ) $head .= implode( $this->eol, $this->_meta ) . $this->eol;
        if ( ! empty( $this->_links ) ) $head .= implode( $this->eol, $this->_links ) . $this->eol;
        if ( ! empty( $this->_cssLinks ) ) $head .= "<link rel=\"Stylesheet\" href=\"" . $this->newCssLink() . "\" type=\"text/css\" />" . $this->eol;
        if ( ! empty( $this->_cssIgnoreLinks ) ) $head .= implode( $this->eol, $this->_cssIgnoreLinks ) . $this->eol;
        if ( ! empty( $this->_style ) ) $head .= "<style type=\"text/css\">" . $this->minifyCss( implode( $this->eol, $this->_style ) ) . "</style>" . $this->eol;
        $head .= "<script type=\"text/javascript\" src=\"".$this->base_siteurl."js/jquery/jquery.min.js\"></script>" . $this->eol;
        $head = $this->minifyHTML( $head );
        $this->_content = preg_replace( '/<head>/i', $head, $this->_content, 1 );

        /*$body = "";
        if ( ! empty( $this->_jsLinks ) ) $body .= "<script type=\"text/javascript\" src=\"" . $this->newJSLink() . "\"></script>" . $this->eol;
        if ( ! empty( $this->_jsInline ) ) $body .= "<script type=\"text/javascript\">" . $this->eol . $this->minifyJsInline( implode( $this->eol, $this->_jsInline ) ) . $this->eol . "</script>" . $this->eol;
        $body .= "</body>";
        $this->_content = preg_replace( '/<\/body>/i', $body, $this->_content, 1 );*/

        return $this->_content;
    }

    /**
     * optimezer::conditionCallback()
     * 
     * @param mixed $matches
     * @return
     */
    private function conditionCallback( $matches )
    {
        $num = $this->_condCount;
        $this->_condCount++;
        return "{|condition_" . $num . "|}";
    }
    
    /**
     * optimezer::jsCallback()
     * 
     * @param mixed $matches
     * @return
     */
    private function jsCallback( $matches )
    {
        $num = $this->_jsCount;
        $this->_jsCount++;
        return "{|js_" . $num . "|}";
    }

    /**
     * optimezer::newCssLink()
     * 
     * @return
     */
    private function newCssLink()
    {
        $newCSSLink = md5( implode( array_keys( $this->_cssLinks ) ) );
        $newCSSLinkPath = $this->siteRoot . '/' . $this->cssDir . '/' . $newCSSLink . '.opt.css';

        if ( ! file_exists( $newCSSLinkPath ) )
        {
            $contents = "";
            foreach ( $this->_cssLinks as $link => $media )
            {
                $link = preg_replace( "#^" . $this->base_siteurl . "#", "", $link );

                if ( ! empty( $media ) and ! preg_match( "/all/", $media ) ) $contents .= "@media " . $media . "{" . $this->eol;
                $contents .= $this->getCssContent( $link ) . $this->eol;
                if ( ! empty( $media ) and ! preg_match( "/all/", $media ) ) $contents .= "}" . $this->eol;
            }
            $contents = $this->minifyCss( $contents );
            file_put_contents( $newCSSLinkPath, $contents );

        }

        return $this->base_siteurl . $this->cssDir . "/" . $newCSSLink . ".opt.css";
    }

    /**
     * optimezer::newJSLink()
     * 
     * @return
     */
    private function newJSLink()
    {
        $newJSLink = md5( implode( $this->_jsLinks ) );
        $newJSLinkPath = $this->siteRoot . '/' . $this->jsDir . '/' . $newJSLink . '.opt.js';

        if ( ! file_exists( $newJSLinkPath ) )
        {
            $contents = "";
            foreach ( $this->_jsLinks as $link )
            {
                $link = preg_replace( "#^" . $this->base_siteurl . "#", "", $link );
                $contents .= file_get_contents( $this->siteRoot . '/' . $link ) . $this->eol;
            }
            $contents = $this->minifyJs( $contents );
            file_put_contents( $newJSLinkPath, $contents );

        }

        return $this->base_siteurl . $this->jsDir . "/" . $newJSLink . ".opt.js";
    }

    /**
     * optimezer::minifyJs()
     * 
     * @param mixed $contents
     * @return
     */
    private function minifyJs( $contents )
    {
        $contents = preg_replace( "/(\r\n)+|(\n|\r)+/", "\r\n", $contents );
        return $contents;
    }

    /**
     * optimezer::minifyJsInline()
     * 
     * @param mixed $jsInline
     * @return
     */
    private function minifyJsInline( $jsInline )
    {
        $jsInline = preg_replace( '/(?:^\\s*<!--\\s*|\\s*(?:\\/\\/)?\\s*-->\\s*$)/', '', $jsInline );
        $jsInline = $this->minifyJs( $jsInline );
        $jsInline = preg_replace( '/^\s+|\s+$/m', '', $jsInline );
        return $jsInline;
    }

    /**
     * optimezer::getCssContent()
     * 
     * @param mixed $link
     * @return
     */
    private function getCssContent( $link )
    {
        $content = file_get_contents( $this->siteRoot . '/' . $link );
        $this->cssImgNewPath = "../../" . str_replace( '\\', '/', dirname( $link ) ) . "/";
        $content = preg_replace_callback( "/url[\s]*\([\s]*[\'\"]*([^\'\"\)]+)[\'\"]*[\s]*\)/", array( $this, 'changeCssURL' ), $content );
        return $content;
    }

    /**
     * optimezer::changeCssURL()
     * 
     * @param mixed $matches
     * @return
     */
    private function changeCssURL( $matches )
    {
        $url = $this->cssImgNewPath . $matches[1];
        while(preg_match("/([^\/(\.\.)]+)\/\.\.\//",$url))
        {
            $url = preg_replace( "/([^\/(\.\.)]+)\/\.\.\//", "", $url );
        }
        return "url(" . $url . ")";
    }

    /**
     * optimezer::minifyCss()
     * 
     * @param mixed $cssContent
     * @return
     */
    private function minifyCss( $cssContent )
    {
        $cssContent = preg_replace( '@>/\\*\\s*\\*/@', '>/*keep*/', $cssContent );
        $cssContent = preg_replace( '@/\\*\\s*\\*/\\s*:@', '/*keep*/:', $cssContent );
        $cssContent = preg_replace( '@:\\s*/\\*\\s*\\*/@', ':/*keep*/', $cssContent );
        $cssContent = preg_replace_callback( '@\\s*/\\*([\\s\\S]*?)\\*/\\s*@', array( $this, 'commentCB' ), $cssContent );

        $cssContent = preg_replace( '/[\s\t\r\n]+/', ' ', $cssContent );
        $cssContent = preg_replace( '/[\s]*(\:|\,|\;|\{|\})[\s]*/', "$1", $cssContent );
        $cssContent = preg_replace( "/[\#]+/", "#", $cssContent );
        $cssContent = str_replace( array( ' 0px', ':0px', ';}', ':0 0 0 0', ':0.', ' 0.' ), array( ' 0', ':0', '}', ':0', ':.', ' .' ), $cssContent );
        $cssContent = preg_replace( '/\\s*([{;])\\s*([\\*_]?[\\w\\-]+)\\s*:\\s*(\\b|[#\'"-])/x', '$1$2:$3', $cssContent );

        $cssContent = preg_replace_callback( '/(?:\\s*[^~>+,\\s]+\\s*[,>+~])+\\s*[^~>+,\\s]+{/x', array( $this, 'selectorsCB' ), $cssContent );
        $cssContent = preg_replace( '/([^=])#([a-f\\d])\\2([a-f\\d])\\3([a-f\\d])\\4([\\s;\\}])/i', '$1#$2$3$4$5', $cssContent );
        $cssContent = preg_replace_callback( '/font-family:([^;}]+)([;}])/', array( $this, 'fontFamilyCB' ), $cssContent );
        $cssContent = preg_replace( '/@import\\s+url/', '@import url', $cssContent );
        $cssContent = preg_replace( '/:first-l(etter|ine)\\{/', ':first-l$1 {', $cssContent );
        $cssContent = preg_replace( "/[^\}]+\{[\s|\;]*\}[\s]*/", "", $cssContent );
        $cssContent = preg_replace( "/[\s]+/", " ", $cssContent );
        $cssContent = trim( $cssContent );
        return $cssContent;
    }

    /**
     * optimezer::commentCB()
     * 
     * @param mixed $m
     * @return
     */
    private function commentCB( $m )
    {
        $hasSurroundingWs = ( trim( $m[0] ) !== $m[1] );
        $m = $m[1];
        if ( $m === 'keep' )
        {
            return '/**/';
        }
        if ( $m === '" "' )
        {
            return '/*" "*/';
        }
        if ( preg_match( '@";\\}\\s*\\}/\\*\\s+@', $m ) )
        {
            return '/*";}}/* */';
        }
        if ( preg_match( '@^/\\s*(\\S[\\s\\S]+?)\\s*/\\*@x', $m, $n ) )
        {
            return "/*/" . $n[1] . "/**/";
        }
        if ( substr( $m, -1 ) === '\\' )
        {
            return '/*\\*/';
        }
        if ( $m !== '' && $m[0] === '/' )
        {
            return '/*/*/';
        }
        return $hasSurroundingWs ? ' ' : '';
    }

    /**
     * optimezer::selectorsCB()
     * 
     * @param mixed $m
     * @return
     */
    private function selectorsCB( $m )
    {
        return preg_replace( '/\\s*([,>+~])\\s*/', '$1', $m[0] );
    }

    /**
     * optimezer::fontFamilyCB()
     * 
     * @param mixed $m
     * @return
     */
    private function fontFamilyCB( $m )
    {
        $m[1] = preg_replace( '/\\s*("[^"]+"|\'[^\']+\'|[\\w\\-]+)\\s*/x', '$1', $m[1] );
        return 'font-family:' . $m[1] . $m[2];
    }

    /**
     * optimezer::minifyHTML()
     * 
     * @param mixed $content
     * @return
     */
    private function minifyHTML( $content )
    {
        $content = preg_replace_callback( '/<!--([\s\S]*?)-->/', array( $this, 'HTMLCommentCB' ), $content );
        $content = preg_replace( '/<([^\>]+)\s+\/\s+\>/', '<$1 />', $content );
        $content = preg_replace( '#<(br|hr|input|img|meta)([^>]+)>#', "<\\1 \\2 />", $content );
        $content = preg_replace( '#\s*\/\s*\/>#', " />", $content );
        $content = preg_replace( '/^\s+|\s+$/m', '', $content );

        return $content;
    }

    /**
     * optimezer::HTMLCommentCB()
     * 
     * @param mixed $m
     * @return
     */
    private function HTMLCommentCB( $m )
    {
        return ( strpos( $m[1], '[' ) === 0 || strpos( $m[1], '<![' ) !== false ) ? $m[0] : '';
    }

    /**
     * optimezer::HTMLoutsideTagCB()
     * 
     * @param mixed $m
     * @return
     */
    private function HTMLoutsideTagCB( $m )
    {
        return '>' . preg_replace( '/^\\s+|\\s+$/', ' ', $m[1] ) . '<';
    }

}

?>