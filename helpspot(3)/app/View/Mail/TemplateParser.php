<?php

namespace HS\View\Mail;

use HS\Mailbox;
use HS\Domain\Workspace\Request;

class TemplateParser
{
    protected $templates;

    protected $request = [];

    protected $mailbox = [];

    public function __construct()
    {
        $this->templates = hs_unserialize(hs_setting('cHD_EMAIL_TEMPLATES'));
    }

    /**
     * Parse a string for simple blade echos
     *
     * @param $value
     * @param array $data
     * @return string|string[]|null
     */
    public function templateString($value, $data = [])
    {
        $pattern = sprintf('/(@)?%s\s*(.+?)\s*%s(\r?\n)?/s', '{{', '}}');

        $callback = function ($matches) {
            $whitespace = empty($matches[3]) ? '' : $matches[3].$matches[3];
            // Converts vars to ##$var##
            $wrapped = sprintf('##%s##', $matches[2]);
            return $matches[1] ? substr($matches[0], 1) : "$wrapped{$whitespace}";
        };

        $string = preg_replace_callback($pattern, $callback, $value);

        //loop through the replace vars
        foreach ($data as $k => $var) {
            $string = str_replace('##$'.$k.'##', $var, $string);
        }

        return $string;
    }

    public function subject($template, $data, $mailboxId)
    {
        $uniqueTemplateName = $this->uniqueTemplateName($template);
        TemplateTemporaryFile::create($uniqueTemplateName, $this->getTemplate($template, $mailboxId, 'subject'));
        return (string)mailView($uniqueTemplateName, $data);
    }

    /**
     * @param $template The top-level template being used (HS_Settings > cHD_EMAIL_TEMPLATES)
     * @param $data Array of key-value pairs used to parse the template
     * @param $mailboxId The mailbox used to send the mail, in case it has some custom templates
     * @param $message - The body of the message (reply to customer, automation/trigger/mail messages, responses, etc) which may also have variables
     * @return array - An array containing the HTML and TEXT version of the parsed email content
     */
    public function body($template, $data, $mailboxId, $message)
    {
        $mainTemplateContentHtml = $this->getTemplate($template, $mailboxId, 'html');
        $mainTemplateContentText = $this->getTemplate($template, $mailboxId);

        $bodyTemplateName = $this->uniqueTemplateName($template);
        TemplateTemporaryFile::create($bodyTemplateName.'_html', $mainTemplateContentHtml);
        TemplateTemporaryFile::create($bodyTemplateName.'_text', $mainTemplateContentText);

        $messageTemplateName = $this->uniqueTemplateName('message');
        TemplateTemporaryFile::create($messageTemplateName.'_html', $message);
        TemplateTemporaryFile::create($messageTemplateName.'_text', hs_html_2_markdown($message));

        // If this is a public note and the init message is private, remove that message
        if($template == 'public' && ($data['initialrequest_type'] ?? 0) == 0) {
            $data['initialrequest'] = '';
        }

        return [
            'html' => relToAbs(
                parseShortcuts(
                    (string)mailView($bodyTemplateName.'_html', $this->prepHtmlEmail($messageTemplateName, $mainTemplateContentHtml, $message, $data))
                ),
                cHOST
            ),
            'text' => $this->replaceRelativeUrlsInText(
                parseShortcuts(
                    (string)mailView($bodyTemplateName.'_text', $this->prepTextEmail($messageTemplateName, $mainTemplateContentText, $message, $data))
                )
            ),
        ];
    }

    /**
     * Get an email template, checking for mailbox versions before defaulting back to default templates
     * @param $template
     * @param $mailboxId
     * @param string $type
     * @return mixed
     */
    protected function getTemplate($template, $mailboxId, $type='')
    {
        $type = (! empty($type))
            ? '_' . ltrim($type, '_')
            : '';

        if($template == 'tAutoResponse') {
            if(! isset($this->mailbox[$mailboxId])) {
                $this->mailbox[$mailboxId] = Mailbox::find($mailboxId);
            }

            $mailboxTemplateField = $template . $type;
            return $this->mailbox[$mailboxId]->$mailboxTemplateField;
        }

        $standardTemplate = $template . $type;
        $mailboxTemplate = 'mb' . $mailboxId . '_' . $standardTemplate;

        return (isset($this->templates[$mailboxTemplate]) && ! empty($this->templates[$mailboxTemplate]))
            ? $this->templates[$mailboxTemplate]
            : $this->templates[$standardTemplate];
    }

