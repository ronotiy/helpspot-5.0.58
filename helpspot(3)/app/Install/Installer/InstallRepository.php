<?php

namespace HS\Install\Installer;

use HS\License\License;
use Illuminate\Database\Seeder;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Migrations\Migrator;

class InstallRepository
{
    /**
     * @var \Illuminate\Database\DatabaseManager
     */
    protected $db;

    /**
     * @var \Illuminate\Database\Migrations\Migrator
     */
    private $migrator;

    /**
     * @var \Illuminate\Database\Seeder
     */
    private $seeder;

    /**
     * @var string
     */
    private $defaultConnection;

    /**
     * @var string
     */
    private $migrationPath;

    public function __construct(DatabaseManager $db, Migrator $migrator, Seeder $seeder, $defaultConnection, $migrationPath)
    {
        $this->db = $db;
        $this->migrator = $migrator;
        $this->seeder = $seeder;
        $this->defaultConnection = $defaultConnection;
        $this->migrationPath = $migrationPath;
    }

    /**
     * Save the user uploaded license.
     * @param License $license
     * @param string $encodedAndEncryptedLicense
     */
    public function saveLicense(License $license, $encodedAndEncryptedLicense)
    {
        $this->saveGlobal('cHD_CUSTOMER_ID', $license->customerId);
        $this->saveGlobal('cHD_LICENSE', $encodedAndEncryptedLicense);
        $this->saveGlobal('cHD_PORTAL_MSG', lg_inst_portal_msg);
    }

    /**
     * Save multiple globals/settings.
     * @param array $data
     * @return $this
     */
    public function saveGlobals(array $data)
    {
        foreach ($data as $key => $value) {
            $this->saveGlobal($key, $value);
        }

        return $this;
    }

    /**
     * Save a global/setting.
     * @param $key
     * @param $value
     */
    public function saveGlobal($key, $value)
    {
        $this->db->table('HS_Settings')
            ->where('sSetting', $key)
            ->update(['tValue' => $value]);
    }

    /**
     * Add a new global/setting.
     * @param $key
     * @param $value
     */
    public function addGlobal($key, $value)
    {
        $this->db->table('HS_Settings')
            ->insert(['sSetting' => $key, 'tValue' => $value]);
    }

    /**
     * Run Migrations and Seeds.
     * @return $this
     */
    public function migrateAndSeed()
    {
        $this->migrate();
        $this->seed();

        return $this;
    }

    /**
     * Run migrations.
     * @return $this
     */
    public function migrate()
    {
        // Create Migrations table if does not exist
        if (! $this->db->getSchemaBuilder()->hasTable('HS_Migrations')) {
            $migRepo = $this->migrator->getRepository();
            $migRepo->createRepository();
        }

        $this->migrator->run($this->migrationPath);

        return $this;
    }

    /**
     * Run seeds.
     * @return $this
     */
    public function seed()
    {
        $this->seeder->run();

        return $this;
    }

    /**
     * Initialize all settings
     * into global constants.
     */
    public function initializeGlobals()
    {
        $globals = $this->db->table('HS_Settings')->get();

        foreach ($globals as $global) {
            if (! defined($global->sSetting)) {
                define($global->sSetting, $global->tValue);
            }
        }

        return $this;
    }

    /**
     * Add some API methods.
     */
    public function importApi()
    {
        ob_start();
        include_once cBASEPATH.'/helpspot/lib/utf8.lib.php';
        include_once cBASEPATH.'/helpspot/lib/util.lib.php';
        require_once cBASEPATH.'/helpspot/lib/class.auto.rule.php';
        require_once cBASEPATH.'/helpspot/lib/api.hdcategories.lib.php';
        require_once cBASEPATH.'/helpspot/lib/api.users.lib.php';
        require_once cBASEPATH.'/helpspot/lib/api.requests.lib.php';
        require_once cBASEPATH.'/helpspot/lib/api.lib.php';
        require_once cBASEPATH.'/helpspot/pear/Crypt_RC4/Rc4.php';
        include_once cBASEPATH.'/helpspot/lib/phpass/PasswordHash.php';
        require_once cBASEPATH.'/helpspot/lib/class.auto.rule.php';
        ob_clean();
    }

