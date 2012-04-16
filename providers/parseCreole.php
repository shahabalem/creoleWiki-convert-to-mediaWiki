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
        
        $content = preg_replace("#\[([^\]\|]+?)\]#", "[[$1]]", $content);
        $content = preg_replace("#\[\[(https?://[^\|]+?)\|([^\]]+?)\]\]#", "[[$1 $2]]", $content);

        $content = str_replace("[[[", "[[", $content);
        $content = str_replace("]]]", "]]", $content);

        $content = preg_replace("#=(.+?)=#", "==$1==\n", $content);
        //$content = str_replace("=", "==", $content);
        $content = preg_replace("#\*\*\*(.*?)\*\*\*#", "'''$1'''", $content);
        $content = preg_replace("#\*\*(.*?)\*\*#", "'''$1'''", $content);
        $content = preg_replace("#\*(.*?)\*#", "''$1''", $content);

        $content = preg_replace("#^\s+#m", "", $content);
        $content = preg_replace("#[\r\n]+#", "\n\n",$content);
        $content = str_replace("\\\\", "\n", $content);

        $content = preg_replace("#//(.*?)//#", "<I>$1</I>", $content);

        $content = preg_replace("#(?m)^(\s+)?(\()?(\d+|×)(\))?(\.|\-|\s)#", "#.", $content);

        $content = $this -> parseRefrence($content);
        return $content;
        
    }

    function parseRefrence($content){
        preg_match("/پي نوشت|پی نوشت(.*)/s", $content, $refrences);

        if (count($refrences) > 0 && isset ($refrences[1])){
            preg_match_all ("#(?m)^(\s+)?(\()?(\d+|×)(\))?(\.|\-|\s)(.*)$#", $refrences[1], $matchesarray);
            $text = preg_replace("/پي نوشت|پی نوشت(.*)/s", "", $content);
            
            //echo $text. '<hr>';
            
            if(isset ($matchesarray)){
                foreach ($matchesarray[3] as $key => $ref) {
                    $refContent = $matchesarray[6][$key];
                    
                    if (preg_match("#\(($ref)\)#", $text, $match)){

                        $text = str_replace($match[0], "<ref>$refContent</ref>", $text);
                        $refrences[1] = preg_replace("#(?m)^(\s+)?(\()?($ref)(\))?(\.|\-|\s)(.*)$#", "", $refrences[1],1);
                    }
                }
                //echo $text . "<hr>";
//                var_dump($matchesarray);
            }
            
            $refrences[1] = preg_replace("#(\s|\n){2}#", "\n", $refrences[1]);
            $refrences[1] = preg_replace("/پي نوشت|پی نوشت/", "", $refrences[1]);
            return $text . "==پانویس ==\n<references />" . $refrences[1];
        }
        //return $content;
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