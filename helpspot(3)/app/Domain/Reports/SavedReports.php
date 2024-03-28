<?php

namespace HS\Domain\Reports;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;

class SavedReports extends Model {

    protected $table = 'HS_Saved_Reports';

    protected $primaryKey = 'xReport';

    protected $guarded = [];

    public $timestamps = false;

    /**
     * @param array $user
     * @param array $res
     * @return bool
     */
    public function add(array $user, array $res)
    {
        $this->sReport = $res['title'];
        $this->sFolder = $this->cleanFolders($res);
        $this->xPerson = $user['xPerson'];
        $this->fType = $res['fType'];
        $this->sPage = $res['sPage'];
        $this->sShow = $res['sShow'];
        $this->fEmail = (isset($res['fEmail'])) ? $res['fEmail'] : 0;
        $this->tData = hs_serialize($res);
        $this->fSendEvery = $res['fSendEvery'];
        $this->fSendDay = $res['fSendDay'];
        $this->fSendTime = $res['fSendTime'];
        $this->fSendToStaff = (is_array($res['fSendToStaff']) ? implode(',', $res['fSendToStaff']) : '');
        $this->fSendToExternal = $this->processExternalEmails($res['fSendToExternal']);
        $this->dtSendsAt = $this->calculateNextSendDate();
        $this->save();

        $lastInsertId = $this->xReport;

        //If permission group, add them
        if($res['fType'] == 3 && isset($res['fPermissionGroup']) && !empty($res['fPermissionGroup'])){
            foreach($res['fPermissionGroup'] AS $k=>$group){
                $GLOBALS['DB']->Execute( 'INSERT INTO HS_Report_Group(xReport,xGroup) VALUES (?,?)', array($lastInsertId,$group) );
            }
        }
        if($res['fType'] == 4 && isset($res['sPersonList']) && !empty($res['sPersonList'])){
            foreach($res['sPersonList'] AS $k=>$person){
                $GLOBALS['DB']->Execute( 'INSERT INTO HS_Report_People(xReport,xPerson) VALUES (?,?)', array($lastInsertId,$person) );
            }
        }

        return $lastInsertId;
    }

    /**
     * @param array $user
     * @param array $res
     * @return mixed
     */
    public function edit(array $user, array $res)
    {
        $xReport = $this->xReport;
        $this->sReport = $res['title'];
        $this->sFolder = $this->cleanFolders($res);
        $this->fType = $res['fType'];
        $this->sPage = $res['sPage'];
        $this->sShow = $res['sShow'];
        $this->fEmail = (isset($res['fEmail'])) ? $res['fEmail'] : 0;
        $this->tData = hs_serialize($res);
        $this->fSendEvery = $res['fSendEvery'];
        $this->fSendDay = $res['fSendDay'];
        $this->fSendTime = $res['fSendTime'];
        $this->fSendToStaff = (is_array($res['fSendToStaff']) ? implode(',', $res['fSendToStaff']) : '');
        $this->fSendToExternal = $this->processExternalEmails($res['fSendToExternal']);
        $this->dtSendsAt = $this->calculateNextSendDate();
        $this->save();

        // If permission group, add them
        if ($res['fType'] == 3 && isset($res['fPermissionGroup']) && !empty($res['fPermissionGroup'])) {
            if (isAdmin()) {
                $GLOBALS['DB']->Execute( 'DELETE FROM HS_Report_Group WHERE xReport = ?', array($xReport) );
                foreach ($res['fPermissionGroup'] AS $k=>$group) {
                    $GLOBALS['DB']->Execute( 'INSERT INTO HS_Report_Group(xReport,xGroup) VALUES (?,?) ', array($xReport,$group) );
                }
            } else {
                // Since they aren't admins we only add their group to the existing. They can't take away groups.
                $existingGroups = $GLOBALS['DB']->GetCol( 'SELECT xGroup FROM HS_Report_Group WHERE xReport = ?', array($xReport) );
                foreach ($res['fPermissionGroup'] AS $k=>$group) {
                    if ( ! in_array($group, $existingGroups)) {
                        $GLOBALS['DB']->Execute( 'INSERT INTO HS_Report_Group(xReport,xGroup) VALUES (?,?) ', array($xReport,$group) );
                    }
                }
            }
        }

        if ($res['fType'] == 4 && isset($res['sPersonList']) && !empty($res['sPersonList'])) {
            $GLOBALS['DB']->Execute( 'DELETE FROM HS_Report_People WHERE xReport = ?', array($xReport) );
            foreach ($res['sPersonList'] AS $k=>$person) {
                $GLOBALS['DB']->Execute( 'INSERT INTO HS_Report_People(xReport,xPerson) VALUES (?,?) ', array($xReport,$person) );
            }
        }

        return $xReport;
    }

