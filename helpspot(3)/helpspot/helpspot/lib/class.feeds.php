<?php

// SECURITY: Don't allow direct calls
if (! defined('cBASEPATH')) {
    die();
}

/*
CLASSES TO BUILD DATA FEEDS. INCLUDEDS FRAMEWORK AND SUPPORTING CLASSES LIKE DATE MGMT.
*/

class feedBase
{
    // Array of items for the feed
    public $items = [];

    // Data type
    public $contentType = 'application/xml';

    // Text encoding
    public $encoding = 'UTF-8';

    // Page name
    public $pagename = '';

    // Requried channel vars
    public $title;

    public $description;

    public $link;

    public $copyright;

    /*****************************************
    PUBLIC METHODS
    *****************************************/
    //add an item to the array
    public function addItem($item)
    {
        $this->items[] = $item;
    }

    //Every subclass should override this to return the finished feed
    public function render()
    {
    }
}

class feedItem
{
    // Requried vars
    public $title;

    public $description;

    public $link;

    // Optional Stuff
    public $author;

    public $authorEmail;

    public $image;

    public $category;

    public $comments;

    public $guid;

    public $source;

    public $creator;

    // Unix date
    public $date;

    // Other namespace elements
    //var $additionalElements = Array();
}

/*****************************************
FEED TYPE SPECIFIC CLASSES
*****************************************/
class RSS20 extends feedBase
{
    public function __construct()
    {
    }

    public function render()
    {
        header('Content-type: text/xml; charset='.$this->encoding, true);
        header('Content-Disposition: inline; filename='.str_replace(' ', '_', hs_htmlspecialchars($this->pagename)));

        $feed = '<?xml version="1.0" encoding="'.$this->encoding."\"?>\n";
        $feed .= "<rss version=\"2.0\">\n";
        $feed .= "    <channel>\n";
        $feed .= '        <title>'.hs_htmlspecialchars($this->title)."</title>\n";
        $feed .= '        <description>'.hs_htmlspecialchars($this->description)."</description>\n";
        $feed .= '        <copyright>'.hs_htmlspecialchars($this->copyright)."</copyright>\n";
        $feed .= '        <link>'.hs_htmlspecialchars($this->link)."</link>\n";
        $feed .= "        <generator>HelpSpot</generator>\n";

        $c = count($this->items);
        for ($i = 0; $i < $c; $i++) {
            $feed .= "        <item>\n";
            $feed .= '            <title>'.hs_htmlspecialchars(hs_strip_tags($this->items[$i]->title))."</title>\n";
            $feed .= '            <link>'.hs_htmlspecialchars($this->items[$i]->link)."</link>\n";
            $feed .= '            <description><![CDATA[ '.$this->items[$i]->description." ]]></description>\n";
            if ($this->items[$i]->category != '') {
                $feed .= '            <category>'.hs_htmlspecialchars($this->items[$i]->category)."</category>\n";
            }
            if ($this->items[$i]->date != '') {
                $feed .= '            <pubDate>'.hs_htmlspecialchars(RFCDate($this->items[$i]->date))."</pubDate>\n";
            }
            if ($this->items[$i]->guid != '') {
                $feed .= '            <guid>'.hs_htmlspecialchars($this->items[$i]->guid)."</guid>\n";
            }
            $feed .= "        </item>\n";
        }
        $feed .= "    </channel>\n";
        $feed .= "</rss>\n";

        return $feed;
    }
}

class OPML extends feedBase
{
    public function __construct()
    {
    }

    public function render()
    {
        header('Content-type: text/xml; charset='.$this->encoding, true);
        header('Content-Disposition: inline; filename='.str_replace(' ', '_', hs_htmlspecialchars($this->pagename)));

        $feed = '<?xml version="1.0" encoding="ISO-8859-1"?>';
        $feed .= '<opml version="1.1">';
        $feed .= '<head>';
        $feed .= '	<title>'.hs_htmlspecialchars($this->title).'</title>';
        $feed .= '</head>';
        $feed .= '<body>';

        $c = count($this->items);
        for ($i = 0; $i < $c; $i++) {
            $feed .= '<outline text="'.hs_htmlspecialchars($this->items[$i]->title).'" title="'.hs_htmlspecialchars($this->items[$i]->title).'"';
            if (is_array($this->items[$i]->description)) {
                $feed .= '>';
                foreach ($this->items[$i]->description as $v) {
                    $feed .= '<outline text="'.hs_htmlspecialchars($v->title).'" title="'.hs_htmlspecialchars($v->title).'" type="rss" version="RSS" xmlURL="'.hs_htmlspecialchars($v->link).'" />';
                }
                $feed .= '</outline>';
            } else {
                $feed .= ' type="rss" version="RSS" xmlURL="'.hs_htmlspecialchars($this->items[$i]->link).'" />';
            }
        }

        $feed .= '</body>';
        $feed .= '</opml>';

        return $feed;
    }
}
