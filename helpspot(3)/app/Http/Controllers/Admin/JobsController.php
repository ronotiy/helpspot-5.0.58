<?php

namespace HS\Http\Controllers\Admin;

use HS\FailedJob;
use HS\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class JobsController extends Controller
{
    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $this->loadLang();
        Artisan::call('queue:retry', ['id' => $id]);

        return back()->with('feedback', lg_admin_jobsmgmt_job_retried);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $this->loadLang();

        FailedJob::findOrFail($id)
            ->delete();

        return back()->with('feedback', lg_admin_jobsmgmt_job_deleted);
    }

    protected function loadLang()
    {
        include_once cBASEPATH.'/helpspot/lib/class.language.php';
        $GLOBALS['lang'] = new \language('admin.tools.jobsmgmt');
    }
}