    /**
     * @param $range
     * @return array
     */
    public function calculateDateRange($range)
    {
        switch ($range) {
            case 'today':
                return [
                    'start' => Carbon::now()->startOfDay()->timestamp,
                    'end' => Carbon::now()->timestamp,
                ];
                break;
            case 'yesterday':
                return [
                    'start' => Carbon::yesterday()->startOfDay()->timestamp,
                    'end' => Carbon::yesterday()->endOfDay()->timestamp,
                ];
                break;
            case 'last7days':
                return [
                    'start' => Carbon::now()->subDays(7)->startOfDay()->timestamp,
                    'end' => Carbon::yesterday()->endOfDay()->timestamp,
                ];
                break;
            case 'last30days':
                return [
                    'start' => Carbon::now()->subDays(30)->startOfDay()->timestamp,
                    'end' => Carbon::yesterday()->endOfDay()->timestamp,
                ];
                break;
        }
    }

    /**
     * Calculate the next send date based on report options
     *
     * @return string
     */
    public function calculateNextSendDate()
    {
        // Make sure we want this
        if (! $this->fSendToStaff && ! $this->fSendToExternal) {
            return null;
        }

        return calculateNextSend($this->fSendTime, $this->fSendDay, $this->fSendEvery);
    }

    /**
     * @param $emails
     * @return string
     */
    protected function processExternalEmails($emails)
    {
        $emailString = '';
        $emails = explode(',', $emails);
        foreach ($emails as $email) {
            $email = trim($email);
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $emailString .= $email.', ';
            }
        }
        return rtrim($emailString, ', ');
    }

    /**
     * Can the user edit the form?
     *
     * @param array $user
     * @param array $report
     * @return bool
     */
    public function canEdit(array $user, array $report)
    {
        if (isAdmin() || $user['xPerson'] == $report['xPerson'] || $report['xPerson'] == 0) {
            return true;
        }

        return false;
    }

    /**
     * @param array $user
     * @return \Illuminate\Support\Collection
     */
    public function sidebarFolders(array $user)
    {
        $xPerson = is_numeric($user['xPerson']) ? $user['xPerson']: 0;

        $folders = DB::table('HS_Saved_Reports')
            ->whereRaw('xPerson = ? OR
				fType = 1 OR
				 (fType = 2 AND xPerson = ?) OR
				 (fType = 3 AND ? IN (SELECT xGroup FROM HS_Report_Group WHERE xGroup = HS_Report_Group.xGroup)) OR
				 (fType = 4 AND ? IN (SELECT xPerson FROM HS_Report_People WHERE xPerson = HS_Report_People.xPerson))',
                [$xPerson, $xPerson, $user['xGroup'], $xPerson])
            ->get();

        return $folders;
    }

    /**
     * Get the report folders for a user
     *
     * @param array $user
     * @return array|bool
     */
    public function folders(array $user)
    {
        $xPerson = is_numeric($user['xPerson']) ? $user['xPerson']: 0;

        $res = $GLOBALS['DB']->Execute( 'SELECT DISTINCT sFolder FROM HS_Saved_Reports
		WHERE (xPerson = ? OR
				fType = 1 OR
				 (fType = 2 AND xPerson = ?) OR
				 (fType = 3 AND ? IN (SELECT xGroup FROM HS_Report_Group WHERE xGroup = HS_Report_Group.xGroup)) OR
				 (fType = 4 AND ? IN (SELECT xPerson FROM HS_Report_People WHERE xPerson = HS_Report_People.xPerson)))
				ORDER BY sFolder ASC', array($xPerson, $xPerson, $user['xGroup'], $xPerson) );

        if ($res === false) {
            errorLog($GLOBALS['DB']->ErrorMsg(),'Database');
            return false;
        }

        return rsToArray($res,'sFolder');
    }

    /**
     * clean folders
     * @param $res
     * @return string
     */
    protected function cleanFolders($res)
    {
        $folders = explode('/',$res['sFolder']);
        foreach ($folders AS $k=>$folder) {
            $folders[$k] = trim($folder);
        }
        return implode(' / ',$folders);
    }
}
