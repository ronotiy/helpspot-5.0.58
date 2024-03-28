<?php

use HS\Domain\Documents\S3File;
use HS\Domain\Workspace\Document;
use HS\Domain\Workspace\History;

class Destroy
{
    /**
     * Delete a request by id
     * @param $id
     * @return bool
     */
    public function request($id)
    {
        logMsg('DELETED request '. $id.' through the customer tools');
        apiDeleteRequest($id, __FILE__, __LINE__);
        return true;
    }

    /**
     * Loop through all the requests and delete each one.
     * @param $reqs
     * @return bool
     */
    public function requests($reqs)
    {
        logMsg('Attempting to delete '. $reqs->RecordCount() .' requests');
        while($req = $reqs->FetchRow()) {
            $this->request($req['xRequest']);
        }
        return true;
    }

    /**
     * Delete a history item
     * @param int $id
     * @return bool
     * @throws Exception
     */
    public function history($id)
    {
        logMsg('Attempting to delete the request history: #'. $id);
        $history = History::find($id);

        // delete any attachments associated with the item.
        foreach($history->documents as $document) {
            $this->deleteAttachment($document);
        }

        // Finally delete the primary item
        return $history->delete();
    }

    /**
     * Delete a single history item
     * @param $id
     * @return bool
     */
    protected function deleteHistoryItem($id)
    {
        // Finally delete the item
        if (! $res = $GLOBALS['DB']->Execute( 'DELETE FROM HS_Request_History WHERE xRequestHistory = ?', [$id]) ) {
            errorLog($GLOBALS['DB']->ErrorMsg(),'Database', __FILE__,__LINE__);
        }
        return true;
    }

    /**
     * Delete Attachment from a disk
     *
     * @param $doc
     * @throws Exception
     */
    protected function deleteAttachment(Document $doc)
    {
        if (! hs_empty($doc->sFileLocation) && ! hs_empty($doc->xDocumentId)) {
            if( strpos($doc->sFileLocation, 's3://') !== false ) {
                (new S3File($doc->sFileLocation))->delete();
            }
            unlink(cHD_ATTACHMENT_LOCATION_PATH . $doc->sFileLocation);
        }
        $doc->delete();
    }

    /**
     * @param $id
     * @return bool
     */
    public function getRequestHistory($id)
    {
        $req = $GLOBALS['DB']->GetRow( 'SELECT * FROM HS_Request_History WHERE xRequestHistory = ?', [$id]);
        if ($req == false) {
            errorLog($GLOBALS['DB']->ErrorMsg(),'Database',__FILE__, __LINE__);
            return false;
        }

        return $req;
    }

    /**
     * @param $email
     * @return bool
     */
    public function redactEmailInEvents($email)
    {
        $GLOBALS['DB']->Execute( 'UPDATE HS_Request_Events SET sValue = ? WHERE sValue = ?', ['DELETEDCUSTOMER@EXAMPLE.COM', $email] );
        $GLOBALS['DB']->Execute( 'UPDATE HS_Request_Events SET sLabel = ? WHERE sLabel = ?', ['DELETEDCUSTOMER@EXAMPLE.COM', $email] );
        $GLOBALS['DB']->Execute( 'UPDATE HS_Request_Events SET sDescription = REPLACE(sDescription, ?, ?) WHERE INSTR(sDescription, ?)', [$email, 'DELETEDCUSTOMER@EXAMPLE.COM', $email] );
        return true;
    }

    /**
     * @param $customerId
     * @return bool
     */
    public function redactCustomerInEvents($customerId)
    {
        $GLOBALS['DB']->Execute( 'UPDATE HS_Request_Events SET sValue = ? WHERE sValue = ?', ['DELETED_CUSTOMER', $customerId] );
        $GLOBALS['DB']->Execute( 'UPDATE HS_Request_Events SET sLabel = ? WHERE sLabel = ?', ['DELETED_CUSTOMER', $customerId] );
        $GLOBALS['DB']->Execute( 'UPDATE HS_Request_Events SET sDescription = REPLACE(sDescription, ?, ?) WHERE INSTR(sDescription, ?)', [$customerId, 'DELETED_CUSTOMER', $customerId] );
        return true;
    }

    /**
     * Find all requests by email.
     *
     * @param $email
     * @return bool
     */
    public function findRequestsByEmail($email)
    {
        $reqs = $GLOBALS['DB']->Execute( 'SELECT * FROM HS_Request
                                     WHERE sEmail = ?
                                     ORDER BY xRequest DESC', array($email) );
        if($reqs == false){
            errorLog($GLOBALS['DB']->ErrorMsg(),'Database',__FILE__,__LINE__);
            return false;
        }else{
            return $reqs;
        }
    }

    /**
     * Find all requests by customer id.
     *
     * @param $id
     * @return bool
     */
    public function findRequestsByCustomerId($id)
    {
        $reqs = $GLOBALS['DB']->Execute( 'SELECT * FROM HS_Request
                                     WHERE sUserId = ?
                                     ORDER BY xRequest DESC', array($id) );
        if($reqs == false){
            errorLog($GLOBALS['DB']->ErrorMsg(),'Database',__FILE__,__LINE__);
            return false;
        }else{
            return $reqs;
        }
    }
}
