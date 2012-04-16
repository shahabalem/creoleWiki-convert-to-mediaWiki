<?php



class convertCreoleWiki
{
    private static $_convertCreoleWiki;
    private $_dbConnect; //connection to DB
    private $_stmt; //quary for fetch a row
    private $_creoleWikiContent; //content of creole wiki
    private $_mediaWikiContent; //converted data to media wiki
    private $categorylist;


    /*
     * this function use for connet to db just one time
     */
    public static function getInstance()
        {
            if (self::$_convertCreoleWiki === null) {
                self::$_convertCreoleWiki = new convertCreoleWiki;

                self::$_convertCreoleWiki -> connectDb();
            }
            return self::$_convertCreoleWiki;
        }
    /*
     * Initial fnction for connect to DB wiki of lifray
     * Initial function for connect to mediawiki API
     */
    function  __construct() {
    }

    /*
     * connet to lifray mysql DB
     * @param $dbName name of database defult is test
     * @param $user user name to connect to Db defualt is root
     * @param $pass password for connect to db default is shahab
     */
    function connectDb($dbName = 'test2',$user = 'root', $pass = 'shahab'){
        try {
            $this->_dbConnect = new PDO("mysql:host=localhost;dbname=$dbName", $user, $pass);

            /** set charset for database **/
            $this-> _dbConnect ->query("SET CHARACTER SET 'utf8'");

            $this -> _dbConnect ->query("DELETE FROM `tmpCategory` WHERE 1");

            /*** set the error reporting attribute ***/
            $this->_dbConnect->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        } catch (PDOException $e) {
            print "Error!: " . $e->getMessage() . "<br/>";
            die();
        }
    }
    function _init(){
        $sql = 'DELETE FROM `tmpCategory` WHERE 1';
        $stmt = $this-> _dbConnect ->prepare($sql);
        $stmt ->execute();
    }

    /*
     * createQuary
     * @param $sql this function use for select quary that each fetch need
     * @return data that fetch of Database
     */
    function createQuary(){
        $sql = "SELECT a.* FROM `WikiPage` AS a
                JOIN `WikiPageResource` AS b ON a.resourcePrimKey = b.resourcePrimKey 
                WHERE 
                    a.nodeId = '41781' AND
                    a.version = 
                        (SELECT max(version) FROM `WikiPage` 
                         WHERE resourcePrimKey = a.resourcePrimKey)  
                GROUP BY a.resourcePrimKey
                limit 200,400";


//
//        $sql = "SELECT a.*, max(a.version) As ver
//                FROM `WikiPage` AS a
//                JOIN `WikiPageResource` AS b
//                ON a.resourcePrimKey = b.resourcePrimKey
//                Where a.nodeId = '41781'
//                GROUP BY a.resourcePrimKey
//                LIMIT 400, 600";

        $stmt = $this-> _dbConnect ->prepare($sql);
        
        $stmt -> setFetchMode(PDO::FETCH_OBJ);
        $this -> _stmt = $stmt;
        $this -> _stmt ->execute();
        
    }

    /*
     * fetch a row of content
     */
    function fetchRow(){
        $this -> _creoleWikiContent = $this -> _stmt -> fetch();
        //var_dump($this -> _creoleWikiContent);
        
        if ($this -> _creoleWikiContent)
            return true;
        return false;
    }

    /**
     * filed format can be Creole or html
     * return false if format differnt of this
     * @return format of filed text
     */
    function rowFormat(){
        
        $format = $this -> _creoleWikiContent -> format;
        if ($format == 'html' || $format = 'creole')
            return $format;
        return false;
    }

    /*
     * convert Html page to mediaWiki syntax
     */
    function parseHtml(){
        include_once 'parseCreole.php';
        $obj = new parseCreole($this-> _creoleWikiContent);

        $this -> _mediaWikiContent = $obj -> parse('html');
    }

    /*
     * convert creole Wiki syntax to mediaWiki syntax
     *
     */
    function parseCreole(){

        include_once 'parseCreole.php';
        $obj = new parseCreole($this -> _creoleWikiContent);
        $this -> _mediaWikiContent = $obj ->parse();
        //var_dump($this-> _creoleWikiContent);
        //var_dump($this-> _mediaWikiContent -> content);
        

    }
    /*
     * connect to mediaWiki api and create new page
     */
    function createpage(){

        
        include_once 'botclasses.php';
        $obj = new lyricwiki(null,null,'http://localhost/mediawiki/api.php');
        $obj ->edit($this -> _mediaWikiContent -> title,
                    $this -> _mediaWikiContent -> content,
                    $this -> _mediaWikiContent -> summary,
                    $this -> _mediaWikiContent -> minor);

    }

