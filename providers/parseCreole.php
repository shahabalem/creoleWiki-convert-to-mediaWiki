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
        }else
            $this -> _mediaWikiContent -> content = $this ->parseHtml($this -> _creoleWikiContent -> content);
               
        return $this->_mediaWikiContent;
    }

    function parseContent($content){


        $content = $this ->parseLinks($content);

        //delete extra brachet from text and link
        $content = str_replace("[[[", "[[", $content);
        $content = str_replace("]]]", "]]", $content);

        //corrent title to mediaWiki format
        $content = preg_replace("#(?m)^\s*?=(.+?)=\s*?$#", "==$1==\n", $content);

        //correct bold text
        $content = preg_replace("#\*\*\*(.*?)\*\*\*#", "'''$1'''", $content);
        $content = preg_replace("#\*\*(.*?)\*\*#", "'''$1'''", $content);
        $content = preg_replace("#\*(.*?)\*#", "''$1''", $content);

        //trim paraghraph corrent and delete first space of paraphraph
        $content = preg_replace("#^\s+#m", "", $content);

        //reular for corrent the force new line
        $content = str_replace("\\\\", "\n", $content);

        //regular for correct paragraph new line
        $content = preg_replace("#[\r\n]+#", "\n\n",$content);

        //regular for correct Italic text
        $content = preg_replace("#//(.*?)//#", "<I>$1</I>", $content);

        //function for fetch refrence of buttom of page and standard it in mediawiki format
        $content = $this -> parseRefrence($content);

        //regular for corrent number of the first file
        $content = preg_replace("#(?m)^([\s\*]+?)(\()?(\d+|×)(\))?(\.|\-|\s)#", "#", $content);
        return $content;
        
    }

    /*
     * this fucntion use for correct refrence in content fetch refrence
     * button of content and add text of refrence in correct place in main content
     */
    function parseRefrence($content){
        preg_match("/پي نوشت|پی نوشت(.*)/s", $content, $refrences);

        if (count($refrences) > 0 && isset ($refrences[1])){
            
            $pattern = "#(?m)^(\s+)?(\()?(\d+|×)(\))?(\.|\-|\s)(.*)$#";
            preg_match_all ($pattern, $refrences[1], $matchesarray);

            $pattern = "#(?m)^(\s+)?((\()?(\d+|×)(\))?)([\sو]+?)((\()?(\d+|×)(\))?)(\.|\-|\s)(.*)$#";
            if (preg_match_all($pattern, $refrences[1], $matches)){
                foreach ($matches[0] as $key => $match){
                    
                    $matchesarray[3][] = $matches[4][$key];
                    $matchesarray[3][] = $matches[9][$key];
                    $matchesarray[6][] = $matches[12][$key];
                    $matchesarray[6][] = $matches[12][$key];
                }
                $refrences[1] = preg_replace($pattern, "", $refrences[1],1);
            }
            //var_dump($matchesarray);
            $text = preg_replace("/پي نوشت|پی نوشت(.*)/s", "", $content);
            
            //echo $text. '<hr>';
            
            if(isset ($matchesarray)){
                foreach ($matchesarray[3] as $key => $ref) {
                    $refContent = $matchesarray[6][$key];
                    
                    if (preg_match("#\(($ref)\)#", $text, $match)){

                        $text = str_replace($match[0], "<ref>$refContent</ref>", $text);
                        $refrences[1] = preg_replace("#(?m)^(\s+)?(\()?($ref)(\))?(\.|\-|\s)(.*)$#", "", $refrences[1],1);
                    }
                    elseif(0){
                        echo "";
                    }
                }
                //echo $text . "<hr>";
//                var_dump($matchesarray);
            }
            
            $refrences[1] = preg_replace("#(\s|\n){2}#", "\n", $refrences[1]);
            $refrences[1] = preg_replace("/پي نوشت|پی نوشت/", "", $refrences[1]);
            return $text . "==پانویس ==\n<references />" . "\n\n\n\n" . $refrences[1];
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

        //correct internal and external link
        $content = preg_replace("#\[([^\]\|]+?)\]#", "[[$1]]", $content);

        $pattern = "#\[\[(https?://[^\|]+?)\|([^\]]+?)\]\]#";

        return preg_replace_callback(
                    $pattern,
                    create_function(
                        '$match',
                        '   $link = trim($match[1], "#");
                            $address = trim($match[2]);
                            return preg_replace("#\n|\r#", "", "[$link $address]");'),
                    $content);




        return FALSE;
    }
}