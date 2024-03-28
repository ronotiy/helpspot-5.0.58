<?php

namespace HS\Install\Installer\Actions;

use Illuminate\Support\MessageBag;
use Illuminate\Validation\Factory as Validator;

class CheckAccount
{
    /**
     * Set data to check.
     * @var array
     */
    protected $data = [];

    /**
     * Validation rules.
     * @var array
     */
    protected $accountRules = [
        'helpdeskname'          => 'required',
        'customerid'            => 'required',
        'license'               => 'required',
        'fname'                 => 'required',
        'lname'                 => 'required',
        'adminemail'            => 'required|email',
        'adminpass'             => 'required|min:8',
        'notemail'              => 'required|email',
        'cHD_TIMEZONE_OVERRIDE' => 'required',
        'notificationname'      => 'required',
    ];

    /**
     * SMTP Validation rules
     * TODO: Confirm these are required.
     * @var array
     */
    protected $smtpRules = [
        'cHD_MAIL_SMTPHOST'     => 'required',
        'cHD_MAIL_SMTPPORT'     => 'required',
        'cHD_MAIL_SMTPUSER'     => 'required',
        'cHD_MAIL_SMTPPASS'     => 'required',
    ];

    /**
     * Validation Messages.
     * @var array
     */
    protected $messages = [
        'helpdeskname.required'          => lg_inst_empty,
        'customerid.required'            => lg_inst_empty,
        'license.required'               => lg_inst_empty,
        'fname.required'                 => lg_inst_namefirstlast,
        'lname.required'                 => lg_inst_namefirstlast,
        'adminemail.required'            => lg_inst_validemail,
        'adminemail.email'               => lg_inst_validemail,
        'adminpass.required'             => lg_inst_validpassword,
        'notemail.required'              => lg_inst_validemail,
        'notemail.email'                 => lg_inst_validemail,
        'cHD_TIMEZONE_OVERRIDE.required' => lg_inst_selecttz,
        'notificationname.required'      => lg_inst_validemail,

        'cHD_MAIL_SMTPHOST.required'     => lg_inst_empty,
        'cHD_MAIL_SMTPPORT.required'     => lg_inst_empty,
        'cHD_MAIL_SMTPUSER.required'     => lg_inst_empty,
        'cHD_MAIL_SMTPPASS.required'     => lg_inst_empty,
    ];

    /**
     * Validation Errors.
     * @var \Illuminate\Foundation\MessageBag
     */
    protected $errors;

    /**
     * @var \Illuminate\Validation\Factory
     */
    private $validator;

    public function __construct(Validator $validator)
    {
        $this->validator = $validator;
        $this->errors = new MessageBag; // Empty messagebag to start
    }

    public function isValid(array $data, $useSmtp = false)
    {
        if ($useSmtp) {
            // Combine in SMTP rules
            $this->accountRules = array_merge($this->accountRules, $this->smtpRules);
        }

        $this->data = $data;

        $name = (isset($data['adminname'])) ? $this->parseName($data['adminname']) : '';
        $notificationName = (isset($data['notemail'])) ? $this->parseNotificationName($data['notemail']) : '';

        $this->data['fname'] = isset($name['fname']) ? $name['fname'] : '';
        $this->data['lname'] = isset($name['lname']) ? $name['lname'] : '';
        $this->data['notificationname'] = $notificationName;

        $validator = $this->validator->make($this->data, $this->accountRules, $this->messages);

        if ($validator->fails()) {
            $this->errors = $validator->errors();

            return false;
        }

        return true;
    }

    public function getData()
    {
        return $this->data;
    }

    public function getErrors()
    {
        return $this->errors;
    }

    protected function parseNotificationName($notificationEmail)
    {
        $notificationName = explode('@', $notificationEmail);

        return $notificationName[0];
    }

    protected function parseName($name)
    {
        $temp = trim($name);

        // Filter stuff that we don't use
        // E.g. parentheses - Chris (L) Fidao
        $temp = preg_replace('/ ?\(.*?\)/', '', $temp);

        $out = [
            'fname' => '',
            'lname' => '',
        ];

        if (! strstr(trim($temp), ' ')) {
            //If name doesn't have a space then return as only last
            $out['fname'] = 'none';
            $out['lname'] = $temp;
        } else {
            //normal format of fname followed by lname with space
            $t = explode(' ', $temp);
            $out['fname'] = $t[0];
            unset($t[0]);
            $out['lname'] = implode(' ', $t);
        }

        $out['fname'] = trim($out['fname']);
        $out['lname'] = trim($out['lname']);

        return $out;
    }
}
