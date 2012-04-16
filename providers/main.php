<?php

/*
 * class http use for connect to server and upload data with php program
 * this class use curl for this 
 * 
 * class wikipedia, extened and lyricwiki use for connect to wikipedia api 
 * wikipedia class is basical function for connect to wiki api and other extend some function  
 * 
 */
include_once 'botclasses.php';


/*
 * class that use for convert creole wiki to media wiki
 * connect to database and fetch row of it
 */
include_once 'convertCreoleWiki.php';

//echo phpinfo();exit;
/*** start the main program ***/
$obj = convertCreoleWiki::getInstance();
$obj -> createQuary();
$count = 0;
while($obj -> fetchRow()){

    if ($parse = $obj -> Rowformat()){

        //parse content of each page and change it to mediaWiki format
        $parse = 'parse'. ucfirst(strtolower($parse));
        $obj -> $parse();


//        continue;
        //find redirect page and set special content for it
        $obj -> redirectPage();

        //find tags that assign to each page
        $obj -> findTag();

        //find categorys that assign to each page
        $obj -> findCategory();

        //create page and send content to page use of mediawiki api
        $obj -> createPage();

        //insert category to temp table of database for future
        $obj -> parseCategorys();
    } else {
//        continue;
        if ($count++ == 100)
            break;
    }
    
}

$obj -> createCategorys();

