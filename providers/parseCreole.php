<?php

class parseCreole {

    private $_creoleWikiContent;
    private $_mediaWikiContent;
    private $_subPageList;
    private $_relativePageList;

    function   __construct($data){
        $this-> _creoleWikiContent = $data;
       
    }

    function parse($format = 'creole'){

        $this -> _mediaWikiContent -> title = $this -> parseTitle($this -> _creoleWikiContent -> title) ;
        $this -> _mediaWikiContent -> minor = $this -> parseMinor($this -> _creoleWikiContent -> minorEdit) ;

        $this -> _mediaWikiContent -> summary = $this -> _creoleWikiContent -> summary;

        if ($format = 'creole'){
            $this -> _mediaWikiContent -> content = $this -> parseContent($this -> _creoleWikiContent -> content);
            
            $this -> _mediaWikiContent -> subCategory = $this -> parseSubcategorys($this -> _creoleWikiContent -> content);

            $this -> _mediaWikiContent -> subPages = $this -> parseLinks($this-> _creoleWikiContent -> content);
        }else
            $this -> _mediaWikiContent -> content = $this ->parseHtml($this -> _creoleWikiContent -> content);
               
        return $this->_mediaWikiContent;
    }

    function parseContent($content){
        
        $content = preg_replace("#\[\[(https?://[^\|]+?)\|([^\]]+?)\]\]#", "[[$1 $2]]", $content);
        $content = preg_replace("#\[([^\]\|]+?)\]#", "[[$1]]", $content);
        $content = str_replace("[[[", "[[", $content);
        $content = str_replace("]]]", "]]", $content);

        $content = preg_replace("#=(.+?)=#", "==$1==\n", $content);
        //$content = str_replace("=", "==", $content);
        $content = preg_replace("#\*\*\*(.*?)\*\*\*#", "'''$1'''", $content);
        $content = preg_replace("#\*\*(.*?)\*\*#", "'''$1'''", $content);
        $content = preg_replace("#\*(.*?)\*#", "''$1''", $content);

        $content = preg_replace("#^\s+#m", "", $content);
        $content = str_replace("\n", "\n\n",$content);
        $content = str_replace("\\\\", "\n", $content);
        $content = str_replace("پی نوشت", "==پی نوشت==\n{{پی نوشت}}", $content);

        $content = preg_replace("#//(.*?)//#", "<I>$1</I>", $content);
        return $content;
        
    }

    /*
     * change table style to wiki
     */
    function parseTable($content){
        //pass
    }

    function parseHtml($content){
        return "<nowiki>\n" . $content . "\n</nowiki>";
    }
    /*
     * find sub pages in content
     * pattern of subpage is * **[subpage]**
     */
    function parseSubcategorys($content){
        if(preg_match_all("#\*\s\*\*\[([^\]\|]+?)\]\*\*#", $content, $subcategorys)){
            return $subcategorys[1];
        }
        return FALSE;
    }

    function parseTitle($title){
        if(preg_match("/[\x{0600}-\x{06FF}\x]+/u", $title))
            return $title;
        return FALSE;
    }

    function parseMinor($minor){
        return ($minor == 0 ? true : false);
    }

    function parseLinks($content){
        if (preg_match_all("#\[([^\]\|]+?)\]#", $content, $subpages))
                return $subpages[1];

        return FALSE;
    }

}