<?php
/*
A class to handle content classification based on Bayesion techniques

Pub methods:
train - trains the system
untrain - removes previous training
check - checks input against categories to determine relevancy

NOTES:
Category -1 is SPAM
          0 is Not SPAM

ARTICLES
http://www.bgl.nu/bogofilter/bayes.html

USE:
$text['subject'] = $mbox->header[$mid]['subject'];
$text['from']    = $msgFromName.' '.$msgFromEmail;
$text['body']    = $msgMessage;
$text['headers'] = $mbox->header[$mid]['fromaddress'].' '.$mbox->header[$mid]['reply_toaddress'].' '.$mbox->header[$mid]['senderaddress'].'
                    '.$mbox->header[$mid]['from_host'][0].' '.$mbox->header[$mid]['from_personal'][0];

$filter = new UserScape_Bayesian_Classifier($text);
    //TRAIN
        $filter->Train($catid);
    //CHECK
        $filter->Check(); 	//returns guess at category
*/

// SECURITY: Don't allow direct calls
if (! defined('cBASEPATH')) {
    die();
}

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

//Flags used
// hs_setting('cHD_BAYESIAN_PROB_SPAM') ( .9 base )
// cHD_BAYESIAN_PROB_CAT ( .5 base )

class UserScape_Bayesian_Classifier
{
    //Tokens for this piece of text
    public $allTokens = [];

    //All the tokens by category with counts
    public $allWordsCatCount = [];

    //Request id being classified
    public $xRequest;

    //Min token
    public $minToken = 3;

    //Max token
    public $maxToken = 20;

    //Min token
    public $numberTokens = 15;

    //Message count threshold that must be passed before classification starts
    public $msgCtStart = 40;

    //Category message counts
    public $catMsgCounts = [];

    //Probability above which this message will be considered spam
    public $spam_probability = .90;

    //Probability of being spam
    public $probability = 0;

    //Count table
    public $table_counts = '';

    //Word table
    public $table_words = '';

    //spam check type, mail or portal
    public $check_type = '';

    //filtering level, on(1) off(0) or checking only(2)
    public $filter_status = 1;

    //Constructor
    public function __construct($text, $for = 'mail')
    {
        $this->check_type = $for;

        if ($this->check_type == 'mail' && hs_setting('cHD_SPAMFILTER') == 0) {
            $this->filter_status = 0;
        } elseif ($this->check_type == 'mail' && hs_setting('cHD_SPAMFILTER') == 2) {
            $this->filter_status = 2;
        }

        //Set tables for mail vs forums
        if ($this->check_type == 'mail' && $this->filter_status != 0) {
            $this->table_counts = 'HS_Bayesian_MsgCounts';
            $this->table_words = 'HS_Bayesian_Corpus';
            $this->spam_probability = hs_setting('cHD_BAYESIAN_PROB_SPAM');
        } else {
            $this->table_counts = 'HS_Portal_Bayesian_MsgCounts';
            $this->table_words = 'HS_Portal_Bayesian_Corpus';
            $this->spam_probability = hs_setting('cHD_PORTAL_BAYESIAN_PROB_SPAM');
        }

        if (! empty($this->table_counts)) { //need this check in case it's for mail and spam filter is off, then don't run this
            $this->catMsgCounts = $GLOBALS['DB']->GetArray('SELECT xCategory,iMsgCount FROM '.$this->table_counts);
            $this->allTokens = $this->_Tokenize($text);
        }
    }

    /* TRAIN SYSTEM ON A CATEGORY */
    public function Train($catid = -1)
    {
        if ($this->filter_status == 1) {
            $sql = [];
            //NEED SOME LOGIC HERE TO BALANCE OUT TRAINING SETS. ONCE ONE GETS VERY LARGE, SAY SPAM. THEN WE NEED TO LET THE OTHER SETS
            //CATCHUP OR STATS WILL START TO GET THROWN OFF. IT APPEARS THAT WAITING UNTIL SOMETHING LIKE 1000 IS GOOD.
            if (! empty($this->allTokens)) {
                //Update message count for category
                if (isset($this->catMsgCounts[$catid])) {
                    $sql[] = 'UPDATE '.$this->table_counts.' SET iMsgCount = iMsgCount + 1 WHERE xCategory = '.$catid;
                } else {	//if category has never had a message then create it
                    $sql[] = 'INSERT INTO '.$this->table_counts.'(xCategory,iMsgCount) VALUES ('.$catid.',1)';
                }
                // Update corpus table
                $wordArray = $this->_getWordsByCategory();
                foreach ($this->allTokens as $k=>$c) {
                    if (isset($wordArray[$k][$catid])) {
                        $sql[] = 'UPDATE '.$this->table_words.' SET iCount = iCount + '.$c.' WHERE sWord = '.qstr(utf8_trim($k)).' AND xCategory = '.$catid;
                    } else {
                        $sql[] = 'INSERT INTO '.$this->table_words.'(iCount,sWord,xCategory) VALUES ('.$c.','.qstr(utf8_trim($k)).','.$catid.')';
                    }
                }

                //Execute all SQL queries at once so we don't have to make so many connections and query calls
                if (! empty($sql)) {
                    foreach ($sql as $k=>$v) {
                        try {
                            $GLOBALS['DB']->Execute($v);
                        } catch(\Exception $e) {
                            // Don't halt execution on this error, but do log it
                            Log::error($e);
                        }
                    }
                }
            }
        }
    }

