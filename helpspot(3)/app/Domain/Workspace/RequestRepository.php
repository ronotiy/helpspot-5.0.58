<?php

namespace HS\Domain\Workspace;

include_once cBASEPATH.'/helpspot/lib/class.requestupdate.php';

use requestUpdate;

class RequestRepository
{
    public function requests(array $requests)
    {
        return Request::find($requests);
    }

    public function requestsFromSearch($history)
    {
        return Request::with(['history' => function ($query) {
            $query->where('fInitial', 1);
        }])->find($history);
    }

    public function forceOpen($request)
    {
        $request['fOpen'] = 1;
        $request['xStatus'] = hs_setting('cHD_STATUS_ACTIVE');
        $request['dtGMTOpened'] = date('U');	//current dt
        $request['xPersonAssignedTo'] = $this->getAssignment($request);

        $update = new requestUpdate($request['xRequest'], $request, 0, __FILE__, __LINE__);
        $update->notify = false; //notify below instead
        $update->skipTrigger = true; // Call triggers below
        return $update->checkChanges();
    }

    public function getAssignment($request)
    {
        //if the user isn't active then send to inbox
        $ustatus = apiGetUser($request['xPersonAssignedTo']);
        if ($ustatus['fDeleted'] == 1) {
            return 0;
        }

        return $request['xPersonAssignedTo'];
    }
}