    /**
     * Prep html-specific template variables (often parsed as sub-templates)
     * @param $messageTemplateName
     * @param $templateContent
     * @param $message
     * @param $data
     * @return array
     */
    protected function prepHtmlEmail($messageTemplateName, $templateContent, $message, $data)
    {
        // Reply Above
        $replyAboveTemplateName = $this->uniqueTemplateName('partials_replyabove_html');
        TemplateTemporaryFile::create($replyAboveTemplateName, $this->templates['partials_replyabove_html']);
        $data['replyabove'] = mailView($replyAboveTemplateName, $data);

        // Get customer history into data, only if it's needed
        if ($this->needsLastCustomerNote($templateContent, $data) || $this->needsLastCustomerNote($message, $data)) {
            $request = $this->requestWithPublicHistory($data['requestid']);
            $data['lastcustomernote'] = mailView('mail.hard-coded.history-html', ['request' => $request]);
        }

        if ($this->needsFullRequestHistory($templateContent, $data) || $this->needsFullRequestHistory($message, $data)) {
            $request = $this->requestWithPublicHistory($data['requestid']);
            $data['fullpublichistory'] = mailView('mail.hard-coded.history-html', ['request' => $request, 'excludeCurrentNote' => false]);
        }

        if ($this->needsFullRequestHistoryEx($templateContent, $data) || $this->needsFullRequestHistoryEx($message, $data)) {
            $request = $this->requestWithPublicHistory($data['requestid']);
            $data['fullpublichistoryex'] = mailView('mail.hard-coded.history-html', ['request' => $request, 'excludeCurrentNote' => true]);
        }

        // Parse the message ahead of time as a restricted template (variables and comments only)
        $data['message'] = (string)restrictedView($messageTemplateName.'_html', $data);

        return $data;
    }

    /**
     * Prep text-specific template variables (often parsed as sub-templates)
     * @param $messageTemplateName
     * @param $templateContent
     * @param $message
     * @param $data
     * @return array
     */
    protected function prepTextEmail($messageTemplateName, $templateContent, $message, $data)
    {
        // Reply Above
        $replyAboveTemplateName = $this->uniqueTemplateName('partials_replyabove');
        TemplateTemporaryFile::create($replyAboveTemplateName, $this->templates['partials_replyabove']);
        $data['replyabove'] = mailView($replyAboveTemplateName, $data);

        // Get customer history into data, only if it's needed
        if ($this->needsLastCustomerNote($templateContent, $data) || $this->needsLastCustomerNote($message, $data)) {
            $request = $this->requestWithPublicHistory($data['requestid']);
            $data['lastcustomernote'] = mailView('mail.hard-coded.history-text', ['request' => $request]);
        }

        if ($this->needsFullRequestHistory($templateContent, $data) || $this->needsFullRequestHistory($message, $data)) {
            $request = $this->requestWithPublicHistory($data['requestid']);
            $data['fullpublichistory'] = mailView('mail.hard-coded.history-text', ['request' => $request, 'excludeCurrent' => false]);
        }

        if ($this->needsFullRequestHistoryEx($templateContent, $data) || $this->needsFullRequestHistoryEx($message, $data)) {
            $request = $this->requestWithPublicHistory($data['requestid']);
            $data['fullpublichistoryex'] = mailView('mail.hard-coded.history-text', ['request' => $request, 'excludeCurrent' => true]);
        }

        // Parse the message ahead of time as a restricted template (variables and comments only)
        $data['message'] = (string)restrictedView($messageTemplateName.'_text', $data);

        // Ensure initial request note for text email has no html
        $data['initialrequest'] = hs_html_2_markdown($data['initialrequest']);

        return $data;
    }

    /**
     * Get request history for templates that need request history
     * @param $xRequest
     * @return Request
     */
    protected function requestWithPublicHistory($xRequest)
    {
        if(isset($this->request[$xRequest])) {
            return $this->request[$xRequest];
        }

        // todo: too much memory usage on large request histories?
        return $this->request[$xRequest] = Request::with('publicHistory')
            ->where('xRequest', $xRequest)
            ->first();
    }

    /**
     * @param $templateContent
     * @param $data
     * @return bool
     */
    protected function needsLastCustomerNote($templateContent, $data)
    {
        // Has $lastcustomernote and a requestid is present
        return (strpos($templateContent, '$lastcustomernote') !== false && isset($data['requestid']));
    }

    /**
     * @param $templateContent
     * @param $data
     * @return bool
     */
    protected function needsFullRequestHistory($templateContent, $data)
    {
        return (
            // Has $fullpublichistory and a requestid is present
            (strpos($templateContent, '$fullpublichistory') !== false) && isset($data['requestid'])
        );
    }

    /**
     * @param $templateContent
     * @param $data
     * @return bool
     */
    protected function needsFullRequestHistoryEx($templateContent, $data)
    {
        return (
            // Has $fullpublichistoryex and a requestid is present
            (strpos($templateContent, '$fullpublichistoryex') !== false) && isset($data['requestid'])
        );
    }

    /**
     * Generate a unique ID for the template name
     * @param $template
     * @return string
     */
    protected function uniqueTemplateName($template)
    {
        return sprintf('%s_%s', uniqid(), $template);
    }

    /**
     * Replace the relative urls.
     * Looking for index.php that is preceded by a space.
     *
     * @param $body
     * @return mixed
     */
    public function replaceRelativeUrlsInText($body)
    {
        return preg_replace("#(?<=[\s])index.php\?#", cHOST.'/index.php?', $body);
    }
}