    /* UNTRAIN SYSTEM ON A CATEGORY */
    public function Untrain($catid = -1)
    {
        if ($this->filter_status == 1) {
            $sql = [];

            if (! empty($this->allTokens) && isset($this->catMsgCounts[$catid])) {
                //Update message count for category
                if (isset($this->catMsgCounts[$catid])) {
                    $sql[] = 'UPDATE '.$this->table_counts.' SET iMsgCount = iMsgCount - 1 WHERE xCategory = '.$catid;
                }

                // Update corpus table
                $wordArray = $this->_getWordsByCategory();
                foreach ($this->allTokens as $k=>$c) {
                    if (isset($wordArray[$k][$catid])) {
                        $sql[] = 'UPDATE '.$this->table_words.' SET iCount = iCount - '.$c.' WHERE sWord = '.qstr(utf8_trim($k)).' AND xCategory = '.$catid;
                    }
                }

                //Execute all SQL queries at once so we don't have to make so many connections and query calls
                if (! empty($sql)) {
                    foreach ($sql as $k=>$v) {
                        try {
                            $GLOBALS['DB']->Execute($v);
                        } catch(\Exception $e) {
                            // Don't halt execution on this error, but do log it
                            Log::error($e);
                        }
                    }
                }
            }
        }
    }

    /* RETURNS CAT THAT TEXT BELONGS TO. CURRENTLY ONLY CHECKS FOR SPAM/NOT SPAM */
    public function Check()
    {
        if ($this->filter_status == 1 || $this->filter_status == 2) {
            if (isset($this->catMsgCounts['-1']) && isset($this->catMsgCounts['0'])) {
                //$cats = array_keys($this->catMsgCounts);
                // check that enough messages have been classifed to start autoclassification
                $totalMsgCt = $this->catMsgCounts['-1'] + $this->catMsgCounts['0'];

                $this->allWordsCatCount = $this->_getWordsByCategory();
                /*
                //loop over cats
                $i=0;
                foreach($cats AS $cat){
                    if($i > 0){	//ignore first pass
                        $res = $this->_CompareCats($winner['catid'],$cat);
                        if($res > cHD_BAYESIAN_PROB_CAT){	// greater than .5 means challenger won, else existing winner stays
                            $winner = array('catid'=>$cat,'prob'=>$res);
                        }else{
                            $winner = $winner;
                        }
                    }else{
                        $winner = array('catid'=>$cat,'prob'=>0);
                    }
                    $i++;
                }
                */
                //echo $this->_CompareCats(0,-1).' '.hs_setting('cHD_BAYESIAN_PROB_SPAM').'<br>';
                //DETERMINE SPAM VS NONSPAM
                $this->probability = $this->_CompareCats(0, -1);

                //echo '<br>'.$compres.'<br>';
                if ($totalMsgCt > $this->msgCtStart && floatval($this->probability) > floatval($this->spam_probability)) {
                    return '-1';
                } else {
                    return '0';
                }
            } else {
                //No data yet so presume not spam
                return 0;
            }
        } else {
            //Spam filter off so return as not spam
            return 0;
        }
    }

    /* PROBABILITY THAT TEXT BELONGS TO CAT1 */
    public function _CompareCats($cat1, $cat2)
    {
        $prob = [];
        $bestTokens = [];

        // Find probs for one category vs another
        foreach (array_keys($this->allTokens) as $v) {

            //if word not seen in a category before than assign 0
            if (! isset($this->allWordsCatCount[$v][$cat1])) {
                $this->allWordsCatCount[$v][$cat1] = 0;
            }
            if (! isset($this->allWordsCatCount[$v][$cat2])) {
                $this->allWordsCatCount[$v][$cat2] = 0;
            }

            // word must appear at least once in one of the categories
            if ($this->allWordsCatCount[$v][$cat1] != 0 || $this->allWordsCatCount[$v][$cat2] != 0) {
                $b = $this->allWordsCatCount[$v][$cat1];		//Number of times token found in first category (spam)
                $B = $this->catMsgCounts[$cat1];				//Number of messages used to create corpus	(spam)
                $g = $this->allWordsCatCount[$v][$cat2];		//Number of times token found in first category (non-spam)
                $G = $this->catMsgCounts[$cat2];				//Number of messages used to create corpus	(non-spam)

                // limit min and max probs
                $res = ($b / $B) / (($b / $B) + ($g / $G));
                $res = $res > .99 ? .99 : $res;
                $res = $res < .01 ? .01 : $res;
                $prob[$v] = $res;
            }
        }

        // Find the most interesting X tokens from the message
        foreach ($prob as $k=>$v) {
            $bestTokens[$k] = abs($v - .5);		//calculate absolute distance from .5
        }
        asort($bestTokens); //sort by absolute distance from .5
        $bestTokens = array_slice($bestTokens, -$this->numberTokens, $this->numberTokens);	//cut down to only top $this->numberTokens

        // Finally determine the probability that this is or isn't the tested category (http://www.paulgraham.com/naivebayes.html)
        $top = 1;
        $bottom = 1;
        foreach (array_keys($bestTokens) as $k) {
            $top = $top * $prob[$k];
            $bottom = $bottom * (1 - $prob[$k]);
        }

        @$result = log($top) / (log($top) + log($bottom));	//hide errors, just returns as not spam

        return $result;
    }

