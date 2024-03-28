<?php

namespace HS\Jobs;

use HS\Mail\Attachments;
use HS\Mail\HelpspotMailer;
use HS\Mail\HelpspotMessage;
use HS\Domain\Workspace\History;
use HS\Domain\Workspace\Request;
use HS\Mail\Mailer\MessageBuilder;
use HS\Notifications\EmailSendError;

use Illuminate\Support\Str;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class SendMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, AdministratesJobs;

    protected $jobName = 'Send Email';
    protected $jobCategory = 'mail.outgoing';

    /**
     * @var Attachments
     */
    private $attachments;

    /**
     * @var MessageBuilder
     */
    private $messageBuilder;

    /**
     * @var bool
     */
    private $publicEmail;

    /**
     * The maximum number of exceptions to allow before failing.
     *
     * @var int
     */
    public $maxExceptions = 1;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 1;

    /**
     * Create a new job instance.
     *
     * @param MessageBuilder $messageBuilder
     * @param Attachments $attachments
     * @param bool $publicEmail If this outgoing email is a public reply/external email
     */
    public function __construct(MessageBuilder $messageBuilder, Attachments $attachments=null, $publicEmail=false)
    {
        $this->messageBuilder = $messageBuilder;
        $this->attachments = is_null($attachments) ? new Attachments : $attachments;
        $this->publicEmail = $publicEmail;
    }

    /**
     * Execute the job.
     *
     * @return void
     * @throws \Exception
     */
    public function handle()
    {
        try {
            $mailer = HelpspotMailer::via($this->messageBuilder->mailboxId())
                ->withAttachments($this->attachments);

            if ($this->messageBuilder->getRequestId()) {
                $mailer->getMessage()->getSwiftMessage()->setId($this->generateIdHeader());
            }

            $mailer->send( new HelpspotMessage($this->messageBuilder) );
        } catch(\Exception $e) {
            // Report email error if related to a request
            if( $requestId = $this->messageBuilder->getRequestId() )
            {
                with(Request::with('assigned')->find($requestId), function(?Request $request) use($e) {
                    if( $request ) {
                        // Notify user if it has someone assigned.
                        if ($request->assigned) {
                            $request->assigned->notify( new EmailSendError($request) );
                        }

                        // Add to request history
                        History::create([
                            'xRequest' => $request->xRequest,
                            'xPerson' => -1,
                            'dtGMTChange' => date('U')+1,
                            'fPublic' => 0,
                            'tLog' => '',
                            'tNote' => lg_request_er_emailsenderror.': '.$e->getMessage(),
                            'tEmailHeaders' => '',
                        ]);
                    }
                });
            }

            Log::error($e);
        }
    }

    public function visibleToAdministrators()
    {
        return ! $this->publicEmail;
    }

    public function visibleMetaData()
    {
        return [
            'mailbox' => $this->messageBuilder->mailboxId(),
            'to' => $this->messageBuilder->getTo(),
            'subject' => $this->messageBuilder->getSubject(),
        ];
    }

    protected function generateIdHeader()
    {
        $firstTo = current($this->messageBuilder->getTo());
        $uniqueId = md5(
            hs_setting('cHD_CUSTOMER_ID') .
            $this->messageBuilder->getRequestId() .
            trim(strtolower($firstTo['email']))
        );

        return vsprintf("hs-notification-%s-%s@helpspot.com", [
            $uniqueId,
            Str::random(10),
        ]);
    }
}