    /*
     * if apge is not original page and want to redirect to an other page
     * this function check of redirect title was set it's use of it and set redirect to page
     *
     */
    function redirectPage(){
        if ($this -> _creoleWikiContent -> redirectTitle){
            $this -> _mediaWikiContent -> content = "#تغییرمسیر" . '[[' . $this -> _creoleWikiContent -> redirectTitle . ']]';
            return TRUE;
        }
        return FALSE;
    }

    /*
     * select form table to find tag of each page
     * and add to content for show in page
     *
     */
    function findTag(){

        $resource = $this -> _creoleWikiContent -> resourcePrimKey;
        $sql = "SELECT a.name
                FROM `AssetTag` AS a
                JOIN `AssetEntries_AssetTags` AS b ON a.tagId = b.tagId
                JOIN `AssetEntry` AS c ON c.entryId = b.entryId
                WHERE c.classPK = '$resource'";
        
        $stmt = $this-> _dbConnect -> prepare($sql,array(
            PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true
        ));

        $stmt -> setFetchMode(PDO::FETCH_OBJ);
        $stmt ->execute();
        $tags = $stmt ->fetchAll();

        $tagContent = 'کلید واژه: ';
        if (count($tags) != 0){

            foreach ($tags as $tag){
                $tagContent .= '[[' . $tag -> name . "]] ،";
                $this -> _mediaWikiContent -> subCategory[] = $tag -> name;
            }
            $this -> _mediaWikiContent -> content = "$tagContent\n\n" . $this -> _mediaWikiContent -> content;
        }
        
    }

    
    /*
     * quary for insert category in temp of database
     * to fetch in end of the program
     */
    function instertCategory($page, $parent){
        $sql = "INSERT INTO `tmpCategory`
                    (`page`, `parent`)
                VALUES
                    ('$page', '$parent')";

        $stmt = $this-> _dbConnect -> prepare($sql);
        $stmt ->execute();

    }


    /*
     * Insert all category in end of page
     */
    function parseCategorys(){

        if ($this-> _mediaWikiContent -> subCategory)
            if (is_array($this -> _mediaWikiContent -> subCategory))
                foreach ($this -> _mediaWikiContent -> subCategory  As $category)
                    $this->instertCategory($category, $this -> _mediaWikiContent -> title);
            else
                    $this->instertCategory($this-> _mediaWikiContent -> subCategory, $this -> _mediaWikiContent -> title);
    }


    /*
     * fetch category list of database
     * this category use for mark owner of each page
     * 
     */
    function findCategory(){

        $resource = $this -> _creoleWikiContent -> resourcePrimKey;
        $sql = "SELECT a.name
                FROM `AssetCategory` AS a
                JOIN `AssetEntries_AssetCategories` AS b ON a.categoryId  = b.categoryId
                JOIN `AssetEntry` AS c ON c.entryId = b.entryId
                WHERE c.classPK = '$resource'";

        $stmt = $this-> _dbConnect -> prepare($sql,array(
            PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true
        ));

        $stmt -> setFetchMode(PDO::FETCH_OBJ);
        $stmt ->execute();
        $categorys = $stmt ->fetchAll();

        if (count($categorys) != 0){
            foreach ($categorys as $category){
                $this -> _mediaWikiContent ->  subCategory[] =  $category -> name;
            }
        }

    }

    /*
     * connect to mediaWiki api and create all category and subpage
     */
    function createCategorys(){

        $sql = "SELECT * FROM `tmpCategory` WHERE 1";

        $stmt = $this-> _dbConnect -> prepare($sql);
        $stmt -> setFetchMode(PDO::FETCH_OBJ);
        $this -> categorylist = $stmt;
        $this -> categorylist  ->execute();

        include_once 'botclasses.php';
        $obj = new lyricwiki(null,null,'http://localhost/mediawiki/api.php');

        foreach ($this-> categorylist As $category){
                    $obj -> addcategory ($category -> page, $category -> parent);
        }
    }
}