    /* BREAK TEXT INTO INDIVIDUAL TOKENS
        $text['subject']
        $text['from']
        $text['body']
        $text['headers']


    */
    public function _Tokenize($text)
    {
        $subject = $this->_RawTokens($text['subject'], 's:');
        $from = $this->_RawTokens($text['from'], 'f:');
        $body = $this->_RawTokens($text['body']);
        $headers = $this->_RawTokens($text['headers'], 'h:');
        $tokens = array_merge($subject, $from, $body, $headers);
        $tokens = array_count_values($tokens);
        //asort($tokens);

        return $tokens;
    }

    /* TAKES A STRING AND RETURNS AN ARRAY OF TOKENS */
    public function _RawTokens($string, $prefix = '')
    {
        $final = [];
        $out = [];
        if (! empty($string)) {

            // since mysql isn't case sensitive make everything lowercase
            $string = utf8_strtolower($string);

            preg_match_all('/([A-Za-z0-9\_\-\.\+]+\@[A-Za-z0-9_\-\.]+)/', $string, $emails);
            preg_match_all('/(\d+\.\d+\.\d+.\d+)/', $string, $ips);
            preg_match_all('/https?\:\/\/([A-Za-z0-9\_\-\.\/]+)/', $string, $urls);

            // remove items found with preg_match above so they don't get parsed twice
            $string = str_replace($urls[0], ' ', $string);
            $string = str_replace($emails[0], ' ', $string);
            $string = str_replace($ips[0], ' ', $string);

            //Just use the domain of a url
            foreach ($urls[0] as $k=>$u) {
                $tempu = parse_url($u);
                $urls[0][$k] = $tempu['host'];
            }

            // kill html
            $string = strip_tags($string); //dont use HS version since we always want to remove the tags no matter what

            //find rest of tokens
            $tokens = preg_split('/\s/', $string); //[^_A-Za-z0-9!]+ old regex
            $tokens = array_slice($tokens, 0, 500); // Max number of tokens

            foreach ($tokens as $t) {
                $t = utf8_rtrim($t, '.');

                // Remove some invisible characters which cause problems with the bayesian checking
                $t = $this->cleanToken($t);

                if (utf8_strlen($t) < $this->maxToken &&
                    utf8_strlen($t) > $this->minToken &&
                    ! preg_match('/^[0-9]+$/', $t) &&
                    ! empty($t)) {
                    array_push($final, utf8_trim($t));
                }
            }

            $final = array_merge($final, $emails[0], $ips[0], $urls[1]);
            if (! empty($prefix)) {
                foreach ($final as $v) {
                    $out[] = $prefix.$v;
                }
            }

            return ! empty($out) ? $out : $final;
        } else {
            return [];
        }
    }

    /* Get a words from db */
    public function _getWordsByCategory()
    {
        $allWords = [];

        $query = DB::table($this->table_words)
            ->select(['sWord', 'xCategory', 'iCount']);

        foreach($this->allTokens as $word => $v) {
            $query->orWhere('sWord', utf8_trim($word));
        }

        $wordResult = $query->get();

        //build array of words by cat with counts
        foreach($wordResult as $word) {
            $sWord = $this->cleanToken($word->sWord);
            $allWords[$sWord][$word->xCategory] = $word->iCount;
        }
        return $allWords;
    }

    /**
     * Clean stuff out of strings
     * @param $t
     * @return string|string[]|null
     */
    public function cleanToken($t)
    {
        $t = utf8_trim($t);
        // Replace utf invisible characters
        $t = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F-\x9F]/u', '', $t);
        // Replace line feeds, carriage returns, tabs, etc
        $t = preg_replace('/[\x00-\x1F\x7F-\xA0\xAD]/u', '', $t);
        return $t;
    }
}