    public function addUser($firstName, $lastName, $email, $password)
    {
        return apiAddEditUser([
            'mode'              => 'add',
            'fUserType'         => 1,
            'sFname'            => $firstName,
            'sLname'            => $lastName,
            'sEmail'            => $email,
            'sPassword'         => $password,
            'xPersonPhotoId'    => 0,
            'fNotifyEmail'      => 1,
            'fDefaultToPublic'  => 1,
            'fShowWelcome'      => 1,
        ], __FILE__, __LINE__);
    }

    public function addCategories($person, $type)
    {
        $append = '';
        if ($type === 'external') {
            $append = '_external';
        }

        for ($i = 0; $i < lg_inst_cat_number_cats; $i++) {
            if ($i === 0) {
                $catNum = '';
            } else {
                $catNum = $i + 1;
            }

            apiAddEditCategory([
                'mode'                  => 'add',
                'sCategory'             => constant('lg_inst_cat'.$catNum.'_def'.$append),
                'fAllowPublicSubmit'    => 0,
                'xPersonDefault'        => $person,
                'sReportingTagList'     => explode(',', constant('lg_inst_cat'.$catNum.'_reptags'.$append)),
                'sPersonList'           => serialize([$person]),
                'sCustomFieldList'      => serialize([]),
            ], __FILE__, __LINE__);
        }

        return true;
    }

    public function addRequest()
    {
        $result = apiAddEditRequest([
            'fOpenedVia'        => 7,
            'mode'              => 'add',
            'xPersonOpenedBy'   => 0,
            'xPersonAssignedTo' => 1,
            'xCategory'         => 1,
            'tBody'             => lg_inst_welcomereq,
            'fPublic'           => 0,
            'sFirstName'        => 'Ian',
            'sLastName'         => 'Landsman',
            'sEmail'            => 'customer.service@userscape.com',
        ], 0, __FILE__, __LINE__);

        apiPortalAddLoginIfNew('customer.service@userscape.com', randomPasswordString(8));

        return $result;
    }

    public function addFilters()
    {
        //Create an all open filter
        $f = [];
        $f['mode'] = 'add';
        $f['sFilterName'] = 'All Open';
        $f['anyall'] = 'any';
        $f['displayColumns'] = ['iLastReplyBy', 'fOpenedVia', 'xPersonAssignedTo', 'fullname', 'reqsummary', 'age'];
        $f['sFilterFolder'] = 'Global Filters';
        $f['conditioninit_1'] = 'fOpen';
        $f['conditioninit_2'] = 1;

        $rule = new \hs_auto_rule();
        $rule->SetAutoRule($f);

        $f['fType'] = 1;
        $f['tFilterDef'] = hs_serialize($rule);
        $f['xPerson'] = 1;
        $f['fShowCount'] = 1;
        apiAddEditFilter($f);

        //Create a recently closed filter
        $f = [];
        $f['mode'] = 'add';
        $f['sFilterName'] = 'Recently Closed';
        $f['anyall'] = 'all';
        $f['displayColumns'] = ['iLastReplyBy', 'fOpenedVia', 'xPersonAssignedTo', 'fullname', 'reqsummary', 'age'];
        $f['sFilterFolder'] = 'Global Filters';
        $f['conditioninit_1'] = 'fOpen';
        $f['conditioninit_2'] = 0;
        $f['conditiontime_1'] = 'relativedateclosed';
        $f['conditiontime_2'] = 'past_14';

        $rule = new \hs_auto_rule();
        $rule->SetAutoRule($f);

        $f['fType'] = 1;
        $f['tFilterDef'] = hs_serialize($rule);
        $f['xPerson'] = 1;
        apiAddEditFilter($f);

        return true;
    }

    /**
     * Set where HS_Requests auto increment begins.
     * @throws \InvalidArgumentException
     */
    public function setAutoIncrementStart()
    {
        switch ($this->defaultConnection) {
            case 'mysql':
                $this->db->statement('ALTER TABLE HS_Request AUTO_INCREMENT=12400');

                break;
            case 'sqlsrv':
                $this->db->statement('DBCC CheckIdent (HS_Request,RESEED,12400)');

                break;
            default:
                throw new \InvalidArgumentException('Illegal database connection type given');
        }
    }
}
