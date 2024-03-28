<?php
/**
Special class for holding information about a remote portal
*/
class hs_multiportal
{
    public $xPortal = 0;

    public $sPortalName = '';

    public $sPortalPhone = '';

    public $tPortalMsg = '';

    public $sHost = '';

    public $sPortalPath = '';

    public $xMailboxToSendFrom = false;

    public $kbs = [];

    public $categories = [];

    public $cfs = [];

    public $mailboxes = [];

    /**
     * Constructor, set message ID.
     */
    public function __construct($portalid)
    {
        $portalid = is_numeric($portalid) ? $portalid : 0;

        $portal = apiGetPortal($portalid);

        //Show error if the ID was not valid
        if (! $portal || $portal['fDeleted'] == 1) {
            die('The portal ID is not valid');
        }

        //Setup this portals data
        $this->xPortal = $portalid;
        $this->sPortalName = $portal['sPortalName'];
        $this->sPortalPhone = $portal['sPortalPhone'];
        $this->tPortalMsg = $portal['tPortalMsg'];
        $this->xMailboxToSendFrom = $portal['xMailboxToSendFrom'] == 0 ? false : $portal['xMailboxToSendFrom'];
        $this->sHost = $portal['sHost'];
        $this->sPortalPath = $portal['sPortalPath'];
        $this->kbs = (empty($portal['tDisplayKBs']) ? [] : unserialize($portal['tDisplayKBs']));
        $this->categories = (empty($portal['tDisplayCategories']) ? [] : unserialize($portal['tDisplayCategories']));
        $this->cfs = (empty($portal['tDisplayCfs']) ? [] : unserialize($portal['tDisplayCfs']));
        $this->mailboxes = (empty($portal['tHistoryMailboxes']) ? [] : unserialize($portal['tHistoryMailboxes']));
        $this->sPortalTerms = $portal['sPortalTerms'];
        $this->sPortalPrivacy = $portal['sPortalPrivacy'];
        $this->fRequireAuth = $portal['fRequireAuth'];
    }

    /**
     * If the page/id combo we're on is not for this portal then exit the program.
     */
    public function idCheck($page, $id = false)
    {
        if (! empty($this->kbs)) {
            switch ($page) {
                case 'kb.book':
                case 'kb.printer.friendly':
                    if (! in_array($id, $this->kbs)) {
                        exit();
                    }

                    break;
                case 'kb.chapter':
                    //Find book ID of chapter
                    $bookid = $GLOBALS['DB']->GetOne('SELECT xBook FROM HS_KB_Chapters WHERE xChapter = ?', [$id]);
                    if (! in_array($bookid, $this->kbs)) {
                        exit();
                    }

                    break;
                case 'kb.page':
                    //Find book ID of a page
                    $bookid = $GLOBALS['DB']->GetOne('SELECT HS_KB_Books.xBook FROM HS_KB_Pages, HS_KB_Chapters, HS_KB_Books
					 				 				  WHERE HS_KB_Pages.xPage = ? AND HS_KB_Pages.xChapter = HS_KB_Chapters.xChapter AND
					 				 				  HS_KB_Chapters.xBook = HS_KB_Books.xBook', [$id]);
                    if (! in_array($bookid, $this->kbs)) {
                        exit();
                    }

                    break;
            }
        }
    }

    /**
     * Confirm if the request should be shown in the requset history.
     */
    public function requestValidForPortal(&$req)
    {
        if ($req['fOpenedVia'] == 1 && in_array($req['xOpenedViaId'], $this->mailboxes) ||				//Request came in on an associated mailbox
            $req['xMailboxToSendFrom'] > 0 && in_array($req['xMailboxToSendFrom'], $this->mailboxes) ||	//Emails are being sent out via an associated mailbox
            $req['fOpenedVia'] == 7 && $req['xPortal'] == $this->xPortal) {								//Request was created in this portal
            return true;
        } else {
            return false;
        }
    }
}
